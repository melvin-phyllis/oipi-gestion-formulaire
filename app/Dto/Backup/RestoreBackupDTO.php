<?php

namespace App\Dto\Backup;

final readonly class RestoreBackupDTO
{
    public function __construct(
        public string $confirmationPhrase,
        public string $currentPassword,
    ) {}

    /** @param array{confirmation_phrase: string, current_password: string} $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            confirmationPhrase: $validated['confirmation_phrase'],
            currentPassword: $validated['current_password'],
        );
    }
}
