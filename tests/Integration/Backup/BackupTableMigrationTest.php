<?php

namespace Tests\Integration\Backup;

use App\Enums\Backup\BackupType;
use App\Models\Backup\DatabaseBackupModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class BackupTableMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--no-interaction' => true])->assertExitCode(0);
    }

    public function test_rename_and_rollback_preserve_backup_records_and_identifiers(): void
    {
        $backup = DatabaseBackupModel::query()->create([
            'backup_type' => BackupType::Manual,
            'disk' => 'local',
            'file_path' => 'backups/database/example.sql.gz',
            'file_name' => 'example.sql.gz',
            'size_bytes' => 125,
            'sha256' => str_repeat('a', 64),
            'database_name' => 'test_database',
        ]);
        $backup->delete();
        $before = (array) DB::table('bkp_backup_models')->sole();
        $migration = require database_path('migrations/2026_10_13_200000_rename_database_backup_models_table.php');

        $migration->down();
        $this->assertTrue(Schema::hasTable('sys_database_backup_models'));
        $this->assertFalse(Schema::hasTable('bkp_backup_models'));
        $this->assertSame($before, (array) DB::table('sys_database_backup_models')->sole());

        $migration->up();
        $this->assertFalse(Schema::hasTable('sys_database_backup_models'));
        $this->assertSame($before, (array) DB::table('bkp_backup_models')->sole());
        $this->assertSame($backup->uuid, DatabaseBackupModel::withTrashed()->sole()->uuid);
    }

    public function test_rename_refuses_to_overwrite_an_existing_target_table(): void
    {
        $migration = require database_path('migrations/2026_10_13_200000_rename_database_backup_models_table.php');
        $migration->down();
        Schema::create('bkp_backup_models', function (Blueprint $table): void {
            $table->id();
        });

        try {
            $migration->up();
            $this->fail('Le conflit entre les deux tables doit bloquer le renommage.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('préserver les données', $exception->getMessage());
            $this->assertTrue(Schema::hasTable('sys_database_backup_models'));
            $this->assertTrue(Schema::hasTable('bkp_backup_models'));
        } finally {
            Schema::drop('bkp_backup_models');
            $migration->up();
        }
    }
}
