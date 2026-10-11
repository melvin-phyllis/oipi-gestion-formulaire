# FormRequest and DTO pattern

Use this reference for every create, update, transition, import, upload, or sensitive action.

## FormRequest

The request owns transport-level authorization, normalization, validation rules, and French user-facing messages.

```php
final class AssignMissionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('missions_assign') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'assignee_code' => trim((string) $this->input('assignee_code')),
        ]);
    }

    public function rules(): array
    {
        return [
            'assignee_code' => ['required', 'string', 'exists:adm_user_models,code_unique'],
            'due_at' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
```

Do not rely on validation alone for state-dependent business rules; the service must re-check them transactionally.

## DTO

The DTO is immutable, contains already validated input, and has no database or HTTP dependency.

```php
final readonly class AssignMissionDTO
{
    public function __construct(
        public string $assigneeCode,
        public CarbonImmutable $dueAt,
    ) {
    }

    public static function fromValidated(array $validated): self
    {
        return new self(
            assigneeCode: $validated['assignee_code'],
            dueAt: CarbonImmutable::parse($validated['due_at']),
        );
    }
}
```

Use a DTO for writes even when the first version is small; this keeps HTTP input outside the service contract.
