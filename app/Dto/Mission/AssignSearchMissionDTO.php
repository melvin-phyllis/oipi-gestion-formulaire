<?php

namespace App\Dto\Mission;

use App\Enums\Registry\FormPriority;
use Carbon\CarbonImmutable;

final readonly class AssignSearchMissionDTO
{
    public function __construct(
        public string $searchLocationCodeUnique,
        public string $responsibleUserCodeUnique,
        public ?CarbonImmutable $dueDate,
        public FormPriority $priority,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            $validated['search_location_code_unique'],
            $validated['responsible_user_code_unique'],
            filled($validated['due_date'] ?? null) ? CarbonImmutable::parse($validated['due_date']) : null,
            FormPriority::from($validated['priority']),
        );
    }
}
