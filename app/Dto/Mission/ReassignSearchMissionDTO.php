<?php

namespace App\Dto\Mission;

use App\Enums\Registry\FormPriority;
use Carbon\CarbonImmutable;

final readonly class ReassignSearchMissionDTO
{
    public function __construct(
        public string $searchLocationCodeUnique,
        public string $responsibleUserCodeUnique,
        public ?CarbonImmutable $dueDate,
        public FormPriority $priority,
        public string $reason,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            $validated['search_location_code_unique'],
            $validated['responsible_user_code_unique'],
            filled($validated['due_date'] ?? null) ? CarbonImmutable::parse($validated['due_date']) : null,
            FormPriority::from($validated['priority']),
            $validated['reassignment_reason'],
        );
    }

    public function assignment(): AssignSearchMissionDTO
    {
        return new AssignSearchMissionDTO(
            $this->searchLocationCodeUnique,
            $this->responsibleUserCodeUnique,
            $this->dueDate,
            $this->priority,
        );
    }
}
