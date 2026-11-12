#!/usr/bin/env bash
set -Eeuo pipefail

DOMAIN_ROOT="${1:?Usage: deploy-hostinger.sh <domain-root> <sha> <archive> <base-url>}"
SHA="${2:?Usage: deploy-hostinger.sh <domain-root> <sha> <archive> <base-url>}"
ARCHIVE="${3:?Usage: deploy-hostinger.sh <domain-root> <sha> <archive> <base-url>}"
BASE_URL="${4:?Usage: deploy-hostinger.sh <domain-root> <sha> <archive> <base-url>}"
DEPLOY_ROOT="$DOMAIN_ROOT/deployments"
RELEASES="$DEPLOY_ROOT/releases"
SHARED="$DEPLOY_ROOT/shared"
CURRENT="$DEPLOY_ROOT/current"
LIVE_PATH="$DOMAIN_ROOT/tinggaljalan-app"
PUBLIC_HTML="$DOMAIN_ROOT/public_html"
RELEASE="$RELEASES/$SHA"
PHP_BIN="/opt/alt/php84/usr/bin/php"

recycle_litespeed_workers() {
    command -v pgrep >/dev/null || return 0

    local worker_pids
    worker_pids="$(pgrep -u "$(id -u)" -x lsphp || true)"
    [[ -n "$worker_pids" ]] || return 0

    kill $worker_pids >/dev/null 2>&1 || true
}

[[ "$SHA" =~ ^[0-9a-f]{40}$ ]] || { echo "A full Git SHA is required." >&2; exit 1; }
test -x "$PHP_BIN" || { echo "PHP 8.4 is unavailable." >&2; exit 1; }
for command in composer mysqldump gzip tar curl; do command -v "$command" >/dev/null || { echo "$command is unavailable." >&2; exit 1; }; done
test -L "$LIVE_PATH" && test -L "$CURRENT" || { echo "Run bootstrap-hostinger.sh first." >&2; exit 1; }
test -d "$PUBLIC_HTML" || { echo "public_html is missing." >&2; exit 1; }
test -f "$SHARED/.env" && test -d "$SHARED/storage" || { echo "Shared environment or storage is missing." >&2; exit 1; }
test -r "$ARCHIVE" || { echo "Release archive is unreadable." >&2; exit 1; }
gzip -t "$ARCHIVE" || { echo "Release archive failed gzip integrity validation." >&2; exit 1; }
test "$(df -Pk "$DOMAIN_ROOT" | awk 'NR==2 {print $4}')" -gt 262144 || { echo "At least 256 MB of free disk space is required." >&2; exit 1; }

[[ "$DOMAIN_ROOT" == /home/u304629909/domains/tinggaljalan.com ]]
[[ "$(readlink -f "$DOMAIN_ROOT")" == "$DOMAIN_ROOT" ]]
[[ "$(readlink -f "$DEPLOY_ROOT")" == "$DEPLOY_ROOT" ]]
[[ "$(readlink -f "$RELEASES")" == "$RELEASES" && "$(readlink -f "$SHARED")" == "$SHARED" ]]
[[ "$ARCHIVE" == "$DEPLOY_ROOT/incoming/release-$SHA.tar.gz" && ! -L "$ARCHIVE" ]]
# Serialize production, preview and retention on the same hosting account.
[[ ! -L /home/u304629909/.tinggaljalan-maintenance.lock ]]
exec 8>/home/u304629909/.tinggaljalan-maintenance.lock
flock -n 8 || { echo 'Hosting maintenance/deployment already running.' >&2; exit 1; }
[[ ! -L "$DEPLOY_ROOT/.lock" ]]
exec 9>"$DEPLOY_ROOT/.lock"
flock -n 9 || { echo 'Production deployment/retention already running.' >&2; exit 1; }
# A private journal survives SSH loss or SIGKILL. It contains revisions and a
# phase only, never credentials. Recovery runs under both existing locks.
STATE="$DEPLOY_ROOT/.production-deployment-state"
[[ ! -L "$STATE" && ! -L "$STATE.next" ]]
PREVIOUS="$(readlink -f "$CURRENT")"
[[ "$PREVIOUS" == "$RELEASES/"* && "$(dirname "$PREVIOUS")" == "$RELEASES" ]]
[[ "$(basename "$PREVIOUS")" =~ ^[0-9a-f]{40}$ ]]
[[ -d "$PREVIOUS" && ! -L "$PREVIOUS" && -f "$PREVIOUS/vendor/autoload.php" ]]

