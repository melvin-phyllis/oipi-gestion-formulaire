<?php

namespace App\Dto\Backup;

final readonly class BackupDownloadDTO
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $filename,
    ) {}
}
