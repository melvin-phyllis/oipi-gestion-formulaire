# Controller pattern

Use this reference when creating or changing a controller.

## Responsibilities

A controller may:

- receive a `FormRequest` or route-bound model;
- call one or more application services;
- select a Blade view;
- construct a redirect with a French flash message;
- return JSON only for an explicitly dynamic endpoint.

A controller must not contain query-builder chains, business transitions, transactions, notification orchestration, or permission inference.

```php
final class MissionController extends Controller
{
    public function __construct(
        private readonly MissionService $missionService,
    ) {
    }

    public function ctrl_assign(
        AssignMissionFormRequest $request,
        string $missionUuid,
    ): RedirectResponse {
        $mission = $this->missionService->srv_assign(
            $missionUuid,
            AssignMissionDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return redirect()
            ->route('missions.show', $mission->uuid)
            ->with('success', 'La mission a été affectée.');
    }
}
```

Use explicit return types. Keep public method names prefixed with `ctrl_`.
