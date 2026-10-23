import assert from 'node:assert/strict';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { chromium } from '@playwright/test';

// Hosted verification only. No CMS submissions, fixtures, or database writes.
const [configFile, revision, output] = process.argv.slice(2);
assert.match(revision ?? '', /^[a-f0-9]{40}$/);
assert.ok(configFile && output);
const base = 'https://preview.tinggaljalan.com';
const config = JSON.parse(await readFile(configFile, 'utf8'));
await mkdir(output, { recursive: true, mode: 0o700 });
const browser = await chromium.launch();
const checks = [];
const context = await browser.newContext({
  httpCredentials: { username: 'reviewer', password: config.review_password, origin: base },
  viewport: { width: 1440, height: 1000 },
});
const canonical = html => html.match(/<link\b[^>]*rel="canonical"[^>]*href="([^"]+)"/)?.[1];
const robots = html => html.match(/<meta\b[^>]*name="robots"[^>]*content="([^"]+)"/)?.[1];
const payload = html => JSON.parse(html.match(/<script\b[^>]*data-page="app"[^>]*>([\s\S]*?)<\/script>/)?.[1] ?? 'null');
async function get(target) {
  const response = await context.request.get(base + target, { maxRedirects: 0 });
  assert.equal(response.status(), 200, `HTTP status: ${target}`);
  assert.match(response.headers()['x-robots-tag'] ?? '', /noindex/i, `Preview protection: ${target}`);
  return response.text();
}
try {
  const health = JSON.parse(await get('/up?revision=' + revision));
  assert.equal(health.revision, revision);
  assert.equal(health.status, 'up');
  for (const catalog of ['routes', 'news']) {
    for (const [query, suffix, directive] of [
      ['', '', 'index,follow'], ['?page=1', '', 'index,follow'],
      ['?page=2', '?page=2', 'index,follow'],
      ['?page=2&utm_source=seo-review', '?page=2', 'index,follow'],
      ['?page=2&search=bromo', '', 'noindex,follow'],
      ['?page=2&lang=id', '?page=2', 'noindex,follow'],
    ]) {
      const target = '/' + catalog + query;
      const html = await get(target);
      const data = payload(html);
      const expected = base + '/' + catalog + suffix;
      assert.equal(canonical(html), expected, target);
      assert.equal(robots(html), directive, target);
      assert.equal(data.props.seo.json_ld[0].url, expected, target);
      const pagination = data.props[catalog === 'routes' ? 'routes' : 'articles'];
      if (!query && pagination.last_page > 1) {
        const main = html.match(/<main\b[^>]*class="[^"]*server-seo-content[^\"]*"[^>]*>[\s\S]*?<\/main>/)?.[0] ?? '';
        assert.match(main, new RegExp('<a\\b[^>]*href="[^"]*/' + catalog + '\\?page=2'), target);
      }
      checks.push({ target, canonical: expected, robots: directive, lastPage: pagination.last_page });
    }
  }
  const sitemap = await get('/sitemap.xml');
  const locations = [...sitemap.matchAll(/<loc>([^<]+)<\/loc>/g)].map(match => match[1].replaceAll('&amp;', '&'));
  assert.ok(locations.length > 0);
  assert.equal(new Set(locations).size, locations.length);
  for (const location of locations) {
    assert.ok(!/\s/.test(location), 'Raw whitespace in sitemap URL');
    assert.equal(new URL(location).origin, base);
    assert.ok(!/\/(admin|booking|checkout)(\/|$)/.test(new URL(location).pathname));
  }
  const article = locations.find(url => url.includes('/news/') && /%[a-f0-9]{2}/i.test(url))
    ?? locations.find(url => url.includes('/news/'));
  assert.ok(article, 'Existing sitemap article');
  const articleHtml = await get(article.slice(base.length));
  assert.equal(canonical(articleHtml), article);
  assert.equal(payload(articleHtml).props.seo.json_ld[0].mainEntityOfPage['@id'], article);
  checks.push({ sitemapUrls: locations.length, existingArticle: article,
    encodedArticleInPreview: locations.some(url => url.includes('/news/') && /%[a-f0-9]{2}/i.test(url)),
    limitation: 'CMS validation and legacy-space round trips were tested in CI; hosted CMS writes are excluded.' });

  const photoStory = '/news/preview-inline-photos-39724985689-1';
  const photoHtml = await get(photoStory);
  assert.equal((photoHtml.match(/<figure\b[^>]*class="news-section-photo"/g) ?? []).length, 3);
  for (const [name, viewport] of [['desktop', { width: 1440, height: 1000 }], ['mobile', { width: 390, height: 844 }]]) {
    const page = await context.newPage();
    await page.setViewportSize(viewport);
    await page.goto(base + '/news?page=2', { waitUntil: 'networkidle' });
    await page.locator('.server-seo-content').waitFor({ state: 'detached' });
    assert.equal(await page.locator('link[rel="canonical"]').getAttribute('href'), base + '/news?page=2');
    await page.screenshot({ path: path.join(output, name + '-catalog-page-2.png'), fullPage: true, animations: 'disabled', timeout: 30000 });
    await page.goto(base + photoStory, { waitUntil: 'networkidle' });
    await page.locator('.server-seo-content').waitFor({ state: 'detached' });
    assert.equal(await page.locator('article .news-section-photo').count(), 3);
    for (const image of await page.locator('article .news-section-photo img').all()) {
      await image.scrollIntoViewIfNeeded();
      await image.evaluate(image => image.decode());
      assert.ok(await image.evaluate(image => image.naturalWidth > 0));
    }
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 2));
    await page.screenshot({ path: path.join(output, name + '-preserved-section-photos.png'), fullPage: true, animations: 'disabled', timeout: 30000 });
    await page.close();
    checks.push({ viewport: name, catalog: 'news', catalogCanonicalAfterHydration: true, sectionPhotos: 3, loadedPhotos: true });
  }
  await writeFile(path.join(output, 'seo-verification.json'), JSON.stringify({ revision, checks, passed: true }, null, 2));
  console.log('Read-only SEO and preserved-photo verification passed for ' + revision);
} catch (error) {
  const secrets = [...Object.values(config), Buffer.from('reviewer:' + config.review_password).toString('base64')]
    .filter(value => typeof value === 'string' && value.length);
  const message = secrets.reduce((text, secret) => text.replaceAll(secret, '[redacted]'), String(error.message).split('\n')[0]);
  await writeFile(path.join(output, 'seo-verification.json'), JSON.stringify({ revision, passed: false, error: message }, null, 2));
  console.error('Read-only SEO verification failed: ' + message);
  process.exitCode = 1;
} finally {
  await context.close();
  await browser.close();
}
