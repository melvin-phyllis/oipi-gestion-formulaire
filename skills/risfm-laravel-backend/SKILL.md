---
name: risfm-laravel-backend
description: Implement or review backend work in the RISFM Laravel monolith using its layer-first architecture, custom RBAC, English technical naming, Blade session flows, domain events, queues, audit, and repository conventions. Use for changes under app, routes, database, or backend tests; do not use for isolated visual-only Tabler or CSS work.
---

# RISFM Laravel Backend

Follow `docs/ARCHITECTURE.md` as the source of truth. Preserve Laravel conventions unless this skill defines a deliberate project convention.

## Before editing

1. Inspect the installed Laravel and package versions; never infer them from another project.
2. Inspect sibling code, related migrations, routes, tests, and authorization rules.
3. Check `.ai/rules/index.md` and applicable rules when `.ai/rules` exists.
4. Identify the business domain and the layers the change actually needs.
5. Read only the matching references below.

## Required architecture

Use technical layers first, subdivided by business domain:

```text
FormRequest -> Controller -> Service -> DTO -> Repository -> Model -> database
```

- Controllers: `app/Http/Controllers/{Domain}`; thin HTTP orchestration only.
- Requests: `app/Http/Requests/{Domain}`; validation and request authorization.
- DTOs: `app/Dto/{Domain}`; immutable validated input for writes.
- Services: `app/Services/{Domain}`; business rules, transactions, orchestration, and event dispatch.
- Repository contracts: `app/Repositories/Interfaces/{Domain}`.
- Repository implementations: `app/Repositories/{Domain}`; all persistence and query construction.
- Models: `app/Models/{Domain}`; relations, casts, scopes, and persistence metadata only.
- Events, listeners, jobs, and observers stay in their global Laravel layers and are subdivided by domain.

Do not create `app/Features`, module-local `routes.php` files, or `Http/Controllers/API/V1` for ordinary RISFM work.

## Web application boundary

RISFM is a Blade and Tabler monolith:

- use `routes/*.php` web routes, session authentication, CSRF protection, named routes, and route model binding;
- controllers own `View` and `RedirectResponse` construction;
- services remain independent of HTTP and never return views, redirects, or request objects;
- use JSON only for explicitly dynamic web endpoints;
- add an API version only when an external client is actually requested.

## Naming conventions

All technical identifiers are English. French is allowed only for user-facing text and business documentation.

| Element | Convention | Example |
|---|---|---|
| Controller | `*Controller`, methods prefixed `ctrl_` | `MissionController::ctrl_assign()` |
| Service | `*Service`, methods prefixed `srv_` | `MissionService::srv_assign()` |
| Repository | `*Repository`, methods prefixed `repo_` | `MissionRepository::repo_findByUuid()` |
| Contract | `*RepositoryInterface` | `MissionRepositoryInterface` |
| Model | `*Model` | `SearchMissionModel` |
| DTO | `*DTO` | `AssignMissionDTO` |
| Request | `*FormRequest` | `AssignMissionFormRequest` |
| Event | past tense | `MissionAssigned` |
| Job | imperative action | `SendMissionReminder` |
| Permission | lowercase `resource_action` | `missions_assign` |

Do not translate class names, database objects, routes, status values, permission codes, or variables into French.
Permission codes never contain dots. Dots remain valid for Laravel route names, such as `missions.assign`.

## Domain boundaries

Use one of these domains unless a genuinely new bounded area is required:

- `Authentication`
- `Administration`
- `Organization`
- `Registry`
- `Mission`
- `Analytics`
- `Audit`
- `Backup`

Cross-domain work uses a service contract or a domain event. Never call another controller.

## Data and persistence invariants

- Tables use the documented domain prefix and `*_models` suffix.
- Every business model has internal `id`, public `uuid`, stable `code_unique`, timestamps, and soft deletion unless the architecture documents a justified exception.
- Expose `uuid`, not numeric `id`, in URLs.
- Use immutable `*_code_unique` relationship columns, unique indexes, and database foreign keys where supported.
- Repositories own Eloquent queries. Services must not contain raw SQL or query-builder chains.
- Wrap multi-write business operations in a transaction.
- Dispatch events only after committed state is safe to observe.

## Security invariants

- Authorization is fail-closed: a route or action without an explicit permission mapping is denied.
- Attach authentication, permission, and sensitive-action middleware explicitly to routes.
- Do not infer permissions from controller method names.
- FormRequests must authorize as well as validate writes.
- Validate uploaded MIME type, extension, size, and storage destination; never trust a client filename.
- Store backups and sensitive attachments outside the public directory and download them only through an authorized controller.
- Reauthentication is required for restoration, password/security changes, and other sensitive operations.
- Audit sensitive success and failure outcomes without logging passwords, secrets, tokens, or full sensitive payloads.

## Events, listeners, jobs, and observers

- Events describe completed business facts and use past-tense names.
- Listeners handle independent consequences such as audit, notification, or history.
- Jobs handle slow, retryable, scheduled, or asynchronous work and must be idempotent when retries are possible.
- Observers are limited to systematic technical behavior; do not hide mission assignment, finalization, restoration, or other major workflows in an observer.

## Required verification

Run the narrowest relevant tests, then formatting and static checks available in the project. At minimum for PHP edits:

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact <relevant-test>
```

Also run `git diff --check`. Do not claim database, queue, scheduler, email, or deployment behavior was verified when the environment did not exercise it.

## References

- For HTTP controllers and Blade responses, read [references/controller.md](references/controller.md).
- For services, transactions, and events, read [references/service.md](references/service.md).
- For repositories and container binding, read [references/repository.md](references/repository.md).
- For models, identifiers, and relationships, read [references/model.md](references/model.md).
- For write validation and DTO mapping, read [references/dto-request.md](references/dto-request.md).
- For modular web routes and explicit authorization, read [references/routes.md](references/routes.md).
