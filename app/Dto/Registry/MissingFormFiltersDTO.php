<?php

namespace App\Dto\Registry;

use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;

final readonly class MissingFormFiltersDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $titleTypeCodeUnique = null,
        public ?int $formYear = null,
        public ?FormStatus $status = null,
        public ?FormPriority $priority = null,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            search: filled($validated['search'] ?? null) ? $validated['search'] : null,
            titleTypeCodeUnique: filled($validated['title_type_code_unique'] ?? null)
                ? $validated['title_type_code_unique']
                : null,
            formYear: filled($validated['form_year'] ?? null) ? (int) $validated['form_year'] : null,
            status: filled($validated['status'] ?? null) ? FormStatus::from($validated['status']) : null,
            priority: filled($validated['priority'] ?? null) ? FormPriority::from($validated['priority']) : null,
        );
    }
}
