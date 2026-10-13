<?php

namespace App\Console\Commands;

use App\Services\Backup\DatabaseBackupService;
use Illuminate\Console\Command;
use Throwable;

final class CreateDatabaseBackup extends Command
{
    protected $signature = 'risfm:backup-database';

    protected $description = 'Crée une sauvegarde privée de la base RISFM et applique la rétention';

    public function handle(DatabaseBackupService $backups): int
    {
        try {
            $backup = $backups->srv_createScheduled();
            $deleted = $backups->srv_prune();

            $this->info("Sauvegarde créée : {$backup->file_name}");
            $this->line("Anciennes sauvegardes automatiques supprimées : {$deleted}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('La sauvegarde a échoué. Consultez les journaux de l’application.');

            return self::FAILURE;
        }
    }
}
