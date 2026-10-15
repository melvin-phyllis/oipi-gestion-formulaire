<?php

namespace Tests\Integration\Mission;

use App\Dto\Mission\AssignSearchMissionDTO;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Events\Mission\SearchMissionAssigned;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Repositories\Interfaces\Mission\SearchMissionRepositoryInterface;
use App\Services\Mission\SearchMissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

final class SearchMissionTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--no-interaction' => true])->assertExitCode(0);
    }

    public function test_failure_after_mission_creation_rolls_back_every_write_and_emits_no_event(): void
    {
        Event::fake([SearchMissionAssigned::class]);
        $actor = UserModel::factory()->create();
        $responsible = UserModel::factory()->create();
        $location = SearchLocationModel::factory()->create();
        $form = MissingFormModel::factory()->create();
        $realRepository = $this->app->make(SearchMissionRepositoryInterface::class);
        $repository = $this->mock(SearchMissionRepositoryInterface::class);

        foreach (['repo_findActiveResponsible', 'repo_findActiveLocation', 'repo_lockForm', 'repo_activeAssignmentExists', 'repo_pendingFoundReportExistsForForm', 'repo_create'] as $method) {
            $repository->shouldReceive($method)->andReturnUsing(
                fn (...$arguments) => $realRepository->{$method}(...$arguments),
            );
        }
        $repository->shouldReceive('repo_markFormAssigned')->once()
            ->andThrow(new RuntimeException('Échec simulé après création de la mission.'));

        try {
            $this->app->make(SearchMissionService::class)->srv_assign(
                $form,
                new AssignSearchMissionDTO($location->search_location_code_unique, $responsible->user_code_unique, null, FormPriority::Normal),
                $actor,
            );
            $this->fail('La transaction doit échouer.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Échec simulé', $exception->getMessage());
        }

        $this->assertDatabaseCount('mis_search_mission_models', 0);
        $this->assertSame(FormStatus::Missing, $form->refresh()->status);
        $this->assertNull($form->last_responsible_user_code_unique);
        Event::assertNotDispatched(SearchMissionAssigned::class);
    }

    public function test_assignment_event_observes_committed_form_and_mission_state(): void
    {
        $actor = UserModel::factory()->create();
        $responsible = UserModel::factory()->create();
        $location = SearchLocationModel::factory()->create();
        $form = MissingFormModel::factory()->create();
        $observed = [];
        Event::listen(SearchMissionAssigned::class, function (SearchMissionAssigned $event) use (&$observed): void {
            $observed = [
                'level' => DB::transactionLevel(),
                'missionExists' => SearchMissionModel::query()->where('uuid', $event->missionUuid)->exists(),
                'formStatus' => MissingFormModel::query()->where('uuid', $event->missingFormUuid)->value('status'),
            ];
        });

        $this->app->make(SearchMissionService::class)->srv_assign(
            $form,
            new AssignSearchMissionDTO($location->search_location_code_unique, $responsible->user_code_unique, null, FormPriority::Normal),
            $actor,
        );

        $this->assertSame(0, $observed['level']);
        $this->assertTrue($observed['missionExists']);
        $this->assertSame(FormStatus::InSearch, $observed['formStatus']);
    }
}
