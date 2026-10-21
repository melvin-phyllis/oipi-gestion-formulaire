# Architecture cible de RISFM

## 1. Objet

Ce document définit l’architecture du nouveau RISFM Laravel de l’OIPI. Il est la référence pour l’organisation du backend, les conventions de nommage, la sécurité, la base de données et les traitements automatiques.

RISFM est un monolithe Laravel rendu côté serveur avec Blade et Tabler. Il ne nécessite actuellement ni API versionnée, ni JWT, ni frontend séparé.

> La documentation, l’interface et les messages visibles peuvent être en français. Tous les identifiants techniques sont en anglais : dossiers, namespaces, classes, méthodes, variables, routes, tables, colonnes, événements, statuts internes et permissions.

## 2. Socle technique

- PHP 8.2 ;
- version de Laravel définie par le `composer.lock` du projet ;
- Blade et Tabler compilé localement avec Vite, sans CDN ;
- authentification par session Laravel et protection CSRF ;
- MySQL ou MariaDB ;
- PHPUnit ;
- queue Laravel pour les tâches asynchrones ;
- scheduler Laravel pour les rappels, rapports et sauvegardes.

Les versions installées doivent toujours être vérifiées dans `composer.lock` et `package-lock.json`. Les versions d’un autre projet ne sont jamais reprises par supposition.

## 3. Décision d’architecture

Le code est organisé d’abord par couche technique, puis par domaine métier. Cette organisation reprend la séparation éprouvée de l’ERP OIPI tout en l’adaptant à une application Blade monolithique.

```text
FormRequest
    ↓
Controller (ctrl_*)
    ↓
Service (srv_*)
    ↓
DTO
    ↓
Repository interface / implementation (repo_*)
    ↓
Eloquent Model
    ↓
Database
```

Le contrôleur traduit le protocole HTTP. Le service exécute le cas d’utilisation. Le repository est l’unique frontière des requêtes de persistance. Le modèle décrit la donnée et ses relations.

Les structures suivantes ne sont pas utilisées pour les fonctionnalités ordinaires :

```text
app/Features/
app/Core/
app/Shared/
app/Http/Controllers/API/V1/
```

Une API versionnée ne sera créée que lorsqu’un véritable client externe ou mobile devra consommer RISFM.

## 4. Arborescence cible

```text
app/
├── Console/Commands/
├── Contracts/{Administration,Organization,Registry,Audit,Backup}/
├── Constants/
├── Dto/
│   ├── Administration/
│   ├── Organization/
│   ├── Registry/
│   ├── Mission/
│   ├── Analytics/
│   ├── Audit/
│   └── Backup/
├── Enums/
├── Events/
│   ├── Administration/
│   ├── Registry/
│   ├── Mission/
│   ├── Security/
│   └── Backup/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   │   ├── Authentication/
│   │   ├── Administration/
│   │   ├── Organization/
│   │   ├── Registry/
│   │   ├── Mission/
│   │   ├── Analytics/
│   │   ├── Audit/
│   │   └── Backup/
│   ├── Middleware/
│   ├── Audit/HttpAuditContextProvider.php
│   └── Requests/
│       ├── Authentication/
│       ├── Administration/
│       ├── Organization/
│       ├── Registry/
│       ├── Mission/
│       ├── Analytics/
│       ├── Audit/
│       └── Backup/
├── Jobs/
│   ├── Mission/
│   ├── Analytics/
│   ├── Notification/
│   └── Backup/
├── Listeners/
│   ├── Administration/
│   ├── Registry/
│   ├── Mission/
│   ├── Security/
│   └── Backup/
├── Models/
│   ├── Administration/
│   ├── Organization/
│   ├── Registry/
│   ├── Mission/
│   ├── Audit/
│   └── Backup/
├── Notifications/
├── Observers/
│   ├── Administration/
│   ├── Registry/
│   ├── Mission/
│   └── Audit/
├── Policies/
├── Providers/RepositoryServiceProvider.php
├── Repositories/
│   ├── Interfaces/
│   │   ├── Administration/
│   │   ├── Organization/
│   │   ├── Registry/
│   │   ├── Mission/
│   │   ├── Analytics/
│   │   ├── Audit/
│   │   └── Backup/
│   ├── Administration/
│   ├── Organization/
│   ├── Registry/
│   ├── Mission/
│   ├── Analytics/
│   ├── Audit/
│   └── Backup/
├── Services/
│   ├── Authentication/
│   ├── Administration/
│   ├── Organization/
│   ├── Registry/
│   ├── Mission/
│   ├── Analytics/
│   ├── Audit/
│   ├── Backup/
│   ├── CodeGenerator/
│   ├── CustomLog/
│   └── UploadFileManager/
└── Traits/
    ├── HasUniqueCode.php
    └── HasUuid.php

database/{factories,migrations,seeders}/
resources/{css,js,lang/fr,views}/
tests/{Feature,Integration,Unit}/

routes/
├── web.php
├── console.php
├── authentication.php
├── administration.php
├── organization.php
├── registry.php
├── mission.php
├── analytics.php
├── audit.php
└── backup.php
```

