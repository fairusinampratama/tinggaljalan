# Shared Hostinger capacity and release retention

Production and preview use different databases and application directories but share
one hosting account's file/storage quota. On October 17 and November 12, 2026,
Laravel session creation failed with `Disk quota exceeded`. MySQL remained healthy.
Global `df` capacity did not describe the account limit. The November incident was
resolved by archiving and removing an inactive preview dependency copy.

## Policy

`scripts/deployment/hosting-maintenance.py` is a Python 3 standard-library tool (compatible with the host's Python 3.6). It
never boots Laravel, reads environment contents, queries databases, sends messages,
changes integrations, or removes shared uploads/configuration/backups.

- Each environment retains its active release plus **two complete rollback releases**.
  The immediately previous release is pinned after deployment, even when timestamps
  would otherwise select a different release. Incomplete dependency copies do not
  count as rollbacks. Releases less than one hour old receive a grace period.
- Only direct, canonical, full-SHA release directories with matching `REVISION`
  files and expected shared `.env` / storage symlinks are eligible. Unexpected local
  mutable data, backup files, special files, or symlink release roots require manual
  review. Symlinks inside a removed release are unlinked, not followed.
- Cleanup runs only after the existing environment's HTTP/assets/isolation checks
  succeed. A cleanup failure marks the workflow failed but does not roll back a
  healthy publication. A dry-run and explicit manual cleanup remain available.
- A host account lock and environment deployment lock prevent new deployments,
  rollback, and retention from overlapping. Older branches must incorporate these
  changes before deployment; historical workflow copies cannot enforce new rules.

## Capacity budgets and monitoring

The tool measures the **whole account**, including both sites and other account
directories, with `du -sx --inodes` and `du -sx --block-size=1`. Symlink targets and
other mounted filesystems are not followed. A partial/failed scan fails closed.

The initial operational budgets are **350,000 entries** and **10 GiB allocated**.
These are conservative application safety budgets, **not verified Hostinger plan
limits**. The observed account count after November recovery was 393,022; adding
17,576 removed dependency entries explains the failure near 410,598 entries, but
this does not establish the provider's exact hard quota. Confirm plan limits in
hPanel and lower these budgets if necessary. Never increase them merely to bypass
a blocked deployment.

Before uploading, CI validates that the archive fits a **25,000-entry / 512 MiB**
reservation and checks projected account usage. The host checks again while holding
the account lock before extracting. A further 512 MiB stays available for runtime
writes. Archive growth beyond the reservation requires a deliberate policy review.

The existing production monitor also checks account usage. Reaching **80%** of
either safety budget fails the capacity job, producing a GitHub Actions annotation,
summary, and the account's configured workflow-failure notifications. An SSH failure
also fails the check; the log distinguishes it from an actual usage warning. The
public HTTP smoke job remains independent. No email/SMS destination or credentials
are added. GitHub scheduled runs can be delayed, so this is not a guaranteed
15-minute external uptime/capacity service.

## Manual maintenance without application deployment

Use **Staging SSH preflight** with the selected, reviewed revision:

| Operation | Behavior |
| --- | --- |
| `hosting-capacity` | Read-only account usage and warning check |
| `retention-plan` | Production smoke test; list keep/remove decisions for both environments; smoke again |
| `retention-apply` | Production smoke test; apply bounded retention to both environments; smoke again; verify capacity for another release |

Inspect the plan before applying. Require at least the active release and available
two complete rollbacks to be retained. Unknown directories are kept for manual
review. Cleanup deliberately does not touch legacy application directories,
incoming archives, shared database backups, private/public media, sessions or logs.

Routine retention permanently removes expired immutable application releases;
rollback remains available for the retained two. The manual apply operation first streams immutable expired-release recovery archives
to the runner, verifies them, and uploads them with 90-day retention. It then requires
the exact archived plan digest before removing anything. These code/build archives
exclude all symlinks and runtime/private state. Archives must exclude `.env`, bootstrap config
cache, shared storage, logs, sessions, and database content. GitHub artifact retention
is finite and is not a long-term backup strategy.

If capacity still grows after retention, inspect other account directories and
provider usage. Do not delete customer content or database backups to satisfy a
budget. Moving preview to a separate hosting account provides stronger isolation.

## Validation

Run `python3 tests/deployment/test_hosting_maintenance.py` and the existing deployment
regressions. Tests exercise real temporary trees and real flock contention, active
pointer changes, incomplete releases, shared data preservation, symlink traversal,
unexpected private state, grace periods, archive sizing, and both capacity budgets.
