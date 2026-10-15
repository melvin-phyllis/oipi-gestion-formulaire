<?php

namespace App\Services\Backup;

use Generator;
use RuntimeException;

final class BackupDumpCompatibilityService
{
    /**
     * Adapt only mysqldump table statements; quoted record values remain unchanged.
     *
     * @param  resource  $input
     * @return Generator<int, string>
     */
    public function srv_restoreInput($input): Generator
    {
        while (($line = fgets($input)) !== false) {
            yield preg_replace(
                '/^(\s*(?:\/\*!\d+\s+)?(?:DROP\s+TABLE\s+IF\s+EXISTS|CREATE\s+TABLE|INSERT\s+INTO|LOCK\s+TABLES|ALTER\s+TABLE)\s+)`sys_database_backup_models`/i',
                '$1`bkp_backup_models`',
                $line,
            ) ?? $line;
        }

        if (! feof($input)) {
            throw new RuntimeException('La lecture du fichier SQL à restaurer a échoué.');
        }
    }
}
