<?php

namespace App\Providers;

use App\Contracts\Backup\ApplicationMaintenanceInterface;
use App\Contracts\Backup\DatabaseProcessInterface;
use App\Repositories\Administration\AuthorizationRepository;
use App\Repositories\Administration\RolePermissionRepository;
use App\Repositories\Administration\UserNotificationRepository;
use App\Repositories\Administration\UserRepository;
use App\Repositories\Analytics\AnalyticsRepository;
use App\Repositories\Audit\AuditRepository;
use App\Repositories\Backup\DatabaseBackupRepository;
use App\Repositories\Interfaces\Administration\AuthorizationRepositoryInterface;
use App\Repositories\Interfaces\Administration\RolePermissionRepositoryInterface;
use App\Repositories\Interfaces\Administration\UserNotificationRepositoryInterface;
use App\Repositories\Interfaces\Administration\UserRepositoryInterface;
use App\Repositories\Interfaces\Analytics\AnalyticsRepositoryInterface;
use App\Repositories\Interfaces\Audit\AuditRepositoryInterface;
use App\Repositories\Interfaces\Backup\DatabaseBackupRepositoryInterface;
use App\Repositories\Interfaces\Mission\FoundReportReviewRepositoryInterface;
use App\Repositories\Interfaces\Mission\MissionReminderRepositoryInterface;
use App\Repositories\Interfaces\Mission\SearchMissionRepositoryInterface;
use App\Repositories\Interfaces\Organization\OrganizationDirectoryRepositoryInterface;
use App\Repositories\Interfaces\Registry\DirectFoundReportRepositoryInterface;
use App\Repositories\Interfaces\Registry\FormAttachmentRepositoryInterface;
use App\Repositories\Interfaces\Registry\FormFinalizationRepositoryInterface;
use App\Repositories\Interfaces\Registry\FormReopeningRepositoryInterface;
use App\Repositories\Interfaces\Registry\MissingFormImportRepositoryInterface;
use App\Repositories\Interfaces\Registry\MissingFormRepositoryInterface;
use App\Repositories\Interfaces\Registry\RegistryReferenceRepositoryInterface;
use App\Repositories\Mission\FoundReportReviewRepository;
use App\Repositories\Mission\MissionReminderRepository;
use App\Repositories\Mission\SearchMissionRepository;
use App\Repositories\Organization\OrganizationDirectoryRepository;
use App\Repositories\Registry\DirectFoundReportRepository;
use App\Repositories\Registry\FormAttachmentRepository;
use App\Repositories\Registry\FormFinalizationRepository;
use App\Repositories\Registry\FormReopeningRepository;
use App\Repositories\Registry\MissingFormImportRepository;
use App\Repositories\Registry\MissingFormRepository;
use App\Repositories\Registry\RegistryReferenceRepository;
use App\Services\Backup\ArtisanApplicationMaintenance;
use App\Services\Backup\MySqlDatabaseProcess;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class,
        );
        $this->app->bind(
            UserNotificationRepositoryInterface::class,
            UserNotificationRepository::class,
        );
        $this->app->bind(
            AnalyticsRepositoryInterface::class,
            AnalyticsRepository::class,
        );
        $this->app->bind(
            AuditRepositoryInterface::class,
            AuditRepository::class,
        );
        $this->app->bind(
            DatabaseBackupRepositoryInterface::class,
            DatabaseBackupRepository::class,
        );
        $this->app->bind(
            DatabaseProcessInterface::class,
            MySqlDatabaseProcess::class,
        );
        $this->app->bind(
            ApplicationMaintenanceInterface::class,
            ArtisanApplicationMaintenance::class,
        );
        $this->app->bind(
            AuthorizationRepositoryInterface::class,
            AuthorizationRepository::class,
        );
        $this->app->bind(
            RolePermissionRepositoryInterface::class,
            RolePermissionRepository::class,
        );
        $this->app->bind(
            OrganizationDirectoryRepositoryInterface::class,
            OrganizationDirectoryRepository::class,
        );
        $this->app->bind(
            RegistryReferenceRepositoryInterface::class,
            RegistryReferenceRepository::class,
        );
        $this->app->bind(
            MissingFormRepositoryInterface::class,
            MissingFormRepository::class,
        );
        $this->app->bind(
            MissingFormImportRepositoryInterface::class,
            MissingFormImportRepository::class,
        );
        $this->app->bind(
            DirectFoundReportRepositoryInterface::class,
            DirectFoundReportRepository::class,
        );
        $this->app->bind(
            FormFinalizationRepositoryInterface::class,
            FormFinalizationRepository::class,
        );
        $this->app->bind(
            FormAttachmentRepositoryInterface::class,
            FormAttachmentRepository::class,
        );
        $this->app->bind(
            FormReopeningRepositoryInterface::class,
            FormReopeningRepository::class,
        );
        $this->app->bind(
            SearchMissionRepositoryInterface::class,
            SearchMissionRepository::class,
        );
        $this->app->bind(
            MissionReminderRepositoryInterface::class,
            MissionReminderRepository::class,
        );
        $this->app->bind(
            FoundReportReviewRepositoryInterface::class,
            FoundReportReviewRepository::class,
        );
    }
}
