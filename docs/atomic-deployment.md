# Atomic GitHub-to-Hostinger Deployment

Production is deployed from a validated GitHub `main` commit. The workflow builds frontend assets once, runs PHP and browser validation in separate jobs, packages one immutable release, and pauses for approval. The approved release is uploaded to Hostinger, prewarmed, backed up, migrated, atomically switched, and verified from both Hostinger and an independent GitHub-hosted runner.

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

Create a GitHub environment named `production`. Restrict it to the `main` branch, add the repository owner as a required reviewer, prevent self-review when a second maintainer is available, and add these environment secrets:

| Secret | Value |
| --- | --- |
| `PROD_SSH_HOST` | Production SSH hostname or IP |
| `PROD_SSH_PORT` | Hostinger SSH port |
| `PROD_SSH_USER` | Hostinger SSH username |
| `PROD_SSH_PRIVATE_KEY` | Dedicated Ed25519 deployment private key |
| `PROD_SSH_KNOWN_HOSTS` | Pinned `known_hosts` entry collected out of band |
| `PROD_DOMAIN_ROOT` | `/home/u304629909/domains/tinggaljalan.com` |

Create a repository Actions variable named `PRODUCTION_DEPLOY_ENABLED` with value `false` during initial setup. It must be repository-scoped because GitHub evaluates the deploy job condition before environment-scoped variables become available. The CI workflow will validate `main` without attempting deployment until bootstrap is complete. Change it to `true` only after the first atomic layout health check succeeds.

Production deployment uses a dedicated, always-on Linux VPS runner because GitHub-hosted runners can intermittently time out when opening SSH connections to Hostinger shared hosting. Register one repository runner with these labels:

```text
self-hosted
linux
hostinger-production
```

The self-hosted runner only runs the approved deploy job. It must be on a network that can reach Hostinger SSH, have `ssh`, `scp`, and `bash` available, and have outbound HTTPS access to GitHub for Actions artifacts. Keep it dedicated to this repository and do not run pull-request code on it. Pull requests, release packaging, independent smoke tests, and scheduled monitoring continue to use GitHub-hosted runners.

Protect `main` with pull requests, resolved conversations, blocked force pushes, and these required checks:

```text
Frontend production build
PHP quality and tests
Cross-browser responsive tests
```

After deployment is enabled, every validated push or merge to `main` packages a release and waits for production approval. Production deployments are queued and never cancel an active deployment.

## Always-on deployment runner

Use an Ubuntu VPS that remains online independently of a developer workstation. In **Settings → Actions → Runners → New self-hosted runner**, select Linux x64 and run GitHub's displayed download and configuration commands as a dedicated, non-root `actions-runner` user. Supply the runner name and labels during configuration:

```bash
./config.sh \
  --url https://github.com/fairusinampratama/tinggaljalan \
  --token '<short-lived-registration-token>' \
  --name tinggaljalan-production-vps \
  --labels hostinger-production \
  --unattended
```

The token is short-lived and must only be copied from GitHub during registration. Never save it in shell scripts, documentation, or Actions secrets. Install the configured runner as a service:

```bash
sudo ./svc.sh install actions-runner
sudo ./svc.sh start
sudo ./svc.sh status
```

Find the generated service name, then add a systemd restart policy:

```bash
systemctl list-unit-files 'actions.runner.*'
sudo systemctl edit actions.runner.fairusinampratama-tinggaljalan.tinggaljalan-production-vps.service
```

Use this override:

```ini
[Service]
Restart=always
RestartSec=10
```

Apply it and verify boot recovery:

```bash
sudo systemctl daemon-reload
sudo systemctl enable actions.runner.fairusinampratama-tinggaljalan.tinggaljalan-production-vps.service
sudo systemctl restart actions.runner.fairusinampratama-tinggaljalan.tinggaljalan-production-vps.service
sudo systemctl status actions.runner.fairusinampratama-tinggaljalan.tinggaljalan-production-vps.service
sudo journalctl -u actions.runner.fairusinampratama-tinggaljalan.tinggaljalan-production-vps.service -n 100 --no-pager
```

The generated unit name can differ; use the value returned by `systemctl list-unit-files`. Reboot the VPS once and confirm the runner returns to **Idle** in GitHub without manual intervention. Keep the WSL runner registered but stopped until the VPS completes one real deployment and one scheduled monitor cycle. Then remove the WSL runner from GitHub. To replace or rotate the VPS runner, stop and uninstall its service, run `./config.sh remove` with a fresh removal token, and register the replacement before deleting the old runner.

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

