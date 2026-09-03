#!/usr/bin/env bash
set -Eeuo pipefail

BASE_URL="${1:?Usage: seo-smoke-test.sh <base-url> [php-bin] [cache-bust]}"
PHP_BIN="${2:-php}"
CACHE_BUST="${3:-}"
BASE_URL="${BASE_URL%/}"
CANONICAL_URL="${SEO_CANONICAL_URL:-$BASE_URL}"
CANONICAL_URL="${CANONICAL_URL%/}"
WORK="$(mktemp -d)"
GOOGLEBOT='Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
GOOGLEBOT_IMAGE='Googlebot-Image/1.0'
trap 'rm -rf "$WORK"' EXIT

fail() {
    echo "SEO smoke test failed: $*" >&2
    exit 1
}

fresh_url() {
    local url="$1"

    if [[ -n "$CACHE_BUST" ]]; then
        printf '%s?deployment_revision=%s' "$url" "$CACHE_BUST"
    else
        printf '%s' "$url"
    fi
}

fetch() {
    local url="$1"
    local user_agent="$2"
    local name="$3"
    local metrics status elapsed size

    metrics="$(curl --silent --show-error --max-time 30 \
        --user-agent "$user_agent" \
        --header 'Cache-Control: no-cache' \
        --dump-header "$WORK/$name.headers" \
        --output "$WORK/$name.body" \
        --write-out '%{http_code} %{time_total} %{size_download}' \
        "$url")"
    read -r status elapsed size <<< "$metrics"
    [[ "$status" == "200" ]] || fail "$url returned HTTP $status"

    if awk "BEGIN { exit !($elapsed > 0.4) }"; then
        echo "SEO warning: $url responded in ${elapsed}s (target: <= 0.4s)." >&2
    fi
    if (( size > 204800 )); then
        echo "SEO warning: $url returned ${size} bytes (target: <= 204800 bytes)." >&2
    fi
}

header_value() {
    local name="$1"
    local header="$2"

    tr -d '\r' < "$WORK/$name.headers" \
        | awk -v header="$header" 'index(tolower($0), tolower(header) ":") == 1 { sub(/^[^:]+:[[:space:]]*/, ""); value=$0 } END { print value }'
}

assert_public_crawler_headers() {
    local name="$1"
    local cache_control

    grep -Eiq '^set-cookie:' "$WORK/$name.headers" && fail "$name unexpectedly sets cookies"
    cache_control="$(header_value "$name" 'cache-control')"
    [[ "$cache_control" == *public* ]] || fail "$name is not publicly cacheable"
    [[ "$cache_control" != *private* ]] || fail "$name uses private caching"
}

fetch "$(fresh_url "$BASE_URL/robots.txt")" "$GOOGLEBOT" robots
[[ "$(header_value robots content-type)" == text/plain* ]] || fail 'robots.txt has the wrong content type'
grep -Fqx 'Allow: /' "$WORK/robots.body" || fail 'robots.txt does not allow public crawling'
grep -Fqx 'Disallow: /admin' "$WORK/robots.body" || fail 'robots.txt does not block admin'
grep -Fqx 'Disallow: /booking' "$WORK/robots.body" || fail 'robots.txt does not block booking'
grep -Fqx 'Disallow: /checkout/' "$WORK/robots.body" || fail 'robots.txt does not block checkout'
grep -Fqx "Sitemap: $CANONICAL_URL/sitemap.xml" "$WORK/robots.body" || fail 'robots.txt points to the wrong sitemap'
grep -Eiq '^set-cookie:' "$WORK/robots.headers" && fail 'robots.txt unexpectedly sets cookies'

fetch "$(fresh_url "$BASE_URL/sitemap.xml")" "$GOOGLEBOT" sitemap
[[ "$(header_value sitemap content-type)" == application/xml* ]] || fail 'sitemap.xml has the wrong content type'
assert_public_crawler_headers sitemap

