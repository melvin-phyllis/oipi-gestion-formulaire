<?php

namespace App\Events\Organization;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ServiceDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $serviceUuid,
        public readonly string $actorUuid,
    ) {}
}
