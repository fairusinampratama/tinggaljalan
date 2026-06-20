<?php

namespace App\Http\Controllers;

use App\Models\ArticleCategory;
use App\Models\Destination;
use App\Models\NewsArticle;
use App\Models\TourPackage;
use App\Support\InertiaPublicData;
use App\Support\PublicSite;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $language = PublicSite::language($request);
        $search = trim((string) $request->query('search', ''));
        $category = $request->query('category', 'all');
        $destination = $request->query('destination', 'all');

        $articles = NewsArticle::query()
            ->with(['destination', 'articleCategory', 'tourPackages'])
            ->published()
            ->when($category !== 'all', fn ($query) => $query->whereHas('articleCategory', fn ($categoryQuery) => $categoryQuery->where('slug', $category)))
            ->when($destination !== 'all', fn ($query) => $query->whereHas('destination', fn ($destinationQuery) => $destinationQuery->where('slug', $destination)))
            ->latest('published_at')
            ->get()
            ->filter(function (NewsArticle $article) use ($search, $language) {
                if ($search === '') {
                    return true;
                }

                $haystack = strtolower(implode(' ', [
                    $article->slug,
                    $article->destination?->name,
                    $article->articleCategory?->slug,
                    PublicSite::localized($article->title, $language),
                    PublicSite::localized($article->excerpt, $language),
                    implode(' ', $article->tags ?? []),
                ]));

                return str_contains($haystack, strtolower($search));
            })
            ->values();

        return Inertia::render('NewsPage', [
            'language' => $language,
            'articles' => InertiaPublicData::articles($articles),
            'featured' => ($featured = $articles->firstWhere('is_featured', true) ?? $articles->first()) ? InertiaPublicData::article($featured) : null,
            'categories' => ArticleCategory::query()->active()->ordered()->get(),
            'destinations' => Destination::query()->active()->ordered()->get()->map(fn (Destination $destination) => InertiaPublicData::destination($destination))->values(),
            'search' => $search,
            'categoryFilter' => $category,
            'destinationFilter' => $destination,
            'seo' => Seo::newsIndex($articles, $search !== ''),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $language = PublicSite::language($request);
        $article = NewsArticle::query()
            ->with(['destination', 'articleCategory', 'tourPackages.destination'])
            ->published()
            ->where('slug', $slug)
            ->first();

        if (! $article) {
            return redirect()->route('news.index');
        }

        $related = NewsArticle::query()
            ->with(['destination', 'articleCategory', 'tourPackages'])
            ->published()
            ->whereKeyNot($article->id)
            ->where(function ($query) use ($article) {
                $query->where('destination_id', $article->destination_id)
                    ->orWhere('article_category_id', $article->article_category_id);
            })
            ->latest('published_at')
            ->limit(3)
            ->get();

        return Inertia::render('NewsDetailPage', [
            'language' => $language,
            'article' => InertiaPublicData::article($article),
            'relatedArticles' => InertiaPublicData::articles($related),
            'relatedRoutes' => InertiaPublicData::routes($article->tourPackages->isNotEmpty()
                ? $article->tourPackages
                : TourPackage::query()->with(['destination', 'packageAddOns', 'itineraryItems', 'newsArticles'])->active()->where('destination_id', $article->destination_id)->limit(3)->get()),
            'seo' => Seo::articleDetail($article, $language),
        ]);
    }
}