Les dossiers sont créés au moment où un premier élément réel en a besoin. Aucun répertoire vide n’est ajouté uniquement pour reproduire l’arbre.

## 5. Domaines métier

### `Authentication`

Connexion, déconnexion, récupération et changement de mot de passe, sessions, limitation des tentatives et réauthentification des opérations sensibles.

### `Administration`

Utilisateurs, rôles, permissions directes, paramètres fonctionnels, activation et désactivation des comptes.

### `Organization`

Directions, services rattachés aux directions et rattachement organisationnel des utilisateurs.

### `Registry`

Registre des formulaires manquants, types de titre, statuts, historique, pièces jointes, archivage et parcours `found -> digitized -> entered`.

### `Mission`

Affectation, réaffectation, échéance, démarrage, annulation, compte rendu, résultat, clôture et rappels automatiques.

### `Analytics`

Tableau de bord, statistiques avancées, exports et rapports périodiques.

### `Audit`

Journal d’activité, connexions, échecs de connexion, traçabilité des opérations sensibles et consultation autorisée des traces.

### `Backup`

Création, conservation, téléchargement autorisé, restauration contrôlée et planification des sauvegardes.

## 6. Responsabilités des couches

### FormRequest

- autorise explicitement l’action ;
- normalise les entrées simples ;
- valide les données HTTP ;
- fournit des messages français ;
- ne décide pas d’une transition métier dépendante de l’état courant.

Toute écriture possède un `FormRequest` et produit un DTO.

### Controller

- méthodes publiques préfixées `ctrl_` ;
- reçoit des données validées ou une ressource liée par route ;
- appelle les services ;
- retourne une `View`, une `RedirectResponse` ou exceptionnellement une `JsonResponse` ;
- ne contient ni requête Eloquent, ni transaction, ni logique métier.

### Service

- méthodes publiques préfixées `srv_` ;
- applique les règles métier ;
- coordonne plusieurs repositories ;
- gère transactions et verrous ;
- diffuse les événements lorsque l’état est validé ;
- ne dépend jamais de `Request`, Blade, `View` ou `RedirectResponse`.

Les échanges entre domaines passent par un contrat de service dans
`app/Contracts/{Domain}` ou un événement métier. Un service n'injecte pas le
repository d'un autre domaine. Les lectures de relations Eloquent restent
dans les repositories, y compris les appels `load()` et `loadMissing()`.

Les services de téléchargement et d'export retournent un DTO décrivant le
fichier ou son contenu. Le contrôleur rend le template PDF et construit la
réponse HTTP. Le journal d'audit reçoit son contexte depuis
`AuditContextProviderInterface` ; seul l'adaptateur HTTP connaît la requête.

### DTO

- classe immuable suffixée `DTO` ;
- contient uniquement des données validées et typées ;
- ne connaît ni HTTP ni Eloquent ;
- sert de contrat stable entre contrôleur, service et repository.

Un DTO de résultat transporte les identifiants et valeurs utiles, jamais un
modèle Eloquent mutable.

### Repository

