# Cloud development and UI review

## Working agreement

Define the problem and observable acceptance criteria. Inspect the implementation,
create a feature branch, implement the smallest change, and build/test/render it.
Refine in development before deploying a candidate for interactive staging review.
After the owner approves the rendered result, finish PR checks and merge only with
authorization. Verify the production revision and actual behavior after deployment.

Small UI fixes need a short plan and focused verification. Booking, payment,
authentication, and schema changes need broader regression checks. Screenshots
must show actual application rendering, including desktop and mobile states.

## Reproducible development

Use PHP 8.4 (the current lockfile needs at least 8.4.1), Composer 2, and Node 22.
Install PHP mbstring, intl, XML/DOM, curl, zip, SQLite, GD with WebP, and MySQL
extensions. Dependency versions come from the committed lockfiles.

```bash
node scripts/development/prepare-cloud.mjs --reset-demo
node scripts/development/start-cloud.mjs
```

The prepare command explicitly resets only the separate SQLite demo at
`storage/framework/testing/cloud.sqlite`. It does not rewrite `.env`, use the
production database, or require production credentials. It seeds example data,
disables email, WhatsApp, notifications and payments, provides local hero/logo
assets, generates responsive images, and builds the frontend. Demo admin login:
`admin@tinggaljalan.test` / `password`. Never expose this development server
publicly with these credentials. `COMPOSER_BINARY` may point to a Composer executable.

For fast UI refinements, run `npm run build` again and refresh the page. The
development server serves built assets, avoiding a separate Vite network path.
To use a reusable Codex Cloud environment, record the tested prepare command as
its installation step and the start command as startup instructions; publishing
that environment is a separate account operation.

## Verification

```bash
npm run build:performance
php artisan test
npx playwright install --with-deps chromium firefox webkit
npm run test:browser
```

Existing browser tests use their own `browser.sqlite`, not `cloud.sqlite`.
For focused development checks, prepare that test database and select a project:

```bash
npm run test:browser:prepare
npx playwright test --project=desktop-chromium --project=android-chromium
```

Do not report browsers that were not run as tested. If browser downloads fail,
use a verified official browser executable in a temporary Playwright config and
document the limitation. In this session, servers and shell browser tests must
run within the same execution invocation because separate invocations do not
share localhost networking.

## Review surfaces and verified limits

The shell workspace can render and capture the app. On 2026-10-05 the interactive
cloud browser rejected `http://127.0.0.1:4173` with `ERR_BLOCKED_BY_CLIENT` even
while the workspace server was running. Opening the cloud browser does not
establish network access to the coding workspace. Use captured screenshots for
development feedback and a staging URL for interactive review until a supported
connection is verified.

## Staging readiness and remaining work

Hostinger dashboard inspection on 2026-10-05 confirmed Premium hosting, PHP 8.4,
active SSH, database creation controls, 2.87/25 GB disk usage, 163040/400000
inodes and spare website capacity. The subdomain page showed no existing entries.
These observations establish apparent capacity, not a successful deployment.

Prepare `preview.tinggaljalan.com` with separate application files, database,
storage, APP_KEY and configuration. Use synthetic data, disable outgoing messages
and analytics, and use sandbox credentials for explicit payment testing. Protect
the entire preview with authentication and noindex headers. Replace default
demo admin credentials before any hosted preview is exposed.

A manual GitHub Actions deployment should select a branch, validate it, build
one release, deploy only to staging and verify `/up` against the selected commit.
Use a separate staging environment and pinned SSH host key. Do not copy the
production deployment script unchanged: it recycles all account PHP workers,
which could interrupt production. Verify staging-specific worker/cache behavior
before deploying. Do not store credentials in this document or repository.

Still required: authenticated server preflight, staging document-root and
database provisioning, access protection, deploy workflow, first successful
deployment and browser verification. No staging deployment or environment
publication has been verified yet. Keep the existing production workflow
unchanged during this setup.
