# Laravel + Filament Setup Commands

Laravel is now scaffolded in `/laravel` with Laravel 13 and Filament 5.

Run these commands from `/laravel` when setting up another machine:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
docker compose up -d mysql
php artisan migrate:fresh --seed
php artisan storage:link
```

If the host PHP does not have the extensions required by Filament (`intl`, `zip`, and `pdo_mysql`), or if the test suite needs a higher PHP memory limit, use the Docker PHP app container instead:

```bash
docker compose build app
docker compose up -d mysql
docker compose run --rm app php artisan migrate:fresh --seed
docker compose run --rm app php artisan storage:link
docker compose up -d app
```

## Admin Panel

Filament is installed at:

```txt
/admin
```

Local development user:

```txt
Email: admin@tinggaljalan.test
Password: password
```

To create another admin user interactively:

```bash
php artisan make:filament-user
```

## Local Database

Use Docker MySQL for local admin CRUD testing. This mirrors the shared-hosting target more closely than SQLite and avoids requiring host MySQL to be installed.

The included `compose.yaml` starts MySQL 8.4 on host port `3307`. It creates two databases:

- `tinggaljalan` for local development and manual admin CRUD testing.
- `tinggaljalan_test` for PHPUnit so automated tests do not wipe local seed data.

The matching `.env` values are:

```env
APP_NAME="Tinggal Jalan"
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=tinggaljalan
DB_USERNAME=tinggaljalan
DB_PASSWORD=password
SESSION_DRIVER=file
CACHE_STORE=file
```

Use MySQL for application data so admin CRUD is tested against the same database family as shared hosting. Keep local sessions and cache file-backed unless you specifically need to test database sessions; database-backed sessions/cache can make Docker page loads feel slow on some machines because every request writes to MySQL.

SQLite can still be useful for quick framework bootstrapping, but do not use it for CRUD QA because the production target is shared hosting with MySQL/MariaDB.

## Local Development

After the database is migrated and seeded, start the Laravel server, queue listener, logs, and Vite dev server with:

```bash
composer run dev
```

The admin media fields store uploads on Laravel's public disk, so make sure the storage symlink exists before testing image uploads:

```bash
php artisan storage:link
```

When using the Docker PHP app container, start the web server with:

```bash
docker compose up -d app
```

If the app was already running before changing environment values, recreate it and clear cached config:

```bash
docker compose up -d --force-recreate app
docker compose exec app php artisan optimize:clear
```

Open the admin panel at:

```txt
http://127.0.0.1:8000/admin
```

## Production Preview

Use the production-preview stack when you want to check speed and shared-hosting-like behavior. It runs Nginx in front of PHP-FPM instead of Laravel's development server, uses built frontend assets, and keeps MySQL for application data.

Build frontend assets and create the preview env file:

```bash
npm run build
cp .env.prod-preview.example .env.prod-preview
```

Build and start the preview stack:

```bash
export WWWUSER=$(id -u)
export WWWGROUP=$(id -g)
docker compose -f compose.prod-preview.yaml build
docker compose -f compose.prod-preview.yaml up -d
```

The preview PHP-FPM container runs migrations, seeds route filters, clears stale caches, and creates the storage link at startup when `RUN_PREVIEW_MIGRATIONS=true`. If you need to run the preparation commands manually, use:

```bash
docker compose -f compose.prod-preview.yaml exec php-fpm composer install --optimize-autoloader
docker compose -f compose.prod-preview.yaml exec php-fpm php artisan key:generate --force
docker compose -f compose.prod-preview.yaml exec php-fpm php artisan migrate --force
docker compose -f compose.prod-preview.yaml exec php-fpm php artisan db:seed --class=RouteFilterSeeder --force
docker compose -f compose.prod-preview.yaml exec php-fpm php artisan storage:link
docker compose -f compose.prod-preview.yaml exec php-fpm php artisan config:cache
docker compose -f compose.prod-preview.yaml exec php-fpm php artisan route:cache
docker compose -f compose.prod-preview.yaml exec php-fpm php artisan view:cache
docker compose -f compose.prod-preview.yaml exec php-fpm php artisan icons:cache
docker compose -f compose.prod-preview.yaml exec php-fpm php artisan filament:cache
```

Open the production preview at:

```txt
http://127.0.0.1:8000
```

Use `compose.prod-preview.yaml` for day-to-day local admin CRUD QA, speed checks, and deployment confidence checks. The preview stack is closer to shared hosting because Nginx serves static files directly and PHP-FPM handles PHP with multiple workers and OPcache instead of the single-process `php -S` server.

Do not run the old `docker compose up -d app` development server at the same time, because it also binds port `8000`.

## Verification

Run the automated test suite against the local MySQL database:

```bash
composer test
```

When using the Docker PHP app container, run:

```bash
docker compose run --rm app composer test
```

For admin CRUD smoke testing, verify login/logout, dashboard loading, and create/edit/view/delete flows for the main Filament resources before checking that public pages still render seeded content.
