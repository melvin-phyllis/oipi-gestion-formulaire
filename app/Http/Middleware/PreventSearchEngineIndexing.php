<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PreventSearchEngineIndexing
{
    /**
     * Empêche les moteurs de recherche d'indexer ou d'archiver l'application privée.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set(
            'X-Robots-Tag',
            'noindex, nofollow, noarchive, nosnippet',
        );

        return $response;
    }
}
