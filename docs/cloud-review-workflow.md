# Cloud development and UI review

## Execution checkpoint — 2026-10-05

PR #24 merged as `70a931918e52f4869e4286724cf49cac36dd9a0d`.
Production run `37285820750` initially timed out before upload; its retried
deployment and independent production smoke test passed.

The dedicated staging key authenticated from GitHub Actions in run
`37286227361`. Its public host pin came from the existing production record,
matched to the hPanel endpoint, through export run `37285936663`.

Hostinger provisioned an independent PHP/HTML website for
`preview.tinggaljalan.com`. Its verified root is
`/home/u304629909/domains/preview.tinggaljalan.com`, with its own `public_html`.
The root contains `.tinggaljalan-staging` with the exact preview hostname.
GitHub's staging environment now has five SSH secrets and `STAGING_ROOT`.
Readiness run `37287291391` passed the runtime, canonical root, marker and
free-disk checks. These checks do not establish application deployment readiness.

Database creation is waiting for the owner to enter and submit a new password
in hPanel. Proposed database and user: `u304629909_tj_preview`.
`.env.staging.example` prepares separate settings with empty credential fields.
The earlier "not yet run" sections below describe the original planning state;
this checkpoint is authoritative for completed work.

Remaining execution gates:

1. Create the dedicated database/user and preserve the password privately.
   Verify connection and grants before migrating; never import production data.
2. Create preview-only APP_KEY, storage and configuration. Verify effective
   database settings as well as environment variables: this app stores gateway
   credentials and enabled flags in database tables. Disable email, WhatsApp,
   payments and analytics. Do not run a queue worker or scheduler during setup.
3. Protect the entire preview with authentication before publishing application
   content. Verify unauthenticated home, assets and `/up` are denied; authenticated
   responses have noindex headers. Verify TLS and deny access to secret files.
4. Implement the manual selected-revision staging build/test/deploy workflow and
   staging-only deployment script. Validate exact root and marker before writes,
   check upload integrity, switch a staging release atomically, and automatically
   restore the previous release after failed health checks. Never recycle
   account-wide PHP workers. Keep staging credentials out of production jobs.
5. Prepare synthetic data and a unique admin password; do not expose the default
   development account. Validate database migration and effective integration
   state before releasing the application behind authentication.
6. Deploy the tested revision and verify authenticated `/up` reports that SHA.
   Check home, route/news details, admin login, assets, desktop/mobile rendering,
   and rejection of unauthenticated access. Recheck production health afterward.
7. Deliver the protected review URL and screenshots, with actual checks recorded.
   Application review, user approval and a production promotion remain separate.

## Protected staging deployment implementation

`staging-deploy.yml` offers manual deployment of the selected workflow branch's
exact SHA. It builds and tests that revision with PHP and all configured browser
projects, packages the built frontend and production Composer dependencies, then
uses only the staging environment to upload and deploy it. Its PR validation job
exercises publication, rollback, first-release failure and rejection of the
production path using local fake runtime and HTTP adapters.

The owner must enter three independent secrets in the GitHub `staging` environment:

| Secret | Value |
| --- | --- |
| `STAGING_DB_PASSWORD` | Exact existing password for `u304629909_tj_preview`; nonempty, no control characters. No new database length policy is imposed by deployment. |
| `STAGING_REVIEW_PASSWORD` | Unique preview access password, 16–64 UTF-8 bytes, no control characters. |
| `STAGING_ADMIN_PASSWORD` | Unique preview admin password, 16–64 UTF-8 bytes, no control characters. |

All three values must differ. Enter raw passwords, without JSON quotes or escaping.
GitHub masks each independently. Actions validates all fields before building and
serializes a private JSON transfer file automatically. `STAGING_BOOTSTRAP_JSON`
is obsolete and is not read by the updated workflows. Do not delete it until
migration is verified; the old main workflow still reads it before this PR merges.

The host checks the selected schema and existing credentials before committing
new configuration using atomic file replacement. A failed database connection
writes no new environment or access-password file. Existing credentials are not
silently rotated; use their original values or a separate explicit rotation.

Preview HTTP username
is `reviewer`; application admin email is `preview-admin@tinggaljalan.test`.
Do not send secret values in chat or store them in the repository. App key creation
occurs only on the hosting account, once. Existing credentials are verified rather
than implicitly rotated. The deployment does not start queues or schedule jobs.

