<?php

namespace App\Dto\Analytics;

final readonly class PdfExportDTO
{
    public function __construct(
        public string $filename,
        public string $content,
    ) {}
}
