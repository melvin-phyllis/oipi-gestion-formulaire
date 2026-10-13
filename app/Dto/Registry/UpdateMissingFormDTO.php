<?php

namespace App\Dto\Registry;

use App\Enums\Registry\FormPriority;

final readonly class UpdateMissingFormDTO
{
    public function __construct(
        public string $titleTypeCodeUnique,
        public int $formYear,
        public string $formNumber,
        public FormPriority $priority,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            $validated['title_type_code_unique'],
            (int) $validated['form_year'],
            $validated['form_number'],
            FormPriority::from($validated['priority']),
        );
    }
}
