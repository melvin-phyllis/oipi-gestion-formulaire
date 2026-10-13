<?php

namespace App\Enums\Audit;

enum AuditAction: string
{
    case AuthLoginSucceeded = 'auth_login_succeeded';
    case AuthLoginFailed = 'auth_login_failed';
    case AuthLogout = 'auth_logout';
    case PasswordChanged = 'password_changed';
    case UserCreated = 'user_created';
    case UserUpdated = 'user_updated';
    case UserStatusChanged = 'user_status_changed';
    case UserPasswordReset = 'user_password_reset';
    case RolePermissionsUpdated = 'role_permissions_updated';
    case ProfileUpdated = 'profile_updated';
    case DirectorateCreated = 'directorate_created';
    case DirectorateUpdated = 'directorate_updated';
    case DirectorateStatusChanged = 'directorate_status_changed';
    case DirectorateDeleted = 'directorate_deleted';
    case DirectorateRestored = 'directorate_restored';
    case ServiceCreated = 'service_created';
    case ServiceUpdated = 'service_updated';
    case ServiceStatusChanged = 'service_status_changed';
    case ServiceDeleted = 'service_deleted';
    case ServiceRestored = 'service_restored';
    case ReferenceCreated = 'reference_created';
    case ReferenceUpdated = 'reference_updated';
    case ReferenceStatusChanged = 'reference_status_changed';
    case ReferenceDeleted = 'reference_deleted';
    case ReferenceRestored = 'reference_restored';
    case FormCreated = 'form_created';
    case FormsImported = 'forms_imported';
    case FormRegisteredAsFound = 'form_registered_as_found';
    case FormUpdated = 'form_updated';
    case FormArchived = 'form_archived';
    case FormRestored = 'form_restored';
    case FormReopened = 'form_reopened';
    case FormFinalizationConfirmed = 'form_finalization_confirmed';
    case AttachmentAdded = 'attachment_added';
    case AttachmentDownloaded = 'attachment_downloaded';
    case AttachmentDeleted = 'attachment_deleted';
    case MissionAssigned = 'mission_assigned';
    case MissionStarted = 'mission_started';
    case MissionCompleted = 'mission_completed';
    case MissionCancelled = 'mission_cancelled';
    case MissionReassigned = 'mission_reassigned';
    case FoundReportConfirmed = 'found_report_confirmed';
    case FoundReportRejected = 'found_report_rejected';
    case MissionReminderSent = 'mission_reminder_sent';
    case AnalyticsExported = 'analytics_exported';
    case BackupCreated = 'backup_created';
    case BackupDownloaded = 'backup_downloaded';
    case BackupDeleted = 'backup_deleted';
    case BackupRestored = 'backup_restored';
    case BackupRestoreFailed = 'backup_restore_failed';

