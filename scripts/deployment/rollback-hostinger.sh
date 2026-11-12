#!/usr/bin/env bash
set -Eeuo pipefail

DOMAIN_ROOT="${1:?Usage: rollback-hostinger.sh <domain-root> <target-sha|recover> <base-url>}"
TARGET_SHA="${2:?}"
BASE_URL="${3:?}"
DEPLOY_ROOT="$DOMAIN_ROOT/deployments"
RELEASES="$DEPLOY_ROOT/releases"
CURRENT="$DEPLOY_ROOT/current"
TARGET="$RELEASES/$TARGET_SHA"
STATE="$DEPLOY_ROOT/.production-deployment-state"
PHP_BIN="/opt/alt/php84/usr/bin/php"

recycle_litespeed_workers() {
    command -v pgrep >/dev/null || return 0

    local worker_pids
    worker_pids="$(pgrep -u "$(id -u)" -x lsphp || true)"
    [[ -n "$worker_pids" ]] || return 0

    kill $worker_pids >/dev/null 2>&1 || true
}

[[ "$TARGET_SHA" == recover || "$TARGET_SHA" =~ ^[0-9a-f]{40}$ ]] || { echo 'A full rollback revision or recover is required.' >&2; exit 1; }
[[ "$DOMAIN_ROOT" == /home/u304629909/domains/tinggaljalan.com ]]
[[ "$(readlink -f "$DOMAIN_ROOT")" == "$DOMAIN_ROOT" && "$(readlink -f "$DEPLOY_ROOT")" == "$DEPLOY_ROOT" ]]
[[ "$(readlink -f "$RELEASES")" == "$RELEASES" ]]
[[ ! -L /home/u304629909/.tinggaljalan-maintenance.lock && ! -L "$DEPLOY_ROOT/.lock" ]]
[[ ! -L "$STATE" && ! -L "$STATE.next" ]]
exec 8>/home/u304629909/.tinggaljalan-maintenance.lock
flock -n 8 || { echo 'Hosting maintenance/deployment already running.' >&2; exit 1; }
exec 9>"$DEPLOY_ROOT/.lock"
flock -n 9 || exit 1

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
    recover_pending || { echo 'Pending production operation requires investigation.' >&2; exit 1; }
fi
PREVIOUS="$(readlink -f "$CURRENT")"
[[ -L "$CURRENT" && "$(dirname "$PREVIOUS")" == "$RELEASES" && ! -L "$PREVIOUS" ]]
[[ "$(basename "$PREVIOUS")" =~ ^[0-9a-f]{40}$ ]]
[[ -f "$PREVIOUS/vendor/autoload.php" ]]
if [[ "$TARGET_SHA" == recover ]]; then
    "$PREVIOUS/scripts/deployment/health-check.sh" "$BASE_URL" "$(basename "$PREVIOUS")" "$CURRENT" "$PHP_BIN"
    echo 'Production recovery checked. No database restore or new publication was performed.'
    exit 0
fi
[[ "$(readlink -f "$TARGET")" == "$TARGET" && ! -L "$TARGET" ]]
test -f "$TARGET/vendor/autoload.php" || { echo 'Rollback dependencies are missing.' >&2; exit 1; }
test "$(tr -d '[:space:]' < "$TARGET/REVISION")" == "$TARGET_SHA" || { echo 'Target revision mismatch.' >&2; exit 1; }
if [[ "$PREVIOUS" == "$TARGET" ]]; then
    "$TARGET/scripts/deployment/health-check.sh" "$BASE_URL" "$TARGET_SHA" "$CURRENT" "$PHP_BIN"
    echo 'Target code is already active; verified without publishing.'
    exit 0
fi
[[ ! -e "$CURRENT.rollback" && ! -L "$CURRENT.rollback" ]]
TRANSACTION=0
finish_rollback() {
    local status=$?
    trap - EXIT HUP INT TERM
    if [[ "$TRANSACTION" == 1 ]]; then
        recover_pending || echo 'Recovery incomplete; private production journal retained.' >&2
        [[ "$status" != 0 ]] || status=1
    fi
    exit "$status"
}
trap finish_rollback EXIT
trap 'exit 129' HUP
trap 'exit 130' INT
trap 'exit 143' TERM
(umask 077; printf '%s\n%s\nrollback\n' "$TARGET_SHA" "$(basename "$PREVIOUS")" > "$STATE.next")
mv -Tf "$STATE.next" "$STATE"
TRANSACTION=1
# Journal and recovery are ready before down can create a maintenance marker.
"$PHP_BIN" "$PREVIOUS/artisan" down --retry=60
"$PHP_BIN" "$TARGET/artisan" optimize:clear
"$PHP_BIN" "$TARGET/artisan" config:cache
"$PHP_BIN" "$TARGET/artisan" event:cache
"$PHP_BIN" "$TARGET/artisan" route:cache
"$PHP_BIN" "$TARGET/artisan" view:cache
ln -s "$TARGET" "$CURRENT.rollback"
mv -Tf "$CURRENT.rollback" "$CURRENT"
"$PHP_BIN" "$TARGET/artisan" up
recycle_litespeed_workers
"$TARGET/scripts/deployment/health-check.sh" "$BASE_URL" "$TARGET_SHA" "$CURRENT" "$PHP_BIN"
rm -f -- "$STATE"
TRANSACTION=0
trap - EXIT HUP INT TERM

echo "Rolled back code to $TARGET_SHA. Database migrations were not reversed."
