<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class RuntimeHealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $revisionPath = base_path('REVISION');
        $revision = is_readable($revisionPath) ? trim((string) file_get_contents($revisionPath)) : null;

        return response()
            ->json([
                'status' => 'up',
                'revision' => filled($revision) ? $revision : null,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
