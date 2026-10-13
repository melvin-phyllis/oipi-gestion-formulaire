<?php

namespace App\Dto\Registry;

use App\Enums\Registry\FinderType;
use App\Enums\Registry\FormPriority;
use Carbon\CarbonImmutable;

final readonly class RegisterFoundMissingFormDTO
{
    public function __construct(
        public string $titleTypeCodeUnique,
        public int $formYear,
        public string $formNumber,
        public FormPriority $priority,
        public FinderType $finderType,
        public ?string $finderUserCodeUnique,
        public ?string $finderName,
        public ?string $finderOrganization,
        public ?string $finderContact,
        public string $foundLocationCodeUnique,
        public CarbonImmutable $foundOn,
        public string $resultSummary,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            titleTypeCodeUnique: $validated['title_type_code_unique'],
            formYear: (int) $validated['form_year'],
            formNumber: $validated['form_number'],
            priority: FormPriority::from($validated['priority']),
            finderType: FinderType::from($validated['finder_type']),
            finderUserCodeUnique: $validated['finder_user_code_unique'] ?? null,
            finderName: $validated['finder_name'] ?? null,
            finderOrganization: $validated['finder_organization'] ?? null,
            finderContact: $validated['finder_contact'] ?? null,
            foundLocationCodeUnique: $validated['found_location_code_unique'],
            foundOn: CarbonImmutable::parse($validated['found_on'])->startOfDay(),
            resultSummary: $validated['result_summary'],
        );
    }

    public function missingForm(): CreateMissingFormDTO
    {
        return new CreateMissingFormDTO(
            titleTypeCodeUnique: $this->titleTypeCodeUnique,
            formYear: $this->formYear,
            formNumber: $this->formNumber,
            priority: $this->priority,
        );
    }
}
