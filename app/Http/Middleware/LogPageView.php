<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Support\BotDetector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            // Only log GET requests
            if (!$request->isMethod('GET')) return;

            // Skip AJAX / JSON requests
            if ($request->ajax()) return;
            if (str_contains($request->header('Accept', ''), 'application/json')) return;

            // Skip internal/admin/framework routes
            $path = ltrim($request->path(), '/');
            foreach (['admin', '_ignition', 'livewire'] as $prefix) {
                if (str_starts_with($path, $prefix)) return;
            }

            // Skip error responses
            if ($response->getStatusCode() >= 400) return;

            $bot         = BotDetector::detect($request);
            $visitorHash = BotDetector::visitorHash($request);

            // Resolve referrer
            $referrer = null;
            $rawRef   = $request->header('Referer', '');
            if ($rawRef !== '') {
                $parsed  = parse_url($rawRef);
                $appHost = $request->getHost();
                $refHost = $parsed['host'] ?? '';

                if ($refHost !== '' && $refHost === $appHost) {
                    // Same-site referrer: keep only the path
                    $referrer = ($parsed['path'] ?? '/') ?: '/';
                } elseif ($refHost !== '') {
                    // External referrer: keep domain only
                    $referrer = $refHost;
                }
            }

            // Skip admin user from traffic stats
            if (auth()->check() && auth()->user()->email === config('api.admin_email')) return;

            // Skip internal uptime monitors
            if (str_contains($request->userAgent() ?? '', 'Vitals-Monitor')) return;

            $route = $request->path() === '/' ? '/' : '/' . ltrim($request->path(), '/');

            PageView::create([
                'visitor_hash' => $visitorHash ?: null,
                'user_id'      => auth()->id(),
                'bot'          => $bot,
                'route'        => $route,
                'referrer'     => $referrer,
                'user_agent'   => substr($request->userAgent() ?? '', 0, 512) ?: null,
            ]);
        } catch (\Throwable) {
            // Never break requests
        }
    }
}
