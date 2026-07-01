# Tinggal Jalan - Travel Management Platform

Tinggal Jalan is a comprehensive, multi-lingual travel management platform built with **Laravel 13+** and **Filament PHP v5+**. It handles everything from tour package showcases (like Bromo, Jogja, Tumpak Sewu) to booking workflows and automated payment processing.

## 🚀 Key Features & Capabilities

- **Backend:** Laravel 13+
- **Admin Panel:** Filament PHP v5+

- **Rich Tour Packages:** Manage detailed tour packages (e.g. *Bromo Sunrise Private Jeep*) including full, day-by-day itineraries, dynamic media galleries, and dynamic add-on services (like Drone Documentation or Airport Pickup).
- **Multi-Lingual Foundation:** Core content fields (destinations, FAQs, tour package titles, and descriptions) are structured to support multiple languages (ID, EN, CN).
- **Automated Booking Workflow:** 
    - Full customer booking lifecycle tracking (New, Confirmed, Completed, Cancelled).
    - Complete booking timeline logs (`contacted_at`, `confirmed_at`, etc.).
- **Integrated Payment Gateway:** Automated Midtrans integration for generating Snap payment links, processing IDR/USD exchange rates, and handling webhooks/callbacks automatically.
- **Communication Tracking:** Built-in tracking for WhatsApp and Email notifications (recording when messages are sent, opened, or failed).
- **Dynamic Frontend Content:** Fully manageable Site Settings, Trust Badges, "Why Choose Us" sections, FAQs, and Featured Reviews directly from the admin panel.

## 🛠️ Local Development Setup (Docker Sail)

This repository includes a full local environment using Docker Compose.

### 1. Start the Containers
```bash
docker compose up -d
```
*(This starts the `app` container with PHP/Laravel and the `mysql` database container.)*

### 2. Environment Setup
Create your local environment file and generate the app key:
```bash
cp .env.example .env
docker compose exec app php artisan key:generate
```

### 3. Install Dependencies
```bash
docker compose exec app composer install
docker compose exec app npm install
```

### 4. Build Assets & Seed Real Data
Compile the frontend CSS/JS and populate the database with our rich prototype data (Bromo, Jogja, Reviews, Settings, etc.):
```bash
docker compose exec app npm run build
docker compose exec app php artisan migrate:fresh --seed
```

## 🔐 Admin Panel Access

The Filament administration panel manages the entire platform.

**URL:** `http://localhost:8000/admin`

**Default Admin Credentials:**
- **Email:** `admin@tinggaljalan.test`
- **Password:** `password`

*(Please change these default credentials immediately upon deploying to any staging or production server.)*
