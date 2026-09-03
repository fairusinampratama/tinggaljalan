# SEO Release Verification

Use the Google Search Console Domain property `tinggaljalan.com` or the root URL-prefix property `https://tinggaljalan.com/`. Do not submit the root sitemap from a `/news/` or `/routes/` URL-prefix property.

## Deployment Record

For every release that changes titles, descriptions, canonicals, robots directives, structured data, crawler content, internal links, the sitemap, or favicons, record:

- Production Git revision and deployment time in Asia/Jakarta.
- The pages and expected search-facing changes.
- The production SEO smoke-test result.
- The Search Console verification date and result.

## Immediate Verification

1. Confirm the deployment job reports `SEO smoke test: passed`.
2. Open Search Console URL Inspection for:
   - `https://tinggaljalan.com/`
   - One changed route detail URL.
   - One changed news detail URL.
3. Select **Test live URL**.
4. Confirm Page fetch is successful, indexing is allowed, and the declared canonical is the inspected URL.
5. Open **View tested page** and inspect the screenshot, HTML, HTTP response, and loaded resources.
6. Confirm the expected title, description, canonical, crawler-visible content, and favicon declarations are present.
7. Submit `https://tinggaljalan.com/sitemap.xml` once from the root or Domain property.
8. Request indexing once for each representative changed page.

## Delayed Verification

Google crawling and search-result updates are not deployment gates. Recheck URL Inspection until **Last crawl** is later than the recorded deployment time, then compare **View crawled page** with the expected release.

Track indexed route/news URLs and Search Console impressions. Use public Google searches only as a secondary observation; the `site:` operator is not a complete indexing report. Repeated indexing requests do not accelerate recrawling.
