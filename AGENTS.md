# TinggalJalan agent guide

## Project

TinggalJalan is a multilingual Laravel 13 travel booking application. Laravel serves Inertia responses, React renders the public booking experience, and Filament provides the administration interface. Local development uses PHP 8.4, Node 22, and MySQL 8.4 through `compose.yaml`. Automated PHP and browser tests use SQLite.

## Commands

- Install backend dependencies: `composer install`
- Install frontend dependencies: `npm ci`
- Start the full development stack: `composer dev`
- Run PHP tests: `composer test`
- Build frontend assets: `npm run build`
- Run browser tests: `npm run test:browser`
- Run the node's primary validation: `./scripts/verify`

## Project rules

- Keep customer-facing content and metadata correct in every supported locale.
- Treat booking totals, availability, voucher eligibility, payment state, and gateway callbacks as server-owned behavior. Never trust submitted prices or payment status.
- Preserve public tokens and canonical slugs when changing booking, route, news, sitemap, or SEO behavior.
- Store gateway secrets through the existing encrypted settings path. Do not expose secrets in Inertia payloads, logs, fixtures, or committed environment files.
- Use migrations for schema changes and keep existing data upgradeable. Tests must continue to work with SQLite as configured in `phpunit.xml`.
- Add or update automated tests for behavior changes. Use Playwright when the change depends on browser layout, hydration, consent, navigation, or rendered metadata.
- Do not edit generated files under `vendor/`, `node_modules/`, or `public/build/` by hand.

## Definition of done

Run `./scripts/verify`. Report any skipped check and its reason. Do not claim completion while a relevant check is failing.
