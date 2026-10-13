<?php

namespace App\Dto\Audit;

use App\Enums\Audit\AuditAction;
use App\Enums\Audit\AuditCategory;
use Carbon\CarbonImmutable;

final readonly class AuditFiltersDTO
{
    public function __construct(
        public ?string $search = null,
        public ?AuditCategory $category = null,
        public ?AuditAction $action = null,
        public ?string $actorUserCodeUnique = null,
        public ?CarbonImmutable $occurredFrom = null,
        public ?CarbonImmutable $occurredTo = null,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            search: filled($validated['search'] ?? null) ? trim((string) $validated['search']) : null,
            category: filled($validated['category'] ?? null)
                ? AuditCategory::from($validated['category'])
                : null,
            action: filled($validated['action'] ?? null)
                ? AuditAction::from($validated['action'])
                : null,
            actorUserCodeUnique: filled($validated['actor_user_code_unique'] ?? null)
                ? $validated['actor_user_code_unique']
                : null,
            occurredFrom: filled($validated['occurred_from'] ?? null)
                ? CarbonImmutable::parse($validated['occurred_from'])->startOfDay()
                : null,
            occurredTo: filled($validated['occurred_to'] ?? null)
                ? CarbonImmutable::parse($validated['occurred_to'])->endOfDay()
                : null,
        );
    }
}
