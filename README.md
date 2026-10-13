# OIPI Suivi PI (RISFM) — Registre de suivi des formulaires manquants

Nouvelle application web de l’Office Ivoirien de la Propriété Intellectuelle (OIPI), destinée au suivi centralisé des formulaires d’enregistrement des titres de propriété intellectuelle non retrouvés, manquants ou en cours de recherche.

Ce dépôt contient la future version Laravel de RISFM. Il repart du besoin métier et ne reprend pas automatiquement l’architecture ou les choix techniques d’une application antérieure.

## 1. Problème à résoudre

Dans le cadre de la numérisation et de la saisie des titres de propriété intellectuelle, certains formulaires physiques ne sont pas immédiatement disponibles. Ils peuvent être mal classés, déplacés, confiés à un service, en cours de recherche ou réellement introuvables.

Sans registre centralisé, plusieurs difficultés apparaissent :

- une même recherche peut être réalisée plusieurs fois par des personnes différentes ;
- le responsable d’une recherche n’est pas toujours clairement identifié ;
- les lieux déjà inspectés et les résultats obtenus sont difficiles à retracer ;
- les comptes rendus peuvent être dispersés ou incomplets ;
- les échéances et les dossiers urgents ne sont pas suffisamment visibles ;
- la Direction Générale ne dispose pas d’une vue fiable de l’avancement global ;
- un formulaire retrouvé peut rester en attente de numérisation ou de saisie ;
- l’historique des décisions et des actions peut être perdu.

RISFM doit fournir une source d’information unique, fiable et traçable pour piloter chaque dossier depuis son signalement jusqu’à sa finalisation.

## 2. Objectifs

L’application doit permettre de :

1. centraliser tous les formulaires manquants dans un registre national ;
2. connaître à tout moment l’état et la priorité de chaque dossier ;
3. attribuer les recherches à des responsables clairement identifiés ;
4. conserver les localisations inspectées et les comptes rendus ;
5. éviter les recherches redondantes ;
6. rappeler automatiquement les échéances ;
7. suivre les étapes postérieures à la découverte du formulaire ;
8. produire des statistiques de pilotage fiables ;
9. garantir la traçabilité des opérations sensibles ;
10. protéger les données et limiter chaque utilisateur à ses autorisations.

## 3. Utilisateurs concernés

### Administrateur

Il administre l’application, les utilisateurs, les rôles, les référentiels, les paramètres techniques et les opérations de maintenance autorisées.

### Responsable

Il supervise les dossiers, affecte ou réaffecte les missions, contrôle les résultats, confirme les formulaires retrouvés et suit leur finalisation.

### Agent

Il consulte les missions qui lui sont confiées, réalise les recherches, renseigne les comptes rendus et signale les formulaires retrouvés.

### Consultation

Il accède en lecture seule aux informations que son rôle l’autorise à consulter, sans modifier les dossiers ni les paramètres.

## 4. Registre attendu

Le registre doit présenter au minimum les informations suivantes :

| Champ | Utilité |
|---|---|
| Numéro | Identifier la ligne du registre |
| Type de titre | Marque, brevet, dessin ou modèle, etc. |
| Année | Année de référence du titre |
| Numéro du formulaire | Identifier précisément le formulaire recherché |
| Statut | Indiquer la situation actuelle du dossier |
| Priorité | Distinguer les dossiers urgents des traitements ordinaires |
| Localisation recherchée | Conserver le dernier emplacement inspecté |
| Responsable | Identifier la personne ou le service chargé de la recherche |
| Date de recherche | Situer la dernière opération dans le temps |
| Résultat | Résumer l’issue de la recherche ou l’étape de finalisation |

Les informations complémentaires utiles au suivi pourront inclure le déposant, le mandataire, la date de dépôt, les pièces jointes, les échéances et l’historique complet.

## 5. Cycle de traitement

