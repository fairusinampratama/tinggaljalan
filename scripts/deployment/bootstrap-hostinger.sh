#!/usr/bin/env bash
set -Eeuo pipefail

DOMAIN_ROOT="${1:?Usage: bootstrap-hostinger.sh <domain-root>}"
LIVE_PATH="$DOMAIN_ROOT/tinggaljalan-app"
DEPLOY_ROOT="$DOMAIN_ROOT/deployments"
RELEASES="$DEPLOY_ROOT/releases"
SHARED="$DEPLOY_ROOT/shared"
CURRENT="$DEPLOY_ROOT/current"
PHP_BIN="/opt/alt/php84/usr/bin/php"
STAMP="$(date +%Y%m%d-%H%M%S)"
INITIAL_RELEASE="$RELEASES/bootstrap-$STAMP"
LEGACY="$DEPLOY_ROOT/legacy/tinggaljalan-app-$STAMP"

test -x "$PHP_BIN" || { echo "PHP 8.4 is unavailable." >&2; exit 1; }

if test -L "$LIVE_PATH" && test -L "$CURRENT"; then
    echo "Atomic deployment layout is already initialized."
    exit 0
fi

test -d "$LIVE_PATH" || { echo "$LIVE_PATH must be the existing application directory." >&2; exit 1; }
test -f "$LIVE_PATH/.env" || { echo "The live .env file is missing." >&2; exit 1; }
test -d "$LIVE_PATH/storage" || { echo "The live storage directory is missing." >&2; exit 1; }
test ! -e "$CURRENT" || { echo "$CURRENT already exists but is not a valid initialized symlink." >&2; exit 1; }

mkdir -p "$RELEASES" "$SHARED/backups/database" "$(diname "$LEGACY")"
test "$(df -Pk "$DOMAIN_ROOT" | awk 'NR==2 {print $4}')" -gt 524288 || {
    echo "At least 512 MB of free disk space is required for bootstrap." >&2
    exit 1
}

cp -a "$LIVE_PATH" "$INITIAL_RELEASE"
cp -a "$LIVE_PATH/.env" "$SHARED/.env"
chmod 600 "$SHARED/.env"
cp -a "$LIVE_PATH/storage" "$SHARED/storage"

rm -f "$INITIAL_RELEASE/.env"
rm -rf "$INITIAL_RELEASE/storage" "$INITIAL_RELEASE/public/storage"
ln -s "$SHARED/.env" "$INITIAL_RELEASE/.env"
ln -s "$SHARED/storage" "$INITIAL_RELEASE/storage"
ln -s "$SHARED/storage/app/public" "$INITIAL_RELEASE/public/storage"
mkdir -p "$INITIAL_RELEASE/bootstrap/cache"

"$PHP_BIN" "$LIVE_PATH/artisan" down --retry=60

SWITCHED=0
recover() {
    if [[ "$SWITCHED" == "1" ]]; then
        rm -f "$LIVE_PATH" "$CURRENT"
        mv "$LEGACY" "$LIVE_PATH"
    fi
    "$PHP_BIN" "$LIVE_PATH/artisan" up >/dev/null 2>&1 || true
}
trap recover ERR

mv "$LIVE_PATH" "$LEGACY"
SWITCHED=1
ln -s "$INITIAL_RELEASE" "$CURRENT"
ln -s "deployments/current" "$LIVE_PATH"
"$PHP_BIN" "$LIVE_PATH/artisan" up
trap - ERR

echo "Atomic layout initialized. Legacy application retained at $LEGACY."
