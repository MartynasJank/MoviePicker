<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use App\Models\Roulette;
use App\Models\Setting;
use App\Models\TmdbRequestLog;
use App\Models\User;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::whereHas('roulettes')
            ->withCount('roulettes')
            ->orderByDesc('roulettes_count')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $roulettes = Roulette::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $rowOrder = Setting::get('user_row_order.' . $user->id, []);

        $grouped = $roulettes->groupBy(fn(Roulette $r) => $r->row ?? 'Uncategorised');

        $ordered = collect($rowOrder)
            ->mapWithKeys(fn($g) => [$g => $grouped->get($g, collect())]);

        foreach ($grouped as $name => $items) {
            if (!in_array($name, $rowOrder)) {
                $ungrouped = $ordered->get('Uncategorised', collect())->merge($items);
                $ordered->put('Uncategorised', $ungrouped);
            }
        }

        $tmdbLogs = TmdbRequestLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $pageViewRows = PageView::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at')
            ->get();

        $sessions = [];
        $current  = [];
        foreach ($pageViewRows as $view) {
            if (empty($current)) {
                $current[] = $view;
            } else {
                $gap = $view->created_at->diffInSeconds(end($current)->created_at);
                if ($gap > 1800) { $sessions[] = $current; $current = []; }
                $current[] = $view;
            }
        }
        if (!empty($current)) $sessions[] = $current;

        $processedSessions = [];
        foreach ($sessions as $session) {
            $pages = [];
            foreach ($session as $j => $view) {
                $pages[] = (object)[
                    'route'        => $view->route,
                    'referrer'     => $view->referrer,
                    'created_at'   => $view->created_at,
                    'time_on_page' => isset($session[$j + 1])
                        ? $view->created_at->diffInSeconds($session[$j + 1]->created_at)
                        : null,
                ];
            }
            $start = $session[0]->created_at;
            $end   = end($session)->created_at;
            $processedSessions[] = (object)[
                'start'    => $start,
                'duration' => $start->diffInSeconds($end),
                'pages'    => $pages,
            ];
        }

        // External referrers for this user
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

        // Hourly activity for this user (last 30 days)
        $hourlyRows = PageView::selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupByRaw('HOUR(created_at)')
            ->get()
            ->keyBy('hour');
        $hourly = collect(range(0, 23))->map(fn($h) => (object)[
            'hour'  => $h,
            'total' => $hourlyRows->get($h)?->total ?? 0,
        ]);

        return view('admin.users.show', compact('user', 'ordered', 'roulettes', 'tmdbLogs', 'processedSessions', 'referrers', 'hourly'));
    }

    public function destroyRoulette(User $user, Roulette $roulette)
    {
        abort_if($roulette->user_id !== $user->id, 404);
        $roulette->delete();
        return redirect()->route('admin.users.show', $user)->with('success', "Deleted \"{$roulette->name}\".");
    }
}
