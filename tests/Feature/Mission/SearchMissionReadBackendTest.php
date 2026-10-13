<?php

namespace Tests\Feature\Mission;

use App\Dto\Mission\MissionFiltersDTO;
use App\Enums\Administration\RoleCode;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Services\Mission\SearchMissionReadService;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class SearchMissionReadBackendTest extends TestCase
{
    use RefreshDatabase;

    private SearchMissionReadService $service;

    private UserModel $manager;

    private UserModel $agent;

    private UserModel $otherAgent;

    private SearchLocationModel $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->service = app(SearchMissionReadService::class);
        $this->manager = $this->userForRole(RoleCode::RegistryManager);
        $this->agent = $this->userForRole(RoleCode::SearchAgent);
        $this->otherAgent = $this->userForRole(RoleCode::SearchAgent);
        $this->location = SearchLocationModel::factory()->create();
    }

    public function test_agent_list_is_strictly_limited_to_own_missions(): void
    {
        $ownMission = $this->missionFor($this->agent, 'B-2017-000154');
        $this->missionFor($this->otherAgent, 'M-2014-005421');

        $data = $this->service->srv_list(new MissionFiltersDTO, $this->agent);

        $this->assertFalse($data['canViewAllMissions']);
        $this->assertSame(1, $data['missions']->total());
        $this->assertTrue($data['missions']->first()->is($ownMission));
        $this->assertCount(1, $data['responsibleUsers']);
        $this->assertTrue($data['responsibleUsers']->first()->is($this->agent));
    }

    public function test_manager_list_can_filter_all_missions(): void
    {
        $this->missionFor($this->agent, 'B-2017-000154', FormPriority::Urgent);
        $this->missionFor($this->otherAgent, 'M-2014-005421', FormPriority::Normal);

        $data = $this->service->srv_list(new MissionFiltersDTO(
            search: 'B-2017',
            priority: FormPriority::Urgent,
        ), $this->manager);

        $this->assertTrue($data['canViewAllMissions']);
        $this->assertSame(1, $data['missions']->total());
        $this->assertSame('B-2017-000154', $data['missions']->first()->missingForm->form_number);
        $this->assertContains(MissionStatus::Assigned, $data['statuses']);
    }

    public function test_agent_can_open_own_mission_with_complete_relations_and_actions(): void
    {
        $mission = $this->missionFor($this->agent, 'B-2017-000154');

        $data = $this->service->srv_showData($mission, $this->agent);

        foreach (['missingForm', 'searchLocation', 'responsible', 'assignedBy', 'history', 'reminders', 'foundReview'] as $relation) {
            $this->assertTrue($data['mission']->relationLoaded($relation), "The [$relation] relation should be loaded.");
        }
        $this->assertTrue($data['canRecordResult']);
        $this->assertFalse($data['canAssign']);
        $this->assertFalse($data['canReviewFound']);
    }

    public function test_agent_cannot_open_another_agents_mission(): void
    {
        $mission = $this->missionFor($this->otherAgent, 'M-2014-005421');

        $this->expectException(AuthorizationException::class);
        $this->service->srv_showData($mission, $this->agent);
    }

    public function test_mission_consultation_routes_use_explicit_permissions(): void
    {
        $permission = 'permission:missions_assign,missions_record_any_result,missions_record_own_result,missions_review_found';

        foreach (['missions.index', 'missions.show'] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertContains('GET', $route->methods());
            $this->assertContains($permission, $route->gatherMiddleware());
        }
    }

    private function userForRole(RoleCode $role): UserModel
    {
        return UserModel::factory()->create([
            'role_code_unique' => RoleModel::query()
                ->where('role_code', $role->value)
                ->valueOrFail('role_code_unique'),
        ]);
    }

    private function missionFor(
        UserModel $responsible,
        string $formNumber,
        FormPriority $priority = FormPriority::High,
    ): SearchMissionModel {
        $form = MissingFormModel::factory()->create(['form_number' => $formNumber]);

        return SearchMissionModel::factory()->create([
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'search_location_code_unique' => $this->location->search_location_code_unique,
            'responsible_user_code_unique' => $responsible->user_code_unique,
            'assigned_by_user_code_unique' => $this->manager->user_code_unique,
            'status' => MissionStatus::Assigned,
            'priority' => $priority,
        ]);
    }
}
