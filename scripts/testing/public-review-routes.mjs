const publicOrigins = new Set(['https://tinggaljalan.com', 'https://preview.tinggaljalan.com']);

export function discoverReviewRoutes(sitemap) {
    const paths = [];
    for (const [, value] of sitemap.matchAll(/<loc>\s*([^<]+)\s*<\/loc>/g)) {
        const url = new URL(value.trim().replaceAll('&amp;', '&'));
        if (publicOrigins.has(url.origin) && !url.username && !url.password) paths.push(url.pathname);
    }
    const route = paths.find(path => /^\/routes\/[^/?]+\/?$/.test(path));
    const article = paths.find(path => /^\/news\/[^/?]+\/?$/.test(path));
    if (!route || !article) throw new Error('No current public package/article found in the target sitemap.');
    return [['package-detail', route], ['article-detail', article]];
}
