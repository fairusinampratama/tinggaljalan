# Shared Hosting Deployment Notes

## Server Requirements

- PHP 8.2 or newer.
- Composer support locally or ability to upload the generated `vendor` directory.
- MySQL or MariaDB.
- Ability to point the domain document root to `/laravel/public`.
- Ability to run or simulate:
  - `php artisan migrate --force`
  - `php artisan storage:link`
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`

## Recommended Deployment Flow

Build locally first:

```bash
cd laravel
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Upload:

- Laravel app files.
- `vendor/` if Composer is not available on hosting.
- Built assets from `public/build`.
- `.env` configured for production.

On hosting:

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Public Directory

Best case: set hosting document root to:

```txt
/path/to/repo/laravel/public
```

If the hosting provider cannot change document root, use their Laravel deployment guide or place only the contents of `public` in the web root while keeping the rest of Laravel outside the web root.

## Storage

Use Laravel public storage for uploaded images:

```bash
php artisan storage:link
```

If symlinks are not supported, configure uploads to a public disk supported by the hosting provider.

## Production Defaults

- Use database-backed sessions if the host has unstable file permissions.
- Use database queue for simple background jobs if needed.
- Keep image sizes controlled before upload or add server-side resizing later.
- Keep scheduled jobs optional unless the host supports cron.

