<?php

namespace App\Http\Controllers;

use App\Models\AboutPage;
use App\Models\CompanyMilestone;
use App\Models\PlatformLink;
use App\Models\TeamMember;
use App\Support\InertiaPublicData;
use App\Support\PublicSite;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AboutController extends Controller
{
    public function __invoke(Request $request)
    {
        $aboutPage = AboutPage::query()->published()->firstOrFail();
        $language = PublicSite::language($request);

        return Inertia::render('AboutUsPage', [
            'aboutPage' => InertiaPublicData::aboutPage($aboutPage),
            'teamMembers' => TeamMember::query()->active()->ordered()->get()->map(fn (TeamMember $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => InertiaPublicData::localizedArray($member->role),
                'biography' => InertiaPublicData::localizedArray($member->biography),
                'portrait' => $member->portrait ? InertiaPublicData::assetPath($member->portrait) : null,
                'portraitAlt' => InertiaPublicData::localizedArray($member->portrait_alt),
                'category' => $member->category,
                'location' => $member->location,
                'languages' => $member->languages ?? [],
                'profileUrl' => $member->profile_url,
                'isFeatured' => $member->is_featured,
                'isSample' => $member->is_sample,
            ])->values(),
            'milestones' => CompanyMilestone::query()->active()->ordered()->get()->map(fn (CompanyMilestone $milestone) => [
                'id' => $milestone->id,
                'period' => InertiaPublicData::localizedArray($milestone->period),
                'title' => InertiaPublicData::localizedArray($milestone->title),
                'description' => InertiaPublicData::localizedArray($milestone->description),
                'image' => $milestone->image ? InertiaPublicData::assetPath($milestone->image) : null,
                'imageAlt' => InertiaPublicData::localizedArray($milestone->image_alt),
                'isSample' => $milestone->is_sample,
            ])->values(),
            'platformLinks' => PlatformLink::query()->active()->ordered()->get()->map(fn (PlatformLink $link) => [
                'name' => $link->name,
                'url' => $link->url,
                'logo' => InertiaPublicData::assetPath($link->logo),
            ])->values(),
            'seo' => Seo::about($aboutPage, $language),
        ]);
    }
}
