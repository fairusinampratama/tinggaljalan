<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Faq;
use App\Models\NewsArticle;
use App\Models\PlatformLink;
use App\Models\Review;
use App\Models\TourPackage;
use App\Models\TrustStat;
use App\Support\InertiaPublicData;
use App\Support\PublicSite;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $language = PublicSite::language($request);

        return Inertia::render('HomePage', [
            'language' => $language,
            'destinations' => Destination::query()->active()->featured()->ordered()->get()->map(fn (Destination $destination) => InertiaPublicData::destination($destination))->values(),
            'featuredRoutes' => InertiaPublicData::routeCards(TourPackage::query()->with(['destination', 'newsArticles'])->active()->featured()->ordered()->limit(6)->get()),
            'latestArticles' => InertiaPublicData::articleCards(NewsArticle::query()->with(['destination', 'articleCategory', 'tourPackages'])->published()->latest('published_at')->limit(3)->get()),
            'faqs' => Faq::query()->active()->ordered()->limit(8)->get()->map(fn (Faq $faq) => [
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])->values(),
            'reviews' => Review::query()->active()->featured()->ordered()->limit(Review::MAX_ACTIVE_FEATURED)->get(),
            'trustStats' => TrustStat::query()->active()->ordered()->limit(TrustStat::MAX_ACTIVE)->get(),
            'platformLinks' => PlatformLink::query()->active()->ordered()->limit(PlatformLink::MAX_ACTIVE)->get(),
            'seo' => Seo::home(),
        ]);
    }
}
