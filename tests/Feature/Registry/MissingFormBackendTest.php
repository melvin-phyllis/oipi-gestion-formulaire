<?php

namespace Tests\Feature\Registry;

use App\Dto\Registry\ArchiveMissingFormDTO;
use App\Dto\Registry\CreateMissingFormDTO;
use App\Dto\Registry\MissingFormFiltersDTO;
use App\Dto\Registry\UpdateMissingFormDTO;
use App\Enums\Administration\RoleCode;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Events\Registry\MissingFormArchived;
use App\Events\Registry\MissingFormCreated;
use App\Events\Registry\MissingFormMetadataUpdated;
use App\Events\Registry\MissingFormRestored;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Models\Registry\TitleTypeModel;
use App\Services\Registry\MissingFormService;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

final class MissingFormBackendTest extends TestCase
{
    use RefreshDatabase;

    private MissingFormService $service;

    private UserModel $actor;

    private TitleTypeModel $titleType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MissingFormService::class);
        $this->actor = UserModel::factory()->create();
        $this->titleType = TitleTypeModel::factory()->create([
            'title_type_code' => 'BRV',
            'title_type_name' => 'Brevet',
        ]);
    }

    public function test_creation_generates_the_business_identifiers_and_fixed_initial_state(): void
    {
        Event::fake([MissingFormCreated::class]);

        $form = $this->service->srv_create($this->createDto(), $this->actor);

        $this->assertSame(sprintf('FM-%d-000001', now()->year), $form->registry_number);
        $this->assertStringStartsWith('FRM-', $form->missing_form_code_unique);
        $this->assertNotEmpty($form->uuid);
        $this->assertSame(FormStatus::Missing, $form->status);
        $this->assertSame(FormPriority::High, $form->priority);
        $this->assertSame(1, $form->tracking_cycle);
        $this->assertTrue($form->titleType->is($this->titleType));
        $this->assertTrue($form->createdBy->is($this->actor));
        Event::assertDispatched(MissingFormCreated::class);
    }

    public function test_business_identity_is_unique_even_when_the_existing_form_is_soft_deleted(): void
    {
        $form = $this->service->srv_create($this->createDto(), $this->actor);
        $form->delete();

        try {
            $this->service->srv_create($this->createDto(), $this->actor);
            $this->fail('The duplicate form should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('form_number', $exception->errors());
        }

        $this->assertDatabaseCount('reg_missing_form_models', 1);
    }

    public function test_an_inactive_title_type_cannot_be_used(): void
    {
        $this->titleType->update(['is_active' => false]);

        $this->expectException(ValidationException::class);
        $this->service->srv_create($this->createDto(), $this->actor);
    }

    public function test_metadata_can_be_updated_without_changing_workflow_data(): void
    {
        Event::fake([MissingFormMetadataUpdated::class]);
        $form = $this->service->srv_create($this->createDto(), $this->actor);
        $location = SearchLocationModel::factory()->create();
        $form->update([
            'status' => FormStatus::InSearch,
            'last_search_location_code_unique' => $location->search_location_code_unique,
            'result_summary' => 'Recherche démarrée',
        ]);

        $updated = $this->service->srv_updateMetadata(
            $form->refresh(),
            new UpdateMissingFormDTO(
                titleTypeCodeUnique: $this->titleType->title_type_code_unique,
                formYear: 2018,
                formNumber: 'B-2018-000999',
                priority: FormPriority::Urgent,
            ),
            $this->actor,
        );

        $this->assertSame(2018, $updated->form_year);
        $this->assertSame('B-2018-000999', $updated->form_number);
        $this->assertSame(FormPriority::Urgent, $updated->priority);
        $this->assertSame(FormStatus::InSearch, $updated->status);
        $this->assertSame('Recherche démarrée', $updated->result_summary);
        $this->assertTrue($updated->lastSearchLocation->is($location));
        Event::assertDispatched(MissingFormMetadataUpdated::class);
    }

    public function test_archive_and_restore_preserve_the_previous_workflow_status(): void
    {
        Event::fake([
            MissingFormArchived::class,
            MissingFormRestored::class,
        ]);
        $form = MissingFormModel::factory()->create([
            'title_type_code_unique' => $this->titleType->title_type_code_unique,
            'status' => FormStatus::Digitized,
        ]);

        $archived = $this->service->srv_archive(
            $form,
            new ArchiveMissingFormDTO('Dossier créé par erreur.'),
            $this->actor,
        );

        $this->assertSame(FormStatus::Archived, $archived->status);
        $this->assertSame(FormStatus::Digitized, $archived->status_before_archive);
        $this->assertSame('Dossier créé par erreur.', $archived->archive_reason);
        $this->assertNotNull($archived->archived_at);
        $this->assertTrue($archived->archivedBy->is($this->actor));
        Event::assertDispatched(MissingFormArchived::class);

        $restored = $this->service->srv_restore($archived, $this->actor);

        $this->assertSame(FormStatus::Digitized, $restored->status);
        $this->assertNull($restored->status_before_archive);
        $this->assertNull($restored->archive_reason);
        $this->assertNull($restored->archived_at);
        $this->assertNull($restored->archived_by_user_code_unique);
        Event::assertDispatched(MissingFormRestored::class);
    }

    public function test_archived_form_cannot_be_edited_or_archived_twice(): void
    {
        $form = MissingFormModel::factory()->create([
            'title_type_code_unique' => $this->titleType->title_type_code_unique,
            'status' => FormStatus::Archived,
            'status_before_archive' => FormStatus::Missing,
        ]);

        $this->expectException(LogicException::class);
        $this->service->srv_archive(
            $form,
            new ArchiveMissingFormDTO('Nouvelle tentative.'),
            $this->actor,
        );
    }

    public function test_registry_query_can_combine_business_filters(): void
    {
        MissingFormModel::factory()->create([
            'title_type_code_unique' => $this->titleType->title_type_code_unique,
            'form_year' => 2017,
            'form_number' => 'B-2017-000154',
            'status' => FormStatus::InSearch,
            'priority' => FormPriority::Urgent,
        ]);
        MissingFormModel::factory()->create([
            'title_type_code_unique' => $this->titleType->title_type_code_unique,
            'form_year' => 2017,
            'form_number' => 'B-2017-999999',
            'status' => FormStatus::Found,
            'priority' => FormPriority::Low,
        ]);

        $results = $this->service->srv_paginate(new MissingFormFiltersDTO(
            search: '000154',
            titleTypeCodeUnique: $this->titleType->title_type_code_unique,
            formYear: 2017,
            status: FormStatus::InSearch,
            priority: FormPriority::Urgent,
        ));

        $this->assertSame(1, $results->total());
        $this->assertSame('B-2017-000154', $results->first()->form_number);
        $this->assertTrue($results->first()->relationLoaded('titleType'));
    }

    public function test_authorized_http_endpoint_normalizes_and_creates_a_form(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $role = RoleModel::query()->where('role_code', RoleCode::SystemAdmin->value)->firstOrFail();
        $administrator = UserModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
            'must_change_password' => false,
        ]);

        $this->actingAs($administrator)
            ->withSession(['auth_session_version' => $administrator->session_version])
            ->from('/dashboard')
            ->post(route('registry.forms.store'), [
                'title_type_code_unique' => $this->titleType->title_type_code_unique,
                'form_year' => 2017,
                'form_number' => '  b-2017-000155  ',
                'deposit_date' => '2017-02-03',
                'applicant_name' => 'Ancienne donnée ignorée',
                'representative_name' => 'Ancienne donnée ignorée',
                'priority' => FormPriority::Normal->value,
            ])
            ->assertRedirect('/dashboard')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reg_missing_form_models', [
            'form_number' => 'B-2017-000155',
            'deposit_date' => null,
            'applicant_name' => null,
            'representative_name' => null,
            'status' => FormStatus::Missing->value,
        ]);
    }

    public function test_registry_read_contract_exposes_filters_and_active_creation_options(): void
    {
        $inactiveTitleType = TitleTypeModel::factory()->create(['is_active' => false]);
        MissingFormModel::factory()->create([
            'title_type_code_unique' => $this->titleType->title_type_code_unique,
            'status' => FormStatus::InSearch,
        ]);

        $listData = $this->service->srv_list(new MissingFormFiltersDTO);
        $createData = $this->service->srv_createData();

        $this->assertSame(1, $listData['forms']->total());
        $this->assertContains(FormStatus::InSearch, $listData['statuses']);
        $this->assertContains(FormPriority::Urgent, $listData['priorities']);
        $this->assertTrue($listData['titleTypes']->contains($inactiveTitleType));
        $this->assertTrue($createData['titleTypes']->contains($this->titleType));
        $this->assertFalse($createData['titleTypes']->contains($inactiveTitleType));
    }

    public function test_form_detail_contract_loads_the_complete_business_record(): void
    {
        $activeLocation = SearchLocationModel::factory()->create(['is_active' => true]);
        $inactiveLocation = SearchLocationModel::factory()->create(['is_active' => false]);
        $inactiveUser = UserModel::factory()->inactive()->create();
        $form = MissingFormModel::factory()->create([
            'title_type_code_unique' => $this->titleType->title_type_code_unique,
        ]);

        $data = $this->service->srv_showData($form);
        $loadedForm = $data['missingForm'];

        foreach (['titleType', 'missions', 'searchHistory', 'foundReviews', 'finalizations', 'reopenings'] as $relation) {
            $this->assertTrue($loadedForm->relationLoaded($relation), "The [$relation] relation should be loaded.");
        }
        $this->assertTrue($data['searchLocations']->contains($activeLocation));
        $this->assertFalse($data['searchLocations']->contains($inactiveLocation));
        $this->assertTrue($data['responsibleUsers']->contains($this->actor));
        $this->assertFalse($data['responsibleUsers']->contains($inactiveUser));
    }

    public function test_edit_contract_keeps_current_inactive_title_type_available(): void
    {
        $this->titleType->update(['is_active' => false]);
        $form = MissingFormModel::factory()->create([
            'title_type_code_unique' => $this->titleType->title_type_code_unique,
        ]);

        $data = $this->service->srv_editData($form);

        $this->assertTrue($data['titleTypes']->contains($this->titleType));
    }

    public function test_registry_consultation_routes_are_registered_with_explicit_permissions(): void
    {
        $expectedRoutes = [
            'registry.forms.index' => 'permission:registry_view',
            'registry.forms.create' => 'permission:registry_create',
            'registry.forms.show' => 'permission:registry_view',
            'registry.forms.edit' => 'permission:registry_update_metadata',
        ];

        foreach ($expectedRoutes as $name => $permission) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "The [$name] route should be registered.");
            $this->assertContains('GET', $route->methods());
            $this->assertContains($permission, $route->gatherMiddleware());
        }
    }

    private function createDto(): CreateMissingFormDTO
    {
        return new CreateMissingFormDTO(
            titleTypeCodeUnique: $this->titleType->title_type_code_unique,
            formYear: 2017,
            formNumber: 'B-2017-000154',
            priority: FormPriority::High,
        );
    }
}
