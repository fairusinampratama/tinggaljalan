<?php

namespace App\Http\Controllers;

use App\Support\PublicSite;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function __invoke(Request $request, string $language)
    {
        abort_unless(in_array($language, PublicSite::LANGUAGES, true), 404);

        $request->session()->put('language', $language);

        return back();
    }
}