SITEMAP_URLS="$WORK/sitemap-urls"
SAMPLE_URLS="$WORK/sample-urls"
"$PHP_BIN" -r '
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($argv[1]);
    if ($xml === false || $xml->getName() !== "urlset") {
        fwrite(STDERR, "Invalid sitemap XML.\n"); exit(1);
    }

    $base = rtrim($argv[2], "/");
    $locations = [];
    $samples = ["route" => null, "news" => null, "about" => null];
    foreach ($xml->url as $entry) {
        $location = trim((string) $entry->loc);
        if ($location === "" || ! str_starts_with($location, $base."/")) {
            fwrite(STDERR, "Non-canonical sitemap URL: $location\n"); exit(1);
        }
        $path = parse_url($location, PHP_URL_PATH) ?: "/";
        if (preg_match("#^/(admin|booking|checkout)(/|$)#", $path)) {
            fwrite(STDERR, "Private sitemap URL: $location\n"); exit(1);
        }
        if (isset($locations[$location])) {
            fwrite(STDERR, "Duplicate sitemap URL: $location\n"); exit(1);
        }
        $lastmod = trim((string) $entry->lastmod);
        if ($lastmod !== "" && strtotime($lastmod) === false) {
            fwrite(STDERR, "Invalid lastmod for $location\n"); exit(1);
        }
        $locations[$location] = true;
        if ($samples["route"] === null && preg_match("#^/routes/[^/]+$#", $path)) $samples["route"] = $location;
        if ($samples["news"] === null && preg_match("#^/news/[^/]+$#", $path)) $samples["news"] = $location;
        if ($path === "/about-us") $samples["about"] = $location;
    }

    foreach ([$base."/", $base."/routes", $base."/news"] as $required) {
        if (! isset($locations[$required])) {
            fwrite(STDERR, "Missing sitemap URL: $required\n"); exit(1);
        }
    }
    if ($samples["route"] === null || $samples["news"] === null) {
        fwrite(STDERR, "Sitemap needs route and news detail URLs.\n"); exit(1);
    }
    file_put_contents($argv[3], implode("\n", array_keys($locations))."\n");
    file_put_contents($argv[4], implode("\n", array_filter([
        $base."/", $base."/routes", $samples["route"],
        $base."/news", $samples["news"], $samples["about"],
    ]))."\n");
' "$WORK/sitemap.body" "$CANONICAL_URL" "$SITEMAP_URLS" "$SAMPLE_URLS" || fail 'sitemap.xml did not pass structural validation'

url_number=0
while IFS= read -r url; do
    url_number=$((url_number + 1))
    request_url="$BASE_URL${url#"$CANONICAL_URL"}"
    fetch "$(fresh_url "$request_url")" "$GOOGLEBOT" "sitemap-url-$url_number"
done < "$SITEMAP_URLS"

sample_number=0
while IFS= read -r url; do
    sample_number=$((sample_number + 1))
    request_url="$BASE_URL${url#"$CANONICAL_URL"}"
    fetch "$(fresh_url "$request_url")" "$GOOGLEBOT" "sample-$sample_number"
    "$PHP_BIN" -r '
        libxml_use_internal_errors(true);
        $document = new DOMDocument();
        if (! $document->loadHTMLFile($argv[1])) { fwrite(STDERR, "Invalid HTML.\n"); exit(1); }
        $xpath = new DOMXPath($document);
        $text = fn (string $query): string => trim((string) $xpath->evaluate("string($query)"));
        if ($text("//title") === "") { fwrite(STDERR, "Missing title.\n"); exit(1); }
        if ($text("//meta[translate(@name, \"ABCDEFGHIJKLMNOPQRSTUVWXYZ\", \"abcdefghijklmnopqrstuvwxyz\")=\"description\"]/@content") === "") {
            fwrite(STDERR, "Missing description.\n"); exit(1);
        }
        $robots = strtolower($text("//meta[translate(@name, \"ABCDEFGHIJKLMNOPQRSTUVWXYZ\", \"abcdefghijklmnopqrstuvwxyz\")=\"robots\"]/@content"));
        if (! str_contains($robots, "index") || str_contains($robots, "noindex")) {
            fwrite(STDERR, "Page is not indexable.\n"); exit(1);
        }
        if ($text("//link[contains(concat(\" \", normalize-space(@rel), \" \"), \" canonical \")]/@href") !== $argv[2]) {
            fwrite(STDERR, "Canonical mismatch.\n"); exit(1);
        }
        $main = $xpath->query("//main[contains(concat(\" \", normalize-space(@class), \" \"), \" server-seo-content \")]");
        if ($main->length !== 1 || $xpath->query(".//h1", $main->item(0))->length !== 1) {
            fwrite(STDERR, "Crawler content needs exactly one H1.\n"); exit(1);
        }
        if (str_word_count($main->item(0)->textContent) < 40) {
            fwrite(STDERR, "Crawler content is too short.\n"); exit(1);
        }
    ' "$WORK/sample-$sample_number.body" "$url" || fail "$url failed public-page SEO validation"