- interface suffixée `RepositoryInterface` ;
- implémentation suffixée `Repository` ;
- méthodes publiques préfixées `repo_` ;
- centralise Eloquent, filtres, agrégations, pagination et verrouillage ;
- ne contient ni autorisation ni choix de transition métier ;
- est injecté par son interface via `RepositoryServiceProvider`.

### Model

- classe suffixée `Model` ;
- déclare table, casts, relations et scopes ;
- peut exposer un petit prédicat d’état ;
- ne coordonne jamais un workflow métier.

## 7. Contrat HTTP Blade

Toutes les routes utilisateur utilisent le middleware `web`, les sessions, les cookies sécurisés, CSRF, l’authentification et des noms de route stables.

```php
Route::middleware(['auth', 'permission:missions_assign'])
    ->prefix('missions')
    ->name('missions.')
    ->group(function (): void {
        Route::post('{missionUuid}/assign', [MissionController::class, 'ctrl_assign'])
            ->whereUuid('missionUuid')
            ->name('assign');
    });
```

Les fichiers de routes sont séparés par domaine et chargés depuis `bootstrap/app.php`. Le contrôleur possède la réponse HTTP ; le service ne retourne jamais du JSON ou une redirection.

## 8. Modèle de données

### Préfixes

| Domaine | Préfixe |
|---|---|
| Administration | `adm_` |
| Organization | `org_` |
| Registry | `reg_` |
| Mission | `mis_` |
| Audit | `aud_` |
| Backup | `bkp_` |

Les tables métier suivent `{prefix}_{entity}_models`.

### Tables principales prévues

```text
adm_user_models
adm_role_models
adm_permission_models
adm_role_permission_models
adm_setting_models

org_directorate_models
org_service_models

reg_title_type_models
reg_search_location_models
reg_missing_form_models
reg_form_attachment_models
reg_form_status_history_models

mis_search_mission_models
mis_mission_report_models
mis_mission_history_models

aud_activity_log_models
aud_login_log_models

bkp_backup_models
```

Les statuts du registre ne sont pas paramétrables en base. Ils sont définis par
`App\Enums\Registry\FormStatus` afin de protéger le workflow technique et ses
transitions contre toute modification depuis l’interface d’administration.

### Identifiants et relations

Chaque modèle métier contient normalement :

```text
id              internal numeric identifier
uuid            public identifier used in URLs
code_unique     stable business identifier
created_at
updated_at
deleted_at
```

Les relations utilisent des colonnes `*_code_unique` immuables, indexées et protégées par une clé étrangère lorsque le moteur le permet. Les identifiants numériques ne sont jamais exposés dans les URL.

Les exceptions à `uuid`, `code_unique` ou `SoftDeletes` sont limitées aux tables purement techniques et doivent être justifiées dans la migration.

## 9. Rôles et permissions

Le contrôle d’accès reste volontairement simple :

```text
User -> Role -> Permissions
```

RISFM ne possède aucune table de module principal ou de sous-module. Une permission appartient directement à un rôle par `adm_role_permission_models`. La colonne `group_code` de `adm_permission_models` sert uniquement à regrouper les permissions dans l’interface (`dashboard`, `registry`, `missions`, `attachments`, `analytics`, `administration`, `audit`, `backups`, `notifications`, `profile`) ; elle ne constitue pas un niveau d’autorisation supplémentaire.

Exemples de permissions :

```text
registry_view
registry_create
registry_update_metadata
registry_archive
missions_assign
missions_record_any_result
missions_record_own_result
attachments_add_own
analytics_view
users_manage
roles_manage
settings_manage
backups_manage
audit_view
```

La sécurité est fermée par défaut.

`users_manage` permet de gérer les comptes ordinaires. `roles_manage` est
réservé aux administrateurs système et contrôle l'administration des
habilitations. Un gestionnaire délégué ne peut ni attribuer le rôle
`SYSTEM_ADMIN`, ni modifier, réactiver, désactiver ou réinitialiser un compte
administrateur. Les services refont ces contrôles sur les comptes verrouillés
dans la transaction. Le seeder ciblé `RoleManagementPermissionSeeder` ajoute
le nouveau droit sans réinitialiser les comptes ni leurs délégations.

