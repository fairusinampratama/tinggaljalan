<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (strtolower($request->getHost()) === 'www.tinggaljalan.com') {
            return redirect()->away('https://tinggaljalan.com'.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
