# Initial rendering continuity — Stage 1

Base: `a777cb7b8608ed3028f5709fad50fe3bbc801d9a` (PR #33).
Branch: `fix/initial-render-continuity`.
Scope: local implementation and verification only. No preview/production deployment, merge, infrastructure, credentials, CMS, or production database changes.

## Changes

- `resources/views/partials/server-page-content.blade.php` retains the readable main document and original semantic section renderer (extracted to `server-sections.blade.php`). The new `server-navigation.blade.php` uses the existing CMS logo, visibility rules, translated navigation, and a native mobile menu.
- `server-home-content.blade.php` presents the existing CMS hero slides in a native scrollable carousel, with the established geometry, overlay, typography, search form, and destination grid. It reuses the remaining complete semantic sections rather than implementing the entire React site twice. Every hero slide remains reachable without JavaScript. `server-responsive-image.blade.php` uses the existing responsive-image generator and reserved dimensions. No images, logo assets, or CMS records are replaced.
- `resources/css/app.css` scopes document typography to the document wrapper, reuses the existing design tokens, and applies consistent fragment offsets. Meaningful fallback content is never clipped or hidden pending JavaScript. The accessible homepage H1 follows the existing React design; visible CMS hero headings remain H2s.
- `App\Support\ServerPageContent::copy` is shared through `HandleInertiaRequests`. `BookingContext.jsx` consumes the complete selected-language dictionary from the initial Inertia props; it no longer first renders a navigation-only dictionary and then requests a translation chunk. Language and dictionary update together on subsequent visits. Failed locale requests retain the current interface. One existing missing Chinese destination label is supplied.
- `app.blade.php` makes the existing destination target available and aligns an initial destination/contact fragment before delayed modules finish, including after font loading. `app.jsx` preserves its visible position through DOM replacement before paint, temporarily suppressing the additional smooth scroll and scroll anchoring. Missing initial dictionaries or failed page-module resolution leave the readable document mounted.
- No SSR service, Node server, database optimization, infrastructure change, or SEO monitoring change is introduced.

## Regression tests and evidence

Tests were added before implementation. Against the production revision the initial-homepage layout checks failed on desktop and mobile; before screenshots show the generic document. After screenshots show consistent initial/React hero geometry. Snapshots intentionally delay the entry module and await fonts; they are stable initial-HTML views, not a claim that all fonts/images are present at the earliest network millisecond. Local seed CMS content is used; the original public seed logo is cached unchanged for reproducibility. The existing consent banner still appears after React startup.

Local verification:

- Production Vite build and Pint: passed.
- PHP: 256 tests, 254 passed, 2 existing skips; 2,738 assertions.
- Functional browser regression coverage: 76 checks across desktop Chromium and Pixel 7 Chromium. Includes delayed entry scripts, English/Indonesian/Chinese first render and switching, destination fragment alignment, blocked page modules, unavailable translation chunks, missing initial dictionaries, JavaScript/CSS failure readability, existing article paragraph/section order and heading parity, canonical/noindex metadata, booking, promotions, route policies, and consent behavior. Normal initial-render locale cases also assert no uncaught browser errors.
- Repeated performance measurements: 3 cold loads per profile per revision; measurement tests pass for both profiles.
- Preservation/deployment safeguards: 11 existing Python tests plus the existing PHP staging configuration checks passed. A new PHP test verifies existing custom logo/hero URLs and unchanged fixture records after GET rendering.
- The full existing PHP SEO suite still passes. Canonical/robots/sitemap/indexing logic, article CMS extraction and paragraphs, About visibility, seeders, deployment workflows, staging payment/messaging protections, and environment/database isolation configuration are unchanged.

Run the ordinary suites using PHP 8.4 and installed browser runtimes: `npm run build:performance`, `php artisan test`, `npm run test:browser`, `python -m unittest discover -s tests/deployment`, and `php tests/deployment/test_staging_config.php`. The new loading tests are `tests/Browser/initial-rendering.spec.js`, `initial-performance.spec.js`, and `tests/Feature/InitialRenderingTest.php`. The homepage SEO test now counts headings with attributes rather than requiring literal unstyled tags, and checks the newly crawlable destination filter links; filtered routes remain noindex under the existing policy.

## Local performance comparison

Median of three cold local navigations. Desktop: 40ms latency / 8Mbps / 1x CPU. Mobile: 150ms / 1.6Mbps / 4x CPU. Same local SQLite fixture, generated media, cached original seed logo, browser runtime, and reduced-motion setting. Cache disabled; observation continues for two seconds after complete React copy. Times are seconds, CLS is unitless.

| Metric | Desktop before → after | Mobile before → after |
|---|---:|---:|
| TTFB | 0.229 → 0.191 | 0.221 → 0.214 |
| FCP | 0.596 → 0.644 | 1.888 → 2.196 |
| LCP observed in window | 1.568 → 0.644 | 7.224 → 2.196 |
| CLS observed in window | 0.021 → 0.005 | 0.066 → 0.012 |
| React mounted | 1.505 → 1.381 | 7.141 → 6.258 |
| Complete translation copy | 1.862 → 1.381 | 8.955 → 6.258 |

The dictionary arrives with the document and the intended hero is visible before React. These samples support visual continuity and removal of the later translation transition; they do not establish a Laravel/database speed improvement. FCP increased slightly, and no universal performance gain is claimed. Payload size, host warm-up, fonts, and resource scheduling affect these figures. They are not directly comparable with the investigation's production Lighthouse mobile LCP of 3.2–3.5 seconds and CLS of 0.059–0.108. The LCP/CLS window is a reproducible lab observation, not full-session field telemetry. Resource timings and all samples accompany the screenshot evidence.

## Limits and next gate

The fallback deliberately uses native controls and simplified below-fold presentation. React still adds richer interactions, the consent prompt, and floating controls. This does not promise pixel-identical rendering of every section or zero layout shift. An arbitrarily long CMS hero paragraph can still pressure the fixed hero geometry, as it can in the existing React design; actual preview CMS content needs review. A page-module failure is covered; recovery from arbitrary application runtime crashes remains outside this focused change.

Protected preview must verify actual CMS images, branding, desktop/mobile loading, destination alignment, and current deployment revision. Local fixtures cannot independently attest to remote database contents. Cross-engine GitHub CI is an additional gate before preview. After explicit approval, use the existing protected preview pipeline for this exact feature revision, recheck semantic parity and preservation, and present results for a separate production approval. Do not merge main or deploy production as part of Stage 1.
