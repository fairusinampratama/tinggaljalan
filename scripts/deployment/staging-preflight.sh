#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="${1:?Usage: staging-preflight.sh <staging-root>}"
[[ "$ROOT" =~ ^/home/[a-zA-Z0-9_-]+/domains/preview[.]tinggaljalan[.]com$ ]] || {
    echo 'Refusing an unexpected staging root.' >&2; exit 1;
}
printf 'SSH authentication succeeded. Checking prerequisites.\n'
PHP=/opt/alt/php84/usr/bin/php
test -x "$PHP"
"$PHP" -r 'exit(version_compare(PHP_VERSION, "8.4.1", ">=") ? 0 : 1);'
"$PHP" -r 'foreach (["pdo_mysql", "mbstring", "intl", "dom", "curl", "zip", "gd"] as $extension) { if (!extension_loaded($extension)) { fwrite(STDERR, "Missing extension: $extension\n"); exit(1); } } exit(function_exists("imagewebp") ? 0 : 1);'
for tool in tar gzip curl sha256sum readlink; do command -v "$tool" >/dev/null; done
if ! test -d "$ROOT"; then
    echo 'SSH works; staging directory has not been provisioned.' >&2; exit 1;
fi
test "$(readlink -f "$ROOT")" = "$ROOT" || {
    echo 'Staging root must not resolve to another directory.' >&2; exit 1;
}
test -f "$ROOT/.tinggaljalan-staging" || {
    echo 'Staging identity marker is missing; deployment remains blocked.' >&2; exit 1;
}
test "$(cat "$ROOT/.tinggaljalan-staging")" = 'preview.tinggaljalan.com'
test -d "$ROOT/public_html"
test "$(df -Pk "$ROOT" | awk 'NR==2 {print $4}')" -gt 524288
printf 'PHP runtime, tools, staging identity and free disk checks passed.\n'
