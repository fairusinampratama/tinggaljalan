#!/usr/bin/env bash
set -Eeuo pipefail

MODE="${1:?Usage: staging-preflight.sh <connection|readiness> [staging-root]}"
ROOT="${2:-}"
[[ "$MODE" == connection || "$MODE" == readiness ]] || {
    echo 'Unknown preflight operation.' >&2; exit 1;
}
if [[ "$MODE" == readiness ]]; then
    [[ "$ROOT" =~ ^/home/[a-zA-Z0-9_-]+/domains/preview[.]tinggaljalan[.]com$ ]] || {
        echo 'Refusing an unexpected staging root.' >&2; exit 1;
    }
fi
printf 'SSH authentication succeeded. Checking prerequisites.\n'
PHP=/opt/alt/php84/usr/bin/php
test -x "$PHP"
"$PHP" -r 'exit(version_compare(PHP_VERSION, "8.4.1", ">=") ? 0 : 1);'
"$PHP" -r 'foreach (["pdo_mysql", "mbstring", "intl", "dom", "curl", "zip", "gd"] as $extension) { if (!extension_loaded($extension)) { fwrite(STDERR, "Missing extension: $extension\n"); exit(1); } } exit(function_exists("imagewebp") ? 0 : 1);'
for tool in tar gzip curl sha256sum readlink; do command -v "$tool" >/dev/null; done
curl --version | head -n 1
if [[ "$ROOT" == /home/u304629909/domains/preview.tinggaljalan.com ]]; then
    "$PHP" -r '
    $root = $argv[1];
    foreach (["deployments/shared/storage/logs/laravel.log", "public_html/error_log", "error_log"] as $path) {
        $file = $root."/".$path;
        if (!is_file($file)) { echo "No preview log: ".$path.PHP_EOL; continue; }
        $text = substr(file_get_contents($file), -131072);
        echo "Preview log inspected: ".$path.PHP_EOL;
        foreach (["Permission denied", "Failed opening required", "Composer detected issues in your platform", "requires a PHP version", "Vite manifest not found", "could not find driver", "No application encryption key"] as $reason) {
            if (str_contains($text, $reason)) echo "Detected error category: ".$reason.PHP_EOL;
        }
        preg_match_all("~#[0-9]+ ([^\\r\\n]+?\\([0-9]+\\)):~", $text, $frames);
        foreach (array_slice($frames[1], -8) as $frame) echo "Stack location: ".$frame.PHP_EOL;
    }
    ' "$ROOT"
fi
if [[ "$MODE" == connection ]]; then
    echo 'SSH and runtime checks passed. Staging provisioning was not checked.'
    exit 0
fi
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
