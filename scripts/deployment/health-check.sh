#!/usr/bin/env bash
set -Eeuo pipefail

BASE_URL="${1:?Usage: health-check.sh <base-url> <expected-sha> <current-link> [php-bin]}"
EXPECTED_SHA="${2:?Usage: health-check.sh <base-url> <expected-sha> <current-link> [php-bin]}"
CURRENT_LINK="${3:?Usage: health-check.sh <base-url> <expected-sha> <current-link> [php-bin]}"
PHP_BIN="${4:-/opt/alt/php84/usr/bin/php}"
BASE_URL="${BASE_URL%/}"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

request_200() {
    local url="$1"
    local status
    status="$(curl --silent --show-error --output /dev/null --max-time 30 --write-out '%{http_code}' "$url")"
    [[ "$status" == "200" ]] || { echo "Health check failed: $url returned $status." >&2; return 1; }
}

fresh_request() {
    curl --fail --silent --show-error --max-time 30 \
        --header 'Cache-Control: no-cache' \
        --header 'Pragma: no-cache' \
        "$1"
}

ACTIVE_RELEASE="$(readlink -f "$CURRENT_LINK")"
test -f "$ACTIVE_RELEASE/REVISION" || { echo "Active release has no REVISION file." >&2; exit 1; }
ACTUAL_SHA="$(tr -d '[:space:]' < "$ACTIVE_RELEASE/REVISION")"
[[ "$ACTUAL_SHA" == "$EXPECTED_SHA" ]] || {
    echo "Revision mismatch: expected $EXPECTED_SHA, found $ACTUAL_SHA." >&2
    exit 1
}

RUNTIME_READY=0
for attempt in {1..15}; do
    if HEALTH_JSON="$(fresh_request "$BASE_URL/up?deployment_revision=$EXPECTED_SHA&attempt=$attempt" 2>/dev/null)"; then
        if RUNTIME_SHA="$(printf '%s' "$HEALTH_JSON" | "$PHP_BIN" -r '
            $payload = json_decode(stream_get_contents(STDIN), true);
            if (! is_array($payload) || ($payload["status"] ?? null) !== "up" || ! is_string($payload["revision"] ?? null)) {
                exit(1);
            }
            echo trim($payload["revision"]);
        ')" && [[ "$RUNTIME_SHA" == "$EXPECTED_SHA" ]]; then
            RUNTIME_READY=1
            break
        fi
    fi

    sleep 2
done

[[ "$RUNTIME_READY" == "1" ]] || {
    echo "Runtime revision did not switch to $EXPECTED_SHA after recycling PHP workers." >&2
    exit 1
}

MANIFEST="$ACTIVE_RELEASE/public/build/manifest.json"
test -f "$MANIFEST" || { echo "Active release has no Vite manifest." >&2; exit 1; }

EXPECTED_ASSETS="$WORK/expected-assets"
"$PHP_BIN" -r '
    $manifest = json_decode(file_get_contents($argv[1]), true);
    $entry = $manifest["resources/js/app.jsx"] ?? null;
    if (! is_array($entry) || ! is_string($entry["file"] ?? null)) {
        fwrite(STDERR, "Vite app entry is missing.\n");
        exit(1);
    }
    echo $entry["file"].PHP_EOL;
    foreach ($entry["css"] ?? [] as $asset) {
        if (is_string($asset)) echo $asset.PHP_EOL;
    }
' "$MANIFEST" > "$EXPECTED_ASSETS"
test "$(wc -l < "$EXPECTED_ASSETS")" -ge 2 || { echo "Vite app entry needs JavaScript and CSS assets." >&2; exit 1; }

LIVE_HTML="$WORK/live-home.html"
fresh_request "$BASE_URL/?deployment_revision=$EXPECTED_SHA" > "$LIVE_HTML"

while IFS= read -r asset; do
    grep -Fq "/build/$asset" "$LIVE_HTML" || {
        echo "Live HTML does not reference expected Vite asset /build/$asset." >&2
        exit 1
    }
done < "$EXPECTED_ASSETS"

LIVE_ASSETS="$WORK/live-assets"
"$PHP_BIN" -r '
    $html = file_get_contents($argv[1]);
    preg_match_all("~(?:src|href)=\"([^\"]*/build/assets/[^\"]+)\"~", $html, $matches);
    foreach (array_values(array_unique($matches[1] ?? [])) as $asset) {
        echo html_entity_decode($asset, ENT_QUOTES | ENT_HTML5).PHP_EOL;
    }
' "$LIVE_HTML" > "$LIVE_ASSETS"
test -s "$LIVE_ASSETS" || { echo "Live HTML contains no Vite assets." >&2; exit 1; }

while IFS= read -r asset; do
    case "$asset" in
        http://*|https://*) asset_url="$asset" ;;
        /*) asset_url="$BASE_URL$asset" ;;
        *) asset_url="$BASE_URL/$asset" ;;
    esac
    request_200 "$asset_url"
done < "$LIVE_ASSETS"

request_200 "$BASE_URL/"
request_200 "$BASE_URL/robots.txt"
request_200 "$BASE_URL/admin/login"

SITEMAP="$WORK/sitemap.xml"
SAMPLE_URL_FILE="$WORK/sample-urls"
fresh_request "$BASE_URL/sitemap.xml" > "$SITEMAP"

"$PHP_BIN" -r '
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($argv[1]);
    if ($xml === false) { fwrite(STDERR, "Invalid sitemap XML.\n"); exit(1); }
    $locations = [];
    foreach ($xml->url as $entry) {
        $raw = (string) $entry->loc;
        if ($raw === "" || $raw !== trim($raw) || str_contains($raw, " ") || str_contains($raw, "%20")) {
            fwrite(STDERR, "Malformed sitemap location: ".json_encode($raw)."\n"); exit(1);
        }
        $locations[] = $raw;
    }
    if (count($locations) < 3) { fwrite(STDERR, "Sitemap contains fewer than three URLs.\n"); exit(1); }
    $route = null; $news = null;
    foreach ($locations as $location) {
        $path = parse_url($location, PHP_URL_PATH) ?: "";
        if ($route === null && preg_match("#^/routes/[^/]+$#", $path)) { $route = $location; }
        if ($news === null && preg_match("#^/news/[^/]+$#", $path)) { $news = $location; }
    }
    if ($route === null || $news === null) { fwrite(STDERR, "Sitemap needs a route and news detail URL.\n"); exit(1); }
    echo $route."\n".$news."\n";
' "$SITEMAP" > "$SAMPLE_URL_FILE"

while IFS= read -r url; do
    request_200 "$url"
done < "$SAMPLE_URL_FILE"

echo "Production runtime, Vite assets, and public pages passed for $EXPECTED_SHA."
