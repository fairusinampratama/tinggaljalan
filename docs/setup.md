# Local Development Setup

Laravel lives at the repository root. Run every command below from that directory.

## Canonical WSL workflow

Use Docker Compose as the only PHP web runtime. The canonical development URL is:

```txt
http://localhost:8000
```

Do not also run `php artisan serve`, `composer run dev`, or the production-preview stack. Multiple PHP runtimes share Laravel's `storage` directory with different Linux users and can create session or view files that the other runtime cannot update.

The normal daily startup is:

```bash
npm run build
docker compose up -d
docker compose exec app php artisan migrate --force
```

Verify the stack before opening the site:

```bash
docker compose ps
curl -I http://127.0.0.1:8000/up
```

Both `app` and `mysql` should be running, MySQL should be healthy, and the health request should return HTTP 200.

The development app uses compiled Vite assets from the same `localhost:8000` origin. This is intentional: WSL/Codex forwarding can expose one application port reliably, while a separate Vite port can leave the page without CSS or JavaScript.

### Codex browser panes

Open `http://localhost:8000` in the pane. Codex Desktop may rewrite it to a temporary port such as `localhost:59781`; that rewritten port is a disposable desktop proxy, not the Laravel port. Do not bookmark or manually reuse it. If a pane becomes stale, open `http://localhost:8000` again.

### WSL file ownership

The Compose app runs with `LOCAL_UID` and `LOCAL_GID`, defaulting to `1000:1000`, so Docker and WSL commands create compatible Laravel runtime files. Check the WSL account IDs with `id -u` and `id -g`. If they are not `1000`, add the literal values to `.env`, for example:

```env
LOCAL_UID=1001
LOCAL_GID=1001
```

On a new machine, initialize the project with:

```bash
cp .env.example .env
docker compose build app
docker compose run --rm --no-deps app composer install
npm install
npm run build
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan storage:link
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

Start or resume the complete stack with:

```bash
docker compose up -d
```

The admin media fields store uploads on Laravel's public disk, so make sure the storage symlink exists before testing image uploads:

```bash
docker compose exec app php artisan storage:link
```

Stop it without deleting the MySQL volume:

```bash
docker compose down
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

### Recovery checklist

If `localhost:8000` cannot be reached:

```bash
docker compose ps
docker compose up -d
docker compose logs --tail=100 app
curl -I http://127.0.0.1:8000/up
```

If the page loads without frontend styling or JavaScript, remove a stale Vite hot-file marker and rebuild same-origin assets:

```bash
rm -f public/hot
npm run build
docker compose up -d --force-recreate app
```

If Laravel reports `file_put_contents(...storage/framework/sessions...): Permission denied`, first stop every host `php artisan serve` process. Then normalize only Laravel's writable runtime directories and recreate the app container:

```bash
docker compose exec -T -u root app chown -R "$(id -u):$(id -g)" /var/www/html/storage /var/www/html/bootstrap/cache
docker compose up -d --force-recreate app
docker compose exec app php artisan optimize:clear
```

Do not solve the issue by making the entire repository world-writable. The cause is mixed runtime ownership, not insufficient global permissions.

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
