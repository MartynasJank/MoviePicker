@extends('layouts.app')
@section('page_title', 'Admin Dashboard')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">

    <div class="mb-8 flex items-center gap-3">
        <h1 class="text-2xl font-bold text-white">Admin</h1>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 border border-red-500/20">Admin</span>
    </div>

    @include('admin._nav')

    {{-- Tabs --}}
    <div class="flex gap-1 mb-8 border-b border-white/5 pb-0">
        <a href="{{ route('admin.dashboard') }}"
           class="px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 transition-colors -mb-px
                  {{ $activeTab === 'roulettes' ? 'border-accent text-white' : 'border-transparent text-gray-500 hover:text-white' }}">
            Roulettes
        </a>
        <a href="{{ route('admin.dashboard', ['tab' => 'traffic']) }}"
           class="px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 transition-colors -mb-px
                  {{ $activeTab === 'traffic' ? 'border-accent text-white' : 'border-transparent text-gray-500 hover:text-white' }}">
            Traffic
        </a>
        <a href="{{ route('admin.dashboard', ['tab' => 'tmdb']) }}"
           class="px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 transition-colors -mb-px
                  {{ $activeTab === 'tmdb' ? 'border-accent text-white' : 'border-transparent text-gray-500 hover:text-white' }}">
            TMDB
        </a>
    </div>

    {{-- ================================================================ --}}
    {{-- ROULETTES TAB                                                     --}}
    {{-- ================================================================ --}}
    @if($activeTab === 'roulettes')

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-10">
            <div class="bg-white/3 border border-white/5 rounded-xl p-5">
                <div class="text-3xl font-bold text-white mb-1">{{ $stats['total'] }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Total Roulettes</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-5">
                <div class="text-3xl font-bold text-accent mb-1">{{ $stats['public'] }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Public</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-5">
                <div class="text-3xl font-bold text-blue-400 mb-1">{{ $stats['system'] }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">System</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-5">
                <div class="text-3xl font-bold text-purple-400 mb-1">{{ $stats['community'] }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Community</div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="{{ route('admin.roulettes.index') }}"
               class="group bg-white/3 border border-white/5 hover:border-white/10 rounded-xl p-6 transition-colors">
                <div class="text-lg font-semibold text-white mb-1 group-hover:text-accent transition-colors">Manage Roulettes →</div>
                <div class="text-sm text-gray-500">Edit, reorder, publish or hide roulettes by group.</div>
            </a>
            <a href="{{ route('admin.rows.index') }}"
               class="group bg-white/3 border border-white/5 hover:border-white/10 rounded-xl p-6 transition-colors">
                <div class="text-lg font-semibold text-white mb-1 group-hover:text-accent transition-colors">Row Order →</div>
                <div class="text-sm text-gray-500">Drag to reorder how rows appear on the /roulettes page.</div>
                @if(count($rowOrder))
                <div class="mt-3 flex flex-wrap gap-1">
                    @foreach($rowOrder as $row)
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-white/5 text-gray-400 border border-white/10">{{ $row }}</span>
                    @endforeach
                </div>
                @endif
            </a>
            <a href="{{ route('admin.roulettes.create') }}"
               class="group bg-white/3 border border-white/5 hover:border-white/10 rounded-xl p-6 transition-colors">
                <div class="text-lg font-semibold text-white mb-1 group-hover:text-accent transition-colors">+ New Roulette →</div>
                <div class="text-sm text-gray-500">Create a new curated collection.</div>
            </a>
            <a href="/roulettes" target="_blank"
               class="group bg-white/3 border border-white/5 hover:border-white/10 rounded-xl p-6 transition-colors">
                <div class="text-lg font-semibold text-white mb-1 group-hover:text-accent transition-colors">View /roulettes ↗</div>
                <div class="text-sm text-gray-500">See the public-facing roulettes page.</div>
            </a>
        </div>

    {{-- ================================================================ --}}
    {{-- TRAFFIC TAB                                                        --}}
    {{-- ================================================================ --}}
    @elseif($activeTab === 'traffic')

        @php
            $today        = $tmdb['today'];
            $humanTotal   = $today->human_total ?? 0;
            $botTotal     = $today->bot_total ?? 0;
            $botPageTotal  = $tmdb['bot_pages_today'] + $tmdb['human_pages_today'];
            $botPct        = $botPageTotal > 0 ? number_format(($tmdb['bot_pages_today'] / $botPageTotal) * 100, 2) : '0.00';
            $humanHitRate = $humanTotal > 0 ? round((($today->human_hits ?? 0) / $humanTotal) * 100) : 0;
            $uniqueTotal  = ($today->unique_auth ?? 0) + ($today->unique_anon ?? 0);
            $projected    = $tmdb['revenue_week'] > 0 ? round(($tmdb['revenue_week'] / 7) * 30, 2) : 0;
            $avgSecs      = $tmdb['avg_session_secs'];
            $avgDisplay   = $avgSecs !== null ? ($avgSecs >= 60 ? floor($avgSecs/60).'m '.($avgSecs%60).'s' : $avgSecs.'s') : '—';
        @endphp

        {{-- Revenue --}}
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Estimated Ad Revenue</h2>
        <div class="bg-white/3 border border-white/5 rounded-xl p-5 mb-8">
            <div class="text-xs text-gray-600 mb-4">Human requests as page view proxy @ ${{ $tmdb['rpm'] }} RPM &middot; {{ number_format($humanTotal) }} human requests today</div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <div class="text-2xl font-bold text-accent mb-1">${{ number_format($tmdb['revenue_today'], 2) }}</div>
                    <div class="text-xs text-gray-500">Today</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-white mb-1">${{ number_format($tmdb['revenue_week'], 2) }}</div>
                    <div class="text-xs text-gray-500">Last 7 days</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-400 mb-1">${{ number_format($projected, 2) }}</div>
                    <div class="text-xs text-gray-500">Projected / month</div>
                </div>
            </div>
        </div>

        {{-- Today: human --}}
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Today — Human Traffic</h2>
            @if($tmdb['active_now'] > 0)
                <span class="flex items-center gap-1.5 text-xs text-green-400">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                    {{ $tmdb['active_now'] }} {{ Str::plural('visitor', $tmdb['active_now']) }} now
                </span>
            @endif
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-8">
            <div class="bg-white/3 border border-white/5 rounded-xl p-4">
                <div class="text-2xl font-bold text-white mb-1">{{ number_format($humanTotal) }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Total</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-4">
                <div class="text-2xl font-bold text-blue-400 mb-1">{{ number_format($today->human_live ?? 0) }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Live</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-4">
                <div class="text-2xl font-bold text-green-400 mb-1">{{ number_format($today->human_hits ?? 0) }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Cached</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-4">
                <div class="text-2xl font-bold text-purple-400 mb-1">{{ $humanHitRate }}%</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Hit Rate</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-4">
                <div class="text-2xl font-bold text-yellow-400 mb-1">{{ $today->human_avg_ms ? round($today->human_avg_ms) . 'ms' : '—' }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Avg Response</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-4">
                <div class="text-2xl font-bold text-orange-400 mb-1">{{ $uniqueTotal }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Unique Users</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-4">
                <div class="text-2xl font-bold text-orange-300 mb-1">{{ $today->unique_auth ?? 0 }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Logged In</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-4">
                <div class="text-2xl font-bold text-orange-200 mb-1">{{ $today->unique_anon ?? 0 }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Anonymous</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-4">
                <div class="text-2xl font-bold text-white mb-1">{{ $tmdb['bounce_rate'] !== null ? $tmdb['bounce_rate'].'%' : '—' }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Bounce Rate</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-4">
                <div class="text-2xl font-bold text-white mb-1">{{ $avgDisplay }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Avg Session</div>
            </div>
        </div>

        {{-- Today: bots --}}
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Today — Bot Traffic</h2>
        <div class="grid grid-cols-2 gap-3 mb-8">
            <div class="bg-white/3 border border-white/5 rounded-xl p-4">
                <div class="text-2xl font-bold text-red-400 mb-1">{{ number_format($tmdb['bot_pages_today']) }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Bot Page Visits</div>
            </div>
            <div class="bg-white/3 border border-white/5 rounded-xl p-4">
                <div class="text-2xl font-bold text-red-400 mb-1">{{ $botPct }}%</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">% of All Visits</div>
            </div>
        </div>

        {{-- Last 7 days --}}
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Last 7 Days</h2>
        <div class="bg-white/3 border border-white/5 rounded-xl overflow-x-auto mb-8">
            @if($tmdb['daily']->isEmpty())
                <div class="px-5 py-6 text-sm text-gray-500">No data yet.</div>
            @else
            <table class="w-full text-sm min-w-[520px]">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Date</th>
                        <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Human</th>
                        <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Bots</th>
                        <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Bot %</th>
                        <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Cached</th>
                        <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Hit %</th>
                        <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Uniq</th>
                        <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Est. $</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($tmdb['daily'] as $row)
                    @php
                        $pct       = $row->total > 0 ? round(($row->hits / $row->total) * 100) : 0;
                        $botPctDay = $row->total > 0 ? round((($row->bot_count ?? 0) / $row->total) * 100) : 0;
                        $uniq      = ($row->unique_auth ?? 0) + ($row->unique_anon ?? 0);
                        $estRev    = round((($row->human_total ?? 0) / 1000) * $tmdb['rpm'], 2);
                    @endphp
                    <tr>
                        <td class="px-4 py-2.5 text-gray-300">{{ \Carbon\Carbon::parse($row->date)->format('M j') }}</td>
                        <td class="px-4 py-2.5 text-right text-white">{{ number_format($row->human_total ?? 0) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-400">{{ number_format($row->bot_count ?? 0) }}</td>
                        <td class="px-4 py-2.5 text-right {{ $botPctDay >= 40 ? 'text-red-400' : 'text-gray-500' }}">{{ $botPctDay }}%</td>
                        <td class="px-4 py-2.5 text-right text-green-400">{{ number_format($row->hits) }}</td>
                        <td class="px-4 py-2.5 text-right text-purple-400">{{ $pct }}%</td>
                        <td class="px-4 py-2.5 text-right text-orange-400">{{ $uniq }}</td>
                        <td class="px-4 py-2.5 text-right text-accent">${{ number_format($estRev, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Bots breakdown + Top users side by side --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            @if($tmdb['bots_today']->isNotEmpty())
            <div>
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Bots — Today</h2>
                <div class="bg-white/3 border border-white/5 rounded-xl overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Bot</th>
                                <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Requests</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($tmdb['bots_today'] as $b)
                            <tr>
                                <td class="px-4 py-2.5 font-mono text-xs text-red-300">{{ $b->bot }}</td>
                                <td class="px-4 py-2.5 text-right text-white font-semibold">{{ number_format($b->total) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($tmdb['top_users']->isNotEmpty())
            <div>
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Top Users — Today</h2>
                <div class="bg-white/3 border border-white/5 rounded-xl overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">User</th>
                                <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Type</th>
                                <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Requests</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($tmdb['top_users'] as $u)
                            <tr>
                                <td class="px-4 py-2.5 font-mono text-xs">
                                    @if($u->type === 'auth' && $u->user_id)
                                        <a href="{{ route('admin.users.show', $u->user_id) }}" class="text-gray-300 hover:text-accent transition-colors">{{ $u->label }}</a>
                                    @elseif($u->hash)
                                        <a href="{{ route('admin.visitor.show', $u->hash) }}" class="text-gray-300 hover:text-accent transition-colors">{{ $u->label }}</a>
                                    @else
                                        <span class="text-gray-500">{{ $u->label }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    @if($u->type === 'auth')
                                        <span class="text-xs px-1.5 py-0.5 rounded-full bg-orange-500/10 text-orange-400 border border-orange-500/20">logged in</span>
                                    @else
                                        <span class="text-xs px-1.5 py-0.5 rounded-full bg-white/5 text-gray-400 border border-white/10">anon</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right text-white font-semibold">{{ number_format($u->total) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>

        {{-- Top Pages Today --}}
        @if(!empty($tmdb['top_pages']) && $tmdb['top_pages']->isNotEmpty())
        <div class="mt-8">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Top Pages — Today (Human)</h2>
            <div class="bg-white/3 border border-white/5 rounded-xl overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Route</th>
                            <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Views</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($tmdb['top_pages'] as $page)
                        <tr>
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-200">{{ $page->route }}</td>
                            <td class="px-4 py-2.5 text-right text-white font-semibold">{{ number_format($page->total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Recent Visitors --}}
        @if(!empty($tmdb['recent_visitors']) && $tmdb['recent_visitors']->isNotEmpty())
        <div class="mt-8">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Recent Visitors — Last 24h</h2>
            <div class="bg-white/3 border border-white/5 rounded-xl overflow-x-auto">
                <table class="w-full text-sm" id="visitors-table">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Visitor</th>
                            <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Type</th>
                            <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">
                                <button type="button" data-sort="pages" class="visitor-sort text-accent transition-colors">Pages ↓</button>
                            </th>
                            <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">
                                <button type="button" data-sort="last_seen" class="visitor-sort text-gray-500 hover:text-white transition-colors">Last Seen ↓</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($tmdb['recent_visitors'] as $v)
                        <tr>
                            <td class="px-4 py-2.5">
                                @if($v->visitor_hash)
                                <a href="{{ route('admin.visitor.show', $v->visitor_hash) }}" class="group flex items-baseline gap-2">
                                    <span class="font-mono text-xs text-gray-300 group-hover:text-accent transition-colors">{{ substr($v->visitor_hash, 0, 8) }}</span>
                                    @if($v->user)
                                        <span class="text-xs text-gray-500 group-hover:text-gray-300 transition-colors">{{ $v->user->name }}</span>
                                    @endif
                                </a>
                                @else
                                    <span class="font-mono text-xs text-gray-600">unknown</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5">
                                @if($v->bot)
                                    <a href="{{ $v->visitor_hash ? route('admin.visitor.show', $v->visitor_hash) : '#' }}"
                                       class="text-xs px-1.5 py-0.5 rounded-full bg-red-500/10 text-red-400 border border-red-500/20 font-mono hover:bg-red-500/20 transition-colors">{{ $v->bot }}</a>
                                @else
                                    <span class="text-xs px-1.5 py-0.5 rounded-full bg-green-500/10 text-green-400 border border-green-500/20">human</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right text-white font-semibold">{{ number_format($v->page_count) }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($v->last_seen)->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Hourly heatmap --}}
        <div class="mt-8">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Activity by Hour — Today (Human)</h2>
            <div class="bg-white/3 border border-white/5 rounded-xl p-5">
                @php
                    $hourlyMax = $tmdb['hourly']->max('total') ?: 1;
                    $currentHour = now()->hour;
                @endphp
                <div class="flex gap-1 items-end" style="height:60px">
                    @foreach($tmdb['hourly'] as $h)
                    @php
                        $barH   = $h->total > 0 ? max(4, round(($h->total / $hourlyMax) * 52)) : 2;
                        $isNow  = $h->hour === $currentHour;
                        $color  = $isNow ? 'var(--color-accent)' : 'rgba(255,255,255,0.15)';
                        $opacity = $h->total > 0 ? 1 : 0.3;
                    @endphp
                    <div class="flex-1 flex flex-col items-center justify-end gap-0.5 group relative" style="height:60px" title="{{ $h->hour }}:00 — {{ $h->total }} views">
                        <div class="w-full rounded-sm transition-opacity group-hover:opacity-100"
                             style="height:{{ $barH }}px; background:{{ $color }}; opacity:{{ $opacity }}"></div>
                    </div>
                    @endforeach
                </div>
                <div class="flex gap-1 mt-1">
                    @foreach($tmdb['hourly'] as $h)
                    <div class="flex-1 text-center">
                        @if($h->hour % 6 === 0)
                            <span class="text-[9px] text-gray-600">{{ str_pad($h->hour, 2, '0', STR_PAD_LEFT) }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
                <div class="mt-2 text-xs text-gray-600">Current hour highlighted in red · hover bars for counts</div>
            </div>
        </div>

        {{-- Top Referrers --}}
        @if($tmdb['referrers']->isNotEmpty())
        <div class="mt-8">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Top Referrers — Today</h2>
            <div class="bg-white/3 border border-white/5 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Source</th>
                            <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Visits</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($tmdb['referrers'] as $ref)
                        <tr>
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-300">{{ $ref->referrer }}</td>
                            <td class="px-4 py-2.5 text-right text-white font-semibold">{{ number_format($ref->total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    {{-- ================================================================ --}}
    {{-- TMDB TAB                                                          --}}
    {{-- ================================================================ --}}
    @else

        @php
            $today    = $tmdb['today'];
            $hitRate  = $today->total > 0 ? round(($today->hits / $today->total) * 100) : 0;
            $rps      = $tmdb['req_per_sec'];
            $pct      = $tmdb['rate_pct'];
            $barColor   = $pct >= 75 ? '#ef4444' : ($pct >= 40 ? '#f59e0b' : '#22c55e');
            $labelColor = $pct >= 75 ? 'text-red-400' : ($pct >= 40 ? 'text-yellow-400' : 'text-green-400');
            $status     = $pct >= 75 ? 'High' : ($pct >= 40 ? 'Moderate' : 'Safe');
        @endphp

        {{-- 429 warning --}}
        @if($today->rate_limited > 0)
        <div class="mb-6 flex items-center gap-3 bg-red-500/10 border border-red-500/30 rounded-xl px-5 py-4">
            <span class="text-red-400 text-xl">⚠</span>
            <div>
                <div class="text-sm font-semibold text-red-400">Rate limit hit today</div>
                <div class="text-xs text-red-400/70 mt-0.5">{{ $today->rate_limited }} request{{ $today->rate_limited > 1 ? 's' : '' }} returned 429. TMDB allows ~40 req/sec.</div>
            </div>
        </div>
        @endif

        {{-- Rate limit gauge --}}
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Rate Limit</h2>
        <div class="bg-white/3 border border-white/5 rounded-xl p-5 mb-8">
            <div class="flex items-end justify-between mb-2">
                <div>
                    <span class="text-3xl font-bold {{ $labelColor }}">{{ $rps }}</span>
                    <span class="text-sm text-gray-500 ml-1">req/sec</span>
                    <span class="ml-3 text-xs font-medium px-2 py-0.5 rounded-full {{ $pct >= 75 ? 'bg-red-500/15 text-red-400 border border-red-500/20' : ($pct >= 40 ? 'bg-yellow-500/15 text-yellow-400 border border-yellow-500/20' : 'bg-green-500/15 text-green-400 border border-green-500/20') }}">{{ $status }}</span>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-500">Peak last 30 min</div>
                    <div class="text-sm font-semibold text-gray-300">{{ $tmdb['peak_req_per_sec'] }} req/sec</div>
                </div>
            </div>
            <div class="relative h-2.5 bg-white/5 rounded-full overflow-hidden">
                <div class="absolute inset-y-0 left-0 rounded-full transition-all" style="width: {{ $pct }}%; background: {{ $barColor }}"></div>
                <div class="absolute inset-y-0 w-px bg-red-500/40" style="left: 75%"></div>
            </div>
            <div class="flex justify-between mt-1.5 text-xs text-gray-600">
                <span>0</span>
                <span class="text-red-500/50">30/s danger zone</span>
                <span>40/s limit</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-white/5">
                <div>
                    <div class="text-lg font-bold text-white">{{ $tmdb['last_60s'] }}</div>
                    <div class="text-xs text-gray-500">Live last 60s</div>
                </div>
                <div>
                    <div class="text-lg font-bold text-white">{{ $tmdb['last_5min'] }}</div>
                    <div class="text-xs text-gray-500">Live last 5 min</div>
                </div>
                <div>
                    <div class="text-lg font-bold text-white">{{ round($tmdb['last_5min'] / 5, 1) }}</div>
                    <div class="text-xs text-gray-500">Avg/min (5 min)</div>
                </div>
                <div>
                    <div class="text-lg font-bold {{ ($today->rate_limited ?? 0) > 0 ? 'text-red-400' : 'text-white' }}">{{ $today->rate_limited ?? 0 }}</div>
                    <div class="text-xs text-gray-500">429s today</div>
                </div>
            </div>
        </div>

        {{-- By endpoint --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div>
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">By Endpoint — Human</h2>
                <div class="bg-white/3 border border-white/5 rounded-xl overflow-x-auto">
                    @if($tmdb['by_endpoint']->isEmpty())
                        <div class="px-5 py-6 text-sm text-gray-500">No requests yet today.</div>
                    @else
                    <table class="w-full text-sm min-w-[380px]">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Endpoint</th>
                                <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Total</th>
                                <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Live</th>
                                <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Cached</th>
                                <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Avg ms</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($tmdb['by_endpoint'] as $row)
                            <tr>
                                <td class="px-4 py-2.5 text-gray-300 font-mono text-xs">{{ str_replace('_', '/', $row->endpoint) }}</td>
                                <td class="px-4 py-2.5 text-right text-white">{{ $row->total }}</td>
                                <td class="px-4 py-2.5 text-right text-blue-400">{{ $row->live }}</td>
                                <td class="px-4 py-2.5 text-right text-green-400">{{ $row->hits }}</td>
                                <td class="px-4 py-2.5 text-right text-gray-400">{{ $row->avg_ms ? round($row->avg_ms) : '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>

            <div>
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">By Endpoint — Bots</h2>
                <div class="bg-white/3 border border-white/5 rounded-xl overflow-x-auto">
                    @if($tmdb['by_endpoint_bots']->isEmpty())
                        <div class="px-5 py-6 text-sm text-gray-500">No bot requests today.</div>
                    @else
                    <table class="w-full text-sm min-w-[280px]">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Endpoint</th>
                                <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Requests</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($tmdb['by_endpoint_bots'] as $row)
                            <tr>
                                <td class="px-4 py-2.5 text-red-300 font-mono text-xs">{{ str_replace('_', '/', $row->endpoint) }}</td>
                                <td class="px-4 py-2.5 text-right text-white">{{ number_format($row->total) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>

        {{-- Activity chart --}}
        @if($tmdb['per_minute']->isNotEmpty())
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Activity — Last 30 Minutes <span class="normal-case font-normal text-gray-600">(live requests only)</span></h2>
        <div class="bg-white/3 border border-white/5 rounded-xl p-5 mb-8">
            @php
                $sorted  = $tmdb['per_minute']->sortBy('minute')->values();
                $maxLive = $sorted->max('live') ?: 1;
            @endphp
            <div class="flex items-end gap-1" style="height: 80px">
                @foreach($sorted as $m)
                @php
                    $barH  = max(4, round(($m->live / $maxLive) * 72));
                    $rateM = $m->live / 60;
                    $barC  = $rateM >= 30 ? '#ef4444' : ($rateM >= 15 ? '#f59e0b' : '#3b82f6');
                @endphp
                <div class="flex-1 flex flex-col items-center justify-end gap-0.5 group relative" style="height: 80px">
                    <span class="text-[9px] text-gray-500 group-hover:text-white transition-colors leading-none">{{ $m->live }}</span>
                    <div class="w-full rounded-sm opacity-60 group-hover:opacity-100 transition-opacity"
                         style="height: {{ $barH }}px; background: {{ $barC }}"></div>
                </div>
                @endforeach
            </div>
            @php $total = $sorted->count(); @endphp
            <div class="flex gap-1 mt-1">
                @foreach($sorted as $i => $m)
                <div class="flex-1 text-center">
                    @if($i === 0 || $i === $total - 1 || $i % 5 === 0)
                        <span class="text-[9px] text-gray-600">{{ $m->minute }}</span>
                    @endif
                </div>
                @endforeach
            </div>
            <div class="flex items-center gap-4 mt-3 pt-3 border-t border-white/5 text-xs text-gray-500">
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:#3b82f6"></span> Safe (&lt;15/min)</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:#f59e0b"></span> Moderate (15–30/min)</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:#ef4444"></span> High (&gt;30/min = &gt;0.5/sec)</span>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="text-left py-1.5 pr-4 text-gray-500 font-medium">Time</th>
                            <th class="text-right py-1.5 pr-4 text-gray-500 font-medium">Req</th>
                            <th class="text-right py-1.5 text-gray-500 font-medium">Req/sec avg</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($sorted->sortByDesc('minute') as $m)
                        @php $rs = round($m->live / 60, 2); @endphp
                        <tr>
                            <td class="py-1.5 pr-4 font-mono text-gray-400">{{ $m->minute }}</td>
                            <td class="py-1.5 pr-4 text-right text-white">{{ $m->live }}</td>
                            <td class="py-1.5 text-right {{ $rs >= 0.5 ? 'text-yellow-400' : 'text-gray-400' }}">{{ $rs }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Recent requests --}}
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Recent Requests</h2>
        <div class="bg-white/3 border border-white/5 rounded-xl overflow-hidden overflow-x-auto">
            @if($tmdb['recent']->isEmpty())
                <div class="px-5 py-6 text-sm text-gray-500">No requests logged yet.</div>
            @else
            <table class="w-full text-sm min-w-[780px]">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Time</th>
                        <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Route</th>
                        <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Endpoint</th>
                        <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">Type</th>
                        <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">Status</th>
                        <th class="text-right px-4 py-2.5 text-xs text-gray-500 font-medium">ms</th>
                        <th class="text-left px-4 py-2.5 text-xs text-gray-500 font-medium">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($tmdb['recent'] as $log)
                    <tr>
                        <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">{{ $log->created_at->format('H:i:s') }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-gray-500 whitespace-nowrap">{{ $log->route ?? '—' }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-gray-300">{{ str_replace('_', '/', $log->endpoint) }}</td>
                        <td class="px-4 py-2">
                            @if($log->cached)
                                <span class="text-xs px-1.5 py-0.5 rounded-full bg-green-500/10 text-green-400 border border-green-500/20">cache</span>
                            @else
                                <span class="text-xs px-1.5 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">live</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            @if($log->status_code)
                                <span class="text-xs font-mono {{ $log->status_code === 429 ? 'text-red-400' : ($log->status_code >= 400 ? 'text-orange-400' : 'text-gray-500') }}">
                                    {{ $log->status_code }}
                                </span>
                            @else
                                <span class="text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right text-xs text-gray-500">{{ $log->response_time_ms ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-400">
                            @if($log->bot)
                                @if($log->visitor_hash)
                                <a href="{{ route('admin.visitor.show', $log->visitor_hash) }}"
                                   class="px-1.5 py-0.5 rounded-full bg-red-500/10 text-red-400 border border-red-500/20 font-mono hover:bg-red-500/20 transition-colors">{{ $log->bot }}</a>
                                @else
                                <span class="px-1.5 py-0.5 rounded-full bg-red-500/10 text-red-400 border border-red-500/20 font-mono">{{ $log->bot }}</span>
                                @endif
                            @elseif($log->user)
                                <a href="{{ route('admin.users.show', $log->user_id) }}" class="hover:text-accent transition-colors">{{ $log->user->name }}</a>
                            @elseif($log->visitor_hash)
                                <a href="{{ route('admin.visitor.show', $log->visitor_hash) }}" class="font-mono hover:text-accent transition-colors">{{ $log->visitor_hash }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
        {{ $tmdb['recent']->links('admin.pagination') }}

    @endif

</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var table = document.getElementById('visitors-table');
    if (!table) return;

    var dataUrl     = '{{ route('admin.visitors.data') }}';
    var visitorBase = '{{ url('admin/visitor') }}/';
    var activeSort  = 'last_seen';

    function humanTime(isoString) {
        var diff = Math.floor((Date.now() - new Date(isoString).getTime()) / 1000);
        if (diff < 60)   return diff + 's ago';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    }

    function buildRow(v) {
        var visitorCell;
        if (v.hash) {
            var nameSpan = v.user_name
                ? '<span class="text-xs text-gray-500 group-hover:text-gray-300 transition-colors">' + v.user_name + '</span>'
                : '';
            visitorCell = '<a href="' + visitorBase + v.hash + '" class="group flex items-baseline gap-2">'
                + '<span class="font-mono text-xs text-gray-300 group-hover:text-accent transition-colors">' + v.hash.slice(0, 8) + '</span>'
                + nameSpan + '</a>';
        } else {
            visitorCell = '<span class="font-mono text-xs text-gray-600">unknown</span>';
        }

        var typeCell;
        if (v.bot) {
            var href = v.hash ? visitorBase + v.hash : '#';
            typeCell = '<a href="' + href + '" class="text-xs px-1.5 py-0.5 rounded-full bg-red-500/10 text-red-400 border border-red-500/20 font-mono hover:bg-red-500/20 transition-colors">' + v.bot + '</a>';
        } else {
            typeCell = '<span class="text-xs px-1.5 py-0.5 rounded-full bg-green-500/10 text-green-400 border border-green-500/20">human</span>';
        }

        return '<tr>'
            + '<td class="px-4 py-2.5">' + visitorCell + '</td>'
            + '<td class="px-4 py-2.5">' + typeCell + '</td>'
            + '<td class="px-4 py-2.5 text-right text-white font-semibold">' + v.page_count.toLocaleString() + '</td>'
            + '<td class="px-4 py-2.5 text-xs text-gray-500 whitespace-nowrap">' + humanTime(v.last_seen) + '</td>'
            + '</tr>';
    }

    function loadVisitors(sort) {
        activeSort = sort;

        document.querySelectorAll('.visitor-sort').forEach(function (b) {
            b.classList.toggle('text-accent',      b.dataset.sort === sort);
            b.classList.toggle('text-gray-500',    b.dataset.sort !== sort);
            b.classList.toggle('hover:text-white', b.dataset.sort !== sort);
        });

        fetch(dataUrl + '?sort=' + sort)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                table.querySelector('tbody').innerHTML = data.map(buildRow).join('');
            });
    }

    document.querySelectorAll('.visitor-sort').forEach(function (btn) {
        btn.addEventListener('click', function () {
            loadVisitors(this.dataset.sort);
        });
    });
});
</script>
@endsection
@endsection
