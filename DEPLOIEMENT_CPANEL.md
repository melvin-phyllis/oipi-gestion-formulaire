# Déploiement cPanel — OIPI Suivi PI

Remplacez `<COMPTE>`, `<CHEMIN_PHP>` et les valeurs du modèle par celles de votre hébergement avant d’exécuter les commandes.

## 1. Préparer cPanel

- Activer PHP 8.3 ou supérieur et les extensions requises par Composer.
- Créer une base MySQL et un utilisateur, puis lui donner les droits sur cette base.
- Activer HTTPS et préparer les identifiants SMTP et Pusher Channels.
- Placer le projet dans `/home/<COMPTE>/apps/oipi-suivi-pi`.
- Faire pointer le **Document Root** du domaine vers `/home/<COMPTE>/apps/oipi-suivi-pi/public`. Seul ce dossier doit être public.

## 2. Installer le projet

Dans le terminal cPanel :

```bash
mkdir -p /home/<COMPTE>/apps
cd /home/<COMPTE>/apps
git clone https://github.com/melvin-phyllis/oipi-gestion-formulaire.git oipi-suivi-pi
cd oipi-suivi-pi
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
```

Pour un dépôt privé, configurez une clé de déploiement GitHub en lecture seule.

Les commandes suivantes s’exécutent depuis ce dossier.

## 3. Configurer le fichier .env

Pour la première installation uniquement :

```bash
cp .env.cpanel.example .env
chmod 600 .env
```

Compléter `.env` avec :

- `APP_URL` : le domaine HTTPS ;
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` : les valeurs MySQL cPanel, préfixes compris ;
- `SESSION_DOMAIN` : le domaine sans `https://` ni chemin ;
- `MAIL_*` : les paramètres SMTP de l’hébergeur ;
- `PUSHER_*` : les identifiants Pusher, cluster `eu`.

Conserver `APP_ENV=production` et `APP_DEBUG=false`.

Pour le SMTP : port **587** avec `MAIL_SCHEME=null`, ou port **465** avec `MAIL_SCHEME=smtps`, selon l’hébergeur.

Générer la clé **une seule fois**, à la première installation :

```bash
php artisan key:generate --force
```

Conserver cette clé lors des mises à jour et garder `.env` privé.

## 4. Préparer les fichiers et la base

```bash
chmod -R ug+rwX storage bootstrap/cache
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Si Node.js est absent de cPanel, compiler localement avec les valeurs `VITE_*` de production, puis transférer `public/build/`.

**Le SMTP doit fonctionner avant le seeding.** Le mot de passe temporaire de l’administrateur est envoyé à l’adresse définie dans `AdminSeeder`. Il devra être changé à la première connexion. Un compte existant n’est pas réinitialisé.

## 5. Ajouter les deux tâches cron

Dans **cPanel → Cron Jobs**, choisir **chaque minute** pour les deux tâches. Trouver le chemin PHP avec `command -v php`.

**Planificateur :** relances à 07:00 et sauvegardes à 02:00, selon le fuseau de l’application.

```cron
* * * * * <CHEMIN_PHP> /home/<COMPTE>/apps/oipi-suivi-pi/artisan schedule:run >> /home/<COMPTE>/apps/oipi-suivi-pi/storage/logs/scheduler.log 2>&1
```

**Worker :** traitement des imports et notifications.

```cron
* * * * * flock -n /home/<COMPTE>/apps/oipi-suivi-pi/storage/framework/risfm-queue.lock <CHEMIN_PHP> /home/<COMPTE>/apps/oipi-suivi-pi/artisan queue:work database --queue=imports,notifications,default --stop-when-empty --timeout=3600 --tries=3 >> /home/<COMPTE>/apps/oipi-suivi-pi/storage/logs/queue.log 2>&1
```

Dans les champs de commande cPanel, coller uniquement la commande située **après** les cinq `*`.

Vérifier que `flock` est disponible et que l’hébergeur autorise la durée des traitements volumineux. Conserver `DB_QUEUE_RETRY_AFTER=3900`, supérieur au timeout du worker. Si l’hébergeur fournit un gestionnaire de processus, l’utiliser pour le worker à la place du second cron.

## 6. Vérifier avant l’ouverture

```bash
php artisan migrate:status
php artisan schedule:list
php artisan queue:failed
command -v mysql
command -v mysqldump
php artisan risfm:backup-database
```

Depuis le navigateur, tester la connexion, un courriel, une affectation, un import, les notifications Pusher et le téléchargement d’une sauvegarde. Tester la restauration sur une **base de recette séparée** : elle remplace le contenu de la base.

En cas d’erreur, consulter `storage/logs/laravel.log`, `scheduler.log` et `queue.log`. Pour des styles absents, vérifier `public/build/manifest.json`.

## 7. Mettre à jour

Sauvegarder la base et le stockage privé, puis suspendre les traitements en cours avant la mise à jour.

```bash
cd /home/<COMPTE>/apps/oipi-suivi-pi
php artisan down
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan config:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Reconstruire ou transférer `public/build/` si le frontend a changé, puis rouvrir :

```bash
php artisan up
```