`audit_view` est également réservé aux administrateurs système actifs. Une
ancienne attribution à un autre rôle ne donne aucun accès au journal, à son
entrée de menu ni aux activités d’audit du tableau de bord. Le service de
gestion des habilitations refuse toute nouvelle attribution à un autre rôle.
Le seeder ciblé `AuditAccessPermissionSeeder` désactive les anciennes
attributions non administratives et conserve l’accès de l’administrateur,
sans modifier les comptes, mots de passe ou autres permissions. Il est aussi
appelé par `RolePermissionSeeder` lors de l’initialisation.

- une route protégée déclare explicitement sa permission ;
- une permission absente ou inconnue refuse l’accès ;
- aucune autorisation n’est déduite du nom du contrôleur ou de sa méthode ;
- une policy complète la permission pour vérifier la ressource, son propriétaire ou son état ;
- le middleware ne laisse jamais passer une action parce qu’un code de module manque ;
- les actions publiques sont énumérées explicitement.

## 10. Événements, listeners, jobs et observers

### Événements

Ils décrivent un fait métier déjà produit :

```text
MissingFormCreated
MissingFormUpdated
MissingFormArchived
FormStatusChanged
MissionAssigned
MissionReassigned
MissionStarted
MissionReportSubmitted
MissionCompleted
MissionCancelled
PasswordChanged
BackupCreated
BackupRestored
```

Un événement ne contient aucune logique métier. Il transporte de préférence des identifiants stables.

### Listeners

Ils exécutent des conséquences indépendantes : historique, notification, audit ou mise en queue. Un listener lent délègue à un job.

### Jobs

```text
SendMissionReminder
GeneratePeriodicReport
GenerateRegistryExport
CreateDatabaseBackup
PruneOldAuditEntries
```

Un job rejouable doit être idempotent et définir une stratégie explicite de tentative, délai et échec.

### Observers

Ils restent limités aux automatismes techniques systématiques. Les workflows importants comme l’affectation, la finalisation ou la restauration restent visibles dans un service.

## 11. Transactions et cohérence

Toute opération modifiant plusieurs agrégats est transactionnelle. Un verrou `lockForUpdate()` est utilisé lorsque deux utilisateurs peuvent effectuer la même transition simultanément.

Un événement ou un job qui lit l’état produit par une transaction ne doit être diffusé qu’après commit. Une erreur ne doit jamais laisser un statut, un historique ou une notification partiellement enregistrés.

## 12. Sécurité applicative

- mots de passe hachés par Laravel ;
- cookies sécurisés et sessions régénérées à la connexion ;
- limitation des tentatives de connexion et de récupération ;
- permissions fermées par défaut ;
- refus d’intégration dans une iframe via `X-Frame-Options: DENY` et `Content-Security-Policy: frame-ancestors 'none'` ;
- confirmation récente du mot de passe pour les opérations sensibles ;
- sauvegardes et pièces sensibles stockées hors du répertoire public ;
- téléchargement uniquement via un contrôleur autorisé ;
- validation du type MIME, de l’extension, de la taille et du nom de stockage ;
- secrets uniquement dans l’environnement ;
- aucune donnée secrète dans les logs ;
- journalisation du résultat des actions sensibles ;
- protection contre l’affectation de masse ;
- validation serveur obligatoire, même si le navigateur valide déjà ;
- restauration protégée par permission, réauthentification, phrase de confirmation et audit.

Les limites de connexion se cumulent : 5 requêtes par minute pour un compte (identifiant et e-mail partagent le compteur), et 20 pour une IP, même en changeant d’identifiant. La récupération et la réinitialisation publiques partagent une limite de 3 requêtes par minute par compte et de 10 par IP.

Les vérifications du mot de passe actuel partagent un compteur de 5 requêtes par minute par utilisateur connecté et de 20 par IP : changement de mot de passe, changement de l’e-mail du profil, réinitialisation administrative et restauration. Changer de cible, d’action ou d’IP ne contourne pas le compteur utilisateur. Les modifications ordinaires du profil conservent leur propre limite. Une limite atteinte renvoie `429` avec `Retry-After` ; les réponses JSON contiennent aussi un message français et `retry_after`. Les compteurs doivent utiliser un cache persistant partagé en production, jamais le driver `array`.

