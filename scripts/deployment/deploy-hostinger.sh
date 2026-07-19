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
test ! -e "$RELEASE" || { echo "Release $SHA already exists." >&2; exit 1; }
test "$(df -Pk "$DOMAIN_ROOT" | awk 'NR==2 {print $4}')" -gt 262144 || { echo "At least 256 MB of free disk space is required." >&2; exit 1; }

"$PHP_BIN" "$(command -v composer)" --version >/dev/null
PREVIOUS="$(readlink -f "$CURRENT")"
mkdir -p "$RELEASE"
tar -xzf "$ARCHIVE" -C "$RELEASE"
test "$(tr -d '[:space:]' < "$RELEASE/REVISION")" == "$SHA" || { echo "Release revision mismatch." >&2; exit 1; }

rm -rf "$RELEASE/storage" "$RELEASE/public/storage"
ln -s "$SHARED/.env" "$RELEASE/.env"
ln -s "$SHARED/storage" "$RELEASE/storage"
ln -s "$SHARED/storage/app/public" "$RELEASE/public/storage"
mkdir -p "$RELEASE/bootstrap/cache" "$SHARED/backups/database"

"$PHP_BIN" "$RELEASE/artisan" about --only=environment >/dev/null
"$PHP_BIN" "$RELEASE/artisan" migrate:status >/dev/null
"$PHP_BIN" "$PREVIOUS/artisan" down --retry=60

SWITCHED=0
rollback_on_error() {
    echo "Deployment failed; restoring $PREVIOUS." >&2
    if [[ "$SWITCHED" == "1" ]]; then
        rm -f "$CURRENT.rollback"
        ln -s "$PREVIOUS" "$CURRENT.rollback"
        mv -Tf "$CURRENT.rollback" "$CURRENT"
    fi
    "$PHP_BIN" "$PREVIOUS/artisan" optimize:clear >/dev/null 2>&1 || true
    "$PHP_BIN" "$PREVIOUS/artisan" config:cache >/dev/null 2>&1 || true
    "$PHP_BIN" "$PREVIOUS/artisan" route:cache >/dev/null 2>&1 || true
    "$PHP_BIN" "$PREVIOUS/artisan" view:cache >/dev/null 2>&1 || true
    "$PHP_BIN" "$PREVIOUS/artisan" up >/dev/null 2>&1 || true
    recycle_litespeed_workers
}
trap rollback_on_error ERR

"$PHP_BIN" "$RELEASE/artisan" deploy:backup-database --retain=10 --path="$SHARED/backups/database"
"$PHP_BIN" "$RELEASE/artisan" migrate --force
"$PHP_BIN" "$RELEASE/artisan" optimize:clear
"$PHP_BIN" -r "exit(extension_loaded('gd') && function_exists('imagewebp') ? 0 : 1);"
"$PHP_BIN" "$RELEASE/artisan" images:generate-responsive --missing
"$PHP_BIN" "$RELEASE/artisan" config:cache
"$PHP_BIN" "$RELEASE/artisan" event:cache
"$PHP_BIN" "$RELEASE/artisan" route:cache
"$PHP_BIN" "$RELEASE/artisan" view:cache

rm -f "$CURRENT.next"
ln -s "$RELEASE" "$CURRENT.next"
mv -Tf "$CURRENT.next" "$CURRENT"
SWITCHED=1

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

"$PHP_BIN" "$RELEASE/artisan" up
recycle_litespeed_workers
"$RELEASE/scripts/deployment/health-check.sh" "$BASE_URL" "$SHA" "$CURRENT" "$PHP_BIN"
trap - ERR

RELEASE_LIST="$(mktemp)"
find "$RELEASES" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' | sort -nr | cut -d' ' -f2- > "$RELEASE_LIST"
release_number=0
while IFS= read -r old_release; do
    release_number=$((release_number + 1))
    if (( release_number > 5 )); then
        [[ "$(readlink -f "$CURRENT")" == "$(readlink -f "$old_release")" ]] || rm -rf -- "$old_release"
    fi
done < "$RELEASE_LIST"
rm -f "$RELEASE_LIST"

rm -f "$ARCHIVE"
echo "Deployment completed for $SHA."
