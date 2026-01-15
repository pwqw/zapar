<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if we're in development mode with Vite
        $viteDevServer = '';
        $viteConnectSrc = '';

        if (app()->environment('local', 'development')) {
            $vitePort = env('VITE_PORT', 5173);
            $viteDevServer = " http://localhost:{$vitePort}";
            $viteConnectSrc = " http://localhost:{$vitePort} ws://localhost:{$vitePort}";
        }

        // Build CSP directives
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://app.lemonsqueezy.com{$viteDevServer}",
            "style-src 'self' 'unsafe-inline'{$viteDevServer}",
            "img-src 'self' data: https: blob:{$viteDevServer}",
            "font-src 'self' data:",
            "connect-src 'self' wss: ws:{$viteConnectSrc}",
            "media-src 'self' blob:",
            "object-src 'none'",
            "frame-src 'self' https://docs.google.com https://*.google.com",
            "child-src 'self' https://docs.google.com https://*.google.com",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        return $response;
    }
}
