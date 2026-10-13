<?php

namespace App\Events\Backup;

use App\Enums\Backup\BackupType;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DatabaseBackupCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $backupUuid,
        public readonly BackupType $type,
        public readonly ?string $actorUuid,
    ) {}
}
