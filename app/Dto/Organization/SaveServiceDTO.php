<?php

namespace App\Dto\Organization;

final readonly class SaveServiceDTO
{
    public function __construct(
        public string $directorateCodeUnique,
        public string $serviceCode,
        public ?string $abbreviation,
        public string $serviceName,
        public int $displayOrder,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            directorateCodeUnique: $validated['directorate_code_unique'],
            serviceCode: $validated['service_code'],
            abbreviation: $validated['abbreviation'] ?? null,
            serviceName: $validated['service_name'],
            displayOrder: (int) $validated['display_order'],
        );
    }
}
