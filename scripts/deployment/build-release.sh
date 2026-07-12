#!/usr/bin/env bash
set -Eeuo pipefail

SHA="${1:?Usage: build-release.sh <git-sha> <output.tar.gz>}"
OUTPUT="${2:?Usage: build-release.sh <git-sha> <output.tar.gz>}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

[[ "$SHA" =~ ^[0-9a-f]{40}$ ]] || { echo "A full 40-character Git SHA is required." >&2; exit 1; }

OUTPUT="$(realpath -m "$OUTPUT")"
mkdir -p "$(dirname "$OUTPUT")"

test -d "$ROOT/vendor" || { echo "vendor is missing." >&2; exit 1; }
test -f "$ROOT/public/build/manifest.json" || { echo "Vite build manifest is missing." >&2; exit 1; }

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
STAGE="$WORK/release"
mkdir -p "$STAGE"

git -C "$ROOT" archive --format=tar HEAD | tar -xf - -C "$STAGE"

rm -rf \
    "$STAGE/.github" \
    "$STAGE/tests" \
    "$STAGE/docker" \
    "$STAGE/docs" \
    "$STAGE/node_modules" \
    "$STAGE/storage" \
    "$STAGE/bootstrap/cache" \
    "$STAGE/compose.yaml" \
    "$STAGE/compose.production.yaml" \
    "$STAGE/compose.prod-preview.yaml" \
    "$STAGE/phpunit.xml"

cp -a "$ROOT/vendor" "$STAGE/vendor"
mkdir -p "$STAGE/public"
cp -a "$ROOT/public/build" "$STAGE/public/build"
mkdir -p "$STAGE/bootstrap/cache"
printf '%s\n' "$SHA" > "$STAGE/REVISION"

if find "$STAGE" -maxdepth 2 -type f \( -name '.env' -o -name '*.sql' -o -name '*.sql.gz' -o -name '*-release.zip' \) | grep -q .; then
    echo "The release contains a forbidden environment, backup, or archive file." >&2
    exit 1
fi

tar -czf "$OUTPUT" -C "$STAGE" .
test -s "$OUTPUT" || { echo "Release archive is empty." >&2; exit 1; }
echo "$OUTPUT"
