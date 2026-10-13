<?php

namespace App\Dto\Registry;

final readonly class ImportMissingFormsDTO
{
    public function __construct(
        public string $filePath,
        public string $originalFileName = 'import.csv',
        public string $extension = 'csv',
        public int $sizeBytes = 0,
    ) {}
}
