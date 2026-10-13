<?php

namespace Tests\Feature\Organization;

use App\Enums\Administration\RoleCode;
use App\Events\Organization\DirectorateCreated;
use App\Events\Organization\DirectorateDeleted;
use App\Events\Organization\DirectorateRestored;
use App\Events\Organization\DirectorateUpdated;
use App\Events\Organization\ServiceCreated;
use App\Events\Organization\ServiceDeleted;
use App\Events\Organization\ServiceRestored;
use App\Events\Organization\ServiceUpdated;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Organization\DirectorateModel;
use App\Models\Organization\ServiceModel;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Database\Seeders\Organization\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $administrator;

    private ServiceModel $defaultService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ServiceSeeder::class);

        $this->defaultService = ServiceModel::query()
            ->where('service_code', 'SDSID')
            ->firstOrFail();
        $this->administrator = $this->createUserForRole(RoleCode::SystemAdmin, [
            'username' => 'organization_admin',
            'email' => 'organization.admin@example.test',
        ]);
    }

    public function test_only_an_authorized_user_can_manage_the_organization(): void
    {
        $this->actingAsCurrent($this->administrator)
            ->get(route('organization.index'))
            ->assertOk()
            ->assertSee('Directions et services')
            ->assertSee('Direction générale (DG)')
            ->assertSee('Bureau du directeur général');

        $this->actingAsCurrent($this->administrator)
            ->get(route('organization.directorates.create'))
            ->assertOk()
            ->assertSee('Ajouter une direction');

        $this->actingAsCurrent($this->administrator)
            ->get(route('organization.services.create', $this->defaultService->directorate))
            ->assertOk()
            ->assertSee('Ajouter un service');

        $searchAgent = $this->createUserForRole(RoleCode::SearchAgent);

        $this->actingAsCurrent($searchAgent)
            ->get(route('organization.index'))
            ->assertForbidden();
    }

    public function test_an_administrator_can_create_and_update_a_directorate(): void
    {
        Event::fake([DirectorateCreated::class, DirectorateUpdated::class]);

        $this->actingAsCurrent($this->administrator)
            ->post(route('organization.directorates.store'), [
                'directorate_code' => 'DTEST',
                'directorate_name' => 'Direction de test',
                'display_order' => 70,
            ])
            ->assertSessionHasNoErrors();

        $directorate = DirectorateModel::query()
            ->where('directorate_code', 'DTEST')
            ->firstOrFail();

        $this->actingAsCurrent($this->administrator)
            ->put(route('organization.directorates.update', $directorate), [
                'directorate_code' => 'DTEST_UPDATED',
                'directorate_name' => 'Direction de test mise à jour',
                'display_order' => 75,
            ])
            ->assertSessionHasNoErrors();

        $directorate->refresh();

        $this->assertSame('DTEST_UPDATED', $directorate->directorate_code);
        $this->assertSame('Direction de test mise à jour', $directorate->directorate_name);
        $this->assertSame(75, $directorate->display_order);
        Event::assertDispatched(DirectorateCreated::class);
        Event::assertDispatched(DirectorateUpdated::class);
    }

    public function test_an_administrator_can_create_update_and_move_a_service(): void
    {
        Event::fake([ServiceCreated::class, ServiceUpdated::class]);
        $sourceDirectorate = DirectorateModel::query()
            ->where('directorate_code', 'DG')
            ->firstOrFail();
        $targetDirectorate = DirectorateModel::query()
            ->where('directorate_code', 'DSIDS')
            ->firstOrFail();

        $this->actingAsCurrent($this->administrator)
            ->post(route('organization.services.store'), [
                'directorate_code_unique' => $sourceDirectorate->directorate_code_unique,
                'service_code' => 'SCE_TEST',
                'abbreviation' => 'ST',
                'service_name' => 'Service de test',
                'display_order' => 90,
            ])
            ->assertSessionHasNoErrors();

        $service = ServiceModel::query()
            ->where('service_code', 'SCE_TEST')
            ->firstOrFail();

        $this->actingAsCurrent($this->administrator)
            ->put(route('organization.services.update', $service), [
                'directorate_code_unique' => $targetDirectorate->directorate_code_unique,
                'service_code' => 'SCE_TEST_UPDATED',
                'abbreviation' => '',
                'service_name' => 'Service de test mis à jour',
                'display_order' => 91,
            ])
            ->assertSessionHasNoErrors();

        $service->refresh();

        $this->assertSame($targetDirectorate->directorate_code_unique, $service->directorate_code_unique);
        $this->assertSame('SCE_TEST_UPDATED', $service->service_code);
        $this->assertNull($service->abbreviation);
        Event::assertDispatched(ServiceCreated::class);
        Event::assertDispatched(ServiceUpdated::class);
    }

    public function test_a_directorate_with_active_services_cannot_be_deactivated(): void
    {
        $directorate = DirectorateModel::query()
            ->where('directorate_code', 'DG')
            ->firstOrFail();

        $this->actingAsCurrent($this->administrator)
            ->patch(route('organization.directorates.status.update', $directorate), [
                'is_active' => false,
            ])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($directorate->fresh()->is_active);
    }

    public function test_a_service_with_active_users_cannot_be_deactivated(): void
    {
        $service = ServiceModel::query()
            ->where('service_code', 'SCE_JURIDIQUE')
            ->firstOrFail();
        $user = $this->createUserForRole(RoleCode::SearchAgent, [
            'service_code_unique' => $service->service_code_unique,
        ]);

        $this->actingAsCurrent($this->administrator)
            ->patch(route('organization.services.status.update', $service), [
                'is_active' => false,
            ])
            ->assertSessionHasErrors('is_active');

        $user->update(['is_active' => false]);

        $this->actingAsCurrent($this->administrator)
            ->patch(route('organization.services.status.update', $service), [
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($service->fresh()->is_active);
    }

    public function test_an_unused_service_can_be_soft_deleted_and_restored(): void
    {
        Event::fake([ServiceDeleted::class, ServiceRestored::class]);
        $service = ServiceModel::factory()->create([
            'directorate_code_unique' => $this->defaultService->directorate_code_unique,
            'service_code' => 'DELETE_TEST',
        ]);

        $this->actingAsCurrent($this->administrator)
            ->delete(route('organization.services.destroy', $service))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted('org_service_models', ['uuid' => $service->uuid]);
        Event::assertDispatched(ServiceDeleted::class);

        $deletedService = ServiceModel::withTrashed()->where('uuid', $service->uuid)->firstOrFail();

        $this->actingAsCurrent($this->administrator)
            ->get(route('organization.index'))
            ->assertOk()
            ->assertSee(route('organization.services.restore', $deletedService), false);

        $this->actingAsCurrent($this->administrator)
            ->patch(route('organization.services.restore', $deletedService))
            ->assertSessionHasNoErrors();

        $deletedService->refresh();
        $this->assertFalse($deletedService->trashed());
        $this->assertFalse($deletedService->is_active);
        Event::assertDispatched(ServiceRestored::class);
    }

    public function test_an_empty_directorate_can_be_soft_deleted_and_restored(): void
    {
        Event::fake([DirectorateDeleted::class, DirectorateRestored::class]);
        $directorate = DirectorateModel::factory()->create(['directorate_code' => 'EMPTY_DELETE']);

        $this->actingAsCurrent($this->administrator)
            ->delete(route('organization.directorates.destroy', $directorate))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted('org_directorate_models', ['uuid' => $directorate->uuid]);
        Event::assertDispatched(DirectorateDeleted::class);

        $deletedDirectorate = DirectorateModel::withTrashed()->where('uuid', $directorate->uuid)->firstOrFail();

        $this->actingAsCurrent($this->administrator)
            ->patch(route('organization.directorates.restore', $deletedDirectorate))
            ->assertSessionHasNoErrors();

        $deletedDirectorate->refresh();
        $this->assertFalse($deletedDirectorate->trashed());
        $this->assertFalse($deletedDirectorate->is_active);
        Event::assertDispatched(DirectorateRestored::class);
    }

    public function test_used_organization_items_cannot_be_deleted(): void
    {
        $directorate = DirectorateModel::factory()->create(['directorate_code' => 'USED_DELETE']);
        $service = ServiceModel::factory()->create([
            'directorate_code_unique' => $directorate->directorate_code_unique,
            'service_code' => 'USED_DELETE',
        ]);
        $this->createUserForRole(RoleCode::SearchAgent, [
            'service_code_unique' => $service->service_code_unique,
            'is_active' => false,
        ]);

        $this->actingAsCurrent($this->administrator)
            ->delete(route('organization.services.destroy', $service))
            ->assertSessionHasErrors('service');

        $this->actingAsCurrent($this->administrator)
            ->delete(route('organization.directorates.destroy', $directorate))
            ->assertSessionHasErrors('directorate');

        $this->assertFalse($service->fresh()->trashed());
        $this->assertFalse($directorate->fresh()->trashed());
    }

    /** @param array<string, mixed> $attributes */
    private function createUserForRole(
        RoleCode $roleCode,
        array $attributes = [],
    ): UserModel {
        $role = RoleModel::query()
            ->where('role_code', $roleCode->value)
            ->firstOrFail();

        return UserModel::factory()->create($attributes + [
            'role_code_unique' => $role->role_code_unique,
            'service_code_unique' => $this->defaultService->service_code_unique,
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
