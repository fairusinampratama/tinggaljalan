<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use App\Models\TourPackage;
use App\Support\Seo;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $routes = TourPackage::query()->active()->ordered()->get();
        $articles = NewsArticle::query()->published()->latest('published_at')->get();
        $latestContentDate = collect([
            $routes->max('updated_at'),
            $articles->max(fn (NewsArticle $article) => $article->content_updated_at ?? $article->updated_at),
        ])->filter()->max();

        $urls = collect([
            [
                'loc' => Seo::canonical('/'),
                'lastmod' => optional($latestContentDate)->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'loc' => Seo::canonical('/routes'),
                'lastmod' => optional($routes->max('updated_at'))->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ],
            [
                'loc' => Seo::canonical('/news'),
                'lastmod' => optional($articles->max(fn (NewsArticle $article) => $article->content_updated_at ?? $article->updated_at))->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.85',
            ],
        ]);

        $routes->each(fn (TourPackage $package) => $urls->push([
            'loc' => Seo::canonical('/routes/'.$package->slug),
            'lastmod' => optional($package->updated_at)->toAtomString(),
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ]));

        $articles->each(fn (NewsArticle $article) => $urls->push([
            'loc' => Seo::canonical('/news/'.$article->slug),
            'lastmod' => optional($article->content_updated_at ?? $article->updated_at)->toAtomString(),
            'changefreq' => 'monthly',
            'priority' => $article->is_featured ? '0.75' : '0.7',
        ]));

        return response()
            ->view('public.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
