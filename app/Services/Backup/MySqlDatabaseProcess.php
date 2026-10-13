<?php

namespace App\Services\Backup;

use App\Contracts\Backup\DatabaseProcessInterface;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class MySqlDatabaseProcess implements DatabaseProcessInterface
{
    /** @var array<string, mixed> */
    private array $connection;

    public function __construct()
    {
        $connectionName = (string) config('database.default');
        $this->connection = (array) config("database.connections.{$connectionName}", []);
        $driver = (string) ($this->connection['driver'] ?? '');

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Les sauvegardes RISFM exigent une connexion MySQL ou MariaDB.');
        }
    }

    public function process_dump(string $destinationSqlPath): void
    {
        if (is_file($destinationSqlPath) && ! unlink($destinationSqlPath)) {
            throw new RuntimeException('Impossible de préparer le fichier temporaire de sauvegarde.');
        }

        $defaultsFile = $this->createDefaultsFile();

        try {
            $command = [
                (string) config('risfm.backups.mysqldump_binary', 'mysqldump'),
                "--defaults-extra-file={$defaultsFile}",
                '--single-transaction',
                '--quick',
                '--skip-lock-tables',
                '--no-tablespaces',
                '--routines',
                '--triggers',
                '--events',
                '--hex-blob',
                '--default-character-set=utf8mb4',
                "--result-file={$destinationSqlPath}",
                $this->process_databaseName(),
            ];

            $this->run(new Process($command));

            if (! is_file($destinationSqlPath) || filesize($destinationSqlPath) === 0) {
                throw new RuntimeException('La commande de sauvegarde n’a produit aucun fichier exploitable.');
            }
        } finally {
            $this->secureDelete($defaultsFile);
        }
    }

    public function process_restore(string $sourceSqlPath): void
    {
        if (! is_file($sourceSqlPath) || filesize($sourceSqlPath) === 0) {
            throw new RuntimeException('Le fichier SQL à restaurer est introuvable ou vide.');
        }

        $defaultsFile = $this->createDefaultsFile();
        $input = fopen($sourceSqlPath, 'rb');
        if ($input === false) {
            $this->secureDelete($defaultsFile);

            throw new RuntimeException('Impossible de lire le fichier SQL à restaurer.');
        }

        try {
            $process = new Process([
                (string) config('risfm.backups.mysql_binary', 'mysql'),
                "--defaults-extra-file={$defaultsFile}",
                '--default-character-set=utf8mb4',
                $this->process_databaseName(),
            ]);
            $process->setInput($input);
            $this->run($process);
        } finally {
            fclose($input);
            $this->secureDelete($defaultsFile);
        }
    }

    public function process_databaseName(): string
    {
        $database = (string) ($this->connection['database'] ?? '');
        if ($database === '' || str_contains($database, "\0")) {
            throw new RuntimeException('Le nom de la base de données est invalide.');
        }

        return $database;
    }

    private function run(Process $process): void
    {
        $process->setTimeout(max(60, (int) config('risfm.backups.process_timeout', 900)));
        $process->run();

        if (! $process->isSuccessful()) {
            $error = $process->getErrorOutput();
            if (str_contains($error, 'references invalid table(s)')
                && preg_match("/View '[^.]+\\.([^']+)'/", $error, $matches) === 1) {
                throw new RuntimeException(
                    "La vue SQL [{$matches[1]}] est invalide. Exécutez les migrations avant de relancer la sauvegarde.",
                );
            }

            throw new ProcessFailedException($process);
        }
    }

    private function createDefaultsFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'risfm-db-');
        if ($path === false) {
            throw new RuntimeException('Impossible de créer le fichier temporaire de connexion.');
        }

        $lines = [
            '[client]',
            'host='.$this->optionValue((string) ($this->connection['host'] ?? '127.0.0.1')),
            'port='.$this->optionValue((string) ($this->connection['port'] ?? '3306')),
            'user='.$this->optionValue((string) ($this->connection['username'] ?? '')),
            'password='.$this->optionValue((string) ($this->connection['password'] ?? '')),
        ];

        $socket = (string) ($this->connection['unix_socket'] ?? '');
        if ($socket !== '') {
            $lines[] = 'socket='.$this->optionValue($socket);
        }

        if (file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL, LOCK_EX) === false) {
            $this->secureDelete($path);

            throw new RuntimeException('Impossible d’écrire le fichier temporaire de connexion.');
        }
        chmod($path, 0600);

        return $path;
    }

    private function optionValue(string $value): string
    {
        return '"'.strtr($value, [
            '\\' => '\\\\',
            '"' => '\\"',
            "\n" => '\\n',
            "\r" => '\\r',
        ]).'"';
    }

    private function secureDelete(string $path): void
    {
        if (! is_file($path)) {
            return;
        }

        $size = filesize($path);
        if (is_int($size) && $size > 0) {
            file_put_contents($path, str_repeat("\0", min($size, 4096)), LOCK_EX);
        }
        @unlink($path);
    }
}
