<?php

namespace App\Dto\Administration;

final readonly class NotificationFiltersDTO
{
    public function __construct(
        public string $status = 'all',
        public int $perPage = 15,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            status: (string) ($validated['status'] ?? 'all'),
            perPage: (int) ($validated['per_page'] ?? 15),
        );
    }
}
