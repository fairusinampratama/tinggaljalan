<?php

namespace App\Http\Controllers;

use App\Support\PublicSite;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrivacyPolicyController extends Controller
{
    public function __invoke(Request $request)
    {
        $language = PublicSite::language($request);

        return Inertia::render('PrivacyPolicyPage', [
            'seo' => Seo::privacyPolicy($language, $request),
        ]);
    }
}