write_state() {
    (umask 077; printf '%s\n%s\n%s\n' "$SHA" "$(basename "$PREVIOUS")" "$1" > "$STATE.next")
    mv -Tf "$STATE.next" "$STATE"
}

recover_pending() {
    [[ -f "$STATE" && ! -L "$STATE" ]] || return 1
    local record candidate previous phase active
    mapfile -t record < "$STATE"
    [[ "${#record[@]}" == 3 ]] || return 1
    [[ "${record[0]}" =~ ^[0-9a-f]{40}$ && "${record[1]}" =~ ^[0-9a-f]{40}$ ]] || return 1
    [[ "${record[0]}" != "${record[1]}" && "${record[2]}" =~ ^(prepared|maintenance|rollback)$ ]] || return 1
    candidate="$RELEASES/${record[0]}"
    previous="$RELEASES/${record[1]}"
    phase="${record[2]}"
    [[ -d "$previous" && ! -L "$previous" && "$(readlink -f "$previous")" == "$previous" ]] || return 1
    [[ -f "$previous/vendor/autoload.php" && "$(tr -d '[:space:]' < "$previous/REVISION")" == "${record[1]}" ]] || return 1
    active="$(readlink -f "$CURRENT")"
    # Never overwrite a release published by another operation.
    [[ "$active" == "$previous" || "$active" == "$candidate" ]] || return 1
    [[ ! -L "$candidate" ]] || return 1
    local temporary target
    for temporary in "$CURRENT.next" "$CURRENT.rollback"; do
        if [[ -e "$temporary" || -L "$temporary" ]]; then
            [[ -L "$temporary" ]] || return 1
            target="$(readlink -f "$temporary")"
            [[ "$target" == "$candidate" || "$target" == "$previous" ]] || return 1
            rm -f -- "$temporary" || return 1
        fi
    done
    if [[ "$active" == "$candidate" ]]; then
        [[ ! -L "$CURRENT.rollback" && ! -e "$CURRENT.rollback" ]] || return 1
        ln -s "$previous" "$CURRENT.rollback" || return 1
        mv -Tf "$CURRENT.rollback" "$CURRENT" || return 1
    fi
    if [[ "$phase" == maintenance || "$phase" == rollback || "$active" == "$candidate" ]]; then
        "$PHP_BIN" "$previous/artisan" optimize:clear >/dev/null || return 1
        "$PHP_BIN" "$previous/artisan" config:cache >/dev/null || return 1
        "$PHP_BIN" "$previous/artisan" event:cache >/dev/null || return 1
        "$PHP_BIN" "$previous/artisan" route:cache >/dev/null || return 1
        "$PHP_BIN" "$previous/artisan" view:cache >/dev/null || return 1
        "$PHP_BIN" "$previous/artisan" up >/dev/null || return 1
        recycle_litespeed_workers
    fi
    # Only the journal-owned candidate is disposable. Shared data is reached
    # through symlinks, which rm removes without following their targets.
    [[ "$(readlink -f "$CURRENT")" != "$candidate" ]] || return 1
    if [[ "$phase" != rollback ]]; then rm -rf -- "$candidate" || return 1; fi
    rm -f -- "$STATE" "$STATE.next" || return 1
    echo "Recovered interrupted deployment; previous code restored. Database was not restored."
}

if [[ -e "$STATE" ]]; then
    recover_pending || { echo 'Pending deployment needs manual recovery; no new release attempted.' >&2; exit 1; }
    PREVIOUS="$(readlink -f "$CURRENT")"
fi
# An existing release without our journal is never assumed disposable.
[[ ! -e "$RELEASE" && ! -L "$RELEASE" ]] || { echo "Release $SHA already exists; refusing to replace unowned work." >&2; exit 1; }
[[ ! -e "$CURRENT.next" && ! -L "$CURRENT.next" ]]
[[ ! -e "$CURRENT.rollback" && ! -L "$CURRENT.rollback" ]]
tar -xOf "$ARCHIVE" ./scripts/deployment/hosting-maintenance.py | python3 - capacity --reserve
"$PHP_BIN" "$(command -v composer)" --version >/dev/null

