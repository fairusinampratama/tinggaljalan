import { strict as assert } from 'node:assert';
import { test } from 'node:test';
import { discoverReviewRoutes, isPublicDetailRoute, assertPublicDetailMetadata, reviewArticleSectionId } from '../../scripts/testing/public-review-routes.mjs';

test('reviews discover current public content without a hardcoded CMS slug', () => {
    assert.deepEqual(discoverReviewRoutes('<urlset><loc>https://tinggaljalan.com/routes/NEW-TOUR</loc><loc>https://tinggaljalan.com/news/new-public-story</loc></urlset>'), [
        ['package-detail', '/routes/NEW-TOUR'], ['article-detail', '/news/new-public-story'],
    ]);
});

test('external sitemap locations never become authenticated review targets', () => {
    assert.throws(() => discoverReviewRoutes('<loc>https://evil.example/routes/unsafe</loc><loc>https://tinggaljalan.com/news/valid</loc>'));
    assert.throws(() => discoverReviewRoutes('<loc>https://user:secret@tinggaljalan.com/routes/unsafe</loc><loc>https://tinggaljalan.com/news/valid</loc>'));
});

test('missing public detail content fails verification', () => {
    assert.throws(() => discoverReviewRoutes('<loc>https://tinggaljalan.com/routes</loc>'));
});

test('language and tracking query strings do not affect canonical route discovery', () => {
    assert.deepEqual(discoverReviewRoutes('<loc>https://preview.tinggaljalan.com/routes/TOUR?lang=id&amp;view=public</loc><loc>https://tinggaljalan.com/news/story?lang=cn</loc>'), [
        ['package-detail', '/routes/TOUR'], ['article-detail', '/news/story'],
    ]);
});

test('preview-only public content is checked against its own route metadata', () => {
    const base = 'https://preview.tinggaljalan.com';
    for (const route of ['/news/preview-inline-photos-123-1', '/routes/new-unpublished-production-tour']) {
        const canonical = base + route;
        assert.equal(isPublicDetailRoute(route), true);
        assert.doesNotThrow(() => assertPublicDetailMetadata(route, base,
            {canonical:[canonical],robots:['index,follow']}, {canonical,robots:'index,follow'}));
    }
    for (const route of ['/', '/news', '/routes?destination=bromo']) assert.equal(isPublicDetailRoute(route), false);
});

test('a redirect landing page, foreign canonical, or duplicate declaration cannot pass detail verification', () => {
    const base = 'https://preview.tinggaljalan.com';
    const canonical = base + '/news/preview-only';
    for (const canonicals of [[], [base + '/news'], ['https://evil.example/news/preview-only'], [canonical, canonical]]) {
        assert.throws(() => assertPublicDetailMetadata('/news/preview-only', base,
            {canonical:canonicals,robots:['index,follow']}, {canonical,robots:'index,follow'}));
    }
});

test('language variants keep their self-canonical and strict noindex policy', () => {
    const base = 'https://preview.tinggaljalan.com';
    const canonical = base + '/news/preview-only';
    const metadata = {canonical:[canonical],robots:['noindex,follow']};
    assert.doesNotThrow(() => assertPublicDetailMetadata('/news/preview-only?lang=cn', base, metadata, {canonical,robots:'noindex,follow'}));
    assert.throws(() => assertPublicDetailMetadata('/news/preview-only', base, metadata, {canonical,robots:'noindex,follow'}));
    assert.throws(() => assertPublicDetailMetadata('/news/preview-only?lang=cn', base,
        {canonical:[canonical],robots:['index,follow']}, {canonical,robots:'index,follow'}));
});

test('head metadata must also agree with server page props and cannot escape its origin', () => {
    const base = 'https://preview.tinggaljalan.com';
    const canonical = base + '/routes/public-tour';
    const metadata = {canonical:[canonical],robots:['index,follow']};
    assert.throws(() => assertPublicDetailMetadata('/routes/public-tour', base, metadata, {canonical:base+'/routes/wrong',robots:'index,follow'}));
    assert.throws(() => assertPublicDetailMetadata('https://evil.example/routes/public-tour', base, metadata, {canonical,robots:'index,follow'}));
});

test('translated article headings retain the same English fragment identity as both renderers', () => {
    const section = {heading:{us:'Before you go?',id:'Sebelum berangkat?',cn:'出发前须知'}};
    assert.equal(reviewArticleSectionId(section), 'before-you-go-');
    assert.equal(reviewArticleSectionId({heading:{id:'Sebelum berangkat?'}}), 'sebelum-berangkat-');
});
