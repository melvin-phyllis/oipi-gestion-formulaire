<?php

namespace App\Dto\Analytics;

use Closure;

final readonly class SpreadsheetExportDTO
{
    public function __construct(
        public string $filename,
        public Closure $writer,
    ) {}
}
