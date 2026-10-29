#!/usr/bin/env bash
set -Eeuo pipefail
mode="${1:-inspect}"
[[ "$mode" == inspect || "$mode" == apply ]]
prod=/home/u304629909/domains/tinggaljalan.com/deployments
preview=/home/u304629909/domains/preview.tinggaljalan.com/deployments
prod_sha=2ebe7063298c7657955f9640c5aedda83f56d446
preview_sha=98cc58bcdf404d861a1b4fd9c42f427cbae76935
targets=(501eb36e3fdc2e7f2b4af15f60125d1302d44cc8 d19c49f43fab68f596a79391904f3308572b39a0)
archive="$prod/incoming/release-e2a7140e5f2c2b34b03b8764b7f234fb422aaa6d.tar.gz"
assert_active() {
 [[ "$(readlink -f "$prod/current")" == "$prod/releases/$prod_sha" ]]
 [[ "$(readlink -f "$preview/current")" == "$preview/releases/$preview_sha" ]]
 [[ "$(cat "$prod/current/REVISION")" == "$prod_sha" ]]
 [[ "$(cat "$preview/current/REVISION")" == "$preview_sha" ]]
}
assert_active
for root in "$prod" "$preview"; do
 [[ "$(readlink -f "$root")" == "$root" ]]
 [[ ! -L "$root/releases" ]]
done
[[ -f "$archive" && ! -L "$archive" ]]
for sha in "${targets[@]}"; do
 target="$preview/releases/$sha"
 [[ -d "$target" && ! -L "$target" ]]
 [[ "$(readlink -f "$target")" == "$target" ]]
 [[ "$(cat "$target/REVISION")" == "$sha" ]]
 for pair in ".env:$preview/shared/.env" "storage:$preview/shared/storage" "public/storage:$preview/shared/storage/app/public"; do
   item="${pair%%:*}"; expected="${pair#*:}"
   [[ -L "$target/$item" && "$(readlink -f "$target/$item")" == "$expected" ]]
 done
 printf 'REVIEWED_TARGET=%s KIB=%s INODES=%s\n' "$sha" "$(du -sk "$target" | cut -f1)" "$(du --inodes -s "$target" | cut -f1)"
done
oldest="$(find "$preview/releases" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %f\n' | sort -n | head -2 | cut -d' ' -f2)"
[[ "$oldest" == "$(printf '%s\n' "${targets[@]}")" ]]
printf 'MODE=%s\n' "$mode"
[[ "$mode" == apply ]] || exit 0
fingerprint_media() {
 for root in "$prod" "$preview"; do
   (cd "$root/shared/storage/app/public"; find . -type f -print0 | sort -z | xargs -0 -r sha256sum)
 done | sha256sum | cut -d' ' -f1
}
retained() {
 find "$prod/releases" "$preview/releases" -mindepth 1 -maxdepth 1 -type d \
   ! -path "$preview/releases/${targets[0]}" ! -path "$preview/releases/${targets[1]}" -print | sort
}
before_media="$(fingerprint_media)"
before_env="$(sha256sum "$prod/shared/.env" "$preview/shared/.env")"
before_archive="$(sha256sum "$archive")"
before_retained="$(retained)"
assert_active
for sha in "${targets[@]}"; do rm -rf --one-file-system -- "$preview/releases/$sha"; done
assert_active
[[ "$(fingerprint_media)" == "$before_media" ]]
[[ "$(sha256sum "$prod/shared/.env" "$preview/shared/.env")" == "$before_env" ]]
[[ "$(sha256sum "$archive")" == "$before_archive" ]]
[[ "$(retained)" == "$before_retained" ]]
for sha in "${targets[@]}"; do [[ ! -e "$preview/releases/$sha" ]]; done
echo 'Verified: both active releases, remaining code copies, environments, uploaded media and production release archive unchanged.'
