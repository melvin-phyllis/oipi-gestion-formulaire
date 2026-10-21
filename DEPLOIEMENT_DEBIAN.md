# Déploiement sur un serveur Debian — OIPI Suivi PI

Cette procédure suppose un serveur Debian administrable avec `sudo`, un nom de domaine et un accès HTTPS. Remplacer toutes les valeurs entre `<...>` avant d'exécuter les commandes.

Le projet est installé dans `/var/www/oipi-suivi-pi`. Nginx ne doit exposer que son sous-dossier `public/`.

## 1. Préparer le serveur

Installer les composants suivants :

- Git, curl, unzip et cron ;
- Nginx ;
- MySQL 8 ou MariaDB ;
- PHP 8.2 ou supérieur avec PHP-FPM ;
- Composer 2 ;
- Node.js 22 et npm, uniquement nécessaires pour compiler les ressources.

Exemple de paquets PHP, après avoir configuré une source Debian fournissant PHP 8.2 :

```bash
sudo apt update
sudo apt install nginx mariadb-server git curl unzip cron
sudo apt install php8.2-cli php8.2-fpm php8.2-mysql php8.2-sqlite3 \
    php8.2-mbstring php8.2-xml php8.2-curl php8.2-gd php8.2-zip php8.2-intl
```

Vérifier les versions avant de poursuivre :

```bash
php -v
composer --version
node --version
npm --version
mysql --version
nginx -v
```

Le projet et sa CI utilisent PHP 8.2 et Node.js 22. Ne pas installer Composer ou npm en tant que dépendances du répertoire public.

## 2. Créer la base de données

Ouvrir MySQL ou MariaDB avec un compte administrateur :

```bash
sudo mariadb
```

Créer une base et un utilisateur dédiés. Remplacer le mot de passe avant exécution :

```sql
CREATE DATABASE oipi_risfm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'risfm_user'@'localhost' IDENTIFIED BY '<MOT_DE_PASSE_MYSQL_FORT>';
GRANT ALL PRIVILEGES ON oipi_risfm.* TO 'risfm_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Ne pas utiliser le compte `root` dans le fichier `.env` de l'application.

## 3. Cloner le projet

Utiliser un compte de déploiement non-root. Pour un dépôt privé, ajouter au préalable une clé de déploiement GitHub en lecture seule.

```bash
sudo mkdir -p /var/www/oipi-suivi-pi
sudo chown <UTILISATEUR_DEPLOIEMENT>:www-data /var/www/oipi-suivi-pi
git clone https://github.com/melvin-phyllis/oipi-gestion-formulaire.git /var/www/oipi-suivi-pi
cd /var/www/oipi-suivi-pi
```

Installer les dépendances PHP :

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
```

## 4. Configurer l'application

Créer le fichier de production :

```bash
cp .env.debian.example .env
sudo chown <UTILISATEUR_DEPLOIEMENT>:www-data .env
sudo chmod 640 .env
```

Compléter `.env` avec :

- le domaine HTTPS dans `APP_URL` et `SESSION_DOMAIN` ;
- les identifiants MySQL/MariaDB ;
- les paramètres SMTP ;
- les identifiants Pusher Channels ;
- `APP_ENV=production`, `APP_DEBUG=false` et `SESSION_SECURE_COOKIE=true`.

Générer la clé une seule fois lors de la première installation :

```bash
php artisan key:generate --force
```

Conserver cette même `APP_KEY` lors des mises à jour. Sa perte rendrait illisibles les données chiffrées existantes.

## 5. Compiler les ressources

Le fichier `.env` doit être configuré avant la compilation, car les variables `VITE_PUSHER_*` sont intégrées au JavaScript généré.

```bash
npm ci
npm run build
test -f public/build/manifest.json
```

Le dossier `node_modules/` n'est jamais servi par Nginx. Si Node.js ne doit pas être installé sur le serveur, exécuter ces commandes sur une machine utilisant Node.js 22, puis transférer intégralement `public/build/`.

## 6. Préparer les permissions et la base

Nginx et le worker doivent pouvoir écrire uniquement dans `storage/` et `bootstrap/cache/` :

```bash
sudo chown -R <UTILISATEUR_DEPLOIEMENT>:www-data /var/www/oipi-suivi-pi
sudo chmod -R ug+rwX storage bootstrap/cache
```