```text
Signalement du formulaire manquant
                ↓
      Inscription dans le registre
                ↓
       Affectation d’une mission
                ↓
  Recherche dans une ou plusieurs localisations
                ↓
          Saisie du compte rendu
                ↓
       ┌────────┴─────────┐
       │                  │
   Introuvable         Retrouvé
       │                  ↓
 Nouvelle recherche   Numérisation
 ou clôture validée       ↓
                       Saisie
                          ↓
                 Finalisation du dossier
```

La découverte d’un formulaire ne suffit donc pas à terminer le dossier. La finalisation doit suivre les étapes **Retrouvé → Numérisé → Saisi**.

## 6. Règles métier principales

- Un numéro de formulaire doit identifier un seul dossier actif cohérent.
- Toute mission doit être rattachée à un formulaire et à un responsable.
- Une recherche doit préciser la localisation inspectée, la date et son compte rendu.
- L’historique ne doit pas être remplacé lors d’une nouvelle recherche.
- Un agent ne doit modifier que les missions et résultats autorisés par son rôle.
- Le signalement « retrouvé » par un agent doit pouvoir être confirmé par un responsable habilité.
- Une mission terminée, annulée ou remplacée ne doit plus générer de rappel actif.
- Les changements importants doivent être journalisés avec l’utilisateur, la date et l’action.
- L’archivage doit retirer le dossier du registre actif sans supprimer son historique.
- Les référentiels désactivés doivent rester visibles dans les anciens dossiers, sans être proposés pour de nouvelles opérations.

Ces règles constituent un cadrage initial. Elles devront être validées avec les responsables métier de l’OIPI avant la mise en production.

## 7. Périmètre fonctionnel cible

### Registre et formulaires

- création, consultation, modification contrôlée et archivage ;
- recherche, filtres, tri et pagination ;
- détection des doublons ;
- import contrôlé de données existantes ;
- pièces jointes et justificatifs.

### Missions de recherche

- affectation et réaffectation ;
- date limite et niveau de priorité ;
- saisie des comptes rendus ;
- annulation ou réouverture encadrée ;
- historique chronologique des recherches ;
- rappels et escalades en cas de retard.

### Finalisation

- confirmation du formulaire retrouvé ;
- suivi de la numérisation ;
- confirmation de la saisie ;
- date de résolution et clôture du dossier.

### Pilotage

- tableau de bord adapté au rôle connecté ;
- volumes par statut, type de titre, année, direction et localisation ;
- dossiers urgents ou en retard ;
- délais moyens de recherche et de résolution ;
- taux global de résolution ;
- exports destinés au suivi opérationnel et à la Direction Générale.

### Administration

- utilisateurs, rôles et permissions ;
- directions et services ;
- types de titres, statuts et localisations ;
- paramètres de l’application ;
- journal d’activité et suivi des connexions.

## 8. Notifications et échéances

Le système devra prévenir les utilisateurs concernés lors des événements importants :

- nouvelle affectation ;
- réaffectation ou annulation ;
- échéance proche ;
- mission en retard ;
- formulaire signalé comme retrouvé ;
- validation ou refus d’une étape ;
- finalisation du dossier.

Les rappels devront être exécutés par des tâches planifiées Laravel et rester traçables. Leur calendrier exact sera validé avec l’OIPI.

## 9. Exigences de sécurité

- authentification sécurisée et renouvellement contrôlé des mots de passe ;
- autorisations vérifiées côté serveur pour chaque action ;
- protection CSRF, validation stricte des entrées et requêtes paramétrées ;
- limitation des tentatives de connexion et des demandes sensibles ;
- sessions sécurisées, révocables et expirant après inactivité ;
- journalisation des actions administratives et métier ;
- contrôle des types, tailles et accès aux pièces jointes ;
- secrets conservés hors du dépôt Git ;
- sauvegardes testées et procédure de restauration documentée ;
- utilisation obligatoire de HTTPS en production.

## 10. Exigences non fonctionnelles

L’application devra être :

