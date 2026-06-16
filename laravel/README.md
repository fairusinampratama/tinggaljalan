# Tinggal Jalan Laravel Foundation

This is the Laravel + Filament full-version workspace for Tinggal Jalan.

The current React prototype at the repository root remains the visual and behavior reference. Do not delete or move the React files during the migration.

## Installed Stack

- Laravel 13
- Filament 5
- Blade + Tailwind CSS 4
- SQLite for local bootstrap
- MySQL/MariaDB planned for shared-hosting production

## Local Admin

Filament admin is available at:

```txt
/admin
```

Local development credentials:

```txt
Email: admin@tinggaljalan.test
Password: password
```

Change this before any production deployment.

## Current Purpose

This first step creates the backend/admin foundation only. Public pages and CRUD resources will be migrated later, section by section, from the React prototype into Blade + Filament-backed data.

## Useful Commands

```bash
composer install
npm install
npm run build
php artisan migrate
php artisan serve
```

## Migration Rule

The React app stays as the source of truth until the Laravel version reaches feature parity.