The workflow uses PHP 8.4 and Node 22. It builds Vite and static responsive assets once, passes those exact assets to the PHP tests, browser tests, and release packager, and only packages after every validation job passes. Production Composer dependencies and the validated assets are stored with a `REVISION` file in one immutable artifact.

### Responsive image generation

Production PHP must have GD with WebP support enabled. Admin uploads generate responsive WebP variants immediately in shared public storage, so uploading hero, destination, route, news, or gallery images does not require running npm. Each deployment also runs `php artisan images:generate-responsive --missing` after shared storage is linked to backfill older uploads or repair missing variants.

Static repository images are still generated during CI with `npm run build:performance` and packaged into the immutable release under `public/images/generated`.

The remote deployment then:

1. Performs command, symlink, configuration, and disk checks.
2. Extracts into `deployments/releases/<commit-sha>`.
3. Links shared `.env`, `storage`, and public uploads.
4. Verifies the packaged dependencies and prewarms release-local configuration, event, and route caches while production stays online.
5. Generates missing responsive images and creates a verified compressed MySQL backup.
6. Enables maintenance mode and runs forward-only migrations plus the shared compiled-view cache.
7. Atomically switches `deployments/current`.
8. Recycles the account's LiteSpeed PHP workers so the new process reads the new release and Vite manifest.
9. Confirms `/up` reports the expected runtime revision, live HTML references the expected Vite entry files, and every referenced frontend asset returns HTTP 200.
10. Runs sitemap, route, news, admin, and SEO checks before accepting the release.
11. Retains five code releases and ten database backups after success.

If any post-maintenance step fails, the script restores the previous code symlink, rebuilds its caches, recycles LiteSpeed PHP workers again, and brings the previous release online. Database migrations are not reversed, so production migrations must use the expand/contract pattern and remain compatible with the previous release.

The `/up` endpoint returns JSON containing `status` and the active `revision` read from the release's `REVISION` file. Its response disables caching so deployment checks cannot be satisfied by stale CDN or browser content.

After the Hostinger-side gate succeeds, the `Verify deployed production` job independently checks `/up`, the homepage, Vite JavaScript and CSS assets, `robots.txt`, sitemap, admin login, one route detail, and one news detail from a GitHub-hosted runner. A failure marks the workflow failed and uploads response bodies, headers, and status codes, but does not run a second rollback after the deployment session has ended.

The separate `Production monitoring` workflow runs the same read-only test every 15 minutes. Successful checks emit no custom summary. Failed checks retain diagnostics for seven days and use normal GitHub Actions failure notifications. The scheduled monitor checks the currently active revision without expecting a particular commit; the post-deployment check requires the exact packaged SHA.

## One-time About content seed

The About migration is additive and runs with the normal release. After the first release containing the About tables is healthy, seed only the approved About content classes:

~~~bash
php artisan db:seed --class=AboutPageSeeder --force
php artisan db:seed --class=TeamMemberSeeder --force
php artisan db:seed --class=CompanyMilestoneSeeder --force
~~~

Run these commands from the active release only after the deployment database backup has completed. The seeders use stable **seed_key** values, are idempotent, and preserve edited records. Do not run **DatabaseSeeder**, **migrate:fresh**, or a generic **--seed** command in production because the root seeder also changes admin and gateway configuration.

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

Rollback changes code only. It deliberately does not reverse migrations or restore a database backup. It performs the same PHP-worker recycle and runtime/asset checks as a forward deployment; if validation fails after the switch, it restores the previously active release.

## Recovery and operations

- Database backups are stored under `deployments/shared/backups/database` and must remain outside the public document root.
- Failed release directories are retained for diagnosis; pruning happens only after a successful health check.
- A release is identifiable by its `REVISION` file and the GitHub Actions deployment summary.
- Inspect the runner with `systemctl status` and `journalctl`; systemd automatically restarts a failed listener.
- A queued deployment with no assigned runner indicates a VPS, network, or runner-service problem. Do not bypass the protected environment by deploying from WSL.
- A failed Hostinger health gate restores the previous code release automatically. A failed independent or scheduled smoke test requires investigation and an explicit rollback decision.
- Never run the root **DatabaseSeeder** or generic **--seed** in production. Approved one-time content seeders must be run explicitly by class after a backup.
- Never edit release files directly. Make a Git commit and let the workflow create a new immutable release.
- Rotate and replace the dedicated deployment key immediately if GitHub or the Hostinger account is compromised.
