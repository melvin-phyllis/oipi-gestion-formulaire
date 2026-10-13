<?php

namespace App\Dto\Registry;

final readonly class SaveTitleTypeDTO
{
    public function __construct(
        public string $titleTypeCode,
        public string $titleTypeName,
        public int $displayOrder,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            $validated['title_type_code'],
            $validated['title_type_name'],
            (int) $validated['display_order'],
        );
    }
}
