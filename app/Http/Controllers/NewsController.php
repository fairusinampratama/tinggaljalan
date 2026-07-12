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

        $query = NewsArticle::query()
            ->with(['destination', 'articleCategory', 'tourPackages'])
            ->published()
            ->when($category !== 'all', fn ($q) => $q->whereHas('articleCategory', fn ($cq) => $cq->where('slug', $category)))
            ->when($destination !== 'all', fn ($q) => $q->whereHas('destination', fn ($dq) => $dq->where('slug', $destination)))
            ->when($search !== '', function ($q) use ($search, $language) {
                $q->where(function ($inner) use ($search, $language) {
                    $inner->where('slug', 'like', "%{$search}%")
                        ->orWhere("title->{$language}", 'like', "%{$search}%")
                        ->orWhere('title->us', 'like', "%{$search}%")
                        ->orWhere("excerpt->{$language}", 'like', "%{$search}%")
                        ->orWhere('excerpt->us', 'like', "%{$search}%")
                        ->orWhere('sections', 'like', "%{$search}%")
                        ->orWhere('tags', 'like', "%{$search}%")
                        ->orWhereHas('destination', fn ($dq) => $dq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('articleCategory', fn ($cq) => $cq
                            ->where('slug', 'like', "%{$search}%")
                            ->orWhere("label->{$language}", 'like', "%{$search}%")
                            ->orWhere('label->us', 'like', "%{$search}%"));
                });
            })
            ->latest('published_at');

        $featuredModel = (clone $query)->where('is_featured', true)->first() ?? (clone $query)->first();

        $articles = $query->paginate(12)->withQueryString();
        $seo = Seo::newsIndex($articles->getCollection(), $search !== '', $language);
        
        $articles->getCollection()->transform(fn ($article) => InertiaPublicData::articleCard($article));

        return Inertia::render('NewsPage', [
            'language' => $language,
            'articles' => $articles,
            'featured' => $featuredModel ? InertiaPublicData::articleCard($featuredModel) : null,
            'categories' => ArticleCategory::query()->active()->ordered()->get(),
            'destinations' => Destination::query()->active()->ordered()->get()->map(fn (Destination $destination) => InertiaPublicData::destination($destination))->values(),
            'search' => $search,
            'categoryFilter' => $category,
            'destinationFilter' => $destination,
            'seo' => $seo,
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
            'relatedArticles' => InertiaPublicData::articleCards($related),
            'relatedRoutes' => InertiaPublicData::routeCards($article->tourPackages->isNotEmpty()
                ? $article->tourPackages
                : TourPackage::query()->with(['destination', 'packageAddOns', 'itineraryItems', 'newsArticles'])->active()->where('destination_id', $article->destination_id)->limit(3)->get()),
            'seo' => Seo::articleDetail($article, $language),
        ]);
    }
}
