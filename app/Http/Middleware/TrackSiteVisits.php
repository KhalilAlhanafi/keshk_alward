<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackSiteVisits
{
    /**
     * Handle an incoming request to track visitor traffic.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track successful GET requests and exclude admin, API, and internal routes
        if ($request->isMethod('GET') && !$request->ajax() && !$request->wantsJson()) {
            $path = $request->path();
            if (
                !str_starts_with($path, 'admin') &&
                !str_starts_with($path, 'api') &&
                !str_starts_with($path, 'up') &&
                !str_starts_with($path, '_') &&
                !str_starts_with($path, 'livewire')
            ) {
                $ip = $request->ip();
                $today = now()->toDateString();
                
                // Track daily unique visit per IP per hour to avoid spamming the DB
                $cacheKey = 'visit_tracked_' . md5($ip . '_' . $today . '_' . date('H'));

                if (!Cache::has($cacheKey)) {
                    Cache::put($cacheKey, true, now()->addHours(1));

                    try {
                        SiteVisit::create([
                            'ip_address'   => $ip,
                            'user_id'      => auth()->id(),
                            'url'          => mb_substr($request->fullUrl(), 0, 500),
                            'user_agent'   => mb_substr($request->userAgent() ?? '', 0, 500),
                            'visited_date' => $today,
                        ]);

                        // Invalidate admin dashboard cached stats
                        Cache::forget('admin_dashboard_stats');
                    } catch (\Throwable $e) {
                        // Fail silently so user experience is never affected
                    }
                }
            }
        }

        return $response;
    }
}
