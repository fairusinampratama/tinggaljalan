// Only fixed categories and aggregate counts leave the authenticated browser.
export function startupErrorCategory(value) {
    const message = typeof value === 'string' ? value : String(value?.message ?? '');
    if (message.includes('Initial translations unavailable')) return 'missing-initial-translations';
    if (/dynamically imported module|module script|MIME type|Loading chunk|Importing a module script failed/i.test(message)) return 'module-loading';
    if (/Minified React error #(\d+)/.test(message)) return 'react-' + message.match(/Minified React error #(\d+)/)[1];
    if (/Cannot read properties|Cannot destructure|is not iterable/.test(message)) return 'invalid-render-data';
    if (/is not a function/.test(message)) return 'invalid-function';
    if (/is not defined|before initialization/.test(message)) return 'reference-error';
    return 'other';
}

export function attachStartupDiagnostics(page) {
    const counts = new Map();
    const record = key => counts.set(key, (counts.get(key) ?? 0) + 1);
    page.on('pageerror', error => record('pageerror:' + startupErrorCategory(error)));
    page.on('console', message => {
        if (message.type() !== 'error') return;
        // text() is inspected in memory, never printed or retained in evidence.
        const text = message.text();
        record('console:' + startupErrorCategory(text));
        if (text.includes('TinggalJalan application startup failed.')) record('startup:render-failed');
        if (text.includes('TinggalJalan could not start; server content remains available.')) record('startup:bootstrap-failed');
    });
    page.on('requestfailed', request => {
        const type = request.resourceType();
        if (['script', 'stylesheet', 'font', 'image', 'document'].includes(type)) record('requestfailed:' + type);
    });
    page.on('response', response => {
        const type = response.request().resourceType();
        if (['script', 'stylesheet'].includes(type)) {
            const status = response.status();
            record('asset:' + type + ':' + (Number.isInteger(status) && status >= 100 && status <= 599 ? status : 'unknown'));
        }
    });
    return () => Object.fromEntries([...counts].sort());
}
