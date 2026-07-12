# Atomic GitHub-to-Hostinger Deployment

Production is deployed from a validated GitHub `main` commit. The workflow builds an immutable artifact, uploads it to Hostinger, backs up MySQL, runs migrations and caches, switches the active release symlink, and verifies the live site.

## Production layout

```text
/home/u304629909/domains/tinggaljalan.com/
├── tinggaljalan-app -> deployments/current
└── deployments/
    ├── current -> releases/<commit-sha>
    ├── incoming/
    ├── legacy/
    ├── releases/
    └── shared/
        ├── .env
        ├── backups/database/
        └── storage/
```

The live `.env`, uploaded files, logs, and framework storage are shared. They are never included in release artifacts. Gateway credentials remain encrypted in the production database.

## GitHub configuration

Create a GitHub environment named `production`, then add these environment secrets:

| Secret | Value |
| --- | --- |
| `PROD_SSH_HOST` | Production SSH hostname or IP |
| `PROD_SSH_PORT` | Hostinger SSH port |
| `PROD_SSH_USER` | Hostinger SSH username |
| `PROD_SSH_PRIVATE_KEY` | Dedicated Ed25519 deployment private key |
| `PROD_SSH_KNOWN_HOSTS` | Pinned `known_hosts` entry collected out of band |
| `PROD_DOMAIN_ROOT` | `/home/u304629909/domains/tinggaljalan.com` |

Create an environment variable named `PRODUCTION_DEPLOY_ENABLED` with value `false` during initial setup. The CI workflow will validate `main` without attempting deployment until bootstrap is complete. Change it to `true` only after the first atomic layout health check succeeds.

Protect `main` and require the `Validate application` check. Pull requests run validation but never deploy. After deployment is enabled, every successful push or merge to `main` deploys automatically. Production deployments are queued and never cancel an active deployment.

## Dedicated SSH key

Generate a key used only by GitHub Actions:

```bash
ssh-keygen -t ed25519 -C "tinggaljalan-github-production" -f tinggaljalan-github-production -N ""
```

Add the public key as one line in the Hostinger user's `~/.ssh/authorized_keys`. Store the private key only in `PROD_SSH_PRIVATE_KEY`. Pin the host entry in `PROD_SSH_KNOWN_HOSTS`; do not disable host-key checking and do not store the SSH password in GitHub.

## One-time Hostinger bootstrap

Upload `scripts/deployment/bootstrap-hostinger.sh` to the account home directory and review the disk-space preflight. Run it once during a short maintenance window:

```bash
bash ~/bootstrap-hostinger.sh /home/u304629909/domains/tinggaljalan.com
```

The script copies the existing application into an initial release, copies `.env` and `storage` into shared storage, retains the previous application under `deployments/legacy`, briefly enables maintenance mode, and replaces `tinggaljalan-app` with the atomic symlink. It is idempotent after successful initialization.

Verify immediately:

```bash
readlink -f /home/u304629909/domains/tinggaljalan.com/tinggaljalan-app
/opt/alt/php84/usr/bin/php /home/u304629909/domains/tinggaljalan.com/tinggaljalan-app/artisan about
curl -fsS https://tinggaljalan.com/up
```

After verification, set `PRODUCTION_DEPLOY_ENABLED=true`. The next successful `main` workflow will perform the first automated release.

## Deployment behavior

The workflow uses PHP 8.4 and Node 22, runs Pint, the complete Laravel test suite, generates static responsive image variants, and builds Vite assets. It installs production Composer dependencies and packages built assets with a `REVISION` file.

### Responsive image generation

Production PHP must have GD with WebP support enabled. Admin uploads generate responsive WebP variants immediately in shared public storage, so uploading hero, destination, route, news, or gallery images does not require running npm. Each deployment also runs `php artisan images:generate-responsive --missing` after shared storage is linked to backfill older uploads or repair missing variants.

Static repository images are still generated during CI with `npm run build:performance` and packaged into the immutable release under `public/images/generated`.

The remote deployment then:

1. Performs command, symlink, configuration, and disk checks.
2. Extracts into `deployments/releases/<commit-sha>`.
3. Links shared `.env`, `storage`, and public uploads.
4. Enables maintenance mode.
5. Creates a compressed MySQL backup with `deploy:backup-database`.
6. Runs forward-only migrations and Laravel cache generation.
7. Atomically switches `deployments/current`.
8. Runs live health, sitemap, route, news, admin, and revision checks.
9. Retains five code releases and ten database backups after success.

If any post-maintenance step fails, the script restores the previous code symlink, rebuilds its caches, and brings it online. Database migrations are not reversed, so production migrations must use the expand/contract pattern and remain compatible with the previous release.

## Manual rollback

List available releases and choose an exact SHA:

```bash
ls -1t /home/u304629909/domains/tinggaljalan.com/deployments/releases
```

Upload or run the tracked rollback script:

```bash
bash rollback-hostinger.sh \
  /home/u304629909/domains/tinggaljalan.com \
  <target-commit-sha> \
  https://tinggaljalan.com
```

Rollback changes code only. It deliberately does not reverse migrations or restore a database backup.

## Recovery and operations

- Database backups are stored under `deployments/shared/backups/database` and must remain outside the public document root.
- Failed release directories are retained for diagnosis; pruning happens only after a successful health check.
- A release is identifiable by its `REVISION` file and the GitHub Actions deployment summary.
- Never run seeders in production deployment.
- Never edit release files directly. Make a Git commit and let the workflow create a new immutable release.
- Rotate and replace the dedicated deployment key immediately if GitHub or the Hostinger account is compromised.
