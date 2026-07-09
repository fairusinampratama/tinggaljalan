# Tinggal Jalan

Tinggal Jalan is a multilingual travel booking and operations platform for Indonesian tour providers. The repository contains the public React/Inertia website, Laravel application, Filament admin panel, booking workflow, customer communications, and Midtrans payment integration.

## Technology

- PHP 8.3+ and Laravel 13
- Filament 5 admin panel
- React 19 with Inertia 3
- Tailwind CSS 4 and Vite 8
- MySQL 8.4
- Docker Compose for local development and production-like previews

Laravel lives at the repository root. Run all commands from this directory.

## Main Features

- Multilingual public content in Indonesian, English, and Simplified Chinese
- Tour packages with itineraries, galleries, add-ons, availability rules, and route filters
- Tiered pricing configuration with support for contiguous traveler ranges and open-ended final limits (e.g., 5+)
- Custom group arrangement workflow: bookings exceeding standard price tiers automatically block checkout and direct the user to consult via WhatsApp or scale down their traveler count
- Public booking requests with customer, trip, voucher, and pricing snapshots
- Admin booking queues from availability confirmation through payment and custom quote adjustments to trip completion
- Midtrans Snap payments with automatic USD-to-IDR conversion
- Payment-request invoices and post-payment receipts through email and WhatsApp
- Configurable SMTP, Whatspie, and Midtrans credentials in dedicated admin pages
- Managed destinations, news, FAQs, reviews, trust stats, platform links, site details, and Why Choose Us content

## Local Setup

### Requirements

- Docker with Docker Compose
- Node.js and npm on the host

The Docker app image includes PHP and Composer. Node is used from the host to build frontend assets.

### First Run

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

Open:

- Public website: `http://127.0.0.1:8000`
- Admin panel: `http://127.0.0.1:8000/admin`
- MySQL from the host: `127.0.0.1:3307`

Local admin credentials:

```text
Email: admin@tinggaljalan.test
Password: password
```

Change these credentials before deploying outside local development.

### Local Database

The local Compose stack creates:

- `tinggaljalan` for development and manual QA
- `tinggaljalan_test` for automated tests

The app container connects to MySQL using service hostname `mysql`. Host tools connect through port `3307`. Keep these values in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=tinggaljalan
DB_USERNAME=tinggaljalan
DB_PASSWORD=password
```

Compose overrides the host and port inside the app container while retaining the database name and credentials.

### Daily Commands

```bash
# Start the local stack
docker compose up -d

# Stop it
docker compose down

# Rebuild frontend assets
npm run build

# Apply new migrations
docker compose exec app php artisan migrate

# Clear Laravel and Filament caches
docker compose exec app php artisan optimize:clear

# Follow application logs
docker compose logs -f app
```

After changing environment variables, recreate the app container and clear cached configuration:

```bash
docker compose up -d --force-recreate app
docker compose exec app php artisan optimize:clear
```

If Docker service-name resolution becomes stale after switching between Compose stacks, recreate both services without deleting their volume:

```bash
docker compose up -d --force-recreate mysql app
```

## Testing

Run the PHP test suite inside Docker so it uses the configured test database and PHP extensions:

```bash
docker compose exec -T app composer test
```

Build the frontend separately:

```bash
npm run build
```

## Production Preview

The preview stack runs Nginx and PHP-FPM with built assets and OPcache. Do not run it at the same time as the local `app` service because both bind port `8000`.

```bash
npm run build
cp .env.prod-preview.example .env.prod-preview
export WWWUSER=$(id -u)
export WWWGROUP=$(id -g)
docker compose down
docker compose -f compose.prod-preview.yaml up -d --build
```

Stop the preview stack with:

```bash
docker compose -f compose.prod-preview.yaml down
```

## Payment And Communication Configuration

Development defaults to Midtrans sandbox and log-based email. Configure production integrations from the dedicated admin pages:

- Payment Settings: Midtrans environment and credentials
- Email Gateway Settings: SMTP provider and sender identity
- WhatsApp Gateway Settings: manual `wa.me` or Whatspie
- Site Details: public logo and business contact information

Never commit real API keys or SMTP passwords. Credentials stored through the admin are encrypted by Laravel.

## Documentation

- [Local setup](docs/setup.md)
- [Shared hosting deployment](docs/shared-hosting.md)
- [Frontend structure](docs/frontend-structure.md)
- [Theme tokens](docs/theme-tokens.md)
- [Admin user guide](PANDUAN_PENGGUNA.md)

## License

This is a private Tinggal Jalan application. Confirm ownership and distribution terms before sharing or deploying the source.
