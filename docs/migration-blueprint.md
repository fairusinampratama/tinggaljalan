# React Prototype to Laravel Migration Blueprint

## Data to Model Map

| React source | Laravel model/resource | Notes |
| --- | --- | --- |
| `src/data/destinations.js` | `Destination` | Destination pages, filters, route grouping, article grouping. |
| `src/data/routes.js` | `TourPackage` | Main commercial product model. Use nested itinerary/highlight/inclusion data. |
| `src/data/news.js` | `NewsArticle` | Public `/news` and article detail pages. |
| `src/data/faq.js` | `Faq` | Global and page-specific FAQ blocks. |
| `src/data/home.js` | `HomepageSetting`, `Review`, `TrustStat` | Homepage content, trust strip, reviews, section ordering. |
| `src/data/platforms.js` | `PlatformLink` | Marketplace/external booking links. |
| `src/data/bookingOptions.js` | `BookingSetting`, `AddOn`, `Voucher` | Booking options, pax choices, add-ons, currency defaults. |
| `src/utils/booking.js` | `BookingService` | Price summary, booking code, availability status. |
| `src/utils/seo.js` | SEO fields + view helpers | Meta tags, schema JSON-LD, canonical URLs, sitemap. |
| `src/utils/whatsapp.js` | `WhatsAppMessageService` | WhatsApp handoff URL/message generation. |

## Component to Blade Map

| React component/page | Laravel target |
| --- | --- |
| `Hero.jsx` | `resources/views/components/sections/hero.blade.php` |
| `TrustStripSection.jsx` | `resources/views/components/sections/trust-strip.blade.php` |
| `DestinationSection.jsx` | `resources/views/components/sections/destinations.blade.php` |
| `NewsCardsSection.jsx` | `resources/views/components/news/card-grid.blade.php` |
| `RouteDetailSection.jsx` | `resources/views/packages/show.blade.php` |
| `AvailableOnSection.jsx` | `resources/views/components/sections/platforms.blade.php` |
| `FaqSection.jsx` | `resources/views/components/sections/faqs.blade.php` |
| `Navbar.jsx`, `Footer.jsx` | `resources/views/layouts/public.blade.php` components |
| `BookingPage.jsx` | `resources/views/bookings/create.blade.php` |
| Checkout pages | Booking review/payment/confirmation Blade views |

## First Filament Resources

Build these after the Laravel scaffold and database foundation exist:

1. `DestinationResource`
2. `TourPackageResource`
3. `NewsArticleResource`
4. `BookingResource`
5. `FaqResource`
6. `ReviewResource`
7. `VoucherResource`
8. `AddOnResource`
9. `HomepageSettingResource`
10. `SiteSettingResource`

## Public Route Targets

| Current React route | Laravel route |
| --- | --- |
| `/` | `home` |
| `/routes` | `packages.index` |
| `/routes/{routeId}` | `packages.show` using slug |
| `/news` | `news.index` |
| `/news/{articleSlug}` | `news.show` |
| `/booking` | `bookings.create` |
| `/checkout/review` | `bookings.review` |
| `/checkout/payment` | `bookings.payment` |
| `/checkout/confirmation` | `bookings.confirmation` |

## UI Fidelity Rules

- Keep Tailwind utility-first markup close to the React prototype.
- Use Blade components for repeated public sections.
- Keep Filament for admin CRUD only; do not use Filament to render public pages.
- Use Alpine.js only where local UI state is needed, such as mobile nav, filter panels, and booking controls.
- Preserve current public URLs where possible for SEO continuity.

