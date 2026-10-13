<?php

namespace Database\Seeders\Administration;

use App\Enums\Administration\PermissionCode;
use App\Models\Administration\PermissionModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            PermissionCode::RegistryView->value => ['registry', 'Consulter le registre', 'Consulter les formulaires manquants.'],
            PermissionCode::RegistryCreate->value => ['registry', 'Ajouter un formulaire', 'Déclarer un formulaire manquant dans le registre.'],
            PermissionCode::RegistryRegisterFound->value => ['registry', 'Enregistrer un formulaire déjà retrouvé', 'Créer directement un dossier retrouvé avec sa déclaration de découverte.'],
            PermissionCode::RegistryUpdateMetadata->value => ['registry', 'Modifier les métadonnées', 'Modifier les informations générales d’un formulaire.'],
            PermissionCode::MissionsAssign->value => ['missions', 'Affecter une mission', 'Affecter ou réaffecter une mission de recherche.'],
            PermissionCode::MissionsRecordAnyResult->value => ['missions', 'Saisir tout résultat', 'Enregistrer le résultat de n’importe quelle mission de recherche.'],
            PermissionCode::MissionsRecordOwnResult->value => ['missions', 'Saisir son résultat', 'Enregistrer le résultat d’une mission affectée à son propre compte.'],
            PermissionCode::MissionsReviewFound->value => ['missions', 'Valider un formulaire retrouvé', 'Confirmer ou refuser un signalement de formulaire retrouvé.'],
            PermissionCode::RegistryFinalize->value => ['registry', 'Finaliser un formulaire', 'Valider les étapes de numérisation et de saisie d’un formulaire retrouvé.'],
            PermissionCode::RegistryReopen->value => ['registry', 'Rouvrir un formulaire', 'Rouvrir un formulaire finalisé afin de le corriger.'],
            PermissionCode::AttachmentsAddAny->value => ['attachments', 'Ajouter toute pièce jointe', 'Ajouter une pièce jointe à n’importe quel dossier.'],
            PermissionCode::AttachmentsAddOwn->value => ['attachments', 'Ajouter une pièce à son dossier', 'Ajouter une pièce jointe à un dossier qui lui est affecté.'],
            PermissionCode::AttachmentsDeleteAny->value => ['attachments', 'Supprimer toute pièce jointe', 'Supprimer une pièce jointe de n’importe quel dossier.'],
            PermissionCode::AttachmentsDeleteOwn->value => ['attachments', 'Supprimer sa pièce jointe', 'Supprimer sa propre pièce jointe d’un dossier qui lui est affecté.'],
            PermissionCode::RegistryArchive->value => ['registry', 'Archiver ou restaurer', 'Archiver ou restaurer un formulaire du registre.'],
            PermissionCode::RegistryExport->value => ['registry', 'Exporter le registre', 'Exporter la liste des formulaires.'],
            PermissionCode::RegistryImport->value => ['registry', 'Importer des formulaires', 'Importer en masse des formulaires manquants depuis un fichier contrôlé.'],
            PermissionCode::DashboardView->value => ['dashboard', 'Consulter le tableau de bord', 'Accéder au tableau de bord adapté à son profil.'],
            PermissionCode::AnalyticsView->value => ['analytics', 'Consulter les statistiques', 'Accéder aux statistiques détaillées.'],
            PermissionCode::RegistrySearch->value => ['registry', 'Utiliser la recherche avancée', 'Rechercher et filtrer les formulaires du registre.'],
            PermissionCode::UsersManage->value => ['administration', 'Gérer les utilisateurs', 'Créer, modifier, activer ou désactiver les comptes utilisateurs.'],
            PermissionCode::AuditView->value => ['audit', 'Consulter le journal', 'Consulter le journal d’activité et les connexions.'],
            PermissionCode::SettingsManage->value => ['administration', 'Gérer les paramètres', 'Modifier les paramètres et les listes métier de l’application.'],
            PermissionCode::BackupsManage->value => ['backups', 'Gérer les sauvegardes', 'Créer, télécharger, restaurer et supprimer les sauvegardes autorisées.'],
            PermissionCode::NotificationsView->value => ['notifications', 'Consulter les notifications', 'Consulter et marquer ses notifications comme lues.'],
            PermissionCode::ProfileUpdate->value => ['profile', 'Modifier son profil', 'Modifier les informations autorisées de son propre profil.'],
        ];

        DB::transaction(function () use ($permissions): void {
            foreach ($permissions as $permissionCode => [$groupCode, $permissionName, $description]) {
                $permission = PermissionModel::withTrashed()->firstOrNew([
                    'permission_code' => $permissionCode,
                ]);

                $permission->fill([
                    'group_code' => $groupCode,
                    'permission_name' => $permissionName,
                    'description' => $description,
                    'is_system' => true,
                    'is_active' => true,
                ]);
                $permission->deleted_at = null;
                $permission->save();
            }
        });
    }
}
