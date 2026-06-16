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

        if ($activeTab === 'tmdb') {
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

            $tmdb['last_60s']  = TmdbRequestLog::where('cached', false)
                ->where('created_at', '>=', now()->subSeconds(60))->count();

            $tmdb['last_5min'] = TmdbRequestLog::where('cached', false)
                ->where('created_at', '>=', now()->subMinutes(5))->count();

            $tmdb['req_per_sec']    = round($tmdb['last_60s'] / 60, 2);
            $tmdb['rate_limit_max'] = 40;
            $tmdb['rate_pct']       = min(100, round(($tmdb['req_per_sec'] / 40) * 100));

            $tmdb['per_minute'] = TmdbRequestLog::selectRaw("
                DATE_FORMAT(created_at, '%H:%i') as minute,
                COUNT(*) as live
            ")->where('cached', false)
              ->where('created_at', '>=', now()->subMinutes(30))
              ->groupByRaw("DATE_FORMAT(created_at, '%H:%i')")
              ->orderByDesc('minute')
              ->get();

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

            $tmdb['recent'] = TmdbRequestLog::with('user')
                ->orderByDesc('id')
                ->paginate(25)
                ->appends(['tab' => 'tmdb']);
        }

        if ($activeTab === 'traffic') {
            $today     = now()->toDateString();
            $sevenDays = now()->subDays(6)->startOfDay();
            $rpm = 1.50;
            $tmdb['rpm'] = $rpm;

            $tmdb['traffic_today'] = PageView::selectRaw('
                SUM(CASE WHEN bot IS NULL THEN 1 ELSE 0 END) as human_total,
                SUM(CASE WHEN bot IS NOT NULL THEN 1 ELSE 0 END) as bot_total,
                COUNT(DISTINCT CASE WHEN bot IS NULL AND user_id IS NOT NULL THEN user_id END) as unique_auth,
                COUNT(DISTINCT CASE WHEN bot IS NULL AND user_id IS NULL THEN visitor_hash END) as unique_anon
            ')->whereDate('created_at', $today)->first();

            $tmdb['revenue_today'] = round((($tmdb['traffic_today']->human_total ?? 0) / 1000) * $rpm, 2);

            $tmdb['daily'] = PageView::selectRaw('
                DATE(created_at) as date,
                SUM(CASE WHEN bot IS NULL THEN 1 ELSE 0 END) as human_total,
                SUM(CASE WHEN bot IS NOT NULL THEN 1 ELSE 0 END) as bot_count,
                COUNT(DISTINCT CASE WHEN bot IS NULL AND user_id IS NOT NULL THEN user_id END) as unique_auth,
                COUNT(DISTINCT CASE WHEN bot IS NULL AND user_id IS NULL THEN visitor_hash END) as unique_anon
            ')->where('created_at', '>=', $sevenDays)
              ->groupByRaw('DATE(created_at)')
              ->orderByDesc('date')
              ->get();

            $tmdb['revenue_week'] = round(($tmdb['daily']->sum('human_total') / 1000) * $rpm, 2);

            $tmdb['bots_today'] = PageView::selectRaw('bot, COUNT(*) as total')
                ->whereDate('created_at', $today)
                ->whereNotNull('bot')
                ->groupBy('bot')
                ->orderByDesc('total')
                ->get();

            $topAuth = PageView::selectRaw('user_id, COUNT(*) as total')
                ->whereDate('created_at', $today)
                ->whereNotNull('user_id')
                ->whereNull('bot')
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            $authUsers = User::whereIn('id', $topAuth->pluck('user_id'))->get()->keyBy('id');
            $topAuth = $topAuth->map(fn($r) => (object)[
                'label'   => $authUsers[$r->user_id]?->name ?? "User #{$r->user_id}",
                'type'    => 'auth',
                'total'   => $r->total,
                'user_id' => $r->user_id,
                'hash'    => null,
            ]);

            $topAnon = PageView::selectRaw('visitor_hash, COUNT(*) as total')
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

            $tmdb['bot_pages_today']   = PageView::whereDate('created_at', $today)->whereNotNull('bot')->count();
            $tmdb['human_pages_today'] = PageView::whereDate('created_at', $today)->whereNull('bot')->count();

            $tmdb['top_pages'] = PageView::selectRaw('route, COUNT(*) as total')
                ->whereDate('created_at', $today)
                ->whereNull('bot')
                ->groupBy('route')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            $recentVisitorRows = PageView::selectRaw('visitor_hash, user_id, bot, COUNT(*) as page_count, MAX(created_at) as last_seen')
                ->where('created_at', '>=', now()->subHours(24))
                ->groupBy('visitor_hash', 'user_id', 'bot')
                ->orderByDesc('last_seen')
                ->limit(20)
                ->get();

            $userIds   = $recentVisitorRows->pluck('user_id')->filter()->unique()->values();
            $usersById = User::whereIn('id', $userIds)->get()->keyBy('id');

            $tmdb['recent_visitors'] = $recentVisitorRows->map(function ($row) use ($usersById) {
                $row->user = isset($row->user_id) ? ($usersById[$row->user_id] ?? null) : null;
                return $row;
            });

            $tmdb['active_now'] = PageView::whereNull('bot')
                ->where('created_at', '>=', now()->subMinutes(10))
                ->distinct('visitor_hash')
                ->count('visitor_hash');

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

            $tmdb['referrers'] = PageView::selectRaw('referrer, COUNT(*) as total')
                ->whereDate('created_at', $today)
                ->whereNull('bot')
                ->whereNotNull('referrer')
                ->where('referrer', 'not like', '/%')
                ->groupBy('referrer')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

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
        }

        return view('admin.dashboard', compact('stats', 'rowOrder', 'activeTab', 'tmdb'));
    }

    public function visitorsData()
    {
        $sort    = request('sort', 'last_seen');
        $orderBy = $sort === 'pages' ? 'page_count' : 'last_seen';

        $rows = PageView::selectRaw('visitor_hash, user_id, bot, COUNT(*) as page_count, MAX(created_at) as last_seen')
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('visitor_hash', 'user_id', 'bot')
            ->orderByDesc($orderBy)
            ->limit(20)
            ->get();

        $userIds  = $rows->pluck('user_id')->filter()->unique();
        $usersById = User::whereIn('id', $userIds)->get()->keyBy('id');

        return response()->json($rows->map(function ($row) use ($usersById) {
            $user = $row->user_id ? ($usersById[$row->user_id] ?? null) : null;
            return [
                'hash'       => $row->visitor_hash,
                'bot'        => $row->bot,
                'page_count' => $row->page_count,
                'last_seen'  => \Carbon\Carbon::parse($row->last_seen)->timestamp,
                'user_name'  => $user?->name,
                'user_id'    => $row->user_id,
            ];
        })->values());
    }
}
