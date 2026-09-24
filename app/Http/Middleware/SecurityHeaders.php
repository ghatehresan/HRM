<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SecurityHeaders
{
    /**
     * Baseline response hardening (SECURITY.md §6). The CSP value lives
     * in config hrm.security.csp and deliberately has no 'unsafe-inline'.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if ($response instanceof SymfonyResponse) {
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set(
                'Content-Security-Policy',
                (string) config('hrm.security.csp', "default-src 'self'")
            );
        }

        return $response;
    }
}
