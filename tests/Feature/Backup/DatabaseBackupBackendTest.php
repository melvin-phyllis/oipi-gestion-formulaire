<?php

namespace Tests\Feature\Backup;

use App\Contracts\Backup\ApplicationMaintenanceInterface;
use App\Contracts\Backup\DatabaseProcessInterface;
use App\Dto\Backup\RestoreBackupDTO;
use App\Enums\Administration\RoleCode;
use App\Enums\Audit\AuditAction;
use App\Enums\Backup\BackupType;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Backup\DatabaseBackupModel;
use App\Services\Backup\DatabaseBackupService;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

final class DatabaseBackupBackendTest extends TestCase
{
    use RefreshDatabase;

    private FakeDatabaseProcess $databaseProcess;

    private FakeApplicationMaintenance $maintenance;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config()->set('risfm.backups.disk', 'local');
        config()->set('risfm.backups.directory', 'backups/database');
        config()->set('risfm.backups.retention_days', 14);

        $this->databaseProcess = new FakeDatabaseProcess;
        $this->maintenance = new FakeApplicationMaintenance;
        $this->app->instance(DatabaseProcessInterface::class, $this->databaseProcess);
        $this->app->instance(ApplicationMaintenanceInterface::class, $this->maintenance);
    }

    public function test_backup_routes_are_registered_and_protected(): void
    {
        foreach (['backups.index', 'backups.data', 'backups.store', 'backups.download', 'backups.destroy', 'backups.restore'] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains('session.current', $route->gatherMiddleware());
            $this->assertContains('password.changed', $route->gatherMiddleware());
            $this->assertContains('permission:backups_manage', $route->gatherMiddleware());
        }
    }

    public function test_administrator_can_create_and_list_private_backup_without_internal_paths(): void
    {
        $administrator = $this->systemAdministrator();

        $this->asUser($administrator)
            ->postJson(route('backups.store'))
            ->assertCreated()
            ->assertJsonPath('data.type', BackupType::Manual->value)
            ->assertJsonMissingPath('data.disk')
            ->assertJsonMissingPath('data.file_path')
            ->assertJsonMissingPath('data.sha256')
            ->assertJsonMissingPath('data.database_name');

        $backup = DatabaseBackupModel::query()->sole();
        Storage::disk('local')->assertExists($backup->file_path);
        $this->assertSame(0600, fileperms(Storage::disk('local')->path($backup->file_path)) & 0777);
        $this->assertDatabaseHas('aud_audit_log_models', [
            'action_code' => AuditAction::BackupCreated->value,
            'actor_user_code_unique' => $administrator->user_code_unique,
        ]);

        $this->asUser($administrator)
            ->getJson(route('backups.data'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $backup->uuid)
            ->assertJsonMissingPath('data.0.disk')
            ->assertJsonMissingPath('data.0.file_path')
            ->assertJsonMissingPath('data.0.sha256');
    }

    public function test_user_without_backup_permission_is_forbidden(): void
    {
        $this->seedAuthorization();
        $reader = $this->userForRole(RoleCode::ReadOnly);

        $this->asUser($reader)->getJson(route('backups.data'))->assertForbidden();
        $this->asUser($reader)->postJson(route('backups.store'))->assertForbidden();
    }

    public function test_download_verifies_integrity_and_is_audited(): void
    {
        $administrator = $this->systemAdministrator();
        $backup = app(DatabaseBackupService::class)->srv_createManual($administrator);

        $response = $this->asUser($administrator)
            ->get(route('backups.download', $backup))
            ->assertOk()
            ->assertHeader('content-type', 'application/gzip')
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->assertStringStartsWith("\x1f\x8b", $response->streamedContent());
        $this->assertDatabaseHas('aud_audit_log_models', [
            'action_code' => AuditAction::BackupDownloaded->value,
            'actor_user_code_unique' => $administrator->user_code_unique,
        ]);
    }

    public function test_tampered_backup_cannot_be_downloaded(): void
    {
        $administrator = $this->systemAdministrator();
        $backup = app(DatabaseBackupService::class)->srv_createManual($administrator);
        Storage::disk('local')->put($backup->file_path, 'contenu falsifié');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('intégrité');

        app(DatabaseBackupService::class)->srv_download($backup, $administrator);
    }

    public function test_restore_requires_exact_phrase_and_current_password(): void
    {
        $administrator = $this->systemAdministrator();
        $backup = app(DatabaseBackupService::class)->srv_createManual($administrator);

        $this->asUser($administrator)
            ->postJson(route('backups.restore', $backup), [
                'confirmation_phrase' => 'restaurer oipi',
                'current_password' => 'password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('confirmation_phrase');

        $this->asUser($administrator)
            ->postJson(route('backups.restore', $backup), [
                'confirmation_phrase' => 'RESTAURER OIPI',
                'current_password' => 'incorrect',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertSame(0, $this->maintenance->downCount);
        $this->assertSame(0, $this->databaseProcess->restoreCount);
    }

    public function test_restore_creates_safety_backup_and_controls_maintenance_mode(): void
    {
        $administrator = $this->systemAdministrator();
        $backup = app(DatabaseBackupService::class)->srv_createManual($administrator);

        $this->asUser($administrator)
            ->postJson(route('backups.restore', $backup), [
                'confirmation_phrase' => 'RESTAURER OIPI',
                'current_password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('safety_backup.type', BackupType::PreRestore->value);

        $this->assertSame(1, $this->maintenance->downCount);
        $this->assertSame(1, $this->maintenance->upCount);
        $this->assertSame(2, $this->databaseProcess->dumpCount);
        $this->assertSame(1, $this->databaseProcess->restoreCount);
        $this->assertDatabaseCount('bkp_backup_models', 2);
        $this->assertDatabaseHas('aud_audit_log_models', [
            'action_code' => AuditAction::BackupRestored->value,
            'actor_user_code_unique' => $administrator->user_code_unique,
        ]);
    }

    public function test_failed_restore_reapplies_safety_backup_before_leaving_maintenance(): void
    {
        $administrator = $this->systemAdministrator();
        $service = app(DatabaseBackupService::class);
        $backup = $service->srv_createManual($administrator);
        $this->databaseProcess->failNextRestore = true;

        try {
            $service->srv_restore(
                $backup,
                new RestoreBackupDTO('RESTAURER OIPI', 'password'),
                $administrator,
            );
            $this->fail('La restauration principale devait échouer.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('sauvegarde de sécurité', $exception->getMessage());
        }

        $this->assertSame(2, $this->databaseProcess->restoreCount);
        $this->assertSame(1, $this->maintenance->downCount);
        $this->assertSame(1, $this->maintenance->upCount);
        $this->assertDatabaseHas('aud_audit_log_models', [
            'action_code' => AuditAction::BackupRestoreFailed->value,
        ]);
    }

    public function test_retention_deletes_only_expired_automatic_backups(): void
    {
        $administrator = $this->systemAdministrator();
        $service = app(DatabaseBackupService::class);
        $manual = $service->srv_createManual($administrator);
        $automatic = $service->srv_createScheduled();
        $oldDate = now()->subDays(20);

        DatabaseBackupModel::query()
            ->whereKey([$manual->getKey(), $automatic->getKey()])
            ->update(['created_at' => $oldDate, 'updated_at' => $oldDate]);

        $this->assertSame(1, $service->srv_prune());
        $this->assertDatabaseHas('bkp_backup_models', [
            'id' => $manual->getKey(),
            'deleted_at' => null,
        ]);
        $this->assertSoftDeleted('bkp_backup_models', [
            'id' => $automatic->getKey(),
        ]);
        Storage::disk('local')->assertExists($manual->file_path);
        Storage::disk('local')->assertMissing($automatic->file_path);
    }

    public function test_scheduled_command_creates_backup_and_runs_retention(): void
    {
        $this->artisan('risfm:backup-database')
            ->expectsOutputToContain('Sauvegarde créée')
            ->assertSuccessful();

        $this->assertDatabaseHas('bkp_backup_models', [
            'backup_type' => BackupType::Scheduled->value,
        ]);
    }

    private function asUser(UserModel $user): static
    {
        return $this->actingAs($user)
            ->withSession(['auth_session_version' => $user->session_version]);
    }

    private function systemAdministrator(): UserModel
    {
        $this->seedAuthorization();

        return $this->userForRole(RoleCode::SystemAdmin);
    }

    private function seedAuthorization(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function userForRole(RoleCode $role): UserModel
    {
        return UserModel::factory()->create([
            'role_code_unique' => RoleModel::query()
                ->where('role_code', $role->value)
                ->valueOrFail('role_code_unique'),
            'must_change_password' => false,
            'password' => 'password',
        ]);
    }
}

final class FakeDatabaseProcess implements DatabaseProcessInterface
{
    public int $dumpCount = 0;

    public int $restoreCount = 0;

    public bool $failNextRestore = false;

    public function process_dump(string $destinationSqlPath): void
    {
        $this->dumpCount++;
        file_put_contents(
            $destinationSqlPath,
            "-- MySQL SQL dump for RISFM\nCREATE TABLE example_{$this->dumpCount} (id INTEGER);\n",
        );
    }

    public function process_restore(string $sourceSqlPath): void
    {
        $this->restoreCount++;

        if ($this->failNextRestore) {
            $this->failNextRestore = false;

            throw new RuntimeException('Échec simulé de la restauration principale.');
        }

        if (! str_contains((string) file_get_contents($sourceSqlPath), 'MySQL SQL dump')) {
            throw new RuntimeException('Le SQL restauré est invalide.');
        }
    }

    public function process_databaseName(): string
    {
        return 'oipi_risfm_test';
    }
}

final class FakeApplicationMaintenance implements ApplicationMaintenanceInterface
{
    public int $downCount = 0;

    public int $upCount = 0;

    public function maintenance_down(): void
    {
        $this->downCount++;
    }

    public function maintenance_up(): void
    {
        $this->upCount++;
    }
}
