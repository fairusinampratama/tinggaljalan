#!/usr/bin/env bash
set -Eeuo pipefail

BASE_URL="${1:?Usage: health-check.sh <base-url> <expected-sha> <current-link> [php-bin]}"
EXPECTED_SHA="${2:?Usage: health-check.sh <base-url> <expected-sha> <current-link> [php-bin]}"
CURRENT_LINK="${3:?Usage: health-check.sh <base-url> <expected-sha> <current-link> [php-bin]}"
PHP_BIN="${4:-/opt/alt/php84/usr/bin/php}"
BASE_URL="${BASE_URL%/}"

request_200() {
    local url="$1"
    local status
    status="$(curl --silent --show-error --output /dev/null --max-time 30 --write-out '%{http_code}' "$url")"
    [[ "$status" == "200" ]] || { echo "Health check failed: $url returned $status." >&2; return 1; }
}

request_200 "$BASE_URL/up"
request_200 "$BASE_URL/"
request_200 "$BASE_URL/robots.txt"
request_200 "$BASE_URL/admin/login"

SITEMAP="$(mktemp)"
SAMPLE_URL_FILE="$(mktemp)"
trap 'rm -f "$SITEMAP" "$SAMPLE_URL_FILE"' EXIT
curl --fail --silent --show-error --max-time 30 "$BASE_URL/sitemap.xml" > "$SITEMAP"

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

ACTIVE_RELEASE="$(readlink -f "$CURRENT_LINK")"
test -f "$ACTIVE_RELEASE/REVISION" || { echo "Active release has no REVISION file." >&2; exit 1; }
ACTUAL_SHA="$(tr -d '[:space:]' < "$ACTIVE_RELEASE/REVISION")"
[[ "$ACTUAL_SHA" == "$EXPECTED_SHA" ]] || {
    echo "Revision mismatch: expected $EXPECTED_SHA, found $ACTUAL_SHA." >&2
    exit 1
}

echo "Production health checks passed for $EXPECTED_SHA."
