<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Faq;
use App\Models\TourPackage;
use App\Support\InertiaPublicData;
use App\Support\PublicSite;
use App\Support\RouteFilterOptions;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RouteController extends Controller
{
    public function index(Request $request)
    {
        $language = PublicSite::language($request);
        $search = trim((string) $request->query('search', ''));
        $destination = $request->query('destination', 'all');
        $style = $request->query('style', 'recommended');
        $style = RouteFilterOptions::isActive($style) ? $style : RouteFilterOptions::DEFAULT_SLUG;

        $packages = TourPackage::query()
            ->with(['destination', 'packageAddOns', 'itineraryItems', 'newsArticles'])
            ->active()
            ->when($destination !== 'all', fn ($query) => $query->whereHas('destination', fn ($destinationQuery) => $destinationQuery->where('slug', $destination)))
            ->when($style !== 'recommended', fn ($query) => $query->whereJsonContains('styles', $style))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('slug', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhereHas('destination', fn ($dq) => $dq->where('name', 'like', "%{$search}%"));
                });
            })
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        $seo = Seo::routesIndex($packages->getCollection(), $search !== '');

        $packages->getCollection()->transform(fn ($package) => InertiaPublicData::route($package));

        return Inertia::render('RoutesPage', [
            'language' => $language,
            'routes' => $packages,
            'destinations' => Destination::query()->active()->ordered()->get()->map(fn (Destination $destination) => InertiaPublicData::destination($destination))->values(),
            'search' => $search,
            'destinationFilter' => $destination,
            'styleFilter' => $style,
            'styles' => array_column(RouteFilterOptions::publicOptions(), 'value'),
            'seo' => $seo,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $language = PublicSite::language($request);
        $package = TourPackage::query()
            ->with(['destination', 'itineraryItems', 'packageAddOns', 'newsArticles', 'bookings'])
            ->active()
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug);

                if (ctype_digit($slug)) {
                    $query->orWhere('id', (int) $slug);
                }
            })
            ->first();

        if (! $package) {
            return redirect()->route('routes.index');
        }

        if ($package->slug !== $slug) {
            return redirect()->route('routes.show', ['slug' => $package->slug]);
        }

        return Inertia::render('RouteDetailPage', [
            'language' => $language,
            'route' => InertiaPublicData::route($package),
            'relatedArticles' => InertiaPublicData::articles(PublicSite::relatedArticles($package)),
            'faqs' => Faq::query()
                ->active()

                ->ordered()
                ->limit(8)
                ->get()
                ->map(fn (Faq $faq) => [
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                ])
                ->values(),
            'seo' => Seo::routeDetail($package, $language),
        ]);
    }
}
