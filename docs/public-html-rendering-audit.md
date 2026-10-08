# Public HTML rendering audit — 8 October 2026

## Verdict: Production needs correction; protected preview passed and is ready for review

Production and `main` serve Blade fallback content as the initial Inertia HTML. React subsequently replaces that content. The initial HTML has real content, but article hierarchy, route completeness, About visibility and homepage accuracy have confirmed defects. Privacy has no initial body. These corrections retain the architecture and indexing policy; The protected preview passed; production release requires approval.

Observed pipeline: Discovery PASS → Crawl PASS → HTTP PASS (one requested route redirects) → Render FAIL in current production / corrected candidate → Indexability PASS → Canonical PASS → Semantics FAIL in current production / corrected candidate → Ranking data UNKNOWN.

Repository baseline: `f2f2817f8c1e3bd459d64dfb0d86e7b5f7a40658`. Current stack is Laravel 13, Inertia 3 and React 19 (not the Laravel 12 assumed in earlier project notes).

## Verified architecture

`app.blade.php` dispatches Inertia's SSR gateway. When unavailable it emits a JSON page payload and `#app` containing `server-page-content.blade.php`. No SSR entry/build is configured in the repository. All eleven successfully fetched production public responses contain that fallback/payload. The cloud browser shows the same pages replaced with the React application after loading. The frontend uses `createRoot`, so this is client mounting, not React hydration of an SSR tree.

Before this change, a head script adds `.js` before frontend success, and CSS clips fallback content to one pixel. A blocked page bundle can therefore leave an empty visible page. The correction removes premature clipping, adds scoped readable fallback styles, and lets React remove the fallback naturally on commit. The fallback is inside `#app`; it is not an additional hidden copy after mounting. Gateway-success and gateway-unavailable paths have separate PHP regressions. Real deployed SSR remains unsupported/unobserved; no SSR architecture is introduced.

## Findings and page inventory

| Priority | Page/type | Production evidence | Correction |
|---|---|---|---|
| P1 | Privacy `/privacy-policy` | 200, indexable; initial body lacks server content | Render the existing translated policy sections in Blade |
| P1 | Failed JavaScript | Head script hides fallback before app bundle success | Keep readable fallback until React commits |
| P2 | News detail `/news/Bromo-wildfire` | Initial `Article Guide` H2; seven real article headings are paragraphs | Emit each original section as an H2, with the original section ID and body |
| P2 | News detail `/news/trip-ijen-creater` | Long article has the same flattened fallback structure | Preserve all sections and paragraph order without summarizing |
| P2 | News detail `/news/bukit-lawang-jungle-trekking-orangutan-guide` | Same H2 mismatch; multiline text needs meaningful boundaries | Share paragraph/newline rules between escaped Blade and React output |
| P2 | Route detail `/routes/BROMO` | Fallback omits package prices/options, add-ons, pickup, exclusions and policies | Use the actual visible package payload, price currency, lists, policies, reviews and related guides |
| P2 | About `/about-us` | `profile` visibility is false in live CMS; fallback still folds operating description into the hero intro | Honor all seven section visibility flags, legal-field flags and section order; keep hero intro separate |
| P2 | Home `/` | Fallback includes a fixed explanatory paragraph absent from visitor DOM; truncates visible cards and reviews | Remove invented copy and limits; use CMS hero slides, visible sections and shared UI translations |
| P2 | Routes `/routes` | Initial H1/section labels differ from visitor headings; descriptions hidden by catalog UI are exposed | Use visitor heading/copy, catalog metadata/prices and page-specific results/pagination |
| P2 | News `/news` | Initial headings differ; lists truncate; featured content can differ from results | Use visitor headings, page-specific articles/featured content and pagination |
| P3 | Related content | Article fallback limits related routes to four while live visitor sidebar has six | Remove fallback-only truncation |
| — | `/routes/Bukit-lawang` | HTTP 302 to `/routes`, confirmed in raw response and browser | Preserve existing URL/redirect behavior; route detail cannot be audited at this URL |
| — | Language/filter variants | `?lang=id`, `?lang=cn` and filters intentionally use noindex,follow with canonical main URL | Keep robots/canonicals/hreflang policy; provide readable public fallback also without JS |
| — | Draft/private pages | Controllers exclude drafts/future articles/inactive packages; booking is noindex,nofollow | Existing exclusion/indexing tests retained; no booking fallback introduced |

Normal and Googlebot-compatible requests produced identical fallback blocks for eleven checked URLs, including the three representative articles, Bromo detail, home, both listings, About, Privacy and two variants. No user-agent rendering split was added. Sitemap/robots/indexing code was not changed. Other sitemap entries use these audited templates; the deployment SEO smoke test iterates sitemap URLs and now validates article structure against each response's CMS payload.

## Root causes

The generic fallback list model treats article sections as list items rather than sections. It strips tags from plain CMS text (changing what React safely displays), joins multiple content fields, applies independent limits and hard-coded headings, omits detail fields, and ignores About visibility. Initial fallback suppression assumes JavaScript success. Existing tests explicitly expected `Article Guide` and general word counts, permitting these mismatches.

## Files changed

