# Laravel + Filament Setup Commands

Laravel is now scaffolded in `/laravel` with Laravel 13 and Filament 5.

Run these commands from `/laravel` when setting up another machine:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
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

## Initial Environment Defaults

The local scaffold uses SQLite so it can boot immediately. Use MySQL/MariaDB for final shared-hosting deployment:

```env
APP_NAME="Tinggal Jalan"
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tinggaljalan
DB_USERNAME=root
DB_PASSWORD=
```

Do not design production around SQLite because the target is shared hosting.
