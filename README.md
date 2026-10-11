# RISFM — Registre de suivi des formulaires manquants

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

Le projet est actuellement au stade **d’initialisation technique** :

- le squelette Laravel est installé ;
- les dépendances PHP sont disponibles ;
- l’environnement local et la clé d’application sont initialisés ;
- les tests Laravel de base fonctionnent ;
- aucune fonctionnalité métier RISFM n’est encore implémentée ;
- aucune donnée de l’ancienne application n’a été migrée ;
- le modèle de données définitif reste à concevoir et à valider.

## 14. Prochaines étapes recommandées

1. valider ce cadrage avec les responsables métier ;
2. formaliser les statuts et transitions autorisées ;
3. définir précisément les permissions de chaque rôle ;
4. concevoir le modèle de données Laravel ;
5. définir la stratégie de reprise des données existantes ;
6. construire l’authentification et les autorisations ;
7. développer le registre et les missions par étapes ;
8. ajouter les tests de sécurité et de non-régression ;
9. réaliser une recette métier sur un environnement séparé ;
10. préparer le déploiement et le plan de retour arrière.

## 15. Démarrage du socle Laravel

Prérequis : PHP 8.3, Composer et les extensions demandées par Laravel.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Cette procédure démarre uniquement le socle Laravel. Elle ne constitue pas encore une installation fonctionnelle de RISFM.

## Confidentialité

Ce projet est destiné à l’OIPI. Les données, documents, identifiants et informations de configuration ne doivent pas être publiés ni transmis à des personnes non autorisées.