- responsive sur ordinateur, tablette et téléphone ;
- utilisable avec les navigateurs modernes ;
- performante sur un registre volumineux ;
- accessible et compréhensible par des utilisateurs non techniques ;
- testée automatiquement sur les parcours critiques ;
- déployable sur l’infrastructure retenue par l’OIPI ;
- maintenable grâce aux conventions Laravel et à une documentation à jour.

## 11. Indicateurs de réussite

Le projet sera considéré comme efficace si l’OIPI peut notamment mesurer :

- le nombre total de formulaires à traiter ;
- la répartition entre dossiers en attente, en recherche, retrouvés et finalisés ;
- le taux de résolution global ;
- le nombre de missions en retard ;
- le délai moyen avant la première recherche ;
- le délai moyen de résolution ;
- les localisations les plus sollicitées ;
- la charge de travail par responsable ou service ;
- la progression de la numérisation et de la saisie.

## 12. Hors périmètre initial

Sauf validation ultérieure, la première version ne couvre pas automatiquement :

- l’OCR avancé du contenu des documents ;
- la gestion physique complète des magasins d’archives ;
- la cartographie des rayonnages ;
- la reconnaissance automatique des documents ;
- l’intégration avec tous les systèmes externes de l’OIPI.

Ces fonctions pourront faire l’objet de phases complémentaires.

## 13. État actuel du dépôt

Le projet est actuellement au stade de **stabilisation avant recette métier et déploiement**. Les principaux blocs fonctionnels sont implémentés :

- authentification, changement et réinitialisation sécurisée du mot de passe ;
- administration des utilisateurs, des rôles et des permissions ;
- gestion des directions, des services et des référentiels du registre ;
- création, consultation, modification, archivage et restauration des formulaires manquants ;
- enregistrement direct d’un formulaire déjà retrouvé ;
- import contrôlé et asynchrone de formulaires depuis des fichiers CSV ou XLSX ;
- affectation, démarrage, annulation et réaffectation des missions de recherche ;
- saisie des résultats, contrôle des résultats « à vérifier » et historique des recherches ;
- parcours de finalisation « Retrouvé → Numérisé → Saisi dans l’ERP » et réouverture contrôlée ;
- stockage privé, téléchargement contrôlé et suppression autorisée des pièces jointes ;
- notifications applicatives, notifications par courriel et diffusion en temps réel avec Pusher ;
- tableau de bord, statistiques de pilotage et exports Excel/PDF ;
- journal d’audit des opérations sensibles ;
- sauvegarde, téléchargement, restauration et nettoyage planifié des sauvegardes de base de données ;
- tâches planifiées pour les relances de missions et les sauvegardes automatiques ;
- tests fonctionnels couvrant les principaux modules et règles d’autorisation.

La reprise complète des données de l’ancienne application et la validation sur l’hébergement cPanel cible ne sont pas encore finalisées.

## 14. Prochaines étapes recommandées

1. stabiliser la suite automatisée et corriger toute régression restante ;
2. valider les règles métier, les rôles et les transitions avec les responsables de l’OIPI ;
3. réaliser une recette fonctionnelle complète avec un compte de chaque rôle ;
4. tester les imports volumineux, les courriels, Pusher, les tâches planifiées et les workers de file d’attente ;
5. tester une sauvegarde puis une restauration sur un environnement de recette isolé ;
6. définir et exécuter la stratégie de reprise des données de l’ancienne application ;
7. effectuer une revue de sécurité et vérifier la configuration de production ;
8. déployer sur cPanel, contrôler les journaux et préparer un plan de retour arrière.

## 15. Démarrage du socle Laravel

Prérequis : PHP 8.3, Composer et les extensions demandées par Laravel.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Pour créer les rôles, les référentiels et le compte administrateur initial, configurez d’abord le SMTP dans `.env`, puis lancez `php artisan db:seed`. Le mot de passe temporaire est envoyé à l’adresse définie dans `AdminSeeder` et devra être changé à la première connexion. Le seeder ne réinitialise pas un administrateur déjà présent.

Cette procédure démarre le socle RISFM local. Pour une installation sur cPanel, suivez la procédure de la section suivante et utilisez le modèle `.env.cpanel.example`.

