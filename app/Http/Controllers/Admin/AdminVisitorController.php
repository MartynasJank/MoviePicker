<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use App\Models\TmdbRequestLog;
use App\Models\User;

class AdminVisitorController extends Controller
{
    public function show(string $hash)
    {
        $views = PageView::where('visitor_hash', $hash)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at')
            ->get();

        // Group into sessions: gap > 30 min = new session
        $sessions    = [];
        $currentSession = [];

        foreach ($views as $i => $view) {
            if (empty($currentSession)) {
                $currentSession[] = $view;
            } else {
                $prev = end($currentSession);
                $gap  = $view->created_at->diffInSeconds($prev->created_at);
                if ($gap > 1800) {
                    $sessions[]     = $currentSession;
                    $currentSession = [$view];
                } else {
                    $currentSession[] = $view;
                }
            }
        }
        if (!empty($currentSession)) {
            $sessions[] = $currentSession;
        }

        // Compute time_on_page for each view within each session
        $processedSessions = [];
        foreach ($sessions as $session) {
            $processed = [];
            foreach ($session as $j => $view) {
                $timeOnPage = null;
                if (isset($session[$j + 1])) {
                    $timeOnPage = $view->created_at->diffInSeconds($session[$j + 1]->created_at);
                }
                $processed[] = (object)[
                    'route'       => $view->route,
                    'referrer'    => $view->referrer,
                    'created_at'  => $view->created_at,
                    'time_on_page' => $timeOnPage,
                ];
            }

            $start    = $session[0]->created_at;
            $end      = end($session)->created_at;
            $duration = $start->diffInSeconds($end);

            $processedSessions[] = (object)[
                'start'    => $start,
                'duration' => $duration,
                'pages'    => $processed,
            ];
        }

        // Resolve user from first record that has a user_id
        $userRecord = $views->firstWhere('user_id', '!=', null);
        $user       = $userRecord ? User::find($userRecord->user_id) : null;

        // Bot from first record
        $bot   = $views->first()?->bot;
        $total = $views->count();

        $tmdbLogs = TmdbRequestLog::where('visitor_hash', $hash)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        // Resolve bot/user from tmdb logs if page_views had none
        if (!$bot && !$user) {
            $firstLog = $tmdbLogs->first();
            $bot      = $bot ?? $firstLog?->bot;
            if (!$user && $firstLog?->user_id) {
                $user = User::find($firstLog->user_id);
            }
        }

        // External referrers for this visitor
        $referrerCounts = [];
        foreach ($processedSessions as $session) {
            foreach ($session->pages as $page) {
                if ($page->referrer && !str_starts_with($page->referrer, '/')) {
                    $referrerCounts[$page->referrer] = ($referrerCounts[$page->referrer] ?? 0) + 1;
                }
            }
        }
        arsort($referrerCounts);
        $referrers = array_slice($referrerCounts, 0, 5, true);

        // A→B transitions for this visitor
        $transCounts = [];
        foreach ($processedSessions as $session) {
            for ($j = 0; $j < count($session->pages) - 1; $j++) {
                $from = $session->pages[$j]->route;
                $to   = $session->pages[$j + 1]->route;
                if ($from !== $to) {
                    $key = $from . ' → ' . $to;
                    $transCounts[$key] = ($transCounts[$key] ?? 0) + 1;
                }
            }
        }
        arsort($transCounts);
        $transitions = array_slice($transCounts, 0, 10, true);

        // Hourly activity for this visitor (last 30 days)
        $hourlyRows = PageView::selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->where('visitor_hash', $hash)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupByRaw('HOUR(created_at)')
            ->get()
            ->keyBy('hour');
        $hourly = collect(range(0, 23))->map(fn($h) => (object)[
            'hour'  => $h,
            'total' => $hourlyRows->get($h)?->total ?? 0,
        ]);

        return view('admin.visitor', compact('hash', 'user', 'bot', 'processedSessions', 'total', 'tmdbLogs', 'referrers', 'transitions', 'hourly'));
    }
}
