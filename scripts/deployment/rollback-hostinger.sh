#!/usr/bin/env bash
set -Eeuo pipefail

DOMAIN_ROOT="${1:?Usage: rollback-hostinger.sh <domain-root> <target-sha> <base-url>}"
TARGET_SHA="${2:?Usage: rollback-hostinger.sh <domain-root> <target-sha> <base-url>}"
BASE_URL="${3:?Usage: rollback-hostinger.sh <domain-root> <target-sha> <base-url>}"
DEPLOY_ROOT="$DOMAIN_ROOT/deployments"
CURRENT="$DEPLOY_ROOT/current"
TARGET="$DEPLOY_ROOT/releases/$TARGET_SHA"
PHP_BIN="/opt/alt/php84/usr/bin/php"

recycle_litespeed_workers() {
    command -v pgrep >/dev/null || return 0

    local worker_pids=()
    mapfile -t worker_pids < <(pgrep -u "$(id -u)" -x lsphp || true)
    ((${#worker_pids[@]})) || return 0

    kill "${worker_pids[@]}" >/dev/null 2>&1 || true
}

test -L "$CURRENT" || { echo "Current release symlink is missing." >&2; exit 1; }
test -d "$TARGET" && test -f "$TARGET/REVISION" || { echo "Target release does not exist." >&2; exit 1; }
test "$(tr -d '[:space:]' < "$TARGET/REVISION")" == "$TARGET_SHA" || { echo "Target revision mismatch." >&2; exit 1; }

PREVIOUS="$(readlink -f "$CURRENT")"
"$PHP_BIN" "$PREVIOUS/artisan" down --retry=60
SWITCHED=0
rollback_on_error() {
    if [[ "$SWITCHED" == "1" ]]; then
        rm -f "$CURRENT.rollback"
        ln -s "$PREVIOUS" "$CURRENT.rollback"
        mv -Tf "$CURRENT.rollback" "$CURRENT"
    fi
    "$PHP_BIN" "$PREVIOUS/artisan" up >/dev/null 2>&1 || true
    recycle_litespeed_workers
}
trap rollback_on_error ERR

"$PHP_BIN" "$TARGET/artisan" optimize:clear
"$PHP_BIN" "$TARGET/artisan" config:cache
"$PHP_BIN" "$TARGET/artisan" event:cache
"$PHP_BIN" "$TARGET/artisan" route:cache
"$PHP_BIN" "$TARGET/artisan" view:cache
rm -f "$CURRENT.rollback"
ln -s "$TARGET" "$CURRENT.rollback"
mv -Tf "$CURRENT.rollback" "$CURRENT"
SWITCHED=1
"$PHP_BIN" "$TARGET/artisan" up
recycle_litespeed_workers
"$TARGET/scripts/deployment/health-check.sh" "$BASE_URL" "$TARGET_SHA" "$CURRENT" "$PHP_BIN"
trap - ERR

echo "Rolled back code to $TARGET_SHA. Database migrations were not reversed."
