<?php

namespace App\Http\Middleware;

use App\Support\InertiaPublicData;
use App\Support\PublicSite;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'language' => fn () => PublicSite::language($request),
            'publicData' => fn () => InertiaPublicData::shared($request),
        ]);
    }
}
