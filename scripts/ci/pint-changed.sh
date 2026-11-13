#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

if [[ -n "${GITHUB_BASE_REF:-}" ]]; then
    git fetch --no-tags --depth=1 origin "$GITHUB_BASE_REF"
    BASE="$(git merge-base HEAD "origin/$GITHUB_BASE_REF")"
elif [[ -n "${GITHUB_EVENT_BEFORE:-}" && "$GITHUB_EVENT_BEFORE" != "0000000000000000000000000000000000000000" ]]; then
    BASE="$GITHUB_EVENT_BEFORE"
else
    BASE="HEAD^"
fi

mapfile -t FILES < <(git diff --name-only --diff-filter=ACMRT "$BASE" HEAD -- '*.php')

if (( ${#FILES[@]} == 0 )); then
    echo "No changed PHP files require Pint."
    exit 0
fi

if ! vendor/bin/pint --test "${FILES[@]}"; then
    # Show the exact required formatting in the disposable CI checkout. Keep
    # the check failed; never commit or publish formatter changes from CI.
    vendor/bin/pint "${FILES[@]}"
    git diff -- "${FILES[@]}"
    exit 1
fi
