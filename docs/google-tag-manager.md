# Google Tag Manager

Container: `GTM-58KNQXXZ`. The existing head consent bootstrap loads this
container once after acceptance, including remembered acceptance. It shares the
existing dataLayer and consent controls with the direct Google Ads integration.
It is not injected into Filament. Inertia navigation does not reload the container.
No custom page-view, booking, conversion, or ecommerce events are added here.

`GOOGLE_TAG_MANAGER_ENABLED` defaults to true only in production;
`GOOGLE_TAG_MANAGER_ID` defaults to the container above. Explicitly keep it false
in preview/local environments. The browser test environment enables it with mocked
requests. Set the flag false to disable GTM independently of Google Ads.

The supplied unconditional noscript iframe is deliberately omitted: a JavaScript-
disabled visitor cannot use the existing JavaScript consent banner, and loading
that iframe would bypass the no-GTM-request-before-acceptance behavior.
This is a consent-gated installation, rather than a verbatim paste of both snippets.

## Before release

Review published container tags with its owner. The site still configures
`AW-18427027980` directly: do not configure that same destination/conversion again
in GTM without an explicitly reviewed migration. Container contents are not known
or verified by this source change. Third-party/custom tags must enforce their
appropriate consent checks, including after consent withdrawal. A loaded container
cannot be unloaded by withdrawing consent; Google receives the denied update and
subsequent document loads block the container. Arbitrary custom tags may not honor
that update, so consent compliance needs container-level verification.

Use existing CI, then protected preview with an explicit review of whether live
container requests are appropriate; do not enable real tags against synthetic
bookings without coordinating test mode with the owner. Verify Tag Assistant:
no GTM requests before acceptance/after decline, one container after acceptance,
remembered choice, settings/revocation, no duplicate destinations/events, and
expected Inertia navigation behavior. SPA tracking depends on container triggers;
container loading alone does not prove that analytics or conversions work.

Merge and production deployment require separate owner approval. No live
container validation, GTM publishing, or Google Ads configuration changes are
included in this PR.

The direct Analytics/Ads library now uses advanced consent mode and loads before
acceptance with denied defaults. GTM remains separately consent-gated; do not
interpret GTM blocking as blocking all Google requests. See `google-analytics.md`.
