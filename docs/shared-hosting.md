# Shared Hosting Deployment

The current production process uses validated GitHub Actions builds and atomic Hostinger releases. See [Atomic GitHub-to-Hostinger Deployment](atomic-deployment.md) for the authoritative setup, bootstrap, deployment, backup, health-check, and rollback instructions.

Production requirements:

- PHP 8.4 at `/opt/alt/php84/usr/bin/php`
- Composer 2
- MySQL with `mysqldump`
- `gzip`, `tar`, `curl`, SSH, and symlink support
- Domain document root resolving through `tinggaljalan-app/public`

Do not upload ad-hoc ZIP archives, overwrite `.env` or `storage`, run production seeders, or edit immutable release files directly.