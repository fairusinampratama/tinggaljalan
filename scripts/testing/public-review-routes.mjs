import { articleSectionId } from '../../resources/js/utils/text.js';
import { getLocalized } from '../../resources/js/utils/localization.js';

const publicOrigins = new Set(['https://tinggaljalan.com', 'https://preview.tinggaljalan.com']);

export function reviewArticleSectionId(section) {
    return articleSectionId(getLocalized(section.heading, 'us'));
}

export function isPublicDetailRoute(route) {
    return /^\/(news|routes)\/[^/?]+\/?$/.test(route.split('?')[0]);
}

export function assertPublicDetailMetadata(route, base, metadata, seo) {
    const target = new URL(route, base);
    if (!publicOrigins.has(base) || target.origin !== base || target.username || target.password || !isPublicDetailRoute(target.pathname)) {
        throw new Error('Unexpected public detail target.');
    }
    // Matches Seo::articleDetail/routeDetail and robotsForRequest. Preview CMS
    // content may legitimately have no corresponding production record.
    const canonical = base + target.pathname.replace(/\/$/, '');
    const robots = ['id', 'cn'].includes(target.searchParams.get('lang')) ? 'noindex,follow' : 'index,follow';
    if (metadata.canonical?.length !== 1 || metadata.canonical[0] !== canonical || seo?.canonical !== canonical) {
        throw new Error('Public detail canonical differs from its exact route.');
    }
    if (metadata.robots?.length !== 1 || metadata.robots[0] !== robots || seo?.robots !== robots) {
        throw new Error('Public detail robots policy differs from its request.');
    }
}

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
