<?php

namespace App\Listeners\Audit;

use App\Enums\Audit\AuditAction;
use App\Events\Administration\ProfileUpdated;
use App\Events\Administration\RolePermissionsUpdated;
use App\Events\Administration\UserCreated;
use App\Events\Administration\UserPasswordReset;
use App\Events\Administration\UserStatusChanged;
use App\Events\Administration\UserUpdated;
use App\Events\Analytics\AnalyticsExported;
use App\Events\Authentication\AuthenticationFailed;
use App\Events\Authentication\UserPasswordChanged;
use App\Events\Backup\DatabaseBackupCreated;
use App\Events\Backup\DatabaseBackupDeleted;
use App\Events\Backup\DatabaseBackupDownloaded;
use App\Events\Backup\DatabaseBackupRestored;
use App\Events\Backup\DatabaseBackupRestoreFailed;
use App\Events\Mission\FoundReportConfirmed;
use App\Events\Mission\FoundReportRejected;
use App\Events\Mission\MissionReminderSent;
use App\Events\Mission\SearchMissionAssigned;
use App\Events\Mission\SearchMissionCancelled;
use App\Events\Mission\SearchMissionCompleted;
use App\Events\Mission\SearchMissionReassigned;
use App\Events\Mission\SearchMissionStarted;
use App\Events\Organization\DirectorateCreated;
use App\Events\Organization\DirectorateDeleted;
use App\Events\Organization\DirectorateRestored;
use App\Events\Organization\DirectorateStatusChanged;
use App\Events\Organization\DirectorateUpdated;
use App\Events\Organization\ServiceCreated;
use App\Events\Organization\ServiceDeleted;
use App\Events\Organization\ServiceRestored;
use App\Events\Organization\ServiceStatusChanged;
use App\Events\Organization\ServiceUpdated;
use App\Events\Registry\FormAttachmentAdded;
use App\Events\Registry\FormAttachmentDeleted;
use App\Events\Registry\FormAttachmentDownloaded;
use App\Events\Registry\FormFinalizationStepConfirmed;
use App\Events\Registry\MissingFormArchived;
use App\Events\Registry\MissingFormCreated;
use App\Events\Registry\MissingFormMetadataUpdated;
use App\Events\Registry\MissingFormRegisteredAsFound;
use App\Events\Registry\MissingFormReopened;
use App\Events\Registry\MissingFormRestored;
use App\Events\Registry\MissingFormsImported;
use App\Events\Registry\ReferenceItemCreated;
use App\Events\Registry\ReferenceItemDeleted;
use App\Events\Registry\ReferenceItemRestored;
use App\Events\Registry\ReferenceItemStatusChanged;
use App\Events\Registry\ReferenceItemUpdated;
use App\Models\Administration\UserModel;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AuditEventSubscriber
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function onLogin(Login $event): void
    {
        if (! $event->user instanceof UserModel) {
            return;
        }

        $this->recordSafely(
            AuditAction::AuthLoginSucceeded,
            'Connexion réussie à l’application.',
            actor: $event->user,
            subjectType: 'user',
            subjectUuid: $event->user->uuid,
            metadata: ['remember' => $event->remember],
        );
    }

    public function onLogout(Logout $event): void
    {
        if (! $event->user instanceof UserModel) {
            return;
        }

        $this->recordSafely(
            AuditAction::AuthLogout,
            'Déconnexion de l’application.',
            actor: $event->user,
            subjectType: 'user',
            subjectUuid: $event->user->uuid,
        );
    }

    public function onAuthenticationFailed(AuthenticationFailed $event): void
    {
        $this->recordSafely(
            AuditAction::AuthLoginFailed,
            'Tentative de connexion échouée.',
            metadata: ['login_fingerprint' => $event->loginFingerprint],
        );
    }

    public function onPasswordChanged(UserPasswordChanged $event): void
    {
        $this->recordSafely(
            AuditAction::PasswordChanged,
            'Le mot de passe du compte a été modifié.',
            actorUuid: $event->source === 'self_change' ? $event->userUuid : null,
            subjectType: 'user',
            subjectUuid: $event->userUuid,
            metadata: ['source' => $event->source],
        );
    }

    public function onBusinessEvent(object $event): void
    {
        [$action, $description, $actorUuid, $subjectType, $subjectUuid, $metadata] = match ($event::class) {
            UserCreated::class => [
                AuditAction::UserCreated, 'Un compte utilisateur a été créé.', $event->actorUuid,
                'user', $event->userUuid, [],
            ],
            UserUpdated::class => [
                AuditAction::UserUpdated, 'Un compte utilisateur a été modifié.', $event->actorUuid,
                'user', $event->userUuid, [],
            ],
            UserStatusChanged::class => [
                AuditAction::UserStatusChanged, 'Le statut d’un compte utilisateur a été modifié.', $event->actorUuid,
                'user', $event->userUuid, ['is_active' => $event->isActive],
            ],
            UserPasswordReset::class => [
                AuditAction::UserPasswordReset, 'Le mot de passe d’un compte utilisateur a été réinitialisé.', $event->actorUuid,
                'user', $event->userUuid, [],
            ],
            ProfileUpdated::class => [
                AuditAction::ProfileUpdated, 'Le profil personnel du compte a été modifié.', $event->actorUuid,
                'user', $event->userUuid, ['changed_fields' => $event->changedFields],
            ],
            RolePermissionsUpdated::class => [
                AuditAction::RolePermissionsUpdated, 'Les permissions d’un rôle ont été modifiées.', $event->actorUuid,
                'role', $event->roleUuid,
                [
                    'added_permissions' => $event->addedPermissions,
                    'removed_permissions' => $event->removedPermissions,
                ],
            ],
            DirectorateCreated::class => [
                AuditAction::DirectorateCreated, 'Une direction a été créée.', $event->actorUuid,
                'directorate', $event->directorateUuid, [],
            ],
            DirectorateUpdated::class => [
                AuditAction::DirectorateUpdated, 'Une direction a été modifiée.', $event->actorUuid,
                'directorate', $event->directorateUuid, [],
            ],
            DirectorateStatusChanged::class => [
                AuditAction::DirectorateStatusChanged, 'Le statut d’une direction a été modifié.', $event->actorUuid,
                'directorate', $event->directorateUuid, ['is_active' => $event->isActive],
            ],
            DirectorateDeleted::class => [
                AuditAction::DirectorateDeleted, 'Une direction a été supprimée.', $event->actorUuid,
                'directorate', $event->directorateUuid, [],
            ],
            DirectorateRestored::class => [
                AuditAction::DirectorateRestored, 'Une direction a été restaurée.', $event->actorUuid,
                'directorate', $event->directorateUuid, [],
            ],
            ServiceCreated::class => [
                AuditAction::ServiceCreated, 'Un service a été créé.', $event->actorUuid,
                'service', $event->serviceUuid, [],
            ],
            ServiceUpdated::class => [
                AuditAction::ServiceUpdated, 'Un service a été modifié.', $event->actorUuid,
                'service', $event->serviceUuid, [],
            ],
            ServiceStatusChanged::class => [
                AuditAction::ServiceStatusChanged, 'Le statut d’un service a été modifié.', $event->actorUuid,
                'service', $event->serviceUuid, ['is_active' => $event->isActive],
            ],
            ServiceDeleted::class => [
                AuditAction::ServiceDeleted, 'Un service a été supprimé.', $event->actorUuid,
                'service', $event->serviceUuid, [],
            ],
            ServiceRestored::class => [
                AuditAction::ServiceRestored, 'Un service a été restauré.', $event->actorUuid,
                'service', $event->serviceUuid, [],
            ],
            ReferenceItemCreated::class => [
                AuditAction::ReferenceCreated, 'Une référence métier a été créée.', $event->actorUuid,
                $event->referenceType, $event->referenceUuid, ['reference_type' => $event->referenceType],
            ],
            ReferenceItemUpdated::class => [
                AuditAction::ReferenceUpdated, 'Une référence métier a été modifiée.', $event->actorUuid,
                $event->referenceType, $event->referenceUuid, ['reference_type' => $event->referenceType],
            ],
            ReferenceItemStatusChanged::class => [
                AuditAction::ReferenceStatusChanged, 'Le statut d’une référence métier a été modifié.', $event->actorUuid,
                $event->referenceType, $event->referenceUuid,
                ['reference_type' => $event->referenceType, 'is_active' => $event->isActive],
            ],
            ReferenceItemDeleted::class => [
                AuditAction::ReferenceDeleted, 'Une référence métier a été supprimée.', $event->actorUuid,
                $event->referenceType, $event->referenceUuid, ['reference_type' => $event->referenceType],
            ],
            ReferenceItemRestored::class => [
                AuditAction::ReferenceRestored, 'Une référence métier a été restaurée.', $event->actorUuid,
                $event->referenceType, $event->referenceUuid, ['reference_type' => $event->referenceType],
            ],
            MissingFormCreated::class => [
                AuditAction::FormCreated, 'Un formulaire manquant a été ajouté au registre.', $event->actorUuid,
                'missing_form', $event->missingFormUuid, [],
            ],
            MissingFormsImported::class => [
                AuditAction::FormsImported, 'Des formulaires manquants ont été importés dans le registre.', $event->actorUuid,
                'missing_form_import', null, ['imported_count' => $event->importedCount],
            ],
            MissingFormRegisteredAsFound::class => [
                AuditAction::FormRegisteredAsFound, 'Un formulaire déjà retrouvé a été enregistré.', $event->actorUuid,
                'missing_form', $event->missingFormUuid,
                ['report_uuid' => $event->reportUuid, 'finder_type' => $event->finderType],
            ],
            MissingFormMetadataUpdated::class => [
                AuditAction::FormUpdated, 'Les informations d’un formulaire ont été modifiées.', $event->actorUuid,
                'missing_form', $event->missingFormUuid, [],
            ],
            MissingFormArchived::class => [
                AuditAction::FormArchived, 'Un formulaire a été archivé.', $event->actorUuid,
                'missing_form', $event->missingFormUuid, ['reason' => $event->reason],
            ],
            MissingFormRestored::class => [
                AuditAction::FormRestored, 'Un formulaire archivé a été restauré.', $event->actorUuid,
                'missing_form', $event->missingFormUuid, [],
            ],
            MissingFormReopened::class => [
                AuditAction::FormReopened, 'Un formulaire finalisé a été rouvert.', $event->actorUuid,
                'missing_form', $event->missingFormUuid,
                ['reopening_uuid' => $event->reopeningUuid, 'tracking_cycle' => $event->trackingCycle],
            ],
            FormFinalizationStepConfirmed::class => [
                AuditAction::FormFinalizationConfirmed, 'Une étape de finalisation a été confirmée.', $event->actorUuid,
                'missing_form', $event->missingFormUuid,
                ['finalization_uuid' => $event->finalizationUuid, 'step' => $event->step],
            ],
            FormAttachmentAdded::class => [
                AuditAction::AttachmentAdded, 'Une pièce jointe a été ajoutée au dossier.', $event->actorUuid,
                'form_attachment', $event->attachmentUuid, ['missing_form_uuid' => $event->missingFormUuid],
            ],
            FormAttachmentDownloaded::class => [
                AuditAction::AttachmentDownloaded, 'Une pièce jointe du dossier a été téléchargée.', $event->actorUuid,
                'form_attachment', $event->attachmentUuid, ['missing_form_uuid' => $event->missingFormUuid],
            ],
            FormAttachmentDeleted::class => [
                AuditAction::AttachmentDeleted, 'Une pièce jointe a été supprimée du dossier.', $event->actorUuid,
                'form_attachment', $event->attachmentUuid, ['missing_form_uuid' => $event->missingFormUuid],
            ],
            SearchMissionAssigned::class => [
                AuditAction::MissionAssigned, 'Une mission de recherche a été affectée.', $event->actorUuid,
                'mission', $event->missionUuid,
                ['missing_form_uuid' => $event->missingFormUuid, 'responsible_user_uuid' => $event->responsibleUserUuid],
            ],
            SearchMissionStarted::class => [
                AuditAction::MissionStarted, 'Une mission de recherche a été démarrée.', $event->actorUuid,
                'mission', $event->missionUuid, [],
            ],
            SearchMissionCompleted::class => [
                AuditAction::MissionCompleted, 'Le résultat d’une mission de recherche a été enregistré.', $event->actorUuid,
                'mission', $event->missionUuid,
                ['missing_form_uuid' => $event->missingFormUuid, 'result' => $event->result],
            ],
            SearchMissionCancelled::class => [
                AuditAction::MissionCancelled, 'Une mission de recherche a été annulée.', $event->actorUuid,
                'mission', $event->missionUuid,
                ['missing_form_uuid' => $event->missingFormUuid, 'reason' => $event->reason],
            ],
            SearchMissionReassigned::class => [
                AuditAction::MissionReassigned, 'Une mission de recherche a été réaffectée.', $event->actorUuid,
                'mission', $event->newMissionUuid,
                [
                    'previous_mission_uuid' => $event->previousMissionUuid,
                    'missing_form_uuid' => $event->missingFormUuid,
                    'responsible_user_uuid' => $event->responsibleUserUuid,
                ],
            ],
            FoundReportConfirmed::class => [
                AuditAction::FoundReportConfirmed, 'Un signalement de formulaire retrouvé a été confirmé.', $event->actorUuid,
                'mission', $event->missionUuid,
                ['review_uuid' => $event->reviewUuid, 'missing_form_uuid' => $event->missingFormUuid],
            ],
            FoundReportRejected::class => [
                AuditAction::FoundReportRejected, 'Un signalement de formulaire retrouvé a été rejeté.', $event->actorUuid,
                'mission', $event->missionUuid,
                [
                    'review_uuid' => $event->reviewUuid,
                    'missing_form_uuid' => $event->missingFormUuid,
                    'reason' => $event->reason,
                ],
            ],
            MissionReminderSent::class => [
                AuditAction::MissionReminderSent, 'Une relance automatique de mission a été envoyée.', null,
                'mission', $event->missionUuid,
                ['reminder_uuid' => $event->reminderUuid, 'recipient_uuid' => $event->recipientUuid, 'type' => $event->type],
            ],
            AnalyticsExported::class => [
                AuditAction::AnalyticsExported, 'Les statistiques ont été exportées.', $event->actorUuid,
                'analytics_report', null, ['format' => $event->format, 'filters' => $event->filters],
            ],
            DatabaseBackupCreated::class => [
                AuditAction::BackupCreated, 'Une sauvegarde de la base de données a été créée.', $event->actorUuid,
                'database_backup', $event->backupUuid, ['type' => $event->type],
            ],
            DatabaseBackupDownloaded::class => [
                AuditAction::BackupDownloaded, 'Une sauvegarde de la base de données a été téléchargée.', $event->actorUuid,
                'database_backup', $event->backupUuid, [],
            ],
            DatabaseBackupDeleted::class => [
                AuditAction::BackupDeleted, 'Une sauvegarde de la base de données a été supprimée.', $event->actorUuid,
                'database_backup', $event->backupUuid, ['automatic' => $event->automatic],
            ],
            DatabaseBackupRestored::class => [
                AuditAction::BackupRestored, 'La base de données a été restaurée depuis une sauvegarde.', $event->actorUuid,
                'database_backup', $event->backupUuid, ['safety_backup_uuid' => $event->safetyBackupUuid],
            ],
            DatabaseBackupRestoreFailed::class => [
                AuditAction::BackupRestoreFailed, 'Une tentative de restauration de la base de données a échoué.', $event->actorUuid,
                'database_backup', $event->backupUuid, ['failure_type' => $event->failureType],
            ],
        };

        $this->recordSafely(
            $action,
            $description,
            $actorUuid,
            $subjectType,
            $subjectUuid,
            $metadata,
        );
    }

    /** @return array<class-string, string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'onLogin',
            Logout::class => 'onLogout',
            AuthenticationFailed::class => 'onAuthenticationFailed',
            UserPasswordChanged::class => 'onPasswordChanged',
            UserCreated::class => 'onBusinessEvent',
            UserUpdated::class => 'onBusinessEvent',
            UserStatusChanged::class => 'onBusinessEvent',
            UserPasswordReset::class => 'onBusinessEvent',
            ProfileUpdated::class => 'onBusinessEvent',
            RolePermissionsUpdated::class => 'onBusinessEvent',
            DirectorateCreated::class => 'onBusinessEvent',
            DirectorateUpdated::class => 'onBusinessEvent',
            DirectorateStatusChanged::class => 'onBusinessEvent',
            DirectorateDeleted::class => 'onBusinessEvent',
            DirectorateRestored::class => 'onBusinessEvent',
            ServiceCreated::class => 'onBusinessEvent',
            ServiceUpdated::class => 'onBusinessEvent',
            ServiceStatusChanged::class => 'onBusinessEvent',
            ServiceDeleted::class => 'onBusinessEvent',
            ServiceRestored::class => 'onBusinessEvent',
            ReferenceItemCreated::class => 'onBusinessEvent',
            ReferenceItemUpdated::class => 'onBusinessEvent',
            ReferenceItemStatusChanged::class => 'onBusinessEvent',
            ReferenceItemDeleted::class => 'onBusinessEvent',
            ReferenceItemRestored::class => 'onBusinessEvent',
            MissingFormCreated::class => 'onBusinessEvent',
            MissingFormsImported::class => 'onBusinessEvent',
            MissingFormRegisteredAsFound::class => 'onBusinessEvent',
            MissingFormMetadataUpdated::class => 'onBusinessEvent',
            MissingFormArchived::class => 'onBusinessEvent',
            MissingFormRestored::class => 'onBusinessEvent',
            MissingFormReopened::class => 'onBusinessEvent',
            FormFinalizationStepConfirmed::class => 'onBusinessEvent',
            FormAttachmentAdded::class => 'onBusinessEvent',
            FormAttachmentDownloaded::class => 'onBusinessEvent',
            FormAttachmentDeleted::class => 'onBusinessEvent',
            SearchMissionAssigned::class => 'onBusinessEvent',
            SearchMissionStarted::class => 'onBusinessEvent',
            SearchMissionCompleted::class => 'onBusinessEvent',
            SearchMissionCancelled::class => 'onBusinessEvent',
            SearchMissionReassigned::class => 'onBusinessEvent',
            FoundReportConfirmed::class => 'onBusinessEvent',
            FoundReportRejected::class => 'onBusinessEvent',
            MissionReminderSent::class => 'onBusinessEvent',
            AnalyticsExported::class => 'onBusinessEvent',
            DatabaseBackupCreated::class => 'onBusinessEvent',
            DatabaseBackupDownloaded::class => 'onBusinessEvent',
            DatabaseBackupDeleted::class => 'onBusinessEvent',
            DatabaseBackupRestored::class => 'onBusinessEvent',
            DatabaseBackupRestoreFailed::class => 'onBusinessEvent',
        ];
    }

    /** @param array<string, mixed> $metadata */
    private function recordSafely(
        AuditAction $action,
        string $description,
        ?string $actorUuid = null,
        ?string $subjectType = null,
        ?string $subjectUuid = null,
        array $metadata = [],
        ?UserModel $actor = null,
    ): void {
        try {
            $this->audit->srv_record(
                $action,
                $description,
                $actorUuid,
                $subjectType,
                $subjectUuid,
                $metadata,
                $actor,
            );
        } catch (Throwable $exception) {
            Log::error('Impossible d’enregistrer une entrée du journal d’audit.', [
                'action_code' => $action->value,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
