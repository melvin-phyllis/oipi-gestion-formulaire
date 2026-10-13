<?php

namespace Tests\Feature\Registry;

use App\Dto\Registry\UploadFormAttachmentDTO;
use App\Enums\Administration\RoleCode;
use App\Enums\Audit\AuditAction;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormStatus;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\FormAttachmentModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Services\Registry\FormAttachmentService;
use App\Services\Registry\MissingFormService;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

final class FormAttachmentBackendTest extends TestCase
{
    use RefreshDatabase;

    private FormAttachmentService $service;

    private UserModel $administrator;

    private UserModel $agent;

    private UserModel $otherAgent;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->service = app(FormAttachmentService::class);
        $this->administrator = $this->userForRole(RoleCode::SystemAdmin);
        $this->agent = $this->userForRole(RoleCode::SearchAgent);
        $this->otherAgent = $this->userForRole(RoleCode::SearchAgent);
    }

    public function test_attachment_routes_are_registered_with_explicit_permissions_and_throttling(): void
    {
        $expectations = [
            'registry.forms.attachments.store' => [
                'POST',
                'permission:attachments_add_any,attachments_add_own',
            ],
            'registry.forms.attachments.download' => ['GET', 'permission:registry_view'],
            'registry.forms.attachments.destroy' => [
                'DELETE',
                'permission:attachments_delete_any,attachments_delete_own',
            ],
        ];

        foreach ($expectations as $name => [$method, $permission]) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertContains($method, $route->methods());
            $this->assertContains($permission, $route->gatherMiddleware());
            $this->assertContains('throttle:attachments', $route->gatherMiddleware());
        }
    }

    public function test_add_any_stores_a_verified_file_privately_with_generated_name_and_audit(): void
    {
        $form = MissingFormModel::factory()->create();

        $this->authenticatedAs($this->administrator)
            ->from(route('registry.forms.show', $form))
            ->post(route('registry.forms.attachments.store', $form), [
                'attachment' => $this->pdfUpload('Rapport recherche.pdf'),
            ])
            ->assertRedirect(route('registry.forms.show', $form))
            ->assertSessionHasNoErrors();

        $attachment = FormAttachmentModel::query()->sole();
        Storage::disk('local')->assertExists($attachment->file_path);
        $this->assertStringStartsWith("attachments/forms/{$form->uuid}/", $attachment->file_path);
        $this->assertSame('Rapport recherche.pdf', $attachment->original_name);
        $this->assertSame('rapport-recherche.pdf', $attachment->download_name);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertSame(
            hash('sha256', Storage::disk('local')->get($attachment->file_path)),
            $attachment->sha256,
        );
        $this->assertStringNotContainsString('Rapport recherche', $attachment->file_path);
        $this->assertFileDoesNotExist(public_path('storage/'.$attachment->file_path));
        $this->assertDatabaseHas('aud_audit_log_models', [
            'action_code' => AuditAction::AttachmentAdded->value,
            'subject_uuid' => $attachment->uuid,
        ]);
    }

    public function test_add_own_is_limited_to_the_currently_assigned_form(): void
    {
        $assignedForm = $this->assignedForm($this->agent);
        $otherForm = MissingFormModel::factory()->create();

        $this->authenticatedAs($this->agent)
            ->post(route('registry.forms.attachments.store', $assignedForm), [
                'attachment' => $this->pdfUpload('mission.pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->authenticatedAs($this->agent)
            ->post(route('registry.forms.attachments.store', $otherForm), [
                'attachment' => $this->pdfUpload('interdit.pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('reg_form_attachment_models', 1);
    }

    public function test_attachment_cannot_be_added_to_an_archived_form(): void
    {
        $form = MissingFormModel::factory()->create([
            'status' => FormStatus::Archived,
            'status_before_archive' => FormStatus::Missing,
        ]);

        $this->authenticatedAs($this->administrator)
            ->post(route('registry.forms.attachments.store', $form), [
                'attachment' => $this->pdfUpload('archive.pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('reg_form_attachment_models', 0);
    }

    public function test_a_public_storage_disk_is_rejected_even_if_misconfigured(): void
    {
        config()->set('risfm.attachments.disk', 'public');
        $form = MissingFormModel::factory()->create();
        $upload = $this->pdfUpload('confidentiel.pdf');
        $path = $upload->getRealPath();
        $this->assertNotFalse($path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stockage privé');

        $this->service->srv_add(
            $form,
            new UploadFormAttachmentDTO(
                temporaryPath: $path,
                originalName: $upload->getClientOriginalName(),
                clientExtension: $upload->getClientOriginalExtension(),
                clientMimeType: $upload->getClientMimeType(),
                sizeBytes: (int) $upload->getSize(),
            ),
            $this->administrator,
        );
    }

    public function test_spoofed_pdf_and_oversized_file_are_rejected(): void
    {
        $form = MissingFormModel::factory()->create();

        $this->authenticatedAs($this->administrator)
            ->from(route('registry.forms.show', $form))
            ->post(route('registry.forms.attachments.store', $form), [
                'attachment' => UploadedFile::fake()->createWithContent(
                    'attaque.pdf',
                    '<?php echo "not a pdf";',
                ),
            ])
            ->assertRedirect(route('registry.forms.show', $form))
            ->assertSessionHasErrors('attachment');

        $this->authenticatedAs($this->administrator)
            ->from(route('registry.forms.show', $form))
            ->post(route('registry.forms.attachments.store', $form), [
                'attachment' => UploadedFile::fake()->create(
                    'trop-grand.pdf',
                    10241,
                    'application/pdf',
                ),
            ])
            ->assertRedirect(route('registry.forms.show', $form))
            ->assertSessionHasErrors('attachment');

        $this->assertDatabaseCount('reg_form_attachment_models', 0);
    }

    public function test_download_is_controlled_and_blocks_cross_form_identifier_substitution(): void
    {
        $form = MissingFormModel::factory()->create();
        $otherForm = MissingFormModel::factory()->create();
        $attachment = $this->uploadThroughService($form, $this->administrator);

        $this->authenticatedAs($this->agent)
            ->get(route('registry.forms.attachments.download', [$form, $attachment]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->authenticatedAs($this->agent)
            ->get(route('registry.forms.attachments.download', [$otherForm, $attachment]))
            ->assertForbidden();

        $this->assertDatabaseHas('aud_audit_log_models', [
            'action_code' => AuditAction::AttachmentDownloaded->value,
            'subject_uuid' => $attachment->uuid,
        ]);
    }

    public function test_archived_attachment_download_requires_archive_permission(): void
    {
        $form = MissingFormModel::factory()->create();
        $attachment = $this->uploadThroughService($form, $this->administrator);
        $form->update([
            'status' => FormStatus::Archived,
            'status_before_archive' => FormStatus::Missing,
        ]);

        $this->authenticatedAs($this->agent)
            ->get(route('registry.forms.attachments.download', [$form, $attachment]))
            ->assertForbidden();

        $this->authenticatedAs($this->administrator)
            ->get(route('registry.forms.attachments.download', [$form, $attachment]))
            ->assertOk();
    }

    public function test_download_refuses_a_physically_tampered_file(): void
    {
        $form = MissingFormModel::factory()->create();
        $attachment = $this->uploadThroughService($form, $this->administrator);
        Storage::disk('local')->put($attachment->file_path, str_repeat('X', $attachment->size_bytes));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('intégrité');

        $this->service->srv_download($form, $attachment, $this->administrator);
    }

    public function test_delete_own_requires_both_assignment_and_file_ownership(): void
    {
        $form = $this->assignedForm($this->agent);
        $attachment = $this->uploadThroughService($form, $this->administrator);

        $this->expectException(AuthorizationException::class);

        $this->service->srv_delete($form, $attachment, $this->agent);
    }

    public function test_delete_own_removes_own_file_and_soft_deletes_the_record(): void
    {
        $form = $this->assignedForm($this->agent);
        $attachment = $this->uploadThroughService($form, $this->agent);

        $this->service->srv_delete($form, $attachment, $this->agent);

        Storage::disk('local')->assertMissing($attachment->file_path);
        $this->assertSoftDeleted('reg_form_attachment_models', [
            'uuid' => $attachment->uuid,
            'deleted_by_user_code_unique' => $this->agent->user_code_unique,
        ]);
        $this->assertDatabaseHas('aud_audit_log_models', [
            'action_code' => AuditAction::AttachmentDeleted->value,
            'subject_uuid' => $attachment->uuid,
        ]);
    }

    public function test_delete_any_can_remove_another_users_attachment(): void
    {
        $form = $this->assignedForm($this->agent);
        $attachment = $this->uploadThroughService($form, $this->agent);

        $this->service->srv_delete($form, $attachment, $this->administrator);

        Storage::disk('local')->assertMissing($attachment->file_path);
        $this->assertSoftDeleted('reg_form_attachment_models', ['uuid' => $attachment->uuid]);
    }

    public function test_form_detail_contract_eager_loads_active_attachments_and_uploader(): void
    {
        $form = MissingFormModel::factory()->create();
        $attachment = $this->uploadThroughService($form, $this->administrator);

        $loaded = app(MissingFormService::class)
            ->srv_showData($form)['missingForm'];

        $this->assertTrue($loaded->relationLoaded('attachments'));
        $this->assertTrue($loaded->attachments->first()->is($attachment));
        $this->assertTrue($loaded->attachments->first()->relationLoaded('uploadedBy'));
    }

    private function authenticatedAs(UserModel $user): self
    {
        return $this->actingAs($user)->withSession([
            'auth_session_version' => $user->session_version,
        ]);
    }

    private function userForRole(RoleCode $role): UserModel
    {
        return UserModel::factory()->create([
            'role_code_unique' => RoleModel::query()
                ->where('role_code', $role->value)
                ->valueOrFail('role_code_unique'),
        ]);
    }

    private function assignedForm(UserModel $responsible): MissingFormModel
    {
        $form = MissingFormModel::factory()->create([
            'status' => FormStatus::InSearch,
            'last_responsible_user_code_unique' => $responsible->user_code_unique,
        ]);
        $location = SearchLocationModel::factory()->create();

        SearchMissionModel::factory()->create([
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'tracking_cycle' => $form->tracking_cycle,
            'search_location_code_unique' => $location->search_location_code_unique,
            'responsible_user_code_unique' => $responsible->user_code_unique,
            'status' => MissionStatus::Assigned,
        ]);

        return $form;
    }

    private function uploadThroughService(
        MissingFormModel $form,
        UserModel $actor,
    ): FormAttachmentModel {
        $upload = $this->pdfUpload('preuve.pdf');
        $path = $upload->getRealPath();
        $this->assertNotFalse($path);

        return $this->service->srv_add(
            $form,
            new UploadFormAttachmentDTO(
                temporaryPath: $path,
                originalName: $upload->getClientOriginalName(),
                clientExtension: $upload->getClientOriginalExtension(),
                clientMimeType: $upload->getClientMimeType(),
                sizeBytes: (int) $upload->getSize(),
            ),
            $actor,
        );
    }

    private function pdfUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF",
        );
    }
}
