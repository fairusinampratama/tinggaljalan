# Frontend Structure

This Laravel app now uses Inertia React for the public website.

## Active Public Frontend

- `resources/js/app.jsx` boots the Inertia React app.
- `resources/js/pages` contains public Inertia pages.
- `resources/js/components` contains public React UI components.
- `resources/js/context` contains shared public UI state.
- `resources/js/utils` contains browser-side helpers.
- `resources/js/inertia-router-shim.jsx` bridges old React Router-style imports to Inertia navigation during the migration.
- `resources/views/app.blade.php` is the only active public app shell.

## Active Blade Views

- `resources/views/public/sitemap.blade.php` is still active for XML sitemap rendering.
- Filament owns its admin views and Livewire runtime internally.

## Removed Legacy Frontend

The old public Blade pages, old public Livewire components, old Blade UI components, and old Laravel-side SPA entry files were removed after the Inertia React UI was verified.

## Root React Prototype

The repository root `src/` remains as the original React prototype/reference for now. The production Laravel public frontend lives in `laravel/resources/js`.

## Data Source Rule

The Laravel Inertia frontend must not import prototype business/content data. Public content comes from Laravel database-backed Inertia props and shared `publicData`; only UI translation constants remain in `resources/js/data/translations.js`.
