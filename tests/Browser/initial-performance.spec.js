import { writeFile } from 'node:fs/promises';
import { expect, test } from '@playwright/test';

test('records repeated cold local loading metrics', async ({ page, browserName }, testInfo) => {
  test.skip(browserName !== 'chromium', 'Network/CPU profiles use Chromium CDP.');
  test.setTimeout(90_000);
  if (process.env.TINGGALJALAN_TEST_LOGO_FIXTURE) {
    await page.route('https://assets.zyrosite.com/**', (route) => route.fulfill({
      path: process.env.TINGGALJALAN_TEST_LOGO_FIXTURE, contentType: 'image/png',
    }));
  }
  const cdp = await page.context().newCDPSession(page);
  await cdp.send('Network.enable');
  await cdp.send('Network.setCacheDisabled', { cacheDisabled: true });
  const mobile = Boolean(testInfo.project.use.isMobile);
  await cdp.send('Network.emulateNetworkConditions', {
    offline: false, latency: mobile ? 150 : 40,
    downloadThroughput: mobile ? 200_000 : 1_000_000, uploadThroughput: 96_000,
  });
  await cdp.send('Emulation.setCPUThrottlingRate', { rate: mobile ? 4 : 1 });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.addInitScript(() => {
    window.__loadingMetrics = { cls: 0, lcp: null, reactReady: null, copyReady: null };
    let session = { start: 0, last: 0, value: 0 };
    new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        if (entry.hadRecentInput) continue;
        if (entry.startTime - session.last > 1000 || entry.startTime - session.start > 5000) {
          session = { start: entry.startTime, last: entry.startTime, value: 0 };
        }
        session.last = entry.startTime;
        session.value += entry.value;
        window.__loadingMetrics.cls = Math.max(window.__loadingMetrics.cls, session.value);
      }
    }).observe({ type: 'layout-shift', buffered: true });
    new PerformanceObserver((list) => {
      window.__loadingMetrics.lcp = list.getEntries().at(-1).startTime;
    }).observe({ type: 'largest-contentful-paint', buffered: true });
    new MutationObserver(() => {
      if (document.querySelector('.server-seo-content') || !document.querySelector('#home')) return;
      window.__loadingMetrics.reactReady ??= performance.now();
      if (document.querySelector('#home').textContent.includes('Find a Trip')) {
        window.__loadingMetrics.copyReady ??= performance.now();
      }
    }).observe(document, { childList: true, subtree: true });
  });
  const samples = [];
  for (let sample = 0; sample < 3; sample++) {
    await page.goto('/?lang=us');
    await expect(page.locator('.server-seo-content')).toHaveCount(0);
    await expect(page.locator('#home')).toContainText('Find a Trip');
    await page.waitForFunction(() => window.__loadingMetrics.copyReady !== null);
    // Fixed observation window; below-fold requests and carousel timing cannot
    // extend this sample indefinitely. This is a lab measurement, not a budget.
    await page.waitForTimeout(2000);
    samples.push(await page.evaluate(() => ({
      ...window.__loadingMetrics,
      fcp: performance.getEntriesByName('first-contentful-paint')[0]?.startTime,
      ttfb: performance.getEntriesByType('navigation')[0]?.responseStart,
      resources: performance.getEntriesByType('resource').map((r) => ({
        name: new URL(r.name).pathname, start: r.startTime, duration: r.duration,
        transfer: r.transferSize, encoded: r.encodedBodySize,
      })),
    })));
  }
  const metricsPath = testInfo.outputPath('local-loading-metrics.json');
  await writeFile(metricsPath, JSON.stringify({ profile: mobile ? '150ms / 1.6Mbps / 4x CPU' : '40ms / 8Mbps / 1x CPU', samples }, null, 2));
  await testInfo.attach('local-loading-metrics.json', {
    path: metricsPath,
    contentType: 'application/json',
  });
});
