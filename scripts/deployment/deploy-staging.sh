#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="${1:?Usage: deploy-staging.sh <root> <sha> <archive> <sha256> <config-json>}"
SHA="${2:?}"
ARCHIVE="${3:?}"
DIGEST="${4:?}"
CONFIG="${5:?}"
[[ "$ROOT" == /home/u304629909/domains/preview.tinggaljalan.com ]]
[[ "$SHA" =~ ^[0-9a-f]{40}$ && "$DIGEST" =~ ^[0-9a-f]{64}$ ]]
[[ "$(readlink -f "$ROOT")" == "$ROOT" ]]
[[ "$(cat "$ROOT/.tinggaljalan-staging")" == preview.tinggaljalan.com ]]
[[ "$ARCHIVE" == "$ROOT/deployments/incoming/release-$SHA.tar.gz" ]]
[[ "$CONFIG" == "$ROOT/deployments/incoming/config-$SHA.json" ]]
PHP=/opt/alt/php84/usr/bin/php
BASE=https://preview.tinggaljalan.com
DEPLOY="$ROOT/deployments"
SHARED="$DEPLOY/shared"
CURRENT="$DEPLOY/current"
PUBLIC="$ROOT/public_html"
RELEASE="$DEPLOY/releases/$SHA"
for directory in "$PUBLIC" "$DEPLOY" "$DEPLOY/incoming"; do
    [[ -d "$directory" && "$(readlink -f "$directory")" == "$directory" ]]
done
for directory in "$DEPLOY/releases" "$SHARED"; do
    [[ ! -L "$directory" ]]
    mkdir -p "$directory"
    [[ "$(readlink -f "$directory")" == "$directory" ]]
done
for path in "$ARCHIVE" "$CONFIG" "$SHARED/.env" "$SHARED/.htpasswd" "$SHARED/.seeded" \
    "$PUBLIC/robots.txt" "$PUBLIC/index-$SHA.php" "$PUBLIC/.htaccess.next"; do
    [[ ! -L "$path" ]]
done
for directory in "$SHARED/storage" "$SHARED/storage/app" "$SHARED/storage/app/public" \
    "$SHARED/storage/app/public/admin" "$SHARED/storage/app/public/admin/hero" \
    "$SHARED/storage/framework" "$SHARED/storage/framework/cache" "$SHARED/storage/framework/cache/data" \
    "$SHARED/storage/framework/sessions" "$SHARED/storage/framework/views" "$SHARED/storage/logs"; do
    [[ ! -L "$directory" ]]
done
exec 9>"$DEPLOY/.lock"
flock -n 9 || { echo 'Another staging deployment holds the lock.' >&2; exit 1; }
chmod 600 "$CONFIG"
cleanup() {
    rm -f -- "$CONFIG" "$CURL_CONFIG" "$CURL_CONFIG.html" "$CURL_CONFIG.assets"
    if [[ "${COMPLETED:-0}" != 1 && "${CREATED:-0}" == 1 && "$(readlink -f "$CURRENT" || true)" != "$RELEASE" ]]; then
        rm -rf -- "$RELEASE"
    fi
}
CURL_CONFIG="$(mktemp "$DEPLOY/incoming/curl-XXXXXX")"
trap cleanup EXIT
[[ "$(sha256sum "$ARCHIVE" | cut -d' ' -f1)" == "$DIGEST" ]]
gzip -t "$ARCHIVE"
[[ "$(df -Pk "$ROOT" | awk 'NR==2 {print $4}')" -gt 524288 ]]
[[ ! -e "$RELEASE" && ! -L "$RELEASE" ]]
tar -tzf "$ARCHIVE" | "$PHP" -r '
foreach (explode("\n", stream_get_contents(STDIN)) as $path) {
    if (str_starts_with($path, "/") || in_array("..", explode("/", $path), true)) exit(1);
}'
mkdir "$RELEASE"
CREATED=1
tar -xzf "$ARCHIVE" -C "$RELEASE"
[[ "$(cat "$RELEASE/REVISION")" == "$SHA" ]]
test -f "$RELEASE/vendor/autoload.php"
test -f "$RELEASE/public/build/manifest.json"
"$PHP" "$RELEASE/scripts/deployment/configure-staging.php" "$RELEASE" "$ROOT" configure "$CONFIG"
mkdir -p "$SHARED/storage/app/public/admin/hero" "$SHARED/storage/framework/cache/data" \
    "$SHARED/storage/framework/sessions" "$SHARED/storage/framework/views" "$SHARED/storage/logs"
