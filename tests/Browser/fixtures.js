import { expect, test as base } from '@playwright/test';

// All browser tests use production-shaped IDs but must never send test traffic
// to Google. Per-test script mocks registered later override this fallback.
const googleMeasurement = /^https:\/\/(?:[^/]+\.)?(?:googletagmanager\.com|google-analytics\.com|googleadservices\.com|googlesyndication\.com|doubleclick\.net|analytics\.google\.com)(?:\/|$)/;

export async function blockGoogleMeasurement(context) {
  await context.route(googleMeasurement, (route) => route.fulfill({
    status: 200,
    contentType: 'application/javascript',
    body: '',
  }));
}

export const test = base.extend({
  context: async ({ context }, use) => {
    await blockGoogleMeasurement(context);
    await use(context);
  },
});

export { expect };
