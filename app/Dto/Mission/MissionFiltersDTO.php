<?php

namespace App\Dto\Mission;

use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use Carbon\CarbonImmutable;

final readonly class MissionFiltersDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $responsibleUserCodeUnique = null,
        public ?string $searchLocationCodeUnique = null,
        public ?MissionStatus $status = null,
        public ?FormPriority $priority = null,
        public ?CarbonImmutable $dueFrom = null,
        public ?CarbonImmutable $dueTo = null,
        public bool $overdueOnly = false,
        public int $perPage = 15,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            search: filled($validated['search'] ?? null) ? $validated['search'] : null,
            responsibleUserCodeUnique: filled($validated['responsible_user_code_unique'] ?? null)
                ? $validated['responsible_user_code_unique']
                : null,
            searchLocationCodeUnique: filled($validated['search_location_code_unique'] ?? null)
                ? $validated['search_location_code_unique']
                : null,
            status: filled($validated['status'] ?? null) ? MissionStatus::from($validated['status']) : null,
            priority: filled($validated['priority'] ?? null) ? FormPriority::from($validated['priority']) : null,
            dueFrom: filled($validated['due_from'] ?? null) ? CarbonImmutable::parse($validated['due_from']) : null,
            dueTo: filled($validated['due_to'] ?? null) ? CarbonImmutable::parse($validated['due_to']) : null,
            overdueOnly: (bool) ($validated['overdue_only'] ?? false),
            perPage: (int) ($validated['per_page'] ?? 15),
        );
    }
}