# The web server must traverse to the auth file; private files stay mode 600.
chmod 711 "$DEPLOY" "$SHARED"
ln -s "$SHARED/.env" "$RELEASE/.env"
ln -s "$SHARED/storage" "$RELEASE/storage"
rm -rf -- "$RELEASE/public/storage"
ln -s "$SHARED/storage/app/public" "$RELEASE/public/storage"

# Credentials remain in private files; curl arguments and logs contain no password.
"$PHP" -r '
$input = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
$password = str_replace(["\\", "\""], ["\\\\", "\\\""], $input["review_password"]);
file_put_contents($argv[2], "user = \"reviewer:".$password."\"\n");
chmod($argv[2], 0600);
' "$CONFIG" "$CURL_CONFIG"

PREVIOUS=''
PREVIOUS_ENTRY=index.php
if [[ -L "$CURRENT" ]]; then
    PREVIOUS="$(readlink -f "$CURRENT")"
    [[ "$PREVIOUS" == "$DEPLOY/releases/"* ]]
    PREVIOUS_SHA="$(cat "$PREVIOUS/REVISION")"
    [[ "$PREVIOUS_SHA" =~ ^[0-9a-f]{40}$ ]]
    PREVIOUS_ENTRY="index-$PREVIOUS_SHA.php"
    test -f "$PUBLIC/$PREVIOUS_ENTRY"
elif [[ -e "$CURRENT" ]]; then
    echo 'Unexpected current release path.' >&2; exit 1
else
    printf '<?php http_response_code(503); echo "Preview setup incomplete";\n' > "$PUBLIC/index.php"
fi
# Each revision has a unique PHP entry filename, avoiding stale entry-file opcache.
publish_entry() {
    local entry="$1"
    local entry_pattern="${entry%.php}[.]php"
    cat > "$PUBLIC/.htaccess.next" <<HTACCESS
Options -Indexes -MultiViews
DirectoryIndex $entry
AuthType Basic
AuthName "TinggalJalan Preview"
AuthUserFile "$SHARED/.htpasswd"
Require valid-user
<IfModule mod_headers.c>
    Header always set X-Robots-Tag "noindex, nofollow, noarchive"
    Header always set Cache-Control "private, no-store"
</IfModule>
<FilesMatch "^\\.">
    Require all denied
</FilesMatch>
<FilesMatch "^(?!$entry_pattern\$).*\\.php\$">
    Require all denied
</FilesMatch>
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} !=on
    RewriteRule ^ https://preview.tinggaljalan.com%{REQUEST_URI} [R=302,L]
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    # Route the directory URL explicitly rather than relying on a cached index.
    RewriteRule ^\$ $entry [L]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ $entry [L]
</IfModule>
HTACCESS
mv -f "$PUBLIC/.htaccess.next" "$PUBLIC/.htaccess"
}
# Install access protection before the application can become publicly reachable.
publish_entry "$PREVIOUS_ENTRY"
printf 'User-agent: *\nDisallow: /\n' > "$PUBLIC/robots.txt"
[[ "$(curl --silent --show-error --max-time 30 --output /dev/null --write-out '%{http_code}' "$BASE/")" == 401 ]]

for asset in build images storage js css fonts favicon.ico favicon.png favicon-96x96.png apple-touch-icon.png; do
    if [[ -e "$PUBLIC/$asset" && ! -L "$PUBLIC/$asset" ]]; then
        echo "Unexpected existing public asset: $asset" >&2; false
    fi
done
SWITCHED=0
rollback() {
    echo 'Staging failed; restoring the previous application release.' >&2
    if [[ "$SWITCHED" == 1 && -n "$PREVIOUS" ]]; then
        ln -s "$PREVIOUS" "$CURRENT.rollback"
        mv -Tf "$CURRENT.rollback" "$CURRENT"
        publish_entry "$PREVIOUS_ENTRY"
    elif [[ "$SWITCHED" == 1 ]]; then
        rm -f -- "$CURRENT"
        printf '<?php http_response_code(503); echo "Preview setup incomplete";\n' > "$PUBLIC/index.php"
        publish_entry index.php
    fi
    # Database migrations are not reversed. Use only backward-compatible migrations.
}
trap rollback ERR
"$PHP" "$RELEASE/artisan" migrate --force
"$PHP" "$RELEASE/scripts/deployment/configure-staging.php" "$RELEASE" "$ROOT" initialize "$CONFIG"
for image in hero-bromo.jpg destination-tumpak-sewu.jpg; do
    cp "$RELEASE/public/images/$image" "$SHARED/storage/app/public/admin/hero/$image"