Les middlewares globaux `AddContentSecurityPolicy` et `PreventFraming` enveloppent les réponses Laravel, y compris les redirections et erreurs traitées par le framework. Ils ajoutent une CSP couvrant les ressources et `X-Frame-Options: DENY`, sans supprimer les politiques CSP déjà présentes. Les politiques multiples se cumulent : une autre politique plus restrictive peut donc encore bloquer une ressource.

Un nonce aléatoire est renouvelé pour chaque réponse et transmis à Vite, aux balises Blade `script` et `style` autorisées et aux styles générés par ApexCharts. Les réponses HTML sont privées et non stockables (`Cache-Control: no-store, private`) pour éviter la réutilisation du nonce via un cache. Toute nouvelle balise inline de script ou de style doit porter `nonce="{{ Vite::cspNonce() }}"`. Les gestionnaires HTML comme `onclick`, `onchange` et `onsubmit` sont interdits : utiliser les écouteurs JavaScript du bundle ou d’un script noncé. Aucun `unsafe-inline` ni `unsafe-eval` n’est autorisé pour JavaScript.

Les scripts utilisent `strict-dynamic` avec nonce. Les images et polices sont locales, avec les exceptions `data:` et, pour les images, `blob:`. Les connexions sont limitées à l’origine courante et aux deux endpoints TLS du cluster Pusher configuré (`wss://ws-{cluster}.pusher.com` et `https://sockjs-{cluster}.pusher.com`) ; aucun domaine générique ni CDN n’est autorisé. Le client Pusher utilise les transports WebSocket et XHR, sans chargement distant de SockJS. Les objets, iframes, médias, workers et changements de base URL sont interdits ; les formulaires ne peuvent cibler que l’origine courante.

Les attributs `style` restent explicitement autorisés pour préserver les composants Tabler et les mises à jour graphiques. Il s’agit d’un compromis CSS documenté, pas d’une autorisation des scripts inline ; les éléments `style` restent soumis au nonce dans les navigateurs compatibles CSP 3. `style-src` conserve un repli moins strict pour les anciens navigateurs.

En environnement `local` uniquement, l’origine du serveur Vite déclarée dans son fichier `hot` est ajoutée aux sources nécessaires au développement et au WebSocket HMR. Hors de cet environnement, un éventuel fichier `hot` résiduel est ignoré et seuls les assets compilés sont utilisés. En production avec une `APP_URL` HTTPS, `upgrade-insecure-requests` est ajouté.

Les tests PHP vérifient les directives, les nonces, les pages principales et d’erreur ainsi que l’isolation des sources Vite et Pusher. Les tests Chrome (`npm run test:security`, après `npm run build`) exercent les assets compilés, Tabler, ApexCharts et plusieurs interactions réelles, et vérifient le blocage des injections de scripts, d’attributs événementiels, d’`eval`, de styles et de connexions non autorisés. Ils ne prouvent pas la réception d’un événement par le service Pusher externe.

Les pages servies directement par Apache/cPanel, avant ou sans Laravel, doivent être protégées au niveau du serveur. Sur l’hébergement réel, vérifier les en-têtes reçus, l’absence de violations CSP inattendues dans la console et le fonctionnement effectif de Pusher. Ne pas ajouter une deuxième CSP statique incompatible avec les nonces Laravel.

Les imports XLSX passent par `XlsxRowReaderService` : lecture XML en flux de la seule feuille active, sans charger un classeur PhpSpreadsheet ni convertir la feuille en tableau. Les textes partagés sont indexés dans des fichiers temporaires privés, libérés à la fin ou en cas d’erreur. Les dimensions déclarées et les cellules de mise en forme vides ne déclenchent pas l’allocation d’une grille. Les formules ne sont jamais exécutées ; seules leurs valeurs mises en cache dans le fichier sont lues.

