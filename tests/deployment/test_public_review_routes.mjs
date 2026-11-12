import { strict as assert } from 'node:assert';
import { test } from 'node:test';
import { discoverReviewRoutes } from '../../scripts/testing/public-review-routes.mjs';

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
