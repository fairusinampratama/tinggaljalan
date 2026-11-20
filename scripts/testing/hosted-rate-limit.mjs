import { setTimeout as sleep } from 'node:timers/promises';
import { waitForHostedHandoff } from './hosted-browser-readiness.mjs';

const previewOrigin = 'https://preview.tinggaljalan.com';

export function retryDelay(header, attempt, now = Date.now()) {
    const value = String(header ?? '').trim();
    let seconds = /^\d+$/.test(value) ? Number(value) : value ? Math.ceil((Date.parse(value) - now) / 1000) : NaN;
    if (!Number.isFinite(seconds) || seconds < 0) seconds = 0;
    return Math.max(10 * (2 ** (attempt - 1)), seconds) * 1000;
}

// Only top-level GET navigation is replayed. Booking, login and other submissions
// never pass through this helper. A fresh document clears failed module imports.
export async function navigateHostedPreview(page, target, {
    ready = () => waitForHostedHandoff(page), wait = sleep, onRetry = () => {},
} = {}) {
    if (new URL(target).origin !== previewOrigin) throw new Error('Unexpected preview origin.');
    for (let attempt = 1; attempt <= 3; attempt++) {
        let limited = false;
        let delay = retryDelay(undefined, attempt);
        const inspect = response => {
            if (response.status() !== 429 || new URL(response.url()).origin !== previewOrigin) return;
            if (!['document', 'script', 'stylesheet'].includes(response.request().resourceType())) return;
            limited = true;
            delay = Math.max(delay, retryDelay(response.headers()['retry-after'], attempt));
        };
        page.on('response', inspect);
        let failure;
        let response;
        try {
            response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 30_000 });
            if (!response?.ok()) throw new Error('Preview navigation returned a non-success response.');
            await ready();
            if (!limited) return response;
            failure = new Error('Preview document or application assets were rate limited.');
        } catch (error) {
            failure = error;
        } finally {
            page.off('response', inspect);
        }
        // Never retry unrelated failures, exceed Retry-After, or retry forever.
        if (!limited || attempt === 3 || delay > 60_000) throw failure;
        onRetry({ attempt, status: 429, delaySeconds: delay / 1000 });
        await wait(delay);
    }
}
