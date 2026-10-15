<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

final class AddContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce(base64_encode(random_bytes(24)));
        $nonce = Vite::cspNonce();
        $originalHotFile = Vite::hotFile();
        if (! app()->environment('local')) {
            Vite::useHotFile(storage_path('framework/.csp-hot-disabled-'.hash('sha256', $nonce)));
        }
        try {
            $response = $next($request);
        } finally {
            Vite::useHotFile($originalHotFile);
        }
        $existingPolicies = $response->headers->all('Content-Security-Policy');
        $response->headers->set('Content-Security-Policy', [$this->policy($nonce), ...$existingPolicies]);

        if (str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    private function policy(string $nonce): string
    {
        $scriptSources = ["'self'", "'nonce-{$nonce}'", "'strict-dynamic'"];
        $styleSources = ["'self'", "'nonce-{$nonce}'"];
        $connectSources = ["'self'"];
        $cluster = (string) config('broadcasting.connections.pusher.options.cluster', 'eu');

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $cluster)) {
            $connectSources[] = 'wss://ws-'.$cluster.'.pusher.com';
            $connectSources[] = 'https://sockjs-'.$cluster.'.pusher.com';
        }

        $developmentOrigin = $this->developmentOrigin();
        if ($developmentOrigin !== null) {
            $scriptSources[] = $developmentOrigin;
            $styleSources[] = $developmentOrigin;
            $connectSources[] = $developmentOrigin;
            $connectSources[] = preg_replace('/^http/', 'ws', $developmentOrigin);
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'none'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "frame-src 'none'",
            "form-action 'self'",
            'script-src '.implode(' ', $scriptSources),
            "script-src-attr 'none'",
            "style-src 'self' 'unsafe-inline'",
            'style-src-elem '.implode(' ', $styleSources),
            "style-src-attr 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            'connect-src '.implode(' ', $connectSources),
            "media-src 'none'",
            "worker-src 'none'",
            "manifest-src 'self'",
        ];

        if (app()->environment('production') && parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https') {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }

    private function developmentOrigin(): ?string
    {
        if (! app()->environment('local') || ! Vite::isRunningHot()) {
            return null;
        }

        $url = trim((string) file_get_contents(Vite::hotFile(), false, null, 0, 4096));
        $parts = parse_url($url);
        if ($parts === false || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])
            || ! preg_match('/^(?:[a-z0-9.-]+|\[[a-f0-9:]+\])$/i', $parts['host'] ?? '')) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
