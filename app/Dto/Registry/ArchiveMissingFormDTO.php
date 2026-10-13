<?php

namespace App\Dto\Registry;

final readonly class ArchiveMissingFormDTO
{
    public function __construct(public string $reason) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self($validated['archive_reason']);
    }
}
