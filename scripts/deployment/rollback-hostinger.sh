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

    local worker_pids
    worker_pids="$(pgrep -u "$(id -u)" -x lsphp || true)"
    [[ -n "$worker_pids" ]] || return 0

    kill $worker_pids >/dev/null 2>&1 || true
}

[[ "$TARGET_SHA" =~ ^[0-9a-f]{40}$ ]] || { echo 'A full rollback revision is required.' >&2; exit 1; }
[[ "$DOMAIN_ROOT" == /home/u304629909/domains/tinggaljalan.com ]]
[[ "$(readlink -f "$DOMAIN_ROOT")" == "$DOMAIN_ROOT" && "$(readlink -f "$DEPLOY_ROOT")" == "$DEPLOY_ROOT" ]]
[[ "$(readlink -f "$TARGET")" == "$TARGET" && ! -L "$TARGET" ]]
[[ ! -e "$DEPLOY_ROOT/.production-deployment-state" && ! -L "$DEPLOY_ROOT/.production-deployment-state" ]] || { echo 'Resolve the interrupted deployment before manual rollback.' >&2; exit 1; }
[[ ! -L /home/u304629909/.tinggaljalan-maintenance.lock && ! -L "$DEPLOY_ROOT/.lock" ]]
exec 8>/home/u304629909/.tinggaljalan-maintenance.lock
flock -n 8 || { echo 'Hosting maintenance/deployment already running.' >&2; exit 1; }
exec 9>"$DEPLOY_ROOT/.lock"
flock -n 9 || exit 1
test -f "$TARGET/vendor/autoload.php" || { echo 'Rollback dependencies are missing.' >&2; exit 1; }
test -L "$CURRENT" || { echo "Current release symlink is missing." >&2; exit 1; }
test -d "$TARGET" && test -f "$TARGET/REVISION" || { echo "Target release does not exist." >&2; exit 1; }
test "$(tr -d '[:space:]' < "$TARGET/REVISION")" == "$TARGET_SHA" || { echo "Target revision mismatch." >&2; exit 1; }

PREVIOUS="$(readlink -f "$CURRENT")"
[[ "$(dirname "$PREVIOUS")" == "$DEPLOY_ROOT/releases" && ! -L "$PREVIOUS" ]]
[[ "$(basename "$PREVIOUS")" =~ ^[0-9a-f]{40}$ ]]
[[ ! -e "$CURRENT.rollback" && ! -L "$CURRENT.rollback" ]]
SWITCHED=0
rollback_on_error() {
    if [[ "$(readlink -f "$CURRENT")" == "$TARGET" ]]; then
        rm -f "$CURRENT.rollback"
        ln -s "$PREVIOUS" "$CURRENT.rollback"
        mv -Tf "$CURRENT.rollback" "$CURRENT"
    fi
    "$PHP_BIN" "$PREVIOUS/artisan" up >/dev/null 2>&1 || true
    recycle_litespeed_workers
}
finish_rollback() {
    local status=$?
    trap - EXIT HUP INT TERM
    if [[ "$status" != 0 ]]; then rollback_on_error; fi
    exit "$status"
}
trap finish_rollback EXIT
trap 'exit 129' HUP
trap 'exit 130' INT
trap 'exit 143' TERM
# Recovery must be installed before down can create a maintenance marker.
"$PHP_BIN" "$PREVIOUS/artisan" down --retry=60

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
trap - EXIT HUP INT TERM

echo "Rolled back code to $TARGET_SHA. Database migrations were not reversed."