done < "$SAMPLE_URLS"

fetch "$(fresh_url "$BASE_URL/booking")" "$GOOGLEBOT" booking
"$PHP_BIN" -r '
    $html = file_get_contents($argv[1]);
    if (! preg_match("#<meta[^>]+name=\\\"robots\\\"[^>]+content=\\\"noindex,nofollow\\\"#i", $html)) exit(1);
    if (str_contains($html, "<main class=\"server-seo-content\"")) exit(1);
' "$WORK/booking.body" || fail 'booking page indexing controls are incorrect'

grep -Fq '<link rel="icon" href="/favicon.ico" sizes="any">' "$WORK/sample-1.body" || fail 'homepage does not declare favicon.ico'
grep -Fq '<link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">' "$WORK/sample-1.body" || fail 'homepage does not declare the PNG favicon'
grep -Fq '<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">' "$WORK/sample-1.body" || fail 'homepage does not declare the Apple touch icon'

for icon in favicon.ico favicon-96x96.png favicon.png apple-touch-icon.png; do
    icon_name="icon-${icon//./-}"
    fetch "$(fresh_url "$BASE_URL/$icon")" "$GOOGLEBOT_IMAGE" "$icon_name"
    [[ "$(header_value "$icon_name" content-type)" == image/* ]] || fail "$icon has the wrong content type"
    test -s "$WORK/$icon_name.body" || fail "$icon is empty"
done

"$PHP_BIN" -r '
    foreach ([["favicon-96x96.png", 96], ["favicon.png", 512], ["apple-touch-icon.png", 180]] as [$name, $size]) {
        $image = getimagesize($argv[1]."/icon-".str_replace(".", "-", $name).".body");
        if ($image === false || $image[0] !== $size || $image[1] !== $size) {
            fwrite(STDERR, "$name must be ${size}x${size}.\n"); exit(1);
        }
    }
    $ico = file_get_contents($argv[1]."/icon-favicon-ico.body");
    if (strlen($ico) < 100 || substr($ico, 0, 4) !== "\x00\x00\x01\x00") {
        fwrite(STDERR, "favicon.ico is invalid.\n"); exit(1);
    }
' "$WORK" || fail 'favicon files failed image validation'

if [[ "$BASE_URL" == "$CANONICAL_URL" ]]; then
    WWW_URL="${BASE_URL/https:\/\//https:\/\/www.}"
    redirect_metrics="$(curl --silent --show-error --max-time 30 --output /dev/null --write-out '%{http_code} %{redirect_url}' "$WWW_URL/")"
    read -r redirect_status redirect_url <<< "$redirect_metrics"
    [[ "$redirect_status" == "301" && "$redirect_url" == "$BASE_URL/" ]] || fail 'www hostname does not redirect permanently to the canonical host'
fi

echo "Production SEO smoke test passed for $BASE_URL."
