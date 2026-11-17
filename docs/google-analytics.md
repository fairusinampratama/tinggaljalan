# Google Analytics and Ads: advanced consent mode

The shared Google tag uses advanced consent mode. A synchronous bootstrap at the
start of the public `<head>` queues denied defaults for `ad_storage`,
`analytics_storage`, `ad_user_data` and `ad_personalization` before destination
configuration. A single static async script follows it, using GA4
`G-1L9N8XTME6` when enabled, otherwise the configured Ads ID. Config commands for
both enabled products are queued once per document. A loaded Google library may
fetch its own destination resources; those are not extra application loaders.

Before a choice, after Decline, and after withdrawal, the consent-aware Google
library still loads and may send cookieless measurements. It must respect denied
storage consent. Accept queues granted updates on the current page without
reloading or reconfiguring the destinations. Saved acceptance is restored before
configuration; the existing `tinggaljalan-consent-v1` key is preserved. Existing
analytics/advertising cookies are not actively erased by this implementation;
withdrawal sends denied updates. Verify real cookie behavior with Google tools.

GTM remains separately blocked until acceptance because its published contents
are not controlled by this code. See `google-tag-manager.md`. No public tracking
bootstrap is included in Filament admin.

`GOOGLE_ANALYTICS_ENABLED` defaults true only in production and independently
disables Analytics. `GOOGLE_ANALYTICS_ID` overrides the measurement ID. Ads retains
its existing enable flag and ID. Keep all live tracking disabled in local and
protected preview environments; CI enables it with mocked Google scripts.

No manual SPA page views, purchases, bookings, or conversion events are added.
Virtual page views depend on each destination's Enhanced Measurement history
settings. Verify navigation, event counts and receipt for each intended property;
a queued config or successful script response is not proof of receipt.

The Google tag may have multiple aliases/destinations configured in Google. Do
not add duplicate destination configurations in GTM or change existing account
links without reviewing them with the owner. Ads conversion-rule corrections are
outside this release. The older destination's observed SPA gap remains unverified
until its settings and real navigation events can be checked.

## Validation and release

`npm run test:tracking` exercises the actual inline bootstrap across seven enabled
product combinations and unavailable storage. PHP tests verify the rendered
static loader and default-consent ordering. Mocked browser tests cover initial
loading, denied defaults, decline, grant, saved choices, withdrawal, single
loaders, navigation and settings access. Mocked tests do not prove real Google
cookie handling, data receipt, or an installation-detector pass.

Follow branch → CI → preview approval → preview verification → production
approval → production verification. Keep preview protected; use isolated test
measurement or mocked tags rather than production conversions in preview.

After approved production deployment, run the exact Analytics installation Test,
Tag Assistant consent/hit checks, and receiving-account checks. The standard
static script addresses discoverability of the loader, but a Google detector
pass remains unverified until that real test succeeds. Never claim success from
source inspection or CI alone.

Rollback: revert this PR's bootstrap and privacy-copy changes together to restore
basic consent loading. No database changes, Google account changes or historical
data deletion are part of this implementation. Saved consent choices survive.
