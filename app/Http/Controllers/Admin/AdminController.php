<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Roulette;
use App\Models\Setting;
use App\Models\TmdbRequestLog;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total'     => Roulette::count(),
            'public'    => Roulette::where('is_public', true)->count(),
            'system'    => Roulette::where('is_system', true)->count(),
            'community' => Roulette::where('is_system', false)->count(),
        ];

        $rowOrder   = Setting::get('roulette_row_order', []);
        $activeTab  = request('tab', 'overview');
        $tmdb       = [];

        if ($activeTab === 'tmdb') {
            $today     = now()->toDateString();
            $sevenDays = now()->subDays(6)->startOfDay();

            $tmdb['today'] = TmdbRequestLog::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN cached = 0 THEN 1 ELSE 0 END) as live,
                SUM(CASE WHEN cached = 1 THEN 1 ELSE 0 END) as hits,
                AVG(CASE WHEN cached = 0 THEN response_time_ms ELSE NULL END) as avg_ms,
                SUM(CASE WHEN status_code = 429 THEN 1 ELSE 0 END) as rate_limited
            ')->whereDate('created_at', $today)->first();

            // Short-window live request counts for rate limit monitoring
            $tmdb['last_60s']  = TmdbRequestLog::where('cached', false)
                ->where('created_at', '>=', now()->subSeconds(60))->count();

            $tmdb['last_5min'] = TmdbRequestLog::where('cached', false)
                ->where('created_at', '>=', now()->subMinutes(5))->count();

            // Current avg req/sec (live only, over last 60s) vs TMDB limit of 40/s
            $tmdb['req_per_sec']    = round($tmdb['last_60s'] / 60, 2);
            $tmdb['rate_limit_max'] = 40;
            $tmdb['rate_pct']       = min(100, round(($tmdb['req_per_sec'] / 40) * 100));

            // Per-minute breakdown for last 30 minutes (live requests only)
            $tmdb['per_minute'] = TmdbRequestLog::selectRaw("
                DATE_FORMAT(created_at, '%H:%i') as minute,
                COUNT(*) as live
            ")->where('cached', false)
              ->where('created_at', '>=', now()->subMinutes(30))
              ->groupByRaw("DATE_FORMAT(created_at, '%H:%i')")
              ->orderByDesc('minute')
              ->get();

            // Peak req/sec in last 30 min (max live requests in any single minute / 60)
            $tmdb['peak_req_per_sec'] = $tmdb['per_minute']->max('live')
                ? round($tmdb['per_minute']->max('live') / 60, 2)
                : 0;

            $tmdb['by_endpoint'] = TmdbRequestLog::selectRaw('
                endpoint,
                COUNT(*) as total,
                SUM(CASE WHEN cached = 0 THEN 1 ELSE 0 END) as live,
                SUM(CASE WHEN cached = 1 THEN 1 ELSE 0 END) as hits,
                AVG(CASE WHEN cached = 0 THEN response_time_ms ELSE NULL END) as avg_ms
            ')->whereDate('created_at', $today)
              ->groupBy('endpoint')
              ->orderByDesc('total')
              ->get();

            $tmdb['daily'] = TmdbRequestLog::selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN cached = 0 THEN 1 ELSE 0 END) as live,
                SUM(CASE WHEN cached = 1 THEN 1 ELSE 0 END) as hits
            ')->where('created_at', '>=', $sevenDays)
              ->groupByRaw('DATE(created_at)')
              ->orderByDesc('date')
              ->get();

            $tmdb['recent'] = TmdbRequestLog::with('user')
                ->orderByDesc('id')
                ->limit(50)
                ->get();
        }

        return view('admin.dashboard', compact('stats', 'rowOrder', 'activeTab', 'tmdb'));
    }
}
