import { expect, test } from '@playwright/test';

const contact = (page, placement) => page.locator(`[data-whatsapp-placement="${placement}"]`);
const placements = ['route-upper', 'route-sidebar', 'route-mobile'];
const message = 'Halo Tinggal Jalan, saya ingin konsultasi rute perjalanan.';

async function ready(page, slug = 'bromo-sunrise') {
  await page.goto(`/routes/${slug}`);
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  if (await page.getByTestId('consent-decline').isVisible()) await page.getByTestId('consent-decline').click();
}

// Replace only the disposable response payload, exercising the actual
// BookingContext precedence without mutating CMS records or production settings.
async function overrideProps(page, change) {
  await page.route('**/routes/*', async route => {
    const response = await route.fetch();
    if (!response.headers()['content-type']?.includes('text/html')) return route.fulfill({ response });
    const html = await response.text();
    const pattern = /(<script data-page="app" type="application\/json">)([\s\S]*?)(<\/script>)/;
    expect(html).toMatch(pattern);
    const body = html.replace(pattern, (_, start, json, end) => {
      const data = JSON.parse(json);
      change(data.props);
      return start + JSON.stringify(data).replaceAll('<', '\\u003c') + end;
    });
    await route.fulfill({ response, body });
  });
}

test('all package placements retain the existing chat destination on several packages', async ({ page }) => {
  for (const slug of ['bromo-sunrise', 'jogja-heritage', 'tumpak-sewu-daytrip']) {
    await ready(page, slug);
    const expected = await page.locator('[data-whatsapp-placement="footer"]').getAttribute('href');
    expect(new URL(expected).searchParams.get('text')).toBe(message);
    for (const placement of placements) {
      await expect(contact(page, placement)).toHaveAttribute('href', expected);
      await expect(contact(page, placement)).toHaveAttribute('target', '_blank');
      await expect(contact(page, placement)).toHaveAttribute('rel', 'noreferrer');
    }
    await expect(contact(page, 'route-upper')).toHaveText('WhatsApp');
    await expect(contact(page, 'route-upper')).toHaveAccessibleName('Chat via WhatsApp');
    await expect(contact(page, page.viewportSize().width < 1024 ? 'route-mobile' : 'route-sidebar')).toBeVisible();
  }
});

test('upper and existing placements honor context URL overrides, base fallback and disabled preview links', async ({ page }) => {
  for (const source of ['page', 'shared', 'base', 'disabled', 'missing']) {
    await page.unroute('**/routes/*');
    const custom = 'https://wa.me/628555000001?text=' + encodeURIComponent('Synthetic trip & date? 中文\nAvailability');
    await overrideProps(page, props => {
      delete props.whatsappUrl;
      delete props.publicData.whatsappUrl;
      props.publicData.site.whatsappBaseUrl = 'https://wa.me/628555000002';
      if (source === 'page') props.whatsappUrl = custom;
      if (source === 'shared') props.publicData.whatsappUrl = custom;
      if (source === 'disabled') props.publicData.whatsappUrl = '#';
      if (source === 'missing') delete props.publicData.site.whatsappBaseUrl;
    });
    await ready(page);
    const expected = ['disabled', 'missing'].includes(source) ? '#' : source === 'base' ? 'https://wa.me/628555000002' : custom;
    for (const placement of placements) await expect(contact(page, placement)).toHaveAttribute('href', expected);
  }
});

test('upper action stays right aligned above the gallery with wrapping chips', async ({ page }, testInfo) => {
  for (const longCopy of [false, true]) {
    if (longCopy) await overrideProps(page, props => {
      props.route.pickupLabel = { us: 'Hotel pickup from Malang city centre and surrounding accommodation districts' };
      props.route.destinationName = { us: 'East Java, Indonesia — mountain and waterfall experience' };
    });
    await ready(page);
    for (const width of [320, 390, 768, 1440]) {
      await page.setViewportSize({ width, height: width < 768 ? 900 : 1000 });
      const summary = await page.getByTestId('package-contact-summary').boundingBox();
      const chips = await page.getByTestId('package-detail-chips').boundingBox();
      const button = await contact(page, 'route-upper').boundingBox();
      const photo = await page.getByRole('button', { name: 'Open full-screen gallery', exact: true }).boundingBox();
      expect(button.x + button.width).toBeCloseTo(summary.x + summary.width, 0);
      expect(button.y + button.height).toBeLessThan(photo.y);
      expect(button.height).toBeGreaterThanOrEqual(48);
      if (width < 768) {
        expect(chips.width).toBeCloseTo(summary.width, 0);
        expect(button.y).toBeGreaterThanOrEqual(chips.y + chips.height);
      } else {
        expect(button.y).toBeCloseTo(chips.y, 0);
        expect(button.x).toBeGreaterThanOrEqual(chips.x + chips.width - 1);
      }
      expect(button.y).toBeGreaterThanOrEqual(summary.y);
      expect(button.y + button.height).toBeLessThanOrEqual(summary.y + summary.height + 1);
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
      await page.screenshot({ path: testInfo.outputPath(`upper-whatsapp-${longCopy ? 'long-' : ''}${width}.png`) });
    }
  }
});

test('upper label follows the existing package language', async ({ page }) => {
  for (const [locale, label] of [['id', 'Chat melalui WhatsApp'], ['cn', '通过 WhatsApp 咨询']]) {
    await page.goto(`/language/${locale}`);
    await ready(page);
    await expect(contact(page, 'route-upper')).toHaveAccessibleName(label);
    await expect(contact(page, 'route-upper')).toHaveText('WhatsApp');
    await expect(contact(page, 'route-upper')).toHaveAttribute('href', await contact(page, 'route-mobile').getAttribute('href'));
  }
});

test('real upper and existing clicks open the same chat once, without adding duplicate analytics events', async ({ page, context }) => {
  await page.addInitScript(() => {
    window.__contactClicks = [];
    document.addEventListener('click', event => {
      const link = event.target.closest('[data-whatsapp-placement]');
      if (link) window.__contactClicks.push(link.dataset.whatsappPlacement);
    }, { capture: true });
  });
  await page.route('https://www.googletagmanager.com/**', route => route.fulfill({ contentType: 'application/javascript', body: '' }));
  await context.route('https://wa.me/**', route => route.fulfill({ contentType: 'text/html', body: '<p>Synthetic WhatsApp handoff: no message sent.</p>' }));
  await ready(page);
  const original = page.viewportSize();
  for (const granted of [false, true]) {
    if (granted) await page.evaluate(() => window.TinggalJalanConsent.grant());
    await page.evaluate(() => { window.__contactClicks = []; });
    const before = await page.evaluate(() => window.dataLayer.length);
    for (const placement of ['route-upper', original.width < 1024 ? 'route-mobile' : 'route-sidebar']) {
      const expected = await contact(page, placement).getAttribute('href');
      const popupPromise = page.waitForEvent('popup');
      // Click the actual icon too: its event must resolve to the same link.
      await contact(page, placement).locator('img').click();
      const popup = await popupPromise;
      await popup.waitForURL(expected);
      expect(new URL(popup.url()).searchParams.get('text')).toBe(message);
      await popup.close();
    }
    expect(await page.evaluate(() => window.__contactClicks)).toEqual(['route-upper', original.width < 1024 ? 'route-mobile' : 'route-sidebar']);
    expect(await page.evaluate(() => window.dataLayer.length)).toBe(before);
  }
  await contact(page, 'route-upper').focus();
  await expect(contact(page, 'route-upper')).toBeFocused();
});
