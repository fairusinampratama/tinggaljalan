#!/usr/bin/env bash
set -Eeuo pipefail
root="${1:?production domain root required}"
mode="${2:-inspect}"
[[ "$mode" == inspect || "$mode" == apply ]]
[[ "$root" == /home/u304629909/domains/tinggaljalan.com ]]
[[ "$(readlink -f "$root")" == "$root" ]]
deploy="$root/deployments"
shared="$deploy/shared"
active=2ebe7063298c7657955f9640c5aedda83f56d446
old=f2f2817f8c1e3bd459d64dfb0d86e7b5f7a40658
target="$deploy/releases/$old"
archives=(
 "$deploy/incoming/release-d55f9a35243f2da15e9856c7667518623a198332.tar.gz"
 "$deploy/incoming/release-d2cb8d2fd3d1d098b20255f3521ee0cc55cde570.tar.gz"
)
assert_active() {
 [[ "$(readlink -f "$deploy/current")" == "$deploy/releases/$active" ]]
 [[ "$(readlink -f "$root/tinggaljalan-app")" == "$deploy/releases/$active" ]]
 [[ "$(cat "$deploy/releases/$active/REVISION")" == "$active" ]]
}
assert_active
[[ ! -L "$deploy/releases" && ! -L "$deploy/incoming" ]]
[[ -d "$target" && ! -L "$target" ]]
[[ "$(readlink -f "$target")" == "$target" ]]
[[ "$(cat "$target/REVISION")" == "$old" ]]
for pair in ".env:$shared/.env" "storage:$shared/storage" "public/storage:$shared/storage/app/public"; do
 name="${pair%%:*}"; destination="${pair#*:}"
 [[ -L "$target/$name" && "$(readlink -f "$target/$name")" == "$destination" ]]
done
[[ "$(find "$deploy/releases" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %f\n' | sort -n | head -1 | cut -d' ' -f2)" == "$old" ]]
for archive in "${archives[@]}"; do
 [[ -f "$archive" && ! -L "$archive" ]]
 [[ "$(readlink -f "$archive")" == "$archive" ]]
 revision="${archive##*/release-}"; revision="${revision%.tar.gz}"
 gzip -t "$archive"
 [[ "$(tar -xOf "$archive" ./REVISION)" == "$revision" ]]
done
printf 'ACTIVE_REVISION=%s\n' "$active"
du -sk -- "$target" "${archives[@]}"
printf 'MODE=%s\n' "$mode"
[[ "$mode" == apply ]] || exit 0
media_fingerprint() {
 ( cd "$shared/storage/app/public"; find . -type f -print0 | sort -z | xargs -0 -r sha256sum ) | sha256sum | cut -d' ' -f1
}
env_before="$(sha256sum "$shared/.env")"
media_before="$(media_fingerprint)"
releases_before="$(find "$deploy/releases" -mindepth 1 -maxdepth 1 -type d ! -name "$old" -printf '%f\n' | sort)"
before_kib="$(du -sk "$deploy" | cut -f1)"
assert_active
rm -f -- "${archives[@]}"
rm -rf --one-file-system -- "$target"
assert_active
[[ ! -e "$target" ]]
[[ "$(sha256sum "$shared/.env")" == "$env_before" ]]
[[ "$(media_fingerprint)" == "$media_before" ]]
[[ "$(find "$deploy/releases" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | sort)" == "$releases_before" ]]
after_kib="$(du -sk "$deploy" | cut -f1)"
printf 'RECLAIMED_KIB=%s\n' "$((before_kib-after_kib))"
echo 'Verified: active code, other retained releases, environment and uploaded media unchanged.'
