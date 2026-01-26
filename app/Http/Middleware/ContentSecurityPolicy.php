<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        // Get all unique stream hosts from radio stations (cached for 1 hour)
        $radioStreamHosts = $this->getRadioStreamHosts();

        // Get all unique hosts from podcast episodes (cached for 1 hour)
        $podcastEpisodeHosts = $this->getPodcastEpisodeHosts();

        // Merge and deduplicate all media hosts
        $allMediaHosts = array_unique(array_merge($radioStreamHosts, $podcastEpisodeHosts));
        $mediaHostsStr = !empty($allMediaHosts) ? ' ' . implode(' ', $allMediaHosts) : '';

        // Build CSP directives
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://app.lemonsqueezy.com{$viteDevServer}",
            "style-src 'self' 'unsafe-inline'{$viteDevServer}",
            "img-src 'self' data: https: blob:{$viteDevServer}",
            "font-src 'self' data:",
            "connect-src 'self' wss: ws:{$viteConnectSrc}",
            "media-src 'self' blob:{$mediaHostsStr}",
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

    /** @return array<string> */
    private function getRadioStreamHosts(): array
    {
        return Cache::remember('radio_station_stream_hosts_v2', 3600, function (): array {
            try {
                return DB::table('radio_stations')
                    ->whereNotNull('stream_host')
                    ->distinct()
                    ->pluck('stream_host')
                    ->toArray();
            } catch (\Illuminate\Database\QueryException $e) {
                // If column doesn't exist or query fails, return empty array
                return [];
            }
        });
    }

    /** @return array<string> */
    private function getPodcastEpisodeHosts(): array
    {
        return Cache::remember('podcast_episode_hosts_v1', 3600, function (): array {
            try {
                // Get unique hosts from podcast episode URLs (songs with podcast_id)
                $paths = DB::table('songs')
                    ->whereNotNull('podcast_id')
                    ->distinct()
                    ->pluck('path')
                    ->toArray();

                $hosts = [];
                foreach ($paths as $path) {
                    $parsed = parse_url($path);
                    if (!isset($parsed['host'])) {
                        continue;
                    }

                    $scheme = $parsed['scheme'] ?? 'https';
                    $host = $parsed['host'];
                    $port = $parsed['port'] ?? null;

                    $fullHost = $scheme . '://' . $host;
                    if ($port && !in_array($port, [80, 443], true)) {
                        $fullHost .= ':' . $port;
                    }

                    $hosts[$fullHost] = true;
                }

                return array_keys($hosts);
            } catch (\Illuminate\Database\QueryException $e) {
                return [];
            }
        });
    }
}