`deploy-staging.sh` validates the fixed preview root and identity marker before
writing files, uses a staging lock, checks archive SHA256, verifies the dedicated
database before migrating, and installs HTTP authentication before publication.
Every revision has its own immutable PHP front-controller filename, with an atomic
`.htaccess` update. This avoids reliance on account-wide worker restarts. Old PHP
entry files are denied. Shared storage and configuration remain preview-specific.

Curated content seeders run on first setup without `DatabaseSeeder`'s default admin
or gateway credentials. Gateway and notification database flags are disabled and
credentials cleared; the development admin is rejected. Health checks verify
the SHA, noindex, denied anonymous access, secret-file denial and live JS/CSS.
Failure restores the prior application entry/release. Migrations and shared data
are not rolled back; candidate migrations must remain backward-compatible.
Browser verification captures real desktop/mobile home, lists, detail pages and
admin login in a seven-day `staging-review-<sha>` artifact. It does not claim a
booking/payment submission test or application admin login authentication test.

This implementation still requires PR checks, default-branch workflow availability,
private configuration entry, a successful Hostinger deployment and hosted rendering
verification before the preview can be reported as ready.

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

## Staging SSH preflight (prepared, not yet run)

The new `staging-preflight.yml` workflow has a syntax-only PR check and a manual
SSH job. The manual job uses the `staging` GitHub environment and requires:

| Setting | Type | Purpose |
|---|---|---|
| STAGING_SSH_HOST | Secret | Hostinger SSH server |
| STAGING_SSH_PORT | Secret | Hostinger SSH port |
| STAGING_SSH_USER | Secret | Hosting account user |
| STAGING_SSH_PRIVATE_KEY | Secret | Dedicated staging key |
| STAGING_SSH_KNOWN_HOSTS | Secret | Independently verified SSH host-key entry |
| STAGING_ROOT | Environment variable | Exact `/home/<user>/domains/preview.tinggaljalan.com` root |

Verify the server key through a trusted source before storing it. A network
keyscan alone does not establish identity. The workflow never disables SSH host
verification. Its directory guard intentionally fails until hosting is provisioned
with a `.tinggaljalan-staging` file containing `preview.tinggaljalan.com`.
If Hostinger uses a different document-root layout, inspect and review that layout
before changing the guard; do not point it to the production domain directory.

Run the workflow from the feature branch after GitHub recognizes the manual
workflow. GitHub requires a workflow_dispatch workflow to exist on the default
branch for normal manual dispatch; adding this file to a feature branch alone
may not expose its Run workflow button. Any workflow-only merge needs separate
release authorization because the current main push triggers production CI.

A successful SSH connection followed by a missing-directory error proves only
connection, not hosting readiness. Completion requires runtime, staging marker,
canonical directory and disk checks to pass. This preflight does not create a
subdomain, database, files, or a deployment. A separate staging deployment script
and build/test pipeline remain to be implemented after the hosting layout is
verified. The production workflow is unchanged.

## Revised bootstrap sequence

The preflight now offers three explicit manual operations:

1. `export-host-record`: references the existing production environment only to
   compare its host/port to staging's hPanel-confirmed endpoint and extract a
   matching public SSH host record. It does not reference the production private
   key, authenticate to SSH, or deploy. Its one-day artifact contains public
   server identity, not a client credential. Download and store that record in
   `STAGING_SSH_KNOWN_HOSTS`; preserve provenance as the existing production pin,
   not as newly independently verified vendor identity.
2. `connection`: uses staging secrets to authenticate and check the runtime/tools.
   It needs no staging root, marker or database. Passing proves connectivity and
   runtime only.
3. `readiness`: additionally checks the exact independent website root, marker,
   public directory and disk capacity. Provision an independent PHP/HTML website
   through Add Website onboarding, rather than the nested Subdomains form.

The production record export intentionally fails if production host/port differs
from staging. Resolve that mismatch before changing its comparison. Production
protection rules still apply to the export job. Exported host records need a
reviewed transfer into the staging environment; no admin API token is introduced.

The workflow must be made available on main before manual dispatch. The existing
main-push pipeline may redeploy production when this PR merges. Do not merge as
an implicit setup step: review checks and obtain approval for that concrete effect.
The production deployment workflow itself remains unchanged.