TRANSACTION=0
finish_deployment() {
    local status=$?
    trap - EXIT HUP INT TERM
    if [[ "$TRANSACTION" == 1 ]]; then
        if recover_pending; then
            # Safe retry of the same revision gets a fresh upload.
            rm -f -- "$ARCHIVE" "$ARCHIVE.part"
        else
            echo 'Recovery incomplete; journal retained for investigation.' >&2
        fi
        [[ "$status" != 0 ]] || status=1
    fi
    exit "$status"
}
trap finish_deployment EXIT
trap 'exit 129' HUP
trap 'exit 130' INT
trap 'exit 143' TERM
write_state prepared
TRANSACTION=1
mkdir -p "$RELEASE"
if ! tar -xzf "$ARCHIVE" -C "$RELEASE"; then
    echo "Release archive extraction failed." >&2
    exit 1
fi
find "$RELEASE" -type d -exec chmod 755 {} +
test "$(tr -d '[:space:]' < "$RELEASE/REVISION")" == "$SHA" || { echo "Release revision mismatch." >&2; exit 1; }

printf '%s\n' "$(sha256sum "$ARCHIVE" | cut -d' ' -f1)" > "$RELEASE/.release-archive.sha256"
rm -rf "$RELEASE/storage" "$RELEASE/public/storage"
ln -s "$SHARED/.env" "$RELEASE/.env"
ln -s "$SHARED/storage" "$RELEASE/storage"
ln -s "$SHARED/storage/app/public" "$RELEASE/public/storage"
mkdir -p "$RELEASE/bootstrap/cache" "$SHARED/backups/database"

"$PHP_BIN" "$RELEASE/artisan" about --only=environment >/dev/null
"$PHP_BIN" "$RELEASE/artisan" migrate:status >/dev/null
"$PHP_BIN" "$RELEASE/artisan" config:cache
"$PHP_BIN" "$RELEASE/artisan" event:cache
"$PHP_BIN" "$RELEASE/artisan" route:cache
"$PHP_BIN" -r "exit(extension_loaded('gd') && function_exists('imagewebp') ? 0 : 1);"
"$PHP_BIN" "$RELEASE/artisan" images:generate-responsive --missing

"$PHP_BIN" "$RELEASE/artisan" deploy:backup-database --retain=100 --path="$SHARED/backups/database"

# Record the intent before down: a failure inside down can still leave a
# maintenance marker behind.
write_state maintenance
"$PHP_BIN" "$PREVIOUS/artisan" down --retry=60
"$PHP_BIN" "$RELEASE/artisan" migrate --force
"$PHP_BIN" "$RELEASE/artisan" view:cache

rm -f "$CURRENT.next"
ln -s "$RELEASE" "$CURRENT.next"
mv -Tf "$CURRENT.next" "$CURRENT"

link_public_asset() {
    local name="$1"
    local link="$PUBLIC_HTML/$name"
    local target="../tinggaljalan-app/public/$name"

    if [[ -e "$link" && ! -L "$link" ]]; then
        mv "$link" "$PUBLIC_HTML/$name.legacy-$SHA"
    fi

    rm -f "$link"
    ln -s "$target" "$link"
}

link_public_asset build
link_public_asset images
link_public_asset robots.txt
link_public_asset favicon.ico
link_public_asset favicon.png
link_public_asset favicon-96x96.png
link_public_asset apple-touch-icon.png

"$PHP_BIN" "$RELEASE/artisan" up
recycle_litespeed_workers
"$RELEASE/scripts/deployment/health-check.sh" "$BASE_URL" "$SHA" "$CURRENT" "$PHP_BIN"
# Commit before retention: a housekeeping failure must not roll back healthy
# code or turn an otherwise successful publication into a false failure.
rm -f -- "$STATE"
TRANSACTION=0

post_success_cleanup() {
local BACKUP_LIST
BACKUP_LIST="$(mktemp)" || return 1
find "$SHARED/backups/database" -mindepth 1 -maxdepth 1 -type f -name '*.sql.gz' -printf '%T@ %p\n' \
    | sort -nr \
    | cut -d' ' -f2- > "$BACKUP_LIST" || return 1
backup_number=0
while IFS= read -r old_backup; do
    backup_number=$((backup_number + 1))
    if (( backup_number > 10 )); then
        rm -f -- "$old_backup" || return 1
    fi
done < "$BACKUP_LIST"
rm -f "$BACKUP_LIST" || return 1

python3 "$RELEASE/scripts/deployment/hosting-maintenance.py" retention production --apply \
    --previous "$(basename "$PREVIOUS")" --account-lock-fd 8 --lock-fd 9 || return 1

rm -f "$ARCHIVE"
}
if ! (set -Eeuo pipefail; post_success_cleanup); then
    echo 'WARNING: Release is healthy; post-success retention needs attention.' >&2
fi
echo "Deployment completed for $SHA."