## 16. Installation en production sur cPanel

Le fichier [`.env.cpanel.example`](.env.cpanel.example) est un modèle : il ne contient aucun secret réel. Il faut le copier vers `.env` sur le serveur, puis remplacer toutes les valeurs entre chevrons par celles fournies par cPanel et les services utilisés.

### Préparer l’hébergement

1. Choisir PHP 8.3 et activer les extensions requises par Composer et Laravel, notamment `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `xml`, `ctype`, `curl`, `dom`, `gd` et `zip`.
2. Créer une base MySQL et un utilisateur depuis cPanel, puis lui accorder les droits sur cette base. cPanel préfixe souvent leurs noms par le nom du compte.
3. Déployer le code dans un répertoire privé, hors de `public_html`, et configurer le domaine pour que sa racine web pointe vers le dossier `public/` de Laravel. Le répertoire racine de l’application ne doit jamais être exposé publiquement.
4. Installer les dépendances PHP de production :

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
```

### Configurer l’application

Depuis la racine du projet sur le serveur :

```bash
cp .env.cpanel.example .env
php artisan key:generate
```

Modifier ensuite `.env` : renseigner le vrai domaine HTTPS, la base et l’utilisateur MySQL cPanel, le SMTP, ainsi que les identifiants Pusher. Garder `APP_ENV=production`, `APP_DEBUG=false` et `SESSION_ENCRYPT=true`. Ne jamais déposer le `.env` dans `public_html`, le committer ou transmettre ses secrets.

Le SMTP doit être opérationnel avant le seeding : le compte administrateur initial reçoit son mot de passe temporaire par courriel. Vérifier aussi auprès de l’hébergeur le nom du serveur SMTP et le port/chiffrement attendus.

Construire les ressources frontend avant le transfert ou sur un environnement Node compatible, puis déployer le répertoire `public/build/` généré :

```bash
npm ci
npm run build
```

### Initialiser et optimiser

Après avoir vérifié les paramètres et sauvegardé la base si elle contient déjà des données :

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Le seeder crée les rôles, référentiels et l’administrateur initial si nécessaire. Il n’est pas destiné à réinitialiser le mot de passe d’un compte existant. Vérifier que PHP peut écrire dans `storage/` et `bootstrap/cache/`.

### Tâches planifiées et file d’attente

Créer dans cPanel une tâche cron qui appelle `php artisan schedule:run` chaque minute. Exemple à adapter avec le chemin PHP et le chemin absolu du compte cPanel :

```cron
* * * * * /usr/local/bin/php /home/<COMPTE>/chemin-vers-projet/artisan schedule:run >> /dev/null 2>&1
```

Cette tâche déclenche notamment les relances de missions et les sauvegardes planifiées définies par l’application. Les imports et notifications utilisent aussi la file d’attente `database` : un worker doit donc être exécuté. Privilégier un gestionnaire de processus fourni par l’hébergeur. Si cPanel ne fournit qu’un cron, configurer un lancement sans chevauchement et vérifier que la commande `flock` est disponible ; adapter les chemins et la durée maximale au contrat d’hébergement :

```cron
* * * * * flock -n /home/<COMPTE>/chemin-vers-projet/storage/framework/risfm-queue.lock /usr/local/bin/php /home/<COMPTE>/chemin-vers-projet/artisan queue:work database --queue=imports,notifications,default --stop-when-empty --timeout=3600 --tries=3 >> /home/<COMPTE>/chemin-vers-projet/storage/logs/risfm-queue.log 2>&1
```

Après chaque modification du `.env`, reconstruire le cache de configuration avec `php artisan config:cache`. Consulter les journaux Laravel et les journaux de tâches cron après le déploiement, puis effectuer une recette avec des comptes et données de test avant l’ouverture aux utilisateurs.

## Confidentialité

Ce projet est destiné à l’OIPI. Les données, documents, identifiants et informations de configuration ne doivent pas être publiés ni transmis à des personnes non autorisées.
