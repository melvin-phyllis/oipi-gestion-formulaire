<?php

namespace App\Contracts\Backup;

interface DatabaseProcessInterface
{
    public function process_dump(string $destinationSqlPath): void;

    public function process_restore(string $sourceSqlPath): void;

    public function process_databaseName(): string;
}
