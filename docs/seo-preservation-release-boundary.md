# SEO and preservation-only release boundary

Starting production: `f2f2817f8c1e3bd459d64dfb0d86e7b5f7a40658`. Starting main: `273cd85681a99d6ce2f11a7e0e958de15cfd430e`. Combined production workflow 37743297295 cancelled before deployment.

All application files and dependency locks are byte-identical to original SEO PR #31 head `323ea1861edad7aaf00c78c66dbb0ba1761c9ab1`. Its three d774ad6 preservation files are unchanged. Production deployment/rollback and migrations are unchanged from the previous production baseline.

## Retained against production baseline

```text
M	.github/workflows/staging-deploy.yml
A	app/Support/PlainText.php
M	app/Support/ServerPageContent.php
A	docs/public-html-rendering-audit.md
M	resources/css/app.css
A	resources/js/components/ui/TextParagraphs.jsx
M	resources/js/data/translations/cn.js
A	resources/js/data/translations/cn.json
M	resources/js/data/translations/id.js
A	resources/js/data/translations/id.json
M	resources/js/data/translations/us.js
A	resources/js/data/translations/us.json
M	resources/js/pages/NewsDetailPage.jsx
A	resources/js/utils/text.js
M	resources/views/app.blade.php
A	resources/views/partials/plain-text.blade.php
M	resources/views/partials/server-page-content.blade.php
A	scripts/deployment/assert-article-html.php
M	scripts/deployment/configure-staging.php
M	scripts/deployment/deploy-staging.sh
M	scripts/deployment/seo-smoke-test.sh
M	scripts/testing/staging-browser-smoke.mjs
A	scripts/testing/staging-preservation-readonly.php
A	tests/Browser/public-html-parity.spec.js
A	tests/Feature/PublicHtmlParityTest.php
M	tests/Feature/SeoInfrastructureTest.php
M	tests/deployment/test_staging_deploy.py
```

## Excluded from combined release

```text
D	docs/design/indonesian-ornaments.md
M	resources/css/app.css
D	resources/images/decorative/approved-source/README.md
D	resources/images/decorative/approved-source/orangutan-foliage.svg
D	resources/images/decorative/approved-source/regional-flow.svg
D	resources/images/decorative/approved-source/regional-mobile.svg
D	resources/images/decorative/foliage-sumatra-java.svg
D	resources/images/decorative/orangutan-canopy.svg
D	resources/images/decorative/refined/README.md
D	resources/images/decorative/refined/foliage-sumatra-java.svg
D	resources/images/decorative/refined/orangutan-canopy.svg
D	resources/images/decorative/refined/regional-band.svg
D	resources/images/decorative/refined/regional-flow.svg
D	resources/images/decorative/refined/regional-mobile.svg
D	resources/images/decorative/regional-band.svg
D	resources/images/decorative/regional-flow.svg
D	resources/images/decorative/regional-mobile.svg
D	resources/js/components/decorative/DecorativeBackdrop.jsx
M	resources/js/components/layout/AppLayout.jsx
M	resources/js/components/sections/RouteDetailSection.jsx
M	resources/js/components/ui/PageShell.jsx
M	resources/js/pages/AboutUsPage.jsx
M	resources/js/pages/HomePage.jsx
M	resources/js/pages/NewsDetailPage.jsx
M	resources/js/pages/NewsPage.jsx
M	resources/js/pages/PrivacyPolicyPage.jsx
M	resources/js/pages/RoutesPage.jsx
```

The ornament source remains recoverable on `feat/home-indonesian-atmosphere` at `d774ad65db36b0a6ef47c0ad81466fc6d157abaa` and merged history; the branch is not deleted. No history rewrite or blanket PR revert. Shared CSS retains the SEO fallback rules from PR #31.

## Preservation paths

Preview: configure-staging refuses reseeding without marker whenever public CMS rows exist; does not reset logo; deploy-staging copies bundled hero images only when absent and refuses symlink destinations. Preview database/user isolation and disabled outbound integrations remain enforced.

Production: deploy-hostinger reuses shared environment/storage, never runs seeders or writes site settings, generates only missing responsive derivatives, backs up the database before the standard no-op migration phase, and atomically switches releases with health-check rollback. No migration file changes.

Read-only verification extends CMS/media hashes to production and checks for absence of ornament DOM. The optional production browser review has no booking submission or authentication writes. These verification changes do not alter application behavior.
