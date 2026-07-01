# Tinggal Jalan

This is the main repository for the **Tinggal Jalan** web platform, built with **Laravel** and **Filament PHP**. 

The application serves both the public-facing travel website and a comprehensive backend admin panel.

## Technology Stack

- **Backend:** Laravel 11+
- **Admin Panel:** Filament PHP v3+
- **Frontend:** Blade Templates + Tailwind CSS
- **Database:** MySQL (via Docker for local development)

## Local Development (Docker)

This project uses Laravel Sail / Docker Compose for local development.

### 1. Start the containers
```bash
docker compose up -d
```
*Note: This will spin up the `app` (Laravel) and `mysql` containers.*

### 2. Install Dependencies
```bash
docker compose exec app composer install
docker compose exec app npm install
```

### 3. Setup Environment
Copy `.env.example` to `.env` and generate an application key:
```bash
cp .env.example .env
docker compose exec app php artisan key:generate
```

### 4. Build Frontend Assets & Migrate
```bash
docker compose exec app npm run build
docker compose exec app php artisan migrate:fresh --seed
```

## Admin Access

The Filament administration panel is accessible at:

```txt
URL: http://localhost:8000/admin
```

**Default Local Credentials (from seeders):**
```txt
Email: admin@tinggaljalan.test
Password: password
```
*(Ensure you change these credentials before any production deployment)*
