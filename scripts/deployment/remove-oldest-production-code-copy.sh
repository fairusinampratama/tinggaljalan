#!/usr/bin/env bash
set -Eeuo pipefail
root="${1:?Production domain root required}"
[[ "$root" == /home/*/domains/tinggaljalan.com ]]
[[ "$(readlink -f "$root")" == "$root" ]]
deploy="$root/deployments"
releases="$deploy/releases"
shared="$deploy/shared"
sha=15c1da3aaadb639b83e3d36df4764c37913441be
active=babda3db8b86d95b3dff2fa634988724cd85da56
next=d55f9a35243f2da15e9856c7667518623a198332
target="$releases/$sha"
archive="$deploy/incoming/release-$next.tar.gz"
[[ ! -L "$releases" && "$(readlink -f "$releases")" == "$releases" ]]
[[ "$(readlink -f "$deploy/current")" == "$releases/$active" ]]
[[ "$(readlink -f "$root/tinggaljalan-app")" == "$releases/$active" ]]
[[ "$(cat "$releases/$active/REVISION")" == "$active" ]]
[[ -d "$target" && ! -L "$target" && "$(readlink -f "$target")" == "$target" ]]
[[ "$(cat "$target/REVISION")" == "$sha" ]]
[[ ! -e "$releases/$next" ]]
oldest="$(find "$releases" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %f\n' | sort -n | head -n 1 | cut -d' ' -f2)"
[[ "$oldest" == "$sha" ]]
[[ -L "$target/.env" && -L "$target/storage" && -L "$target/public/storage" ]]
[[ "$(readlink -f "$target/.env")" == "$shared/.env" ]]
[[ "$(readlink -f "$target/storage")" == "$shared/storage" ]]
[[ "$(readlink -f "$target/public/storage")" == "$shared/storage/app/public" ]]
[[ -f "$archive" && ! -L "$archive" ]]
[[ "$(tar -xOf "$archive" ./REVISION | tr -d '[:space:]')" == "$next" ]]
media_digest() {
  find "$shared/storage/app/public" -xdev -type f -print0 | sort -z | xargs -0 -r sha256sum | sha256sum
}
other_releases() {
  find "$releases" -mindepth 1 -maxdepth 1 -type d ! -name "$sha" -printf '%f\n' | sort
}
before_media="$(media_digest)"
before_env="$(sha256sum "$shared/.env")"
before_archive="$(sha256sum "$archive")"
before_others="$(other_releases)"
reclaimed="$(du -sk "$target" | cut -f1)"
[[ "$(readlink -f "$deploy/current")" == "$releases/$active" ]]
rm -rf --one-file-system -- "$target"
[[ ! -e "$target" && ! -L "$target" ]]
[[ "$(readlink -f "$deploy/current")" == "$releases/$active" ]]
[[ "$(readlink -f "$root/tinggaljalan-app")" == "$releases/$active" ]]
[[ "$(sha256sum "$shared/.env")" == "$before_env" ]]
[[ "$(sha256sum "$archive")" == "$before_archive" ]]
[[ "$(media_digest)" == "$before_media" ]]
[[ "$(other_releases)" == "$before_others" ]]
printf 'OLDEST_INACTIVE_CODE_COPY_REMOVED revision=%s reclaimed_KiB=%s\n' "$sha" "$reclaimed"
echo ACTIVE_RELEASE_OTHER_RELEASES_CONFIG_MEDIA_AND_REVIEWED_RELEASE_ARCHIVE_PRESERVED