done
"$PHP" "$RELEASE/artisan" images:generate-responsive --missing
"$PHP" "$RELEASE/artisan" config:cache
"$PHP" "$RELEASE/artisan" event:cache
"$PHP" "$RELEASE/artisan" route:cache
"$PHP" "$RELEASE/artisan" view:cache
ln -s "$RELEASE" "$CURRENT.next"
mv -Tf "$CURRENT.next" "$CURRENT"
SWITCHED=1
for asset in build images storage js css fonts favicon.ico favicon.png favicon-96x96.png apple-touch-icon.png; do
    ln -sfn "../deployments/current/public/$asset" "$PUBLIC/$asset"
done
printf "<?php require '%s/public/index.php';\n" "$RELEASE" > "$PUBLIC/index-$SHA.php"
publish_entry "index-$SHA.php"
for path in /up / /admin/login /robots.txt; do
    [[ "$(curl --silent --show-error --max-time 30 --output /dev/null --write-out '%{http_code}' "$BASE$path")" == 401 ]]
done
RUNTIME_READY=0
for attempt in {1..10}; do
    if curl --fail --silent --show-error --max-time 30 --config "$CURL_CONFIG" \
        "$BASE/up?deployment_revision=$SHA&attempt=$attempt" | "$PHP" -r '
        $result=json_decode(stream_get_contents(STDIN),true);
        exit(($result["status"]??null)==="up" && ($result["revision"]??null)===$argv[1] ? 0 : 1);
        ' "$SHA"; then RUNTIME_READY=1; break; fi
    sleep 2
done
[[ "$RUNTIME_READY" == 1 ]]
HOMEPAGE_READY=0
for attempt in {1..5}; do
    if HEADERS="$(curl --fail --silent --show-error --max-time 30 --config "$CURL_CONFIG" -D - -o /dev/null "$BASE/?deployment_revision=$SHA&attempt=$attempt")"; then
        HOMEPAGE_READY=1; break
    fi
    sleep 2
done
[[ "$HOMEPAGE_READY" == 1 ]]
printf '%s\n' "$HEADERS" | grep -iq '^X-Robots-Tag:.*noindex'
curl --fail --silent --show-error --max-time 30 --config "$CURL_CONFIG" -o "$CURL_CONFIG.html" "$BASE/"
"$PHP" -r '
$manifest=json_decode(file_get_contents($argv[1]),true);
$entry=$manifest["resources/js/app.jsx"]??null;
if (!is_array($entry) || !is_string($entry["file"]??null) || empty($entry["css"])) exit(1);
foreach ([$entry["file"], ...$entry["css"]] as $asset) {
    if (!is_string($asset) || !preg_match("~^assets/[a-zA-Z0-9_.-]+$~",$asset)) exit(1);
    echo $asset.PHP_EOL;
}
' "$RELEASE/public/build/manifest.json" > "$CURL_CONFIG.assets"
while IFS= read -r asset; do
    grep -Fq "/build/$asset" "$CURL_CONFIG.html"
    curl --fail --silent --show-error --max-time 30 --config "$CURL_CONFIG" -o /dev/null "$BASE/build/$asset"
    [[ "$(curl --silent --show-error --max-time 30 --output /dev/null --write-out '%{http_code}' "$BASE/build/$asset")" == 401 ]]
done < "$CURL_CONFIG.assets"
for path in / /admin/login /routes /news; do
    curl --fail --silent --show-error --max-time 30 --config "$CURL_CONFIG" -o /dev/null "$BASE$path"
done
for path in /.env /.htpasswd; do
    STATUS="$(curl --silent --show-error --max-time 30 --config "$CURL_CONFIG" -o /dev/null --write-out '%{http_code}' "$BASE$path")"
    [[ "$STATUS" == 403 || "$STATUS" == 404 ]]
done
trap - ERR
COMPLETED=1
rm -f -- "$ARCHIVE"
echo "Protected staging deployment passed for $SHA."
