<?php

namespace App\Dto\Backup;

use App\Enums\Backup\BackupType;

final readonly class BackupFileDTO
{
    public function __construct(
        public BackupType $type,
        public string $disk,
        public string $filePath,
        public string $fileName,
        public int $sizeBytes,
        public string $sha256,
        public string $databaseName,
        public ?string $creatorCodeUnique,
        public ?string $creatorName,
    ) {}
}
