# WhatsApp contact CTAs

The floating contact action is 56 × 56 CSS pixels below 640px with a 28px
WhatsApp glyph. At 640px and above it becomes a 48px-high pill with a 24px
glyph and the existing translated Chat label. Its accessible name explicitly
includes WhatsApp in English, Indonesian and Chinese.

On route detail pages below 1024px, the floating action is hidden because the
existing bottom contact bar already provides the WhatsApp action (minimum 48px
high). The desktop pill remains available. The bottom bar and floating action
respect safe-area insets and yield while the consent panel or mobile menu is
open. No destination URL, message, booking action or link target is changed.

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
