#!/usr/bin/env bash
set -Eeuo pipefail

DOMAIN_ROOT="${1:?Usage: rollback-hostinger.sh <domain-root> <target-sha> <base-url>}"
TARGET_SHA="${2:?Usage: rollback-hostinger.sh <domain-root> <target-sha> <base-url>}"
BASE_URL="${3:?Usage: rollback-hostinger.sh <domain-root> <target-sha> <base-url>}"
DEPLOY_ROOT="$DOMAIN_ROOT/deployments"
CURRENT="$DEPLOY_ROOT/current"
TARGET="$DEPLOY_ROOT/releases/$TARGET_SHA"
PHP_BIN="/opt/alt/php84/usr/bin/php"

test -L "$CURRENT" || { echo "Current release symlink is missing." >&2; exit 1; }
test -d "$TARGET" && test -f "$TARGET/REVISION" || { echo "Target release does not exist." >&2; exit 1; }
test "$(tr -d '[:space:]' < "$TARGET/REVISION")" == "$TARGET_SHA" || { echo "Target revision mismatch." >&2; exit 1; }

PREVIOUS="$(readlink -f "$CURRENT")"
"$PHP_BIN" "$PREVIOUS/artisan" down --retry=60
trap '"$PHP_BIN" "$PREVIOUS/artisan" up >/dev/null 2>&1 || true' ERR

"$PHP_BIN" "$TARGET/artisan" optimize:clea
"$PHP_BIN" "$TARGET/artisan" config:cache
"$PHP_BIN" "$TARGET/artisan" event:cache
"$PHP_BIN" "$TARGET/artisan" route:cache
"$PHP_BIN" "$TARGET/artisan" view:cache
rm -f "$CURRENT.rollback"
ln -s "$TARGET" "$CURRENT.rollback"
mv -Tf "$CURRENT.rollback" "$CURRENT"
"$PHP_BIN" "$TARGET/artisan" up
"$TARGET/scripts/deployment/health-check.sh" "$BASE_URL" "$TARGET_SHA" "$CURRENT" "$PHP_BIN"
trap - ERR

echo "Rolled back code to $TARGET_SHA. Database migrations were not reversed."
