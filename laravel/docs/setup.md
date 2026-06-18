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
```

If the host PHP does not have the extensions required by Filament (`intl`, `zip`, and `pdo_mysql`), or if the test suite needs a higher PHP memory limit, use the Docker PHP app container instead:

```bash
docker compose build app
docker compose up -d mysql
docker compose run --rm app php artisan migrate:fresh --seed
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
```

SQLite can still be useful for quick framework bootstrapping, but do not use it for CRUD QA because the production target is shared hosting with MySQL/MariaDB.

## Local Development

After the database is migrated and seeded, start the Laravel server, queue listener, logs, and Vite dev server with:

```bash
composer run dev
```

When using the Docker PHP app container, start the web server with:

```bash
docker compose up -d app
```

Open the admin panel at:

```txt
http://127.0.0.1:8000/admin
```

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
