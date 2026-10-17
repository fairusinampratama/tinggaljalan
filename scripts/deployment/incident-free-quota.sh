#!/usr/bin/env bash
set -Eeuo pipefail
# Reversible incident maintenance: one archived dependency copy in an inactive PREVIEW release.
ROOT=/home/u304629909/domains/preview.tinggaljalan.com/deployments
SHA=39d30723fe969ad1a9b226fe731e17db40df1964
RELEASE="$ROOT/releases/$SHA"
VENDOR="$RELEASE/vendor"
[[ "$(readlink -f "$ROOT")" == "$ROOT" ]]
[[ "$(readlink -f "$RELEASE")" == "$RELEASE" ]]
[[ "$(readlink -f "$ROOT/current")" != "$RELEASE" ]]
[[ "$(cat "$RELEASE/REVISION")" == "$SHA" ]]
[[ ! -L "$VENDOR" && -d "$VENDOR" && "$(readlink -f "$VENDOR")" == "$VENDOR" ]]
# No .env, application cache, storage, uploads, DB backups or current release is included.
case "${1:?archive or reclaim}" in
  archive)
    tar -czf - -C "$RELEASE" vendor
    ;;
  reclaim)
    [[ "${2:-}" == archived-and-verified ]]
    exec 9>"$ROOT/.lock"
    flock -n 9
    [[ "$(readlink -f "$ROOT/current")" != "$RELEASE" ]]
    printf 'Inactive preview dependency copy before cleanup: '
    du -s --inodes "$VENDOR"
    rm -rf --one-file-system -- "$VENDOR"
    test ! -e "$VENDOR"
    echo 'Removed only the archived dependency copy from inactive preview release 39d3072.'
    ;;
  *) exit 1 ;;
esac
