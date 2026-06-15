@extends('layouts.app')
@section('page_title', 'Visitor ' . substr($hash, 0, 8))
@section('content')
<div class="max-w-5xl mx-auto px-4 py-10">

    <div class="mb-6">
        <a href="{{ route('admin.dashboard', ['tab' => 'traffic']) }}"
           class="text-xs text-gray-500 hover:text-white transition-colors">
            ← Back to Traffic
        </a>
    </div>

    {{-- Header --}}
    <div class="mb-8 flex items-center gap-4 flex-wrap">
        <h1 class="text-2xl font-bold text-white font-mono">{{ $hash }}</h1>

        @if($user)
            <span class="text-sm text-gray-300">{{ $user->name }}</span>
        @endif

        @if($bot)
            <span class="text-xs px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 border border-red-500/20 font-mono">{{ $bot }}</span>
        @else
            <span class="text-xs px-2 py-0.5 rounded-full bg-green-500/10 text-green-400 border border-green-500/20">human</span>
        @endif
    </div>

    @if($parsedUA)
    <div class="mb-8">
        <div class="text-xs text-gray-600 uppercase tracking-widest mb-2">Device</div>
        <div class="flex flex-wrap items-center gap-2 mb-2">
            @if($parsedUA->browser)
                <span class="text-xs px-2.5 py-1 rounded-full bg-white/5 border border-white/10 text-gray-300">{{ $parsedUA->browser }}</span>
            @endif
            @if($parsedUA->os)
                <span class="text-xs px-2.5 py-1 rounded-full bg-white/5 border border-white/10 text-gray-300">{{ $parsedUA->os }}</span>
            @endif
            @if($parsedUA->device)
                <span class="text-xs px-2.5 py-1 rounded-full bg-white/5 border border-white/10 text-gray-400">{{ $parsedUA->device }}</span>
            @endif
        </div>
        <details class="group">
            <summary class="text-xs text-gray-600 cursor-pointer hover:text-gray-400 transition-colors list-none">Raw UA <span class="group-open:hidden">▸</span><span class="hidden group-open:inline">▾</span></summary>
            <div class="font-mono text-xs text-gray-600 bg-white/3 border border-white/5 rounded-lg px-4 py-2.5 break-all mt-2">{{ $parsedUA->raw }}</div>
        </details>
    </div>
    @endif

    {{-- Summary stats --}}
    @php
        $sessionCount = count($processedSessions);
        $routeCounts  = [];
        foreach ($processedSessions as $session) {
            foreach ($session->pages as $page) {
                $routeCounts[$page->route] = ($routeCounts[$page->route] ?? 0) + 1;
            }
        }
        arsort($routeCounts);
        $topRoute = array_key_first($routeCounts) ?? '—';
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10">
        <div class="bg-white/3 border border-white/5 rounded-xl p-5">
            <div class="text-3xl font-bold text-white mb-1">{{ number_format($total) }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-widest">Total Page Views</div>
        </div>
        <div class="bg-white/3 border border-white/5 rounded-xl p-5">
            <div class="text-3xl font-bold text-accent mb-1">{{ $sessionCount }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-widest">Sessions</div>
        </div>
        <div class="bg-white/3 border border-white/5 rounded-xl p-5">
            <div class="text-lg font-bold text-blue-400 mb-1 font-mono truncate">{{ $topRoute }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-widest">Most Visited Route</div>
        </div>
    </div>

    {{-- Referrers + Hourly --}}
    @if(!empty($referrers) || $hourly->max('total') > 0)
    @if(!empty($referrers))
    <div class="mb-8">
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">External Referrers</h2>
        <div class="bg-white/3 border border-white/5 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-white/5">
                    @foreach($referrers as $domain => $count)
                    <tr>
                        <td class="px-4 py-2.5 font-mono text-xs text-gray-300">{{ $domain }}</td>
                        <td class="px-4 py-2.5 text-right text-white font-semibold">{{ $count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($hourly->max('total') > 0)
    <div class="mb-8">
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Typical Active Hours — Last 30 Days</h2>
        <div class="bg-white/3 border border-white/5 rounded-xl p-5">
            @php $hourlyMax = $hourly->max('total') ?: 1; @endphp
            <div class="flex gap-1 items-end" style="height:48px">
                @foreach($hourly as $h)
                @php $barH = $h->total > 0 ? max(3, round(($h->total / $hourlyMax) * 40)) : 2; @endphp
                <div class="flex-1 rounded-sm" style="height:{{ $barH }}px; background:rgba(255,255,255,{{ $h->total > 0 ? 0.25 : 0.05 }})"
                     title="{{ $h->hour }}:00 — {{ $h->total }}"></div>
                @endforeach
            </div>
            <div class="flex gap-1 mt-1">
                @foreach($hourly as $h)
                <div class="flex-1 text-center">
                    @if($h->hour % 6 === 0)
                        <span class="text-[9px] text-gray-600">{{ str_pad($h->hour, 2, '0', STR_PAD_LEFT) }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- Page Flow --}}
    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">
        Page Flow — Last 30 Days
        @if($sessionCount)
            <span class="ml-2 normal-case font-normal text-gray-600">{{ $sessionCount }} {{ Str::plural('session', $sessionCount) }}</span>
        @endif
    </h2>

    @if(empty($processedSessions))
        <div class="bg-white/3 border border-white/5 rounded-xl px-5 py-8 text-sm text-gray-500 mb-10">
            No page flow recorded yet — page view tracking started recently.
        </div>
    @else
        <div class="space-y-2 mb-10">
            @foreach($processedSessions as $i => $session)
            @php
                $mins = floor($session->duration / 60);
                $secs = $session->duration % 60;
                $durationStr = $mins > 0 ? "{$mins}m {$secs}s" : "{$secs}s";
            @endphp
            <details class="group bg-white/3 border border-white/5 rounded-xl overflow-hidden" {{ $i === 0 ? 'open' : '' }}>
                <summary class="flex items-center justify-between px-5 py-3 cursor-pointer list-none hover:bg-white/2 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-600 w-16">Session {{ $sessionCount - $i }}</span>
                        <span class="text-sm text-gray-300">{{ $session->start->format('M j, Y · H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                        <span>{{ count($session->pages) }} {{ Str::plural('page', count($session->pages)) }}</span>
                        @if($session->duration > 0)<span>{{ $durationStr }}</span>@endif
                        <span class="group-open:rotate-180 transition-transform text-gray-600">▾</span>
                    </div>
                </summary>
                <table class="w-full text-sm border-t border-white/5">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="text-left px-5 py-2 text-xs text-gray-500 font-medium">Route</th>
                            <th class="text-left px-5 py-2 text-xs text-gray-500 font-medium">Referrer</th>
                            <th class="text-left px-5 py-2 text-xs text-gray-500 font-medium">Time</th>
                            <th class="text-right px-5 py-2 text-xs text-gray-500 font-medium">Time on Page</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($session->pages as $page)
                        <tr>
                            <td class="px-5 py-2 font-mono text-xs text-gray-200">{{ $page->route }}</td>
                            <td class="px-5 py-2 font-mono text-xs text-gray-500">{{ $page->referrer ?? '—' }}</td>
                            <td class="px-5 py-2 text-xs text-gray-500 whitespace-nowrap">{{ $page->created_at->format('H:i:s') }}</td>
                            <td class="px-5 py-2 text-right text-xs">
                                @if($page->time_on_page !== null)
                                    @php $t = $page->time_on_page; @endphp
                                    <span class="text-gray-400">{{ $t >= 60 ? floor($t/60).'m '.($t%60).'s' : $t.'s' }}</span>
                                @else
                                    <span class="text-gray-600">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </details>
            @endforeach
        </div>
    @endif

    {{-- TMDB Requests --}}
    @if($tmdbLogs->isNotEmpty())
    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">
        TMDB Requests — Last 30 Days
        <span class="ml-2 normal-case font-normal text-gray-600">{{ number_format($tmdbLogs->total()) }} total</span>
    </h2>
    <div class="bg-white/3 border border-white/5 rounded-xl overflow-x-auto mb-4">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5">
                    <th class="text-left px-5 py-2.5 text-xs text-gray-500 font-medium">Time</th>
                    <th class="text-left px-5 py-2.5 text-xs text-gray-500 font-medium">Endpoint</th>
                    <th class="text-left px-5 py-2.5 text-xs text-gray-500 font-medium">Route</th>
                    <th class="text-left px-5 py-2.5 text-xs text-gray-500 font-medium">Cache</th>
                    <th class="text-right px-5 py-2.5 text-xs text-gray-500 font-medium">ms</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($tmdbLogs as $log)
                <tr>
                    <td class="px-5 py-2 text-xs text-gray-500 whitespace-nowrap">{{ $log->created_at->format('M j H:i:s') }}</td>
                    <td class="px-5 py-2 font-mono text-xs text-gray-300">{{ $log->endpoint }}</td>
                    <td class="px-5 py-2 font-mono text-xs text-gray-500">{{ $log->route }}</td>
                    <td class="px-5 py-2 text-xs">
                        @if($log->cached)<span class="text-blue-400">hit</span>
                        @else<span class="text-gray-500">live</span>@endif
                    </td>
                    <td class="px-5 py-2 text-right text-xs text-gray-500">{{ $log->cached ? '—' : $log->response_time_ms }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $tmdbLogs->links('admin.pagination') }}
    @endif

</div>
@endsection
