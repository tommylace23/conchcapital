<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Treat every /api request as a JSON request so unauthenticated calls
     * return 401 JSON instead of redirecting to a non-existent "login" route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/*') && ! $request->expectsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