Initialiser ensuite la base :

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan optimize
```

Le SMTP doit fonctionner avant `db:seed` : le mot de passe temporaire du premier administrateur est envoyé par courriel et n'est pas affiché dans les logs. Le seeder ne modifie pas un administrateur déjà existant.

## 7. Configurer Nginx et HTTPS

Créer `/etc/nginx/sites-available/oipi-suivi-pi` :

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name <VOTRE-DOMAINE>;

    root /var/www/oipi-suivi-pi/public;
    index index.php;
    charset utf-8;
    client_max_body_size 25M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ ^/index\.php(/|$) {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ \.php$ {
        return 404;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Adapter le socket `php8.2-fpm.sock` à la version PHP réellement installée, puis activer le site :

```bash
sudo ln -s /etc/nginx/sites-available/oipi-suivi-pi /etc/nginx/sites-enabled/oipi-suivi-pi
sudo nginx -t
sudo systemctl reload nginx
```

Installer ensuite le certificat TLS avec le mécanisme validé par l'administrateur du serveur, par exemple Certbot, puis vérifier que `APP_URL` commence par `https://`. Ne pas ajouter une CSP statique dans Nginx : Laravel génère une CSP avec un nonce différent pour chaque réponse.

## 8. Démarrer le worker avec systemd

Les imports et notifications utilisent la file d'attente. Créer `/etc/systemd/system/oipi-risfm-worker.service` :

```ini
[Unit]
Description=Worker Laravel OIPI RISFM
Wants=network-online.target
After=network-online.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/oipi-suivi-pi
ExecStart=/usr/bin/php artisan queue:work database --queue=imports,notifications,default --sleep=3 --tries=3 --timeout=3600 --max-time=3600
Restart=always
RestartSec=5
TimeoutStopSec=3660
KillSignal=SIGTERM

[Install]
WantedBy=multi-user.target
```

Le réglage `DB_QUEUE_RETRY_AFTER=3900` reste supérieur au timeout du worker, fixé à 3 600 secondes.

Activer le service :

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now oipi-risfm-worker
sudo systemctl status oipi-risfm-worker --no-pager
```

Après chaque déploiement, redémarrer le worker afin qu'il charge le nouveau code.

## 9. Activer le scheduler

Créer `/etc/cron.d/oipi-risfm` avec cette ligne :

```cron
* * * * * www-data cd /var/www/oipi-suivi-pi && /usr/bin/php artisan schedule:run >> /var/www/oipi-suivi-pi/storage/logs/scheduler.log 2>&1
```

Puis appliquer les droits et recharger cron :

```bash
sudo chmod 644 /etc/cron.d/oipi-risfm
sudo systemctl restart cron
```

Le scheduler déclenche notamment les relances à 07:00 et les sauvegardes à 02:00. Le fuseau utilisé par l'application est UTC, identique à l'heure d'Abidjan toute l'année.

## 10. Vérifier avant l'ouverture

```bash
php artisan about --only=environment
php artisan migrate:status
php artisan schedule:list
php artisan queue:failed
sudo systemctl is-active php8.2-fpm nginx mariadb oipi-risfm-worker
command -v mysql
command -v mysqldump
curl -I https://<VOTRE-DOMAINE>/up
```

Adapter `php8.2-fpm` et `mariadb` si le serveur utilise une autre version de PHP ou le service `mysql`.

Effectuer ensuite une recette avec un compte de chaque rôle : connexion, courriel, affectation de mission, import, notification Pusher, pièce jointe, export et téléchargement d'une sauvegarde. Tester une restauration uniquement sur une base de recette séparée, car elle remplace tout le contenu de la base.

Consulter les journaux en cas d'erreur :

```bash
tail -f storage/logs/laravel.log
sudo journalctl -u oipi-risfm-worker -f
sudo journalctl -u nginx -f
```

## 11. Mettre à jour l'application

Sauvegarder d'abord la base et les fichiers privés. Depuis `/var/www/oipi-suivi-pi` :

```bash
php artisan down --retry=60
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
sudo systemctl restart oipi-risfm-worker
php artisan up
```

Contrôler ensuite `/up`, les journaux, le worker et les fonctions essentielles. Un déploiement Git réussi ne prouve pas que le serveur sert la nouvelle version : toujours vérifier le domaine public.

## 12. Points de sécurité

- Seul `/var/www/oipi-suivi-pi/public` doit être accessible depuis le Web.
- Le fichier `.env`, les sauvegardes, imports et pièces jointes restent privés.
- Utiliser HTTPS et conserver `APP_DEBUG=false`.
- Protéger les accès SSH et MySQL ; ne pas exposer le port MySQL à Internet.
- Ne jamais exécuter Composer, npm ou Artisan avec des secrets affichés dans l'historique du terminal.
- Tester la sauvegarde et la restauration avant l'ouverture, sur un environnement isolé.
