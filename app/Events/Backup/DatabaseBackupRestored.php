<?php

namespace App\Events\Backup;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DatabaseBackupRestored
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $backupUuid,
        public readonly string $safetyBackupUuid,
        public readonly string $actorUuid,
    ) {}
}