    public function label(): string
    {
        return match ($this) {
            self::AuthLoginSucceeded => 'Connexion réussie',
            self::AuthLoginFailed => 'Connexion échouée',
            self::AuthLogout => 'Déconnexion',
            self::PasswordChanged => 'Mot de passe modifié',
            self::UserCreated => 'Utilisateur créé',
            self::UserUpdated => 'Utilisateur modifié',
            self::UserStatusChanged => 'Statut utilisateur modifié',
            self::UserPasswordReset => 'Mot de passe utilisateur réinitialisé',
            self::RolePermissionsUpdated => 'Permissions du rôle modifiées',
            self::ProfileUpdated => 'Profil personnel modifié',
            self::DirectorateCreated => 'Direction créée',
            self::DirectorateUpdated => 'Direction modifiée',
            self::DirectorateStatusChanged => 'Statut direction modifié',
            self::DirectorateDeleted => 'Direction supprimée',
            self::DirectorateRestored => 'Direction restaurée',
            self::ServiceCreated => 'Service créé',
            self::ServiceUpdated => 'Service modifié',
            self::ServiceStatusChanged => 'Statut service modifié',
            self::ServiceDeleted => 'Service supprimé',
            self::ServiceRestored => 'Service restauré',
            self::ReferenceCreated => 'Référence créée',
            self::ReferenceUpdated => 'Référence modifiée',
            self::ReferenceStatusChanged => 'Statut référence modifié',
            self::ReferenceDeleted => 'Référence supprimée',
            self::ReferenceRestored => 'Référence restaurée',
            self::FormCreated => 'Formulaire créé',
            self::FormsImported => 'Formulaires importés',
            self::FormRegisteredAsFound => 'Formulaire déjà retrouvé enregistré',
            self::FormUpdated => 'Formulaire modifié',
            self::FormArchived => 'Formulaire archivé',
            self::FormRestored => 'Formulaire restauré',
            self::FormReopened => 'Formulaire rouvert',
            self::FormFinalizationConfirmed => 'Étape de finalisation confirmée',
            self::AttachmentAdded => 'Pièce jointe ajoutée',
            self::AttachmentDownloaded => 'Pièce jointe téléchargée',
            self::AttachmentDeleted => 'Pièce jointe supprimée',
            self::MissionAssigned => 'Mission affectée',
            self::MissionStarted => 'Mission démarrée',
            self::MissionCompleted => 'Mission terminée',
            self::MissionCancelled => 'Mission annulée',
            self::MissionReassigned => 'Mission réaffectée',
            self::FoundReportConfirmed => 'Signalement retrouvé confirmé',
            self::FoundReportRejected => 'Signalement retrouvé rejeté',
            self::MissionReminderSent => 'Relance de mission envoyée',
            self::AnalyticsExported => 'Statistiques exportées',
            self::BackupCreated => 'Sauvegarde créée',
            self::BackupDownloaded => 'Sauvegarde téléchargée',
            self::BackupDeleted => 'Sauvegarde supprimée',
            self::BackupRestored => 'Base restaurée',
            self::BackupRestoreFailed => 'Restauration échouée',
        };
    }

    public function category(): AuditCategory
    {
        return match ($this) {
            self::AuthLoginSucceeded,
            self::AuthLoginFailed,
            self::AuthLogout,
            self::PasswordChanged => AuditCategory::Authentication,

            self::UserCreated,
            self::UserUpdated,
            self::UserStatusChanged,
            self::UserPasswordReset,
            self::RolePermissionsUpdated,
            self::ProfileUpdated => AuditCategory::Administration,

            self::DirectorateCreated,
            self::DirectorateUpdated,
            self::DirectorateStatusChanged,
            self::DirectorateDeleted,
            self::DirectorateRestored,
            self::ServiceCreated,
            self::ServiceUpdated,
            self::ServiceStatusChanged,
            self::ServiceDeleted,
            self::ServiceRestored => AuditCategory::Organization,

            self::ReferenceCreated,
            self::ReferenceUpdated,
            self::ReferenceStatusChanged,
            self::ReferenceDeleted,
            self::ReferenceRestored,
            self::FormCreated,
            self::FormsImported,
            self::FormRegisteredAsFound,
            self::FormUpdated,
            self::FormArchived,
            self::FormRestored,
            self::FormReopened,
            self::FormFinalizationConfirmed,
            self::AttachmentAdded,
            self::AttachmentDownloaded,
            self::AttachmentDeleted => AuditCategory::Registry,

            self::MissionAssigned,
            self::MissionStarted,
            self::MissionCompleted,
            self::MissionCancelled,
            self::MissionReassigned,
            self::FoundReportConfirmed,
            self::FoundReportRejected,
            self::MissionReminderSent => AuditCategory::Mission,

            self::AnalyticsExported => AuditCategory::Analytics,

            self::BackupCreated,
            self::BackupDownloaded,
            self::BackupDeleted,
            self::BackupRestored,
            self::BackupRestoreFailed => AuditCategory::Backup,
        };
    }
}
