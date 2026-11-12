# Deployment recovery and bounded maintenance

This change runs through the release-control PR first. It does not require an
application redeployment: after merge, the manual deployment controller uses
these scripts from the selected `main` revision. The existing live application,
database, uploads and environment remain in place.

## Interrupted production deployment

The production script holds the account and site locks before creating a private
`.production-deployment-state` journal in `deployments`. It records the candidate
revision, previous revision and preparation/maintenance phase. It contains no
credentials. The journal is replaced atomically and mode 0600.

An EXIT handler is installed before extraction, cache preparation and backup.
HUP, INT and TERM exit through that handler. On failure, recovery restores only
the code pointer, rebuilds the previous release's caches when maintenance was
attempted, brings it online, and removes only the journal-owned failed candidate.
Shared `.env`, uploaded assets, backups and database content are excluded.

SIGKILL cannot run a handler. The next invocation under the same locks first
reads and validates the pending journal, restores the previous code, and then
retries. This is recovery on the next invocation, not a background watchdog.
If validation fails or an unrelated release has become active, it stops for
investigation. Existing candidates without a matching journal are never deleted
or replaced automatically. An already successful revision is likewise not
silently republished.

Database migrations remain forward-only. A code rollback does not reverse a
migration or recover production database content. Releases must remain compatible
with the preceding schema. Image generation before maintenance can create missing
responsive image variants, as before; recovery never deletes those shared files.

Manual rollback now validates the full revision and canonical paths, refuses to
run over a pending deployment journal, and installs recovery before entering
maintenance. Its catchable signal/error handling restores the previous code.
Manual rollback does not yet have its own SIGKILL journal.

After the existing host-side health gate succeeds, the deployment commits before
housekeeping. Retention failure emits a warning rather than reverting healthy
code. The independent runner smoke test still reports separately and does not
automatically roll back a previously committed release.

## Capacity warning and cleanup

`hosting-maintenance.py maintain --reserve` runs before release upload.
`maintain --alert` is used by the production monitor's capacity job. Both take the
account lock. Healthy capacity is read-only. A complete scan above the warning
threshold or deployment reservation triggers the existing retention policy for
production and preview, then another complete capacity scan.

The policy retains each site's active release and two complete rollback releases,
applies the one-hour grace period, and refuses unknown/private/unsafe release
layouts. It never deletes shared storage, uploaded files, database backups,
database records, or unknown directories. A failed usage scan does not authorize
cleanup. Pending deployment or refresh journals block retention so recovery code
cannot be pruned while an interrupted operation awaits restoration. If retention cannot free enough safe capacity, the command fails and
alerts; it does not broaden the deletion policy.

Automatic retention does not archive expired code artifacts first. Source commits
remain in GitHub; only the active and retained rollback releases are guaranteed
immediately deployable. Use the existing reviewed archive/retention operation when
an additional code artifact archive is needed.

Scheduled GitHub checks remain best-effort. This change does not establish an
independent uptime service or guarantee a fifteen-minute execution interval.

## Validation and remaining work

Disposable filesystem tests exercise early errors, maintenance failures, migration
failure, post-switch health failure, TERM, SIGKILL followed by recovery, safe retry,
unowned candidate preservation, foreign active release refusal and housekeeping
failure. Maintenance tests exercise real retention, lock behavior, incomplete
scans, remeasurement and unresolved capacity. PHP/database behavior is covered by
the normal application suite; these shell fixtures deliberately do not claim to
exercise a live MySQL restore. The additional **Deployment recovery safety** workflow runs the actual Laravel backup command against a fresh MySQL 8.4 service, restores into a second empty disposable database, verifies the migrated application schema, fixture values and foreign keys, and checks failed-dump cleanup. It has no hosting secrets and refuses non-local or existing databases.

Still to implement and verify separately: off-account backup coverage, manual rollback SIGKILL recovery, trusted shared
preview/promotion controller, mandatory preview evidence for production, PR #29
refresh integration, independent uptime alerts, and obsolete workflow retirement.

The **Check production approval gate** manual workflow requests production approval but never checks out code, references secrets, or contacts hosting. Use it to verify a waiting approval without risking an application publication; cancel the waiting run after inspection.
