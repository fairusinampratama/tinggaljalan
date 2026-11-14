# Preview refresh investigation

## Verified baseline

- Repository: `fairusinampratama/tinggaljalan`.
- Main at investigation start: `b2e7d94cc198a067700136920ecd8f3c2c58c688` (PR #62 merged).
- Production runtime: `41ff00f472769c3c16daf10e05c23bcaa75d1fc4`; the existing full production smoke test passed again during this investigation.
- Last verified preview runtime: `0bbc7f1fd49a0d943d17183318dc7a301a3baddf`. Recheck live identity before acceptance; this is a previous observation, not a newly deployed revision.
- Latest actual refresh: `43560991919`, host job `132344628175`. Failed at **responsive media verification**. In-process database/media restoration succeeded; subsequent workflow rollback reported no pending refresh. Temporary private helpers were removed. This was not a successful refresh.
- Subsequent Hostinger readiness/HTTP run `43561713608` passed. It reported no matching permission error. This does not prove media generation succeeds.
- Required CI on merged main: `43560850319` succeeded.
- No new full refresh has been requested during this investigation. Production deployment is not authorized.

## Confirmed findings

| Evidence | Finding |
| --- | --- |
| Refresh `43465212509`, read-only audit `43527324369`, production admin inspection | One article inline-image path contained a persisted temporary Livewire upload handle. PR #61 sanitizes that runtime value in the preview copy; the source authoring cause is not established. |
| Refresh `43536854773` | MySQL diagnostic 2006 occurred during responsive work. In-process restore reused the failed PDO; separate workflow recovery restored preview. |
| PR #62 and refresh `43560991919` | Closing copy connections during filesystem work and reconnecting for verification/recovery removed the reported disconnect; in-process recovery worked. The next verification failure remains unidentified. |
| Complete public-media inspection in this investigation | 164 distinct public uploaded originals across 39 sitemap routes fully decoded using Pillow: 160 JPEG, 4 PNG, no reported decode failures. This covers the discoverable public set and a different decoder; it does not establish Hostinger GD compatibility or coverage of unpublished database references. |

## Confirmed remaining failure class

PR #63 was approved and merged as `7d821cfa9903265ce651816ed205bc5ff985771f`. Targeted Hostinger run `43568113149`, job `132366392326`, inspected the whole reviewed source media manifest without refreshing either database. It verified 183 uploaded references and identified exactly one `tour_packages.gallery` reference as `unsupported-raster-decoder`. Four static references, six empty values and one known temporary upload handle were also classified. Preview schema was unchanged across generation. Capacity reserve passed; scratch workspace and runner credential cleanup succeeded. Full production smoke passed again at unchanged runtime `41ff00f`.

The header validator accepts a real raster type that the generator's three-case JPEG/PNG/WebP decoder dispatch rejects. The file passed the existing allowed-path/image-header policy but cannot reach successful responsive generation under that dispatch. The initial diagnostic intentionally did not log filenames, paths or type values; the exact stored type and its authoring history are not established. Do not guess that it is GIF, AVIF, BMP or corrupt. The public HTTP/Pillow inspection did not establish the complete on-disk database-reference set.

Current correction branch: `fix/preview-native-raster-decoding`. Use GD's byte-based decoder as a fallback for additional native raster types while preserving explicit JPEG/PNG/WebP paths, the existing filename/path allowlist, canonical-byte hashes and strict WebP/width verification. Reject bytes the installed GD cannot decode. Preserve original bytes; generated derivatives remain still images. The diagnostic will report fixed fallback-format labels, allowing actual host verification to identify the type without exposing the source reference.

**Required execution order after an approved correction merge:** prepare and verify the new application code on preview first, then rerun the targeted diagnostic using that deployed generator. Its implementation intentionally uses the deployed application's generator, so running it against old preview code would repeat the known decoder rejection. Only after that diagnostic verifies every reviewed image should a new full DB/media refresh be dispatched. No production deployment is authorized. The remaining refresh is not yet fixed on the real environment.

## Current diagnostic change

Branch: `fix/preview-responsive-media-verification`.

- Add fixed rule categories and aggregate generation counts to refresh verification; retain the original failure category across recovery. Keep strict image, path, hash and schema checks.
- Explicitly check the generator's preview storage boundary before a refresh.
- Add manual, main-only `public-media-generation-check` to the existing staging preflight workflow. It is a **private scratch generation diagnostic**, not the existing zero-file-write `public-media-audit`.
- Inspect preview schema through query-only connections before and after generation. Read only reviewed production media columns in a read-only consistent snapshot; close both connections before CPU work.
- Copy only allowlisted public originals into a new run-owned private directory under preview `deployments/incoming`. Use the actual deployed preview generator with a process-local scratch storage override, then the same responsive verifier used by refresh. Do not modify running configuration, existing public storage, either database, production files, or integrations.
- Hold the hosting account lock, require the existing read-only capacity reserve check, and refuse pending database/code recovery journals. Never generate while a refresh/publication is pending. The capacity check does not apply retention or delete files.
- Collect all field/category counts in one pass. No paths, filenames, row values, credentials, content hashes or image bytes in reports/artifacts. Known temporary handles are reported as runtime state; no temporary/private upload file is accessed.
- Remove only this run's scratch workspace. Do not clean an existing workspace whose creation was refused. Existing `public-media-audit` remains read-only.

## Test/acceptance gates

1. Validate diagnostic syntax, privacy/query-only tests, actual JPEG/PNG/WebP generation and shared verification against disposable MySQL; retain all source/sanitization/DB-media rollback tests. Test header-only corrupt raster, disguised unsupported format, wrong storage, missing/wrong-format/wrong-width derivatives, batch continuation and output canaries.
2. Run required PHP/frontend/browser CI on the reviewed commit. Record exact run IDs/results and the next action in PR metadata after checks; do not change the tested commit merely to record its own CI result.
3. The diagnostic needs an approved main merge because hosting access is main-only. Do not bypass that environment boundary or run a full refresh to obtain the diagnostic evidence.
4. Run `Staging SSH preflight` on current main with operation `public-media-generation-check`. Preserve fixed aggregate evidence and verify workspace cleanup. Establish/reproduce the exact failed rule before proposing a correction.
5. Consolidate an evidence-backed correction with a regression test. After approved merge, run one fresh full refresh and verify DB separation, public-content fidelity, sanitized private/auth/runtime/payment data, disabled integrations, admin login, media, desktop/mobile/functional/crawler checks and unchanged production health.
6. Only after actual refresh acceptance, prepare a fresh verified preview release. Test the promotion receipt against the production approval hold, then cancel without approving deployment.

## Resume rules

Recheck current GitHub/live state, retain unique unmerged work, and update this record with new evidence. Do not rerun an unchanged failed controller, weaken validation, merge without the specific approval established in this chat, approve production deployment, reseed/reset data, or start unrelated UI work. Keep hypotheses separate from findings. No off-platform sensitive dump or private media artifact is permitted.
