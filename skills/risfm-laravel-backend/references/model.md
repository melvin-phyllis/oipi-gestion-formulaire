# Model pattern

Use this reference for Eloquent models, identifiers, relationships, casts, and scopes.

```php
final class SearchMissionModel extends Model
{
    use HasFactory;
    use HasUniqueCode;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'mis_search_mission_models';

    protected $fillable = [
        'form_code_unique',
        'assigned_user_code_unique',
        'status',
        'due_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MissionStatus::class,
            'due_at' => 'immutable_datetime',
        ];
    }
}
```

## Invariants

- `id` is internal only.
- `uuid` is the public route identifier.
- `code_unique` is a stable business identifier.
- Models use `*Model` names and explicit prefixed table names.
- Relationships use immutable `*_code_unique` columns and indexed constraints.
- Models may expose relationships, casts, scopes, and small state predicates.
- Cross-aggregate workflow logic belongs in services.
- Mass assignment must be explicit; never use an unrestricted guarded configuration for business models.
