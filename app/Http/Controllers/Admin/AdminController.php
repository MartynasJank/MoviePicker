<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use App\Models\Roulette;
use App\Models\Setting;
use App\Models\TmdbRequestLog;
use App\Models\User;

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
        $activeTab  = request('tab', 'roulettes');
        $tmdb       = [];

        if (in_array($activeTab, ['tmdb', 'traffic'])) {
            $today     = now()->toDateString();
            $sevenDays = now()->subDays(6)->startOfDay();

            $tmdb['today'] = TmdbRequestLog::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN cached = 0 THEN 1 ELSE 0 END) as live,
                SUM(CASE WHEN cached = 1 THEN 1 ELSE 0 END) as hits,
                AVG(CASE WHEN cached = 0 THEN response_time_ms ELSE NULL END) as avg_ms,
                SUM(CASE WHEN status_code = 429 THEN 1 ELSE 0 END) as rate_limited,
                SUM(CASE WHEN bot IS NULL THEN 1 ELSE 0 END) as human_total,
                SUM(CASE WHEN bot IS NULL AND cached = 0 THEN 1 ELSE 0 END) as human_live,
                SUM(CASE WHEN bot IS NULL AND cached = 1 THEN 1 ELSE 0 END) as human_hits,
                AVG(CASE WHEN bot IS NULL AND cached = 0 THEN response_time_ms ELSE NULL END) as human_avg_ms,
                COUNT(DISTINCT user_id) as unique_auth,
                COUNT(DISTINCT CASE WHEN user_id IS NULL AND bot IS NULL THEN visitor_hash END) as unique_anon,
                SUM(CASE WHEN bot IS NOT NULL THEN 1 ELSE 0 END) as bot_total
            ')->whereDate('created_at', $today)->first();

            $tmdb['bots_today'] = TmdbRequestLog::selectRaw('bot, COUNT(*) as total')
                ->whereDate('created_at', $today)
                ->whereNotNull('bot')
                ->groupBy('bot')
                ->orderByDesc('total')
                ->get();

            $tmdb['by_endpoint_bots'] = TmdbRequestLog::selectRaw('endpoint, COUNT(*) as total')
                ->whereDate('created_at', $today)
                ->whereNotNull('bot')
                ->groupBy('endpoint')
                ->orderByDesc('total')
                ->get();

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
                SUM(CASE WHEN cached = 1 THEN 1 ELSE 0 END) as hits,
                SUM(CASE WHEN bot IS NULL THEN 1 ELSE 0 END) as human_total,
                SUM(CASE WHEN bot IS NOT NULL THEN 1 ELSE 0 END) as bot_count,
                COUNT(DISTINCT user_id) as unique_auth,
                COUNT(DISTINCT CASE WHEN user_id IS NULL AND bot IS NULL THEN visitor_hash END) as unique_anon
            ')->where('created_at', '>=', $sevenDays)
              ->groupByRaw('DATE(created_at)')
              ->orderByDesc('date')
              ->get();

            $rpm = 1.50;
            $tmdb['rpm']           = $rpm;
            $tmdb['revenue_today'] = round((($tmdb['today']->human_total ?? 0) / 1000) * $rpm, 2);
            $tmdb['revenue_week']  = round(($tmdb['daily']->sum('human_total') / 1000) * $rpm, 2);

            $topAuth = TmdbRequestLog::selectRaw('user_id, COUNT(*) as total')
                ->whereDate('created_at', $today)
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->limit(10)
                ->with('user')
                ->get()
                ->map(fn($r) => (object)[
                    'label'   => $r->user?->name ?? "User #{$r->user_id}",
                    'type'    => 'auth',
                    'total'   => $r->total,
                    'user_id' => $r->user_id,
                    'hash'    => null,
                ]);

            $topAnon = TmdbRequestLog::selectRaw('visitor_hash, COUNT(*) as total')
                ->whereDate('created_at', $today)
                ->whereNull('user_id')
                ->whereNull('bot')
                ->whereNotNull('visitor_hash')
                ->groupBy('visitor_hash')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn($r) => (object)[
                    'label'   => $r->visitor_hash,
                    'type'    => 'anon',
                    'total'   => $r->total,
                    'user_id' => null,
                    'hash'    => $r->visitor_hash,
                ]);

            $tmdb['top_users'] = $topAuth->concat($topAnon)->sortByDesc('total')->take(10)->values();

            // Page views: top pages today (human only)
            $tmdb['top_pages'] = PageView::selectRaw('route, COUNT(*) as total')
                ->whereDate('created_at', $today)
                ->whereNull('bot')
                ->groupBy('route')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            // Page views: recent unique visitors in last 24h
            $recentVisitorRows = PageView::selectRaw('visitor_hash, user_id, bot, COUNT(*) as page_count, MAX(created_at) as last_seen')
                ->where('created_at', '>=', now()->subHours(24))
                ->groupBy('visitor_hash', 'user_id', 'bot')
                ->orderByDesc('last_seen')
                ->limit(20)
                ->get();

            // Eager-load users separately
            $userIds = $recentVisitorRows->pluck('user_id')->filter()->unique()->values();
            $usersById = User::whereIn('id', $userIds)->get()->keyBy('id');

            $tmdb['recent_visitors'] = $recentVisitorRows->map(function ($row) use ($usersById) {
                $row->user = isset($row->user_id) ? ($usersById[$row->user_id] ?? null) : null;
                return $row;
            });

            // Active now: unique human visitors in last 10 minutes
            $tmdb['active_now'] = PageView::whereNull('bot')
                ->where('created_at', '>=', now()->subMinutes(10))
                ->distinct('visitor_hash')
                ->count('visitor_hash');

            // Hourly traffic today (human only) — fill all 24 hours
            $hourlyRows = PageView::selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
                ->whereDate('created_at', $today)
                ->whereNull('bot')
                ->groupByRaw('HOUR(created_at)')
                ->get()
                ->keyBy('hour');
            $tmdb['hourly'] = collect(range(0, 23))->map(fn($h) => (object)[
                'hour'  => $h,
                'total' => $hourlyRows->get($h)?->total ?? 0,
            ]);

            // Top external referrers today (external = stored as domain, not /path)
            $tmdb['referrers'] = PageView::selectRaw('referrer, COUNT(*) as total')
                ->whereDate('created_at', $today)
                ->whereNull('bot')
                ->whereNotNull('referrer')
                ->where('referrer', 'not like', '/%')
                ->groupBy('referrer')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            // Bounce rate + avg session length from today's page views
            $todayViews = PageView::whereDate('created_at', $today)
                ->whereNull('bot')
                ->whereNotNull('visitor_hash')
                ->orderBy('visitor_hash')
                ->orderBy('created_at')
                ->get(['visitor_hash', 'created_at']);

            $sessionStats = [];
            foreach ($todayViews->groupBy('visitor_hash') as $views) {
                $session = [];
                foreach ($views->sortBy('created_at') as $view) {
                    if (empty($session) || $view->created_at->diffInSeconds(end($session)->created_at) <= 1800) {
                        $session[] = $view;
                    } else {
                        $sessionStats[] = ['pages' => count($session), 'secs' => $session[0]->created_at->diffInSeconds(end($session)->created_at)];
                        $session = [$view];
                    }
                }
                if (!empty($session)) {
                    $sessionStats[] = ['pages' => count($session), 'secs' => $session[0]->created_at->diffInSeconds(end($session)->created_at)];
                }
            }
            $totalSess = count($sessionStats);
            $bounced   = count(array_filter($sessionStats, fn($s) => $s['pages'] === 1));
            $tmdb['bounce_rate']      = $totalSess > 0 ? round(($bounced / $totalSess) * 100) : null;
            $tmdb['avg_session_secs'] = $totalSess > 0 ? (int) round(array_sum(array_column($sessionStats, 'secs')) / $totalSess) : null;

            // A→B transitions — last 7 days, human, PHP session grouping
            $recentViews = PageView::where('created_at', '>=', $sevenDays)
                ->whereNull('bot')
                ->whereNotNull('visitor_hash')
                ->orderBy('visitor_hash')
                ->orderBy('created_at')
                ->get(['visitor_hash', 'route', 'created_at']);

            $transCounts = [];
            foreach ($recentViews->groupBy('visitor_hash') as $views) {
                $sorted = $views->sortBy('created_at')->values();
                for ($i = 0; $i < $sorted->count() - 1; $i++) {
                    $curr = $sorted[$i];
                    $next = $sorted[$i + 1];
                    if ($next->created_at->diffInSeconds($curr->created_at) <= 1800 && $curr->route !== $next->route) {
                        $key = $curr->route . ' → ' . $next->route;
                        $transCounts[$key] = ($transCounts[$key] ?? 0) + 1;
                    }
                }
            }
            arsort($transCounts);
            $tmdb['transitions'] = array_slice($transCounts, 0, 10, true);

            $tmdb['recent'] = TmdbRequestLog::with('user')
                ->orderByDesc('id')
                ->paginate(25)
                ->appends(['tab' => 'tmdb']);
        }

        return view('admin.dashboard', compact('stats', 'rowOrder', 'activeTab', 'tmdb'));
    }
}
