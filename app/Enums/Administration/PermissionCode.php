<?php

namespace App\Enums\Administration;

enum PermissionCode: string
{
    case RegistryView = 'registry_view';
    case RegistryCreate = 'registry_create';
    case RegistryRegisterFound = 'registry_register_found';
    case RegistryUpdateMetadata = 'registry_update_metadata';
    case MissionsAssign = 'missions_assign';
    case MissionsRecordAnyResult = 'missions_record_any_result';
    case MissionsRecordOwnResult = 'missions_record_own_result';
    case MissionsReviewFound = 'missions_review_found';
    case RegistryFinalize = 'registry_finalize';
    case RegistryReopen = 'registry_reopen';
    case AttachmentsAddAny = 'attachments_add_any';
    case AttachmentsAddOwn = 'attachments_add_own';
    case AttachmentsDeleteAny = 'attachments_delete_any';
    case AttachmentsDeleteOwn = 'attachments_delete_own';
    case RegistryArchive = 'registry_archive';
    case RegistryExport = 'registry_export';
    case RegistryImport = 'registry_import';
    case DashboardView = 'dashboard_view';
    case AnalyticsView = 'analytics_view';
    case RegistrySearch = 'registry_search';
    case UsersManage = 'users_manage';
    case RolesManage = 'roles_manage';
    case AuditView = 'audit_view';
    case SettingsManage = 'settings_manage';
    case BackupsManage = 'backups_manage';
    case NotificationsView = 'notifications_view';
    case ProfileUpdate = 'profile_update';
}
