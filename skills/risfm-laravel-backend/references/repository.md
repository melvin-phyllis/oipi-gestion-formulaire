# Repository pattern

Use this reference for persistence, filtering, locking, pagination, and dependency binding.

## Contract

```php
interface MissionRepositoryInterface
{
    public function repo_findByUuid(string $uuid): SearchMissionModel;

    public function repo_findForUpdateByUuid(string $uuid): SearchMissionModel;

    public function repo_assign(
        SearchMissionModel $mission,
        AssignMissionDTO $data,
        UserModel $actor,
    ): SearchMissionModel;
}
```

## Implementation rules

- All public repository methods use the `repo_` prefix.
- Return concrete typed models, collections, paginators, or documented scalar/array shapes.
- Use eager loading intentionally to avoid N+1 queries.
- Use `lockForUpdate()` only inside a transaction.
- Convert low-level persistence failures to a project repository exception when callers need a stable boundary.
- Do not authorize users or choose business transitions in a repository.

Bind interfaces centrally in `RepositoryServiceProvider`; do not resolve implementations manually.
