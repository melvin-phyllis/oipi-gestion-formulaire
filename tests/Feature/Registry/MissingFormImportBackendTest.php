<?php

namespace Tests\Feature\Registry;

use App\Dto\Registry\ImportMissingFormsDTO;
use App\Enums\Administration\RoleCode;
use App\Enums\Audit\AuditAction;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Enums\Registry\RegistryImportStatus;
use App\Events\Registry\MissingFormImportProgressUpdated;
use App\Events\Registry\MissingFormsImported;
use App\Jobs\Registry\ProcessMissingFormImport;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Audit\AuditLogModel;
use App\Models\Registry\MissingFormImportModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\TitleTypeModel;
use App\Services\Registry\QueuedMissingFormImportService;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Database\Seeders\Registry\TitleTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use ZipArchive;

final class MissingFormImportBackendTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $administrator;

    private UserModel $searchAgent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(TitleTypeSeeder::class);
        Storage::fake('local');

        $this->administrator = $this->createUserForRole(RoleCode::SystemAdmin);
        $this->searchAgent = $this->createUserForRole(RoleCode::SearchAgent);
    }

    public function test_authorized_user_can_atomically_import_normalized_csv_rows(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'anciens-formulaires.csv',
            implode("\n", [
                'Type de titre;Année;Numéro du formulaire;Priorité',
                'BRV;2017; b-2017-000154 ;Urgente',
                'Marque;2014;m-2014-005421;',
            ]),
        );

        $this->actingAsCurrent($this->administrator)
            ->post(route('registry.forms.import'), ['import_file' => $file])
            ->assertRedirect(route('registry.forms.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('reg_missing_form_models', 2);
        $this->assertDatabaseHas('reg_missing_form_models', [
            'form_year' => 2017,
            'form_number' => 'B-2017-000154',
            'priority' => FormPriority::Urgent->value,
            'status' => FormStatus::Missing->value,
        ]);
        $this->assertDatabaseHas('reg_missing_form_models', [
            'form_year' => 2014,
            'form_number' => 'M-2014-005421',
            'priority' => FormPriority::Normal->value,
            'status' => FormStatus::Missing->value,
        ]);

        $audit = AuditLogModel::query()
            ->where('action_code', AuditAction::FormsImported->value)
            ->sole();

        $this->assertSame(2, $audit->metadata['imported_count']);
        $this->assertSame($this->administrator->user_code_unique, $audit->actor_user_code_unique);
    }

    public function test_duplicate_inside_file_rejects_the_complete_import(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'doublons.csv',
            implode("\n", [
                'type_titre,annee,numero_formulaire,priorite',
                'BRV,2017,B-2017-000154,haute',
                'Brevet,2017,B-2017-000154,normale',
            ]),
        );

        $this->actingAsCurrent($this->administrator)
            ->post(route('registry.forms.import'), ['import_file' => $file])
            ->assertRedirect(route('registry.forms.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reg_missing_form_models', 0);
        $this->assertDatabaseCount('aud_audit_log_models', 0);
        $import = MissingFormImportModel::query()->sole();
        $this->assertSame(RegistryImportStatus::Failed, $import->status);
        $this->assertStringContainsString('déjà présent', $import->errors[0]);
    }

    public function test_authorized_user_can_import_a_real_xlsx_workbook(): void
    {
        $path = sys_get_temp_dir().'/risfm-import-'.Str::uuid().'.xlsx';
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Type de titre', 'Année', 'Numéro du formulaire', 'Priorité'],
            ['BRV', 2019, 'B-2019-000777', 'Haute'],
        ]);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        try {
            $file = new UploadedFile(
                $path,
                'anciens-formulaires.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            );

            $this->actingAsCurrent($this->administrator)
                ->post(route('registry.forms.import'), ['import_file' => $file])
                ->assertRedirect(route('registry.forms.index'))
                ->assertSessionHasNoErrors();
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseHas('reg_missing_form_models', [
            'form_year' => 2019,
            'form_number' => 'B-2019-000777',
            'priority' => FormPriority::High->value,
        ]);
    }

    public function test_existing_form_rejects_all_other_rows_in_the_same_import(): void
    {
        $brevet = TitleTypeModel::query()->where('title_type_code', 'BRV')->firstOrFail();
        MissingFormModel::factory()->create([
            'title_type_code_unique' => $brevet->title_type_code_unique,
            'form_year' => 2017,
            'form_number' => 'B-2017-000154',
        ]);
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            implode("\n", [
                'type_titre;annee;numero_formulaire;priorite',
                'BRV;2018;B-2018-000001;normale',
                'BRV;2017;B-2017-000154;urgente',
            ]),
        );

        $this->actingAsCurrent($this->administrator)
            ->post(route('registry.forms.import'), ['import_file' => $file])
            ->assertRedirect(route('registry.forms.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reg_missing_form_models', 1);
        $this->assertDatabaseMissing('reg_missing_form_models', [
            'form_number' => 'B-2018-000001',
        ]);
        $this->assertSame(
            RegistryImportStatus::Failed,
            MissingFormImportModel::query()->sole()->status,
        );
    }

    public function test_web_request_queues_the_private_import_without_processing_it_inline(): void
    {
        Queue::fake();
        $file = UploadedFile::fake()->createWithContent(
            'lot.csv',
            "type_titre;annee;numero_formulaire\nBRV;2020;B-2020-000001",
        );

        $this->actingAsCurrent($this->administrator)
            ->post(route('registry.forms.import'), ['import_file' => $file])
            ->assertRedirect(route('registry.forms.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('missing_form_import_uuid');

        $import = MissingFormImportModel::query()->sole();
        $this->assertSame(RegistryImportStatus::Pending, $import->status);
        Storage::disk('local')->assertExists($import->file_path);
        Queue::assertPushed(
            ProcessMissingFormImport::class,
            fn (ProcessMissingFormImport $job): bool => $job->importCodeUnique === $import->missing_form_import_code_unique,
        );
        $this->assertDatabaseCount('reg_missing_form_models', 0);
    }

    public function test_authorized_user_can_follow_a_completed_import_status(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'lot.csv',
            "type_titre;annee;numero_formulaire\nBRV;2020;B-2020-000002",
        );

        $this->actingAsCurrent($this->administrator)
            ->post(route('registry.forms.import'), ['import_file' => $file])
            ->assertRedirect(route('registry.forms.index'));

        $import = MissingFormImportModel::query()->sole();

        $this->actingAsCurrent($this->administrator)
            ->getJson(route('registry.forms.imports.status', $import))
            ->assertOk()
            ->assertJsonPath('data.status', RegistryImportStatus::Completed->value)
            ->assertJsonPath('data.total_rows', 1)
            ->assertJsonPath('data.imported_rows', 1)
            ->assertJsonPath('data.progress', 100);

        $this->actingAsCurrent($this->searchAgent)
            ->getJson(route('registry.forms.imports.status', $import))
            ->assertForbidden();
    }

    public function test_import_status_is_restricted_to_its_creator(): void
    {
        $otherAdministrator = $this->createUserForRole(RoleCode::SystemAdmin);
        $import = MissingFormImportModel::factory()->create([
            'created_by_user_code_unique' => $this->administrator->user_code_unique,
        ]);

        $this->actingAsCurrent($this->administrator)
            ->getJson(route('registry.forms.imports.status', $import))
            ->assertOk();

        $this->actingAsCurrent($otherAdministrator)
            ->getJson(route('registry.forms.imports.status', $import))
            ->assertForbidden();
    }

    public function test_import_broadcasts_private_progress_until_completion(): void
    {
        Event::fake([MissingFormImportProgressUpdated::class]);
        $file = UploadedFile::fake()->createWithContent(
            'lot.csv',
            "type_titre;annee;numero_formulaire\nBRV;2020;B-2020-000003",
        );

        $this->actingAsCurrent($this->administrator)
            ->post(route('registry.forms.import'), ['import_file' => $file])
            ->assertRedirect(route('registry.forms.index'));

        Event::assertDispatched(
            MissingFormImportProgressUpdated::class,
            fn (MissingFormImportProgressUpdated $event): bool => $event->status === RegistryImportStatus::Completed->value
                && $event->progress === 100
                && $event->importedRows === 1,
        );
    }

    public function test_private_import_channel_is_restricted_to_its_creator(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'testing-key',
            'broadcasting.connections.pusher.secret' => 'testing-secret',
            'broadcasting.connections.pusher.app_id' => 'testing-app',
            'broadcasting.connections.pusher.options.cluster' => 'eu',
        ]);
        require base_path('routes/channels.php');

        $otherAdministrator = $this->createUserForRole(RoleCode::SystemAdmin);
        $import = MissingFormImportModel::factory()->create([
            'created_by_user_code_unique' => $this->administrator->user_code_unique,
        ]);
        $payload = [
            'socket_id' => '1234.5678',
            'channel_name' => "private-registry-imports.{$import->uuid}",
        ];

        $this->actingAsCurrent($this->administrator)
            ->post('/broadcasting/auth', $payload)
            ->assertOk();

        $this->actingAsCurrent($otherAdministrator)
            ->post('/broadcasting/auth', $payload)
            ->assertForbidden();
    }

    public function test_import_requires_its_explicit_permission(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            "type_titre;annee;numero_formulaire\nBRV;2017;B-2017-000154",
        );

        $this->actingAsCurrent($this->searchAgent)
            ->post(route('registry.forms.import'), ['import_file' => $file])
            ->assertForbidden();

        $route = Route::getRoutes()->getByName('registry.forms.import');

        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertContains('permission:registry_import', $route->gatherMiddleware());
        $this->assertContains('throttle:registry-imports', $route->gatherMiddleware());
    }

    public function test_authorized_user_sees_import_button_and_modal_on_registry_index(): void
    {
        $this->actingAsCurrent($this->administrator)
            ->get(route('registry.forms.index'))
            ->assertOk()
            ->assertSee('modal-import-missing-forms')
            ->assertSee('name="import_file"', false)
            ->assertSee(route('registry.forms.import'));

        $this->actingAsCurrent($this->searchAgent)
            ->get(route('registry.forms.index'))
            ->assertOk()
            ->assertDontSee('modal-import-missing-forms')
            ->assertDontSee('name="import_file"', false);
    }

    public function test_import_errors_are_displayed_on_registry_index(): void
    {
        $file = UploadedFile::fake()->create('invalide.pdf', 10, 'application/pdf');

        $this->actingAsCurrent($this->administrator)
            ->from(route('registry.forms.index'))
            ->followingRedirects()
            ->post(route('registry.forms.import'), ['import_file' => $file])
            ->assertOk()
            ->assertSee('Erreur lors de l’importation')
            ->assertSee('Le fichier doit être au format CSV ou XLSX.');
    }

    public function test_php_upload_failure_has_an_explicit_french_message(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'risfm-upload-');
        $this->assertNotFalse($path);
        file_put_contents($path, 'test');

        try {
            $file = new UploadedFile(
                $path,
                'lot-trop-volumineux.csv',
                'text/csv',
                UPLOAD_ERR_INI_SIZE,
                true,
            );

            $this->actingAsCurrent($this->administrator)
                ->from(route('registry.forms.index'))
                ->post(route('registry.forms.import'), ['import_file' => $file])
                ->assertRedirect(route('registry.forms.index'))
                ->assertSessionHasErrors([
                    'import_file' => 'Le fichier n’a pas pu être téléversé. Sa taille dépasse probablement la limite PHP du serveur (upload_max_filesize).',
                ]);
        } finally {
            @unlink($path);
        }
    }

    public function test_xlsx_row_limit_is_checked_before_any_form_is_inserted(): void
    {
        config()->set('risfm.registry_import.max_rows', 2);
        $import = $this->processXlsx([
            ['title_type', 'form_year', 'form_number'],
            ['BRV', 2020, 'B-000001'],
            ['BRV', 2020, 'B-000002'],
            ['BRV', 2020, 'B-000003'],
        ]);

        $this->assertRejectedImport($import, 'limite de 2 lignes');
    }

    public function test_csv_row_count_also_stops_at_the_configured_limit(): void
    {
        config()->set('risfm.registry_import.max_rows', 1);
        $file = UploadedFile::fake()->createWithContent('too-many.csv', "title_type;form_year;form_number\nBRV;2020;B-000001\nBRV;2020;B-000002");
        $this->actingAsCurrent($this->administrator)->post(route('registry.forms.import'), ['import_file' => $file])
            ->assertRedirect(route('registry.forms.index'))->assertSessionHasNoErrors();

        $import = MissingFormImportModel::query()->sole();
        $this->assertSame(RegistryImportStatus::Failed, $import->status);
        $this->assertStringContainsString('limite de 1 lignes', $import->errors[0]);
        $this->assertDatabaseCount('reg_missing_form_models', 0);
    }

    public function test_compressed_xlsx_expansion_is_rejected_with_a_user_facing_error_and_cleaned_up(): void
    {
        config()->set('risfm.registry_import.xlsx.max_part_bytes', 65536);
        $import = $this->processXlsx($this->xlsxRows(), function (ZipArchive $zip): void {
            $zip->addFromString('unused-padding.bin', str_repeat('A', 65537));
        });

        $this->assertRejectedImport($import, 'volumineux une fois décompressé');
    }

    public function test_malformed_xlsx_cannot_import_an_initial_valid_row(): void
    {
        $import = $this->processXlsx($this->xlsxRows(), function (ZipArchive $zip): void {
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertIsString($xml);
            $zip->addFromString('xl/worksheets/sheet1.xml', str_replace('</worksheet>', '<invalid>', $xml));
        });

        $this->assertRejectedImport($import, 'ne peut pas être lu');
    }

    public function test_malicious_xlsx_xml_is_rejected_without_exposing_entity_contents(): void
    {
        $import = $this->processXlsx($this->xlsxRows(), function (ZipArchive $zip): void {
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertIsString($xml);
            $body = preg_replace('/<\?xml[^>]*\?>/', '', $xml);
            $this->assertIsString($body);
            $zip->addFromString('xl/worksheets/sheet1.xml', '<!DOCTYPE worksheet [<!ENTITY probe SYSTEM "file:///etc/passwd">]>'.$body);
        });

        $this->assertRejectedImport($import, 'structure XML non autorisée');
    }

    public function test_sparse_xlsx_dimensions_do_not_create_millions_of_rows_or_cells(): void
    {
        $import = $this->processXlsx($this->xlsxRows(), function (ZipArchive $zip): void {
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertIsString($xml);
            $xml = preg_replace('/<dimension[^>]*\/>/', '<dimension ref="A1:XFD1048576"/>', $xml);
            $zip->addFromString('xl/worksheets/sheet1.xml', str_replace('</sheetData>', '<row r="1048576"><c r="XFD1048576" s="1"/></row></sheetData>', $xml));
        });

        $this->assertSame(RegistryImportStatus::Completed, $import->status);
        $this->assertSame(1, $import->total_rows);
        $this->assertSame(1, $import->imported_rows);
        $this->assertDatabaseHas('reg_missing_form_models', ['form_number' => 'B-2020-000777']);
        Storage::disk('local')->assertMissing($import->file_path);
    }

    /** @return list<list<string|int>> */
    private function xlsxRows(): array
    {
        return [['title_type', 'form_year', 'form_number'], ['BRV', 2020, 'B-2020-000777']];
    }

    /** @param list<list<string|int>> $rows */
    private function processXlsx(array $rows, ?callable $mutate = null): MissingFormImportModel
    {
        Queue::fake();
        Event::fake([MissingFormsImported::class, MissingFormImportProgressUpdated::class]);
        $source = Storage::disk('local')->path('security-test-input.xlsx');
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);
        (new Xlsx($spreadsheet))->save($source);
        $spreadsheet->disconnectWorksheets();

        try {
            if ($mutate !== null) {
                $zip = new ZipArchive;
                $this->assertTrue($zip->open($source));
                try {
                    $mutate($zip);
                } finally {
                    $zip->close();
                }
            }
            $service = app(QueuedMissingFormImportService::class);
            $import = $service->srv_queue(new ImportMissingFormsDTO($source, 'test.xlsx', 'xlsx', filesize($source)), $this->administrator);
            $service->srv_process($import->missing_form_import_code_unique);

            return $import->fresh();
        } finally {
            Storage::disk('local')->delete('security-test-input.xlsx');
        }
    }

    private function assertRejectedImport(MissingFormImportModel $import, string $message): void
    {
        $this->assertSame(RegistryImportStatus::Failed, $import->status);
        $this->assertStringContainsString($message, $import->errors[0]);
        $this->assertSame(0, $import->imported_rows);
        $this->assertDatabaseCount('reg_missing_form_models', 0);
        Storage::disk('local')->assertMissing($import->file_path);
        Storage::disk('local')->assertMissing($import->file_path.'.normalized.csv');
        Event::assertNotDispatched(MissingFormsImported::class);
    }

    private function createUserForRole(RoleCode $roleCode): UserModel
    {
        $roleCodeUnique = RoleModel::query()
            ->where('role_code', $roleCode->value)
            ->valueOrFail('role_code_unique');

        return UserModel::factory()->create([
            'role_code_unique' => $roleCodeUnique,
            'must_change_password' => false,
        ]);
    }

    private function actingAsCurrent(UserModel $user): static
    {
        return $this->actingAs($user)->withSession([
            'auth_session_version' => $user->session_version,
        ]);
    }
}
