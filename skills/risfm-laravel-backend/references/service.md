# Service pattern

Use this reference for business workflows, transitions, transactions, and event dispatch.

## Responsibilities

A service:

- enforces business invariants;
- coordinates repositories;
- opens transactions for multi-write operations;
- dispatches domain events after a successful commit;
- returns models, collections, DTOs, value objects, or typed result objects.

It must not depend on `Request`, `View`, `RedirectResponse`, controllers, or Blade.

```php
final class MissionService
{
    public function __construct(
        private readonly MissionRepositoryInterface $missions,
        private readonly FormRepositoryInterface $forms,
    ) {
    }

    public function srv_assign(
        string $missionUuid,
        AssignMissionDTO $data,
        UserModel $actor,
    ): SearchMissionModel {
        $updatedMission = DB::transaction(function () use ($missionUuid, $data, $actor): SearchMissionModel {
            $mission = $this->missions->repo_findForUpdateByUuid($missionUuid);

            if (! $mission->canBeAssigned()) {
                throw new DomainException('Cette mission ne peut pas être affectée.');
            }

            return $this->missions->repo_assign($mission, $data, $actor);
        });

        MissionAssigned::dispatch($updatedMission->uuid, $actor->uuid);

        return $updatedMission;
    }
}
```

Prefer events that carry stable identifiers instead of large mutable model graphs. Use after-commit dispatch when listeners require committed data.
