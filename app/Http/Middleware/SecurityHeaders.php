<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $h = $response->headers;

        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->is('admin*')) {
            $h->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if (app()->isProduction()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            // Alpine (non-CSP build) needs unsafe-eval; tighten if you switch to @alpinejs/csp.
            $h->set('Content-Security-Policy', "default-src 'self'; "
                . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; "
                . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; "
                . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; "
                . "img-src 'self' data: https:; media-src 'self' blob: https:; connect-src 'self'; "
                . "frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
        }

        return $response;
    }
}
