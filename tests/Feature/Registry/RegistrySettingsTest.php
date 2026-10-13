<?php

namespace Tests\Feature\Registry;

use App\Enums\Administration\RoleCode;
use App\Enums\Registry\FormStatus;
use App\Events\Registry\ReferenceItemDeleted;
use App\Events\Registry\ReferenceItemRestored;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Organization\ServiceModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Models\Registry\TitleTypeModel;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Database\Seeders\Organization\ServiceSeeder;
use Database\Seeders\Registry\SearchLocationSeeder;
use Database\Seeders\Registry\TitleTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class RegistrySettingsTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $administrator;

    private ServiceModel $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ServiceSeeder::class);
        $this->seed(TitleTypeSeeder::class);
        $this->seed(SearchLocationSeeder::class);

        $this->service = ServiceModel::query()->where('service_code', 'SDSID')->firstOrFail();
        $this->administrator = $this->createUser(RoleCode::SystemAdmin);
    }

    public function test_statuses_are_fixed_in_code_and_not_stored_in_an_editable_table(): void
    {
        $this->assertCount(7, FormStatus::cases());
        $this->assertSame('Introuvable', FormStatus::Missing->label());
        $this->assertSame('Saisi', FormStatus::Entered->label());
        $this->assertFalse(FormStatus::InSearch->isResolved());
        $this->assertTrue(FormStatus::Digitized->isResolved());
        $this->assertFalse(Schema::hasTable('reg_form_status_models'));
    }

    public function test_seeders_install_the_official_lists_without_duplicates(): void
    {
        $this->seed(TitleTypeSeeder::class);
        $this->seed(SearchLocationSeeder::class);

        $this->assertDatabaseCount('reg_title_type_models', 8);
        $this->assertDatabaseCount('reg_search_location_models', 10);
        $this->assertDatabaseHas('reg_title_type_models', [
            'title_type_code' => 'BRV',
            'title_type_name' => 'Brevet',
        ]);
        $this->assertDatabaseHas('reg_search_location_models', [
            'location_code' => 'ARCHIVES_CENTRALES',
            'location_name' => 'Archives centrales',
        ]);
    }

    public function test_only_an_authorized_user_can_open_registry_settings(): void
    {
        $this->actingAsCurrent($this->administrator)
            ->get(route('registry.settings.index'))
            ->assertOk()
            ->assertSee('Types de titres')
            ->assertSee('Localisations')
            ->assertSee('Les statuts du workflow sont verrouillés')
            ->assertSee('Introuvable');

        $agent = $this->createUser(RoleCode::SearchAgent);

        $this->actingAsCurrent($agent)
            ->get(route('registry.settings.index'))
            ->assertForbidden();
    }

    public function test_an_administrator_can_create_update_and_disable_a_title_type(): void
    {
        $this->actingAsCurrent($this->administrator)
            ->post(route('registry.settings.title-types.store'), [
                'title_type_code' => 'TEST',
                'title_type_name' => 'Titre de test',
                'display_order' => 20,
            ])
            ->assertSessionHasNoErrors();

        $item = TitleTypeModel::query()->where('title_type_code', 'TEST')->firstOrFail();

        $this->actingAsCurrent($this->administrator)
            ->put(route('registry.settings.title-types.update', $item), [
                'title_type_code' => 'TST',
                'title_type_name' => 'Titre de test modifié',
                'display_order' => 21,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAsCurrent($this->administrator)
            ->patch(route('registry.settings.title-types.status.update', $item), ['is_active' => false])
            ->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('TST', $item->title_type_code);
        $this->assertSame('Titre de test modifié', $item->title_type_name);
        $this->assertFalse($item->is_active);
    }

    public function test_a_location_gets_an_internal_code_that_does_not_change_when_renamed(): void
    {
        $this->actingAsCurrent($this->administrator)
            ->post(route('registry.settings.search-locations.store'), [
                'location_name' => 'Nouvelle salle',
                'description' => 'Deuxième niveau',
                'display_order' => 120,
            ])
            ->assertSessionHasNoErrors();

        $item = SearchLocationModel::query()->where('location_name', 'Nouvelle salle')->firstOrFail();
        $internalCode = $item->location_code;

        $this->actingAsCurrent($this->administrator)
            ->put(route('registry.settings.search-locations.update', $item), [
                'location_name' => 'Salle secondaire',
                'description' => '',
                'display_order' => 121,
            ])
            ->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame($internalCode, $item->location_code);
        $this->assertSame('Salle secondaire', $item->location_name);
        $this->assertNull($item->description);
    }

    public function test_unused_references_can_be_soft_deleted_and_restored(): void
    {
        Event::fake([ReferenceItemDeleted::class, ReferenceItemRestored::class]);
        $titleType = TitleTypeModel::factory()->create(['title_type_code' => 'DEL']);
        $location = SearchLocationModel::factory()->create(['location_code' => 'DELETE_LOCATION']);

        $this->actingAsCurrent($this->administrator)
            ->delete(route('registry.settings.title-types.destroy', $titleType))
            ->assertSessionHasNoErrors();
        $this->actingAsCurrent($this->administrator)
            ->delete(route('registry.settings.search-locations.destroy', $location))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted('reg_title_type_models', ['uuid' => $titleType->uuid]);
        $this->assertSoftDeleted('reg_search_location_models', ['uuid' => $location->uuid]);
        Event::assertDispatched(ReferenceItemDeleted::class, 2);

        $deletedTitleType = TitleTypeModel::withTrashed()->where('uuid', $titleType->uuid)->firstOrFail();
        $deletedLocation = SearchLocationModel::withTrashed()->where('uuid', $location->uuid)->firstOrFail();

        $this->actingAsCurrent($this->administrator)
            ->get(route('registry.settings.index'))
            ->assertOk()
            ->assertSee(route('registry.settings.title-types.restore', $deletedTitleType), false)
            ->assertSee(route('registry.settings.search-locations.restore', $deletedLocation), false);

        $this->actingAsCurrent($this->administrator)
            ->patch(route('registry.settings.title-types.restore', $deletedTitleType))
            ->assertSessionHasNoErrors();
        $this->actingAsCurrent($this->administrator)
            ->patch(route('registry.settings.search-locations.restore', $deletedLocation))
            ->assertSessionHasNoErrors();

        $this->assertFalse($deletedTitleType->refresh()->trashed());
        $this->assertFalse($deletedTitleType->is_active);
        $this->assertFalse($deletedLocation->refresh()->trashed());
        $this->assertFalse($deletedLocation->is_active);
        Event::assertDispatched(ReferenceItemRestored::class, 2);
    }

    public function test_references_used_by_a_form_cannot_be_deleted(): void
    {
        $titleType = TitleTypeModel::factory()->create(['title_type_code' => 'USED']);
        $location = SearchLocationModel::factory()->create(['location_code' => 'USED_LOCATION']);
        MissingFormModel::factory()->create([
            'title_type_code_unique' => $titleType->title_type_code_unique,
            'last_search_location_code_unique' => $location->search_location_code_unique,
        ]);

        $this->actingAsCurrent($this->administrator)
            ->delete(route('registry.settings.title-types.destroy', $titleType))
            ->assertSessionHasErrors('title_type');
        $this->actingAsCurrent($this->administrator)
            ->delete(route('registry.settings.search-locations.destroy', $location))
            ->assertSessionHasErrors('search_location');

        $this->assertFalse($titleType->fresh()->trashed());
        $this->assertFalse($location->fresh()->trashed());
    }

    private function createUser(RoleCode $roleCode): UserModel
    {
        $role = RoleModel::query()->where('role_code', $roleCode->value)->firstOrFail();

        return UserModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
            'service_code_unique' => $this->service->service_code_unique,
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
