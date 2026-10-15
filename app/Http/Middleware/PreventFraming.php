<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PreventFraming
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'DENY');

        $policy = "frame-ancestors 'none'";
        if (! in_array($policy, $response->headers->all('Content-Security-Policy'), true)) {
            $response->headers->set('Content-Security-Policy', $policy, false);
        }

        return $response;
    }
}
