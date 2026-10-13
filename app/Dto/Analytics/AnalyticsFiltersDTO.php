<?php

namespace App\Dto\Analytics;

use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use Carbon\CarbonImmutable;

final readonly class AnalyticsFiltersDTO
{
    public function __construct(
        public ?string $titleTypeCodeUnique = null,
        public ?int $formYear = null,
        public ?FormStatus $status = null,
        public ?FormPriority $priority = null,
        public ?string $responsibleUserCodeUnique = null,
        public ?string $searchLocationCodeUnique = null,
        public ?CarbonImmutable $registeredFrom = null,
        public ?CarbonImmutable $registeredTo = null,
        public int $trendMonths = 12,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            titleTypeCodeUnique: filled($validated['title_type_code_unique'] ?? null)
                ? $validated['title_type_code_unique']
                : null,
            formYear: filled($validated['form_year'] ?? null) ? (int) $validated['form_year'] : null,
            status: filled($validated['status'] ?? null) ? FormStatus::from($validated['status']) : null,
            priority: filled($validated['priority'] ?? null) ? FormPriority::from($validated['priority']) : null,
            responsibleUserCodeUnique: filled($validated['responsible_user_code_unique'] ?? null)
                ? $validated['responsible_user_code_unique']
                : null,
            searchLocationCodeUnique: filled($validated['search_location_code_unique'] ?? null)
                ? $validated['search_location_code_unique']
                : null,
            registeredFrom: filled($validated['registered_from'] ?? null)
                ? CarbonImmutable::parse($validated['registered_from'])->startOfDay()
                : null,
            registeredTo: filled($validated['registered_to'] ?? null)
                ? CarbonImmutable::parse($validated['registered_to'])->endOfDay()
                : null,
            trendMonths: (int) ($validated['trend_months'] ?? 12),
        );
    }
}
