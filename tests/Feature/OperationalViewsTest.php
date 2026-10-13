<?php

namespace Tests\Feature;

use App\Enums\Administration\RoleCode;
use App\Enums\Mission\MissionReminderType;
use App\Enums\Mission\MissionResult;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Mission\FoundReportReviewModel;
use App\Models\Mission\MissionReminderModel;
use App\Models\Mission\SearchHistoryModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\FormAttachmentModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OperationalViewsTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->administrator = $this->userForRole(RoleCode::SystemAdmin, 'ADMIN', 'RISFM');
    }

    public function test_populated_views_display_the_real_business_relations_and_fields(): void
    {
        $reviewer = $this->userForRole(RoleCode::RegistryManager, 'KONE', 'Awa');
        $location = SearchLocationModel::factory()->create(['location_name' => 'Archives centrales']);
        $form = MissingFormModel::factory()->create([
            'status' => FormStatus::NeedsVerification,
            'created_by_user_code_unique' => $this->administrator->user_code_unique,
        ]);
        $mission = SearchMissionModel::factory()->create([
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'search_location_code_unique' => $location->search_location_code_unique,
            'responsible_user_code_unique' => $reviewer->user_code_unique,
            'assigned_by_user_code_unique' => $this->administrator->user_code_unique,
            'status' => MissionStatus::Completed,
            'result' => MissionResult::Found,
            'result_summary' => 'Document retrouvé dans le rayon B4.',
        ]);

        SearchHistoryModel::query()->create([
            'search_mission_code_unique' => $mission->search_mission_code_unique,
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'tracking_cycle' => 1,
            'search_location_code_unique' => $location->search_location_code_unique,
            'search_location_name' => 'Archives centrales',
            'responsible_user_code_unique' => $reviewer->user_code_unique,
            'responsible_name' => 'KONE Awa',
            'form_status' => FormStatus::NeedsVerification,
            'mission_result' => MissionResult::Found,
            'searched_on' => today(),
            'result_summary' => 'Document retrouvé dans le rayon B4.',
            'recorded_by_user_code_unique' => $reviewer->user_code_unique,
            'recorded_by_name' => 'KONE Awa',
            'source' => 'mission_result',
        ]);
        FoundReportReviewModel::factory()->create([
            'search_mission_code_unique' => $mission->search_mission_code_unique,
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'reviewed_by_user_code_unique' => $reviewer->user_code_unique,
        ]);
        MissionReminderModel::factory()->create([
            'search_mission_code_unique' => $mission->search_mission_code_unique,
            'recipient_user_code_unique' => $reviewer->user_code_unique,
            'type' => MissionReminderType::DueToday,
            'database_sent_at' => now(),
        ]);

        $this->authenticate($this->administrator);

        $this->get(route('registry.forms.show', $form))
            ->assertOk()
            ->assertSee('Archives centrales')
            ->assertSee('Recherche : Retrouvé')
            ->assertSee('Document retrouvé dans le rayon B4.')
            ->assertSee('Compte rendu saisi par : KONE Awa');

        $this->get(route('missions.show', $mission))
            ->assertOk()
            ->assertSee('Awa KONE')
            ->assertSee('Échéance aujourd’hui');
    }

    public function test_active_mission_hides_archive_action_in_list_and_detail(): void
    {
        $form = MissingFormModel::factory()->create(['status' => FormStatus::InSearch]);
        SearchMissionModel::factory()->create([
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'responsible_user_code_unique' => $this->administrator->user_code_unique,
            'status' => MissionStatus::Assigned,
        ]);

        $this->authenticate($this->administrator);

        $this->get(route('registry.forms.index'))
            ->assertOk()
            ->assertDontSee('modal-archive-'.$form->uuid)
            ->assertDontSee('Restaurer le dossier');
        $this->get(route('registry.forms.show', $form))
            ->assertOk()
            ->assertDontSee('Archiver le dossier')
            ->assertDontSee('Restaurer le dossier');

        $this->from(route('registry.forms.show', $form))
            ->patch(route('registry.forms.restore', $form))
            ->assertRedirect(route('registry.forms.show', $form))
            ->assertSessionHasErrors([
                'restore' => 'Seul un formulaire archivé peut être restauré.',
            ]);
    }

    public function test_archived_unresolved_form_does_not_fake_finalization_or_reopening(): void
    {
        $form = MissingFormModel::factory()->create([
            'status' => FormStatus::Archived,
            'status_before_archive' => FormStatus::Missing,
            'archive_reason' => 'Doublon administratif.',
            'archived_at' => now(),
        ]);

        $this->authenticate($this->administrator);

        $this->get(route('registry.forms.show', $form))
            ->assertOk()
            ->assertDontSee('Parcours de finalisation du dossier')
            ->assertDontSee('Rouvrir le dossier')
            ->assertSee('Restaurer le dossier');
    }

    public function test_finalization_stepper_marks_only_the_current_step_as_active(): void
    {
        $form = MissingFormModel::factory()->create([
            'status' => FormStatus::Entered,
            'resolved_at' => now(),
        ]);

        $this->authenticate($this->administrator);

        $this->get(route('registry.forms.show', $form))
            ->assertOk()
            ->assertSee('class="step-item" data-finalization-step="found"', false)
            ->assertSee('class="step-item" data-finalization-step="digitized"', false)
            ->assertSee('class="step-item active" data-finalization-step="entered"', false);
    }

    public function test_reassignment_uses_business_selectors_instead_of_technical_code_inputs(): void
    {
        $agent = $this->userForRole(RoleCode::SearchAgent, 'YAO', 'Marc');
        $location = SearchLocationModel::factory()->create(['location_name' => 'Magasin secondaire']);
        $mission = SearchMissionModel::factory()->create([
            'responsible_user_code_unique' => $agent->user_code_unique,
            'search_location_code_unique' => $location->search_location_code_unique,
            'assigned_by_user_code_unique' => $this->administrator->user_code_unique,
            'status' => MissionStatus::Assigned,
            'priority' => FormPriority::High,
        ]);

        $this->authenticate($this->administrator);

        $this->get(route('missions.show', $mission))
            ->assertOk()
            ->assertSee('id="reassign_responsible"', false)
            ->assertSee('value="'.$agent->user_code_unique.'"', false)
            ->assertSee('YAO')
            ->assertSee('value="'.$location->search_location_code_unique.'"', false)
            ->assertSee('Magasin secondaire')
            ->assertSee(FormPriority::Low->label());
    }

    public function test_read_only_user_does_not_receive_a_missions_navigation_link(): void
    {
        $reader = $this->userForRole(RoleCode::ReadOnly, 'LECTEUR', 'Simple');

        $this->authenticate($reader);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('missions.index').'"', false);
    }

    public function test_form_show_view_renders_attachments_table_and_actions(): void
    {
        $form = MissingFormModel::factory()->create();
        $attachment = FormAttachmentModel::factory()->create([
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'original_name' => 'Bordereau_officiel.pdf',
            'uploaded_by_user_code_unique' => $this->administrator->user_code_unique,
            'uploader_name' => 'ADMIN RISFM',
        ]);

        $this->authenticate($this->administrator);

        $this->get(route('registry.forms.show', $form))
            ->assertOk()
            ->assertSee('Pièces jointes associées')
            ->assertSee('Bordereau_officiel.pdf')
            ->assertSee(route('registry.forms.attachments.download', [$form, $attachment]))
            ->assertSee('data-bs-target="#modal-add-attachment"', false);
    }

    public function test_user_profile_link_is_rendered_and_navigable(): void
    {
        $this->authenticate($this->administrator);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('profile.edit'))
            ->assertSee('Mon profil')
            ->assertSee(route('roles.index'))
            ->assertSee(route('notifications.index'))
            ->assertSee('ti ti-bell')
            ->assertSee('data-bs-toggle="sidebar-folded"', false)
            ->assertSee('data-risfm-sidebar-toggle', false)
            ->assertSee('risfm-sidebar-icon-collapse', false)
            ->assertSee('risfm-sidebar-icon-expand', false)
            ->assertDontSee('Réduire ou agrandir la barre latérale')
            ->assertDontSee('<span class="nav-link-title">Notifications</span>', false);

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Modifier mes coordonnées personnelles')
            ->assertSee(route('profile.update'));
    }

    private function authenticate(UserModel $user): void
    {
        $this->actingAs($user)->withSession([
            'auth_session_version' => $user->session_version,
        ]);
    }

    private function userForRole(RoleCode $role, string $lastName, string $firstNames): UserModel
    {
        return UserModel::factory()->create([
            'role_code_unique' => RoleModel::query()
                ->where('role_code', $role->value)
                ->valueOrFail('role_code_unique'),
            'last_name' => $lastName,
            'first_names' => $firstNames,
            'must_change_password' => false,
        ]);
    }
}