- `ServerPageContent.php`: page-specific content/visibility corrections; visitor UI labels from shared JSON; no marketing additions or arbitrary truncation.
- `PlainText.php`, `plain-text.blade.php`, `utils/text.js`, `TextParagraphs.jsx`: blank lines become paragraphs, single newlines become `<br>`, text is escaped. CMS HTML/Markdown remains literal plain text, consistent with the existing textarea contract; no new rich-text language is invented.
- `NewsDetailPage.jsx`: use paragraph renderer and a shared fragment-ID helper; retain section headings/order/navigation.
- `server-page-content.blade.php`: support real section text, heading levels, ordered/unordered lists, images, links and quotes. Reject executable link schemes.
- `app.blade.php`, scoped `app.css`: keep fallback visible until mounting and readable without JS. No hydrated layout styles changed.
- Translation `.json` sources and small `.js` imports: move existing text unchanged into a format both PHP and Vite can consume, avoiding a second translation catalog.
- `SeoInfrastructureTest.php`, `PublicHtmlParityTest.php`, `public-html-parity.spec.js`: structural, visibility, safe text, route content, SSR branch, no-JS, failed-bundle, fragment-navigation and DOM parity regressions.
- `assert-article-html.php`, `seo-smoke-test.sh`: compare initial article H2 text/order, section count, paragraph text/count and line breaks with the actual embedded CMS payload on deployment.
- `staging-browser-smoke.mjs`: authenticated read-only checks for normal/Googlebot fallback parity, all public page types without JavaScript, article CMS/visitor paragraph parity, fragment links and blocked-bundle screenshots.
- Deployment prerequisite from `d774ad6`: preserve existing logo/public content/hero files when deploying preview; retain outbound integration safeguards. No data/media refresh operation is invoked.

## Before and after

Before:

```html
<section><h2>Article Guide</h2><ul><li>
  <p>Can Foreign Tourists Still Visit Mount Bromo?</p>
  <p>No. ...</p>
</li></ul></section>
```

After:

```html
<section id="can-foreign-tourists-still-visit-mount-bromo-">
  <h2>Can Foreign Tourists Still Visit Mount Bromo?</h2>
  <p>No. ...<br>...</p>
  <p>There is currently no confirmed reopening date. ...</p>
</section>
```

For the adversarial multiline regression:

```html
<p>First paragraph<br>second line</p>
<p>Second paragraph &lt;script&gt;alert(1)&lt;/script&gt; &amp; text</p>
```

The text remains complete and literal. Article numbering already stored as plain textarea text stays text; structured package/workflow/milestone lists remain semantic lists.

## Verification

- Local full PHP suite on both standalone and combined UI candidates: 253 tests, 251 passed, two environment-specific skips; 2,675 assertions. Focused suite after final content changes: 26 passed, 495 assertions. Protected preview CI: all 253 PHP tests passed, 2,680 assertions.
- Production Vite build, PHP formatting, shell syntax and Git whitespace checks passed.
- Local Chromium desktop/mobile full browser run: 57 passed and one existing voucher check failed; isolated rerun passed. Final rendering/metadata run: 38 passed; extra multiline/safety browser cases: two passed. Firefox/WebKit were not run locally. Protected preview CI passed all 150 browser cases across desktop Chromium, Firefox, WebKit, iPhone WebKit and Android Chromium.
- Seven deployment-preservation regression tests passed. The local CLI initially printed uncaught exceptions to stdout; correcting the local PHP display-errors setting restored the expected stderr test behavior. No application change was needed.
- Article smoke validator passed against the representative corrected raw HTML fixtures, including long articles.
- 146 before/after screenshots: nine live-content page fixtures, 390px/1440px viewports, four scroll positions, two revisions, plus no-JS/blocked-JS captures. Read-only production page payloads and public media were used locally; carousel autoplay was disabled in the fixture for repeatability. Both fallback failure scenarios remain visible. No browser page exceptions occurred. Article paragraph spacing is the intended visible change; other layouts are retained.
- Bromo detail's mobile document-width overflow was observed in both baseline and candidate. It is pre-existing and remains outside this rendering correction. A few bundled responsive image variants are unavailable in the local fixture; this is shared with the baseline. No production logo or media was written.

## Limits and release state

This report does not establish Google indexing, rankings, snippets or AI Overview citations. GSC was not used. Production no-JS accessibility is inferred from its raw fallback/CSS; candidate no-JS and failed-bundle behavior were exercised locally. The currently configured architecture does not support a real SSR build. Near-midnight browser/server timezone differences may affect a date-based closure banner, as in existing availability rendering.

Draft PR: https://github.com/fairusinampratama/tinggaljalan/pull/31. The feature branch targets `main`. The protected preview integration candidate retains the separate unmerged UI work and logo-preservation prerequisite. Deployed application candidate: `089299c7d5765572e5b056bf468ab51f8bd41a1b`. Protected deployment run: https://github.com/fairusinampratama/tinggaljalan/actions/runs/37726158039. Extended read-only review uses `0f992245f202cfa76706f0e31ae3387e7a279bea` to check that same deployed application revision; only the verification script differs. Review run: https://github.com/fairusinampratama/tinggaljalan/actions/runs/37726814033. Both runs completed successfully. Deployed desktop/mobile pages had no JavaScript or failed JS/CSS asset errors. The extended review passed normal/Googlebot initial fallback equality, readable no-JS fallback on all public page types and language/filter variants, visitor H1/no-duplicate-fallback checks, exact article CMS/server/visitor headings and paragraph/BR parity, fragment-link presence, and desktop/mobile blocked-bundle fallback. Preview remains authenticated and noindex. Production `/up` still reports the original `f2f2817f8c1e3bd459d64dfb0d86e7b5f7a40658` revision and its original article fallback, independently verified after deployment. No merge to `main`, production deployment, database reset/reseed/refresh, media replacement, URL change, payment change or messaging change is authorized by this audit.
