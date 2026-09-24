#!/usr/bin/env bash
set -Eeuo pipefail

BASE_URL="${1:?Usage: production-smoke-test.sh <base-url> [expected-sha] [diagnostics-dir]}"
EXPECTED_SHA="${2:-}"
DIAGNOSTICS="${3:-$(mktemp -d)}"
BASE_URL="${BASE_URL%/}"
CACHE_BUST="smoke_$(date -u +%s)_${RANDOM}"

mkdir -p "$DIAGNOSTICS"

fail() {
    echo "Production smoke test failed: $*" >&2
    echo "Diagnostics: $DIAGNOSTICS" >&2
    exit 1
}

request() {
    local name="$1"
    local url="$2"
    local body="$DIAGNOSTICS/$name.body"
    local headers="$DIAGNOSTICS/$name.headers"
    local status
    local separator='?'

    [[ "$url" == *\?* ]] && separator='&'
    status="$(curl \
        --silent \
        --show-error \
        --location \
        --max-time 30 \
        --retry 2 \
        --retry-delay 1 \
        --retry-all-errors \
        --header 'Cache-Control: no-cache' \
        --header 'Pragma: no-cache' \
        --dump-header "$headers" \
        --output "$body" \
        --write-out '%{http_code}' \
        "$url${separator}monitor=$CACHE_BUST")" || fail "$url could not be reached"

    printf '%s\n' "$status" > "$DIAGNOSTICS/$name.status"
    [[ "$status" == "200" ]] || fail "$url returned HTTP $status"
}

absolute_url() {
    local url="$1"

    case "$url" in
        http://*|https://*) printf '%s\n' "$url" ;;
        /*) printf '%s%s\n' "$BASE_URL" "$url" ;;
        *) printf '%s/%s\n' "$BASE_URL" "$url" ;;
    esac
}

request up "$BASE_URL/up"

UP_BODY="$DIAGNOSTICS/up.body"
grep -Eq '"status"[[:space:]]*:[[:space:]]*"up"' "$UP_BODY" || fail '/up did not report status=up'
RUNTIME_SHA="$(sed -nE 's/.*"revision"[[:space:]]*:[[:space:]]*"([0-9a-f]{40})".*/\1/p' "$UP_BODY" | head -n 1)"
[[ "$RUNTIME_SHA" =~ ^[0-9a-f]{40}$ ]] || fail '/up did not expose a valid revision'

if [[ -n "$EXPECTED_SHA" && "$RUNTIME_SHA" != "$EXPECTED_SHA" ]]; then
    fail "/up reported $RUNTIME_SHA instead of $EXPECTED_SHA"
fi

request homepage "$BASE_URL/"
request robots "$BASE_URL/robots.txt"
request sitemap "$BASE_URL/sitemap.xml"
request admin-login "$BASE_URL/admin/login"

HOME_BODY="$DIAGNOSTICS/homepage.body"
ASSET_LIST="$DIAGNOSTICS/vite-assets.txt"
grep -Eo '(src|href)="[^"]*/build/assets/[^"]+"' "$HOME_BODY" \
    | sed -E 's/^(src|href)="//; s/"$//; s/&amp;/\&/g' \
    | sort -u > "$ASSET_LIST" || true

test -s "$ASSET_LIST" || fail 'homepage did not reference any Vite assets'
grep -Eq '\.js([?&].*)?$' "$ASSET_LIST" || fail 'homepage did not reference a Vite JavaScript asset'
grep -Eq '\.css([?&].*)?$' "$ASSET_LIST" || fail 'homepage did not reference a Vite CSS asset'

asset_number=0
while IFS= read -r asset; do
    asset_number=$((asset_number + 1))
    request "asset-$asset_number" "$(absolute_url "$asset")"
done < "$ASSET_LIST"

SITEMAP_BODY="$DIAGNOSTICS/sitemap.body"
SITEMAP_URLS="$DIAGNOSTICS/sitemap-urls.txt"
grep -Eo '<loc>[^<]+</loc>' "$SITEMAP_BODY" \
    | sed -E 's#</?loc>##g; s/&amp;/\&/g' > "$SITEMAP_URLS" || true

test -s "$SITEMAP_URLS" || fail 'sitemap did not contain any URLs'
ROUTE_URL="$(grep -E '/routes/[^/?<]+/?$' "$SITEMAP_URLS" | head -n 1 || true)"
NEWS_URL="$(grep -E '/news/[^/?<]+/?$' "$SITEMAP_URLS" | head -n 1 || true)"
[[ -n "$ROUTE_URL" ]] || fail 'sitemap did not contain a route detail URL'
[[ -n "$NEWS_URL" ]] || fail 'sitemap did not contain a news detail URL'

request route-detail "$ROUTE_URL"
request news-detail "$NEWS_URL"

printf '%s\n' "$RUNTIME_SHA" > "$DIAGNOSTICS/observed-revision.txt"
echo "Production smoke test passed for $BASE_URL at revision $RUNTIME_SHA."
