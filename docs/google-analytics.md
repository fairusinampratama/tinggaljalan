# Google Analytics 4

Measurement ID: `G-1L9N8XTME6`. The existing consent bootstrap configures GA4
once after acceptance, using the same gtag.js loader and dataLayer as Google Ads.
When GA4 is enabled, the shared loader uses its `G-` ID, matching the supplied
Analytics installation URL. Ads-only installations retain the `AW-` fallback.
Both existing destination config commands remain unchanged. This loader preference
is a controlled diagnostic change, not proof that Google's installation detector
will pass: it still loads only after consent.
Declining or not deciding prevents loading; remembered acceptance loads on a
subsequent document. Revocation updates Google's consent and blocks the loader
on subsequent documents. No GA4 code is included in Filament admin.

`GOOGLE_ANALYTICS_ENABLED` defaults true only for production. Set it false to
disable GA4 independently. `GOOGLE_ANALYTICS_ID` overrides the measurement ID.
Local/preview examples keep it disabled; browser CI uses mocked Google scripts.

No manual SPA page-view, purchase, booking, or other conversion events are added.
The standard config sends an initial page view after acceptance. Inertia virtual
page views depend on GA4's Enhanced Measurement browser-history settings; verify
those settings and actual events in Tag Assistant and Realtime after deployment.
A queued config command or successful script response does not prove receipt.

Do not also configure this measurement ID in GTM unless explicitly migrating it
out of this direct integration: that can cause duplicate page views. Recheck the
published container before release; account-side tags may change independently.
Consent and privacy copy includes analytics in all three existing languages.

Existing consent grants are honored because the existing implementation already
included analytics_storage in the combined choice. No new consent key or banner
workflow is introduced. No credentials are needed to install this public ID.

Validation covers GA4 alone, disabled/invalid IDs, admin exclusion, and browser
consent, repeated grants, shared loading, navigation, remembered grants and
revocation. Follow the existing branch → CI → approval → deployment workflow.

Run `npm run test:tracking` for offline bootstrap coverage across all seven
non-empty Ads/GTM/GA4 combinations, including remembered consent and unavailable
storage. The browser CI runs it before the existing cross-browser suite. Neither
mocked browser tests nor offline tests verify delivery to Google.

After an approved production release, repeat the actual Analytics installation
Test and Tag Assistant checks for each intended destination. Protected preview
cannot establish an external Google detector pass. If detection remains failed,
record that result; do not remove consent gating or add a second Google snippet.
Rollback is reverting the loader preference to `adsId || ga4Id`; no consent key,
Google account settings, or historical data changes are involved.
