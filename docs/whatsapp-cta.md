# WhatsApp contact CTAs

The floating contact action is 56 × 56 CSS pixels below 640px with a 28px
WhatsApp glyph. At 640px and above it becomes a 48px-high pill with a 24px
glyph and the existing translated Chat label. Its accessible name explicitly
includes WhatsApp in English, Indonesian and Chinese.

On route detail pages below 1024px, the floating action is hidden because the
existing bottom contact bar already provides the WhatsApp action (minimum 48px
high). The desktop pill remains available. The bottom bar and floating action
respect safe-area insets and yield while the consent panel or mobile menu is
open. Footer clearance keeps Cookie settings and Back to top above the persistent
actions when scrolled to the bottom. No destination URL, message, booking action or link target is changed.

Official unmodified digital white and green SVG glyphs were obtained from Meta's
2026 logo pack at https://www.meta.com/brand/resources/whatsapp/whatsapp-brand/
on 4 November 2026. White artwork stays white; green artwork stays green.
No remote asset request or additional dependency is needed. Green buttons use
navy text for readable label contrast; focus outlines use the existing navy.

## Measurement handoff

Current main uses a shared consent-gated GA4 / Ads loader and GTM container.
PR #45 changes consent loading independently and is intentionally not included.
This change adds no loader, gtag command, dataLayer event, conversion ID or
analytics network request. It adds non-sensitive `data-whatsapp-placement`
attributes to the floating, route-sidebar, route-mobile, route-card, homepage,
homepage-hero, footer and news links. Attributes contain only fixed placement
names; they contain no phone number, destination URL or message.

An agency can configure ONE GTM Just Links trigger for
`a[data-whatsapp-placement]` (including descendant icon clicks), read placement
from `closest('a[data-whatsapp-placement]').dataset.whatsappPlacement`, and send
one `whatsapp_click` GA4 event with a `cta_placement` parameter. Require granted
analytics consent. Do not send Click URL, Click Text, phone numbers or message
contents. Do not add another GA4 configuration or loader. Audit existing click
triggers and Enhanced Measurement outbound click collection first so this does
not duplicate an existing WhatsApp conversion or disclose WhatsApp query data.
Do not mark all outbound clicks and this event as separate conversions for the
same interaction. Preview in Tag Assistant with accepted, declined and revoked
consent, then verify receipt in GA4 before publishing account-side changes.

No GTM container configuration was changed in this PR; placement attributes
alone do not establish that events are received or improve conversion rates.

## Package upper contact action

The shared `RouteDetailSection` places a green, visibly labelled WhatsApp link
in the trip-detail chips section before the photo gallery. Below 768px, the chips get a full-width row and the
button sits on its own right-aligned row below them. At 768px and above, the
button sits to the right of the wrapping chips when space allows.
It has a minimum 48px height. The original sidebar and sticky mobile links retain
their labels, colors, breakpoints and availability.

All three placements use `components/ui/PackageWhatsAppLink.jsx`: one anchor,
the local brand icon, the original `_blank` / `noreferrer` behavior and placement
attribute. URL resolution remains upstream, in `BookingContext`, with unchanged
precedence: page `props.whatsappUrl`, shared `publicData.whatsappUrl`, site
`whatsappBaseUrl`, then `#`. Null/undefined fall through; an explicit `#` does not.
No component builds another phone number or message.

Trace of the current package page: `RouteController::show()` supplies the package;
`InertiaPublicData::shared()` supplies the generic consultation URL made by
`PublicSite::whatsappUrl()`. `PublicSite::whatsappBase()` reads Site Details'
`SiteSetting.whatsapp_number`, strips non-digits and defaults a missing/null number
to `6281234567890`. It does not invent a country code for a saved local number.
The current message is `Halo Tinggal Jalan, saya ingin konsultasi rute perjalanan.`;
lines are joined with newlines and encoded with `rawurlencode`. The package model,
form and route controller have no per-package WhatsApp number/message override.
Package names, prices, dates and availability are not interpolated into this link.
Booking/payment confirmation pages have their own page URL overrides; those are
outside this change. If package-specific URLs are added upstream later, all three
package placements will receive the same resolved URL automatically.

Only active packages have a public detail page. Package closures show an alert
but keep contact available; no date selection, stock check or login gates it.
The mobile sticky bar remains below 1024px and the sidebar above; persistent
actions still yield to consent/menu overlays. Staging still returns `#` for
WhatsApp and must not open a real chat. The new inline action preserves this.

Measurement: `route-upper` identifies the added action; existing `route-sidebar`
and `route-mobile` values are unchanged. They use the existing GTM attribute
contract documented above. No click listener or analytics event was added, so
this change does not add a second event source. Application tests exercise actual
icon clicks, one popup per click and zero added dataLayer events with consent both
denied and granted (Google scripts stubbed). They cannot verify a separately
published GTM container's event count. Agency Tag Assistant/GA4 verification is
still needed to prove delivery, audit overlapping triggers/Enhanced Measurement,
and configure `cta_placement` for `route-upper` if its trigger uses an allowlist.
An attempted WhatsApp open is not proof that a message was sent or an inquiry
received. No claim of conversion improvement is made.

Tests use disposable SQLite records and synthetic response overrides for custom
URLs, base fallback and staging `#`, never production CMS edits. CI retains the
320/390/768/1440px layout screenshots for review separately from failure traces.