Les garde-fous XLSX sont définis dans `config/risfm.php` : 256 Mio décompressés au total, 128 Mio par composant, 1 024 composants, 1 Mio d’index ZIP, 64 feuilles, 64 colonnes contenant des données, 4 Kio par cellule et 1 000 000 de textes partagés. Le nombre de composants et la taille de l’index ZIP sont contrôlés avant l’ouverture par `ZipArchive`. Les unités XML sont également bornées avant leur analyse ; les DTD, entités et relations externes sont refusées. La limite métier de 100 000 lignes reste inchangée et le comptage s’arrête dès qu’elle est dépassée. Un rejet de sécurité marque l’import comme échoué avec un message français, supprime ses fichiers de travail et ne crée aucun formulaire. L’insertion reste atomique et exécutée par la file `imports`. Le répertoire temporaire PHP doit disposer d’espace libre sur cPanel.

## 13. Scheduler et queues

Le scheduler déclenche les recherches de travail, puis les jobs sont exécutés par la queue :

```text
missions:send-reminders
reports:generate-periodic
backups:create
audit:prune
```

En production, cron appelle le scheduler chaque minute :

```cron
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

Un worker ou un mécanisme cPanel équivalent exécute la queue. Les rapports, courriels et rappels ne doivent pas bloquer une requête web.

## 14. Conventions de nommage

| Élément | Convention | Exemple |
|---|---|---|
| Namespace | anglais, `PascalCase` | `App\Services\Mission` |
| Controller | suffixe `Controller` | `MissionController` |
| Méthode controller | préfixe `ctrl_` | `ctrl_assign` |
| Service | suffixe `Service` | `MissionService` |
| Méthode service | préfixe `srv_` | `srv_assign` |
| Repository | suffixe `Repository` | `MissionRepository` |
| Méthode repository | préfixe `repo_` | `repo_findByUuid` |
| Model | suffixe `Model` | `SearchMissionModel` |
| DTO | suffixe `DTO` | `AssignMissionDTO` |
| Request | suffixe `FormRequest` | `AssignMissionFormRequest` |
| Table | anglais, préfixée | `mis_search_mission_models` |
| Colonne | anglais, `snake_case` | `assigned_user_code_unique` |
| Route | anglais, `kebab-case` | `/mission-reports` |
| Nom de route | anglais, pointé | `missions.assign` |
| Event | anglais, passé | `MissionAssigned` |
| Job | anglais, impératif | `SendMissionReminder` |
| Permission | anglais, `resource_action` | `missions_assign` |

Les points sont réservés aux noms de routes Laravel. Les codes de permission utilisent exclusivement des lettres minuscules et des underscores.

Exemples interdits :

```text
FormulaireController
UtilisateurService
date_recherche
numero_formulaire
/parametres
```

Équivalents attendus :

```text
MissingFormController
UserService
search_date
reference_number
/settings
```

## 15. Tests

- tests Feature pour routes, sessions, CSRF, permissions, validation et réponses ;
- tests Integration pour repositories, transactions, événements et listeners ;
- tests Unit pour règles métier isolées, DTOs et objets de valeur ;
- tests de jobs pour rappels, rapports et sauvegardes ;
- tests de sécurité pour accès interdits, réauthentification, uploads et téléchargements.

Chaque workflow critique couvre au minimum : succès, validation invalide, accès refusé, transition interdite, concurrence pertinente, historique, événement attendu et absence d’effet partiel après erreur.

## 16. Ordre de construction

1. fondations : identifiants, exceptions, logs, provider des repositories ;
2. authentification et sécurité de session ;
3. administration, rôles et permissions directes ;
4. organisation : directions et services ;
5. registre et historique des formulaires ;
6. missions, comptes rendus et événements ;
7. notifications, rappels, scheduler et queue ;
8. statistiques, exports et rapports périodiques ;
9. audit, sauvegarde et restauration ;
10. durcissement, tests de non-régression et déploiement cPanel.

## 17. Règle d’évolution

Une fonctionnalité nouvelle doit préciser son domaine, ses couches, sa permission, ses invariants, ses événements, sa trace d’audit et ses tests. Un domaine ne dépend jamais du contrôleur d’un autre domaine. Toute exception à cette architecture doit être motivée avant son implémentation.

Les instructions exécutables pour les agents se trouvent dans `skills/risfm-laravel-backend/SKILL.md`.
