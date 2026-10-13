<?php

namespace App\Services\Backup;

use App\Contracts\Backup\ApplicationMaintenanceInterface;
use App\Contracts\Backup\DatabaseProcessInterface;
use App\Dto\Backup\BackupFileDTO;
use App\Dto\Backup\BackupFiltersDTO;
use App\Dto\Backup\RestoreBackupDTO;
use App\Enums\Backup\BackupType;
use App\Events\Backup\DatabaseBackupCreated;
use App\Events\Backup\DatabaseBackupDeleted;
use App\Events\Backup\DatabaseBackupDownloaded;
use App\Events\Backup\DatabaseBackupRestored;
use App\Events\Backup\DatabaseBackupRestoreFailed;
use App\Models\Administration\UserModel;
use App\Models\Backup\DatabaseBackupModel;
use App\Repositories\Interfaces\Backup\DatabaseBackupRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class DatabaseBackupService
{
    public function __construct(
        private readonly DatabaseBackupRepositoryInterface $backups,
        private readonly DatabaseProcessInterface $database,
        private readonly ApplicationMaintenanceInterface $maintenance,
    ) {}

    /** @return LengthAwarePaginator<int, DatabaseBackupModel> */
    public function srv_list(BackupFiltersDTO $filters): LengthAwarePaginator
    {
        return $this->backups->repo_paginate($filters);
    }

    public function srv_createManual(UserModel $actor): DatabaseBackupModel
    {
        return $this->create(BackupType::Manual, $actor);
    }

    public function srv_createScheduled(): DatabaseBackupModel
    {
        return $this->create(BackupType::Scheduled);
    }

    public function srv_download(DatabaseBackupModel $backup, UserModel $actor): StreamedResponse
    {
        $this->verifyIntegrity($backup);
        DatabaseBackupDownloaded::dispatch($backup->uuid, $actor->uuid);

        return $this->disk($backup->disk)->download(
            $backup->file_path,
            $backup->file_name,
            [
                'Content-Type' => 'application/gzip',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function srv_delete(DatabaseBackupModel $backup, UserModel $actor): void
    {
        $this->deleteFileAndRecord($backup, $actor->user_code_unique);
        DatabaseBackupDeleted::dispatch($backup->uuid, $actor->uuid, false);
    }

    public function srv_prune(): int
    {
        $retentionDays = max(1, (int) config('risfm.backups.retention_days', 14));
        $expired = $this->backups->repo_expiredAutomatic(CarbonImmutable::now()->subDays($retentionDays));
        $deleted = 0;

        foreach ($expired as $backup) {
            try {
                $this->deleteFileAndRecord($backup, null);
                DatabaseBackupDeleted::dispatch($backup->uuid, null, true);
                $deleted++;
            } catch (Throwable $exception) {
                Log::error('Impossible de purger une ancienne sauvegarde RISFM.', [
                    'backup_code' => $backup->database_backup_code_unique,
                    'exception' => $exception::class,
                ]);
            }
        }

        return $deleted;
    }

    public function srv_restore(
        DatabaseBackupModel $backup,
        RestoreBackupDTO $data,
        UserModel $actor,
    ): DatabaseBackupModel {
        $expectedPhrase = (string) config('risfm.backups.confirmation_phrase', 'RESTAURER OIPI');
        if (! hash_equals($expectedPhrase, $data->confirmationPhrase)) {
            throw ValidationException::withMessages([
                'confirmation_phrase' => "Saisissez exactement « {$expectedPhrase} ».",
            ]);
        }
        if (! Hash::check($data->currentPassword, $actor->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Votre mot de passe actuel est incorrect.',
            ]);
        }

        $this->verifyIntegrity($backup);
        $lock = $this->acquireDatabaseOperationLock();
        $temporarySql = null;
        $safetyBackup = null;
        $failure = null;
        $maintenanceEnabled = false;
        $restoreStarted = false;
        $recoveryFailed = false;
        $databaseDisconnected = false;

        try {
            $this->maintenance->maintenance_down();
            $maintenanceEnabled = true;
            $safetyBackup = $this->create(BackupType::PreRestore, $actor, skipOperationLock: true);
            $temporarySql = $this->temporaryPath('restore-', '.sql');
            $this->decompress($this->absolutePath($backup), $temporarySql);
            $this->assertSqlDump($temporarySql);

            $connection = DB::getDefaultConnection();
            $driver = DB::connection($connection)->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::disconnect($connection);
                $databaseDisconnected = true;
            }
            $restoreStarted = true;
            $this->database->process_restore($temporarySql);
            if ($databaseDisconnected) {
                DB::purge($connection);
                DB::reconnect($connection);
                $databaseDisconnected = false;
            }

            $backup = $this->backups->repo_rehydrate($backup);
            $safetyBackup = $this->backups->repo_rehydrate($safetyBackup);
        } catch (Throwable $exception) {
            $failure = $exception;

            if ($restoreStarted && $safetyBackup instanceof DatabaseBackupModel) {
                try {
                    $this->restoreSafetyBackup($safetyBackup);
                } catch (Throwable $recoveryException) {
                    $recoveryFailed = true;
                    Log::critical('La restauration RISFM et son retour de sécurité ont échoué.', [
                        'backup_code' => $backup->database_backup_code_unique,
                        'safety_backup_code' => $safetyBackup->database_backup_code_unique,
                        'restore_exception' => $exception::class,
                        'recovery_exception' => $recoveryException::class,
                    ]);
                }
            }

            if ($databaseDisconnected) {
                try {
                    DB::purge(DB::getDefaultConnection());
                    DB::reconnect();
                    $databaseDisconnected = false;
                } catch (Throwable) {
                    $recoveryFailed = true;
                }
            }
        } finally {
            if ($temporarySql && is_file($temporarySql)) {
                @unlink($temporarySql);
            }

            if ($maintenanceEnabled && ! $recoveryFailed) {
                try {
                    $this->maintenance->maintenance_up();
                } catch (Throwable $exception) {
                    $failure ??= $exception;
                }
            }

            $this->releaseDatabaseOperationLock($lock);
        }

        if ($failure instanceof Throwable) {
            DatabaseBackupRestoreFailed::dispatch($backup->uuid, $actor->uuid, $failure::class);

            throw new RuntimeException(
                $recoveryFailed
                    ? 'La restauration et le retour de sécurité ont échoué. L’application reste en maintenance pour protéger les données.'
                    : 'La restauration a échoué. La sauvegarde de sécurité préalable a été réappliquée.',
                previous: $failure,
            );
        }

        if (! $safetyBackup instanceof DatabaseBackupModel) {
            throw new RuntimeException('La sauvegarde de sécurité préalable est introuvable.');
        }

        DatabaseBackupRestored::dispatch($backup->uuid, $safetyBackup->uuid, $actor->uuid);

        return $safetyBackup;
    }

    private function create(
        BackupType $type,
        ?UserModel $actor = null,
        bool $skipOperationLock = false,
    ): DatabaseBackupModel {
        if ($skipOperationLock) {
            return $this->createBackupFile($type, $actor);
        }

        $lock = $this->acquireDatabaseOperationLock();

        try {
            return $this->createBackupFile($type, $actor);
        } finally {
            $this->releaseDatabaseOperationLock($lock);
        }
    }

    private function createBackupFile(BackupType $type, ?UserModel $actor): DatabaseBackupModel
    {
        [$diskName, $directory] = $this->storageConfiguration();
        $disk = $this->disk($diskName);
        $disk->makeDirectory($directory);
        $disk->makeDirectory("{$directory}/.tmp");

        $token = Str::lower((string) Str::ulid());
        $timestamp = now()->format('Ymd_His');
        $fileName = "risfm_backup_{$timestamp}_{$token}.sql.gz";
        $rawPath = "{$directory}/.tmp/{$token}.sql";
        $partialPath = "{$directory}/.tmp/{$fileName}.part";
        $finalPath = "{$directory}/{$fileName}";
        $rawAbsolute = $disk->path($rawPath);
        $partialAbsolute = $disk->path($partialPath);

        try {
            $this->database->process_dump($rawAbsolute);
            chmod($rawAbsolute, 0600);
            $this->compress($rawAbsolute, $partialAbsolute);
            chmod($partialAbsolute, 0600);

            if (! $disk->move($partialPath, $finalPath)) {
                throw new RuntimeException('Impossible de finaliser le fichier de sauvegarde.');
            }

            $absolute = $disk->path($finalPath);
            chmod($absolute, 0600);
            $size = filesize($absolute);
            $hash = hash_file('sha256', $absolute);
            if (! is_int($size) || $size <= 0 || ! is_string($hash)) {
                throw new RuntimeException('Le fichier de sauvegarde généré est invalide.');
            }

            $backup = $this->backups->repo_create(new BackupFileDTO(
                type: $type,
                disk: $diskName,
                filePath: $finalPath,
                fileName: $fileName,
                sizeBytes: $size,
                sha256: $hash,
                databaseName: $this->database->process_databaseName(),
                creatorCodeUnique: $actor?->user_code_unique,
                creatorName: $actor ? trim("{$actor->last_name} {$actor->first_names}") : null,
            ));

            DatabaseBackupCreated::dispatch($backup->uuid, $type, $actor?->uuid);

            return $backup;
        } catch (Throwable $exception) {
            $disk->delete([$partialPath, $finalPath]);

            throw $exception;
        } finally {
            $disk->delete($rawPath);
        }
    }

    private function verifyIntegrity(DatabaseBackupModel $backup): void
    {
        $absolute = $this->absolutePath($backup);
        $size = filesize($absolute);
        $hash = hash_file('sha256', $absolute);

        if ($size !== $backup->size_bytes || ! is_string($hash) || ! hash_equals($backup->sha256, $hash)) {
            throw new RuntimeException('L’intégrité de cette sauvegarde ne peut pas être confirmée.');
        }

        $handle = fopen($absolute, 'rb');
        $magic = $handle ? fread($handle, 2) : false;
        if (is_resource($handle)) {
            fclose($handle);
        }
        if ($magic !== "\x1f\x8b") {
            throw new RuntimeException('Le format de cette sauvegarde est invalide.');
        }
    }

    private function deleteFileAndRecord(DatabaseBackupModel $backup, ?string $actorCodeUnique): void
    {
        $disk = $this->disk($backup->disk);
        if ($disk->exists($backup->file_path) && ! $disk->delete($backup->file_path)) {
            throw new RuntimeException('Impossible de supprimer le fichier de sauvegarde.');
        }

        $this->backups->repo_markDeleted($backup, $actorCodeUnique);
    }

    /** @return array{string, string} */
    private function storageConfiguration(): array
    {
        $disk = (string) config('risfm.backups.disk', 'local');
        $directory = trim((string) config('risfm.backups.directory', 'backups/database'), '/');

        if (config("filesystems.disks.{$disk}.driver") !== 'local') {
            throw new RuntimeException('Le stockage des sauvegardes doit utiliser un disque local privé.');
        }
        if ($directory === '' || str_contains($directory, '..') || preg_match('/[\x00-\x1F]/', $directory)) {
            throw new RuntimeException('Le répertoire de sauvegarde configuré est invalide.');
        }

        return [$disk, $directory];
    }

    private function disk(string $name): Filesystem
    {
        if (config("filesystems.disks.{$name}.driver") !== 'local') {
            throw new RuntimeException('Le disque de cette sauvegarde n’est pas un stockage local autorisé.');
        }

        return Storage::disk($name);
    }

    private function absolutePath(DatabaseBackupModel $backup): string
    {
        $disk = $this->disk($backup->disk);
        if (! $disk->exists($backup->file_path)) {
            throw new RuntimeException('Le fichier de sauvegarde est introuvable.');
        }

        return $disk->path($backup->file_path);
    }

    private function compress(string $source, string $destination): void
    {
        $input = fopen($source, 'rb');
        $output = gzopen($destination, 'wb9');
        if ($input === false || $output === false) {
            is_resource($input) && fclose($input);
            is_resource($output) && gzclose($output);

            throw new RuntimeException('Impossible de compresser la sauvegarde.');
        }

        try {
            while (! feof($input)) {
                $chunk = fread($input, 1024 * 1024);
                if ($chunk === false || gzwrite($output, $chunk) === false) {
                    throw new RuntimeException('La compression de la sauvegarde a échoué.');
                }
            }
        } finally {
            fclose($input);
            gzclose($output);
        }
    }

    private function decompress(string $source, string $destination): void
    {
        $input = gzopen($source, 'rb');
        $output = fopen($destination, 'wb');
        if ($input === false || $output === false) {
            is_resource($input) && gzclose($input);
            is_resource($output) && fclose($output);

            throw new RuntimeException('Impossible de décompresser la sauvegarde.');
        }

        try {
            while (! gzeof($input)) {
                $chunk = gzread($input, 1024 * 1024);
                if ($chunk === false || fwrite($output, $chunk) === false) {
                    throw new RuntimeException('La décompression de la sauvegarde a échoué.');
                }
            }
        } finally {
            gzclose($input);
            fclose($output);
        }
        chmod($destination, 0600);
    }

    private function restoreSafetyBackup(DatabaseBackupModel $safetyBackup): void
    {
        $temporarySql = $this->temporaryPath('recovery-', '.sql');

        try {
            $this->verifyIntegrity($safetyBackup);
            $this->decompress($this->absolutePath($safetyBackup), $temporarySql);
            $this->assertSqlDump($temporarySql);
            $this->database->process_restore($temporarySql);
        } finally {
            if (is_file($temporarySql)) {
                @unlink($temporarySql);
            }
        }
    }

    private function assertSqlDump(string $path): void
    {
        $handle = fopen($path, 'rb');
        $header = $handle ? fread($handle, 1024 * 1024) : false;
        if (is_resource($handle)) {
            fclose($handle);
        }

        if (! is_string($header) || (! str_contains($header, 'SQL')
            && ! str_contains($header, 'MySQL')
            && ! str_contains($header, 'MariaDB'))) {
            throw new RuntimeException('Le contenu de la sauvegarde ne ressemble pas à un export SQL valide.');
        }
    }

    /** @return resource */
    private function acquireDatabaseOperationLock()
    {
        $path = storage_path('framework/risfm-database-operation.lock');
        $handle = fopen($path, 'c+');
        if ($handle === false || ! flock($handle, LOCK_EX | LOCK_NB)) {
            is_resource($handle) && fclose($handle);

            throw new RuntimeException('Une autre sauvegarde ou restauration de la base est déjà en cours.');
        }

        return $handle;
    }

    /** @param resource $handle */
    private function releaseDatabaseOperationLock($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    private function temporaryPath(string $prefix, string $suffix): string
    {
        [$diskName, $directory] = $this->storageConfiguration();
        $disk = $this->disk($diskName);
        $disk->makeDirectory("{$directory}/.tmp");

        return $disk->path("{$directory}/.tmp/{$prefix}".Str::uuid().$suffix);
    }
}
