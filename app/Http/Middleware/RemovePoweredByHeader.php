<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemovePoweredByHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->remove('X-Powered-By');

        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
