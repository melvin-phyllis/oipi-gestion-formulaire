<?php

namespace App\Events\Analytics;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AnalyticsExported
{
    use Dispatchable, SerializesModels;

    /** @param array<string, mixed> $filters */
    public function __construct(
        public readonly string $format,
        public readonly string $actorUuid,
        public readonly array $filters,
    ) {}
}
