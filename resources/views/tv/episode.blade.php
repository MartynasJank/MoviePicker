@extends('layouts.app')
@section('page_title', ($show->name ?? 'TV Show').' — S'.str_pad($seasonNumber,2,'0',STR_PAD_LEFT).'E'.str_pad($episodeNumber,2,'0',STR_PAD_LEFT).' — MoviePickr')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6 flex-wrap">
        <a href="/tv/{{ $showId }}" class="hover:text-white transition-colors">{{ $show->name ?? 'Show' }}</a>
        <span class="text-gray-700">›</span>
        <a href="{{ route('tv.season', ['id' => $showId, 'season' => $seasonNumber]) }}" class="hover:text-white transition-colors">{{ $season->name ?? 'Season '.$seasonNumber }}</a>
        <span class="text-gray-700">›</span>
        <span class="text-gray-300">Episode {{ $episodeNumber }}</span>
    </div>

    {{-- Still image --}}
    @if(!empty($episode->still_path))
    <div class="rounded-xl overflow-hidden border border-white/10 mb-6 aspect-video">
        <img src="https://image.tmdb.org/t/p/original{{ $episode->still_path }}"
             alt="{{ $episode->name }}"
             class="w-full h-full object-cover">
    </div>
    @endif

    {{-- Title + meta --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-2 flex-wrap">
            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-accent/15 text-accent border border-accent/20">
                S{{ str_pad($seasonNumber,2,'0',STR_PAD_LEFT) }}E{{ str_pad($episodeNumber,2,'0',STR_PAD_LEFT) }}
            </span>
            @if(!empty($episode->vote_average) && $episode->vote_average > 0)
                <span class="text-xs text-gray-500">★ {{ number_format($episode->vote_average, 1) }}</span>
            @endif
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-white leading-tight">{{ $episode->name ?? 'Episode '.$episodeNumber }}</h1>
        <div class="flex items-center gap-3 mt-1.5 text-sm text-gray-500 flex-wrap">
            @if(!empty($episode->air_date))
                <span>{{ \Carbon\Carbon::parse($episode->air_date)->format('M j, Y') }}</span>
            @endif
            @if(!empty($episode->runtime))
                <span>· {{ $episode->runtime }} min</span>
            @endif
        </div>
        @if(!empty($episode->overview))
            <p class="text-gray-400 mt-4 leading-relaxed">{{ $episode->overview }}</p>
        @endif
    </div>

    {{-- Crew highlights (Director, Writer) --}}
    @php
        $crew = collect($episode->credits->crew ?? []);
        $directors = $crew->where('job', 'Director')->values();
        $writers   = $crew->whereIn('job', ['Writer', 'Teleplay', 'Story', 'Screenplay'])->values();
    @endphp
    @if($directors->isNotEmpty() || $writers->isNotEmpty())
    <div class="flex flex-wrap gap-6 mb-8 text-sm">
        @if($directors->isNotEmpty())
        <div>
            <p class="text-xs text-gray-600 uppercase tracking-widest mb-1">Director</p>
            @foreach($directors as $d)
                <a href="{{ route('person', $d->id) }}" class="text-white font-medium hover:text-accent transition-colors">{{ $d->name }}</a>
            @endforeach
        </div>
        @endif
        @if($writers->isNotEmpty())
        <div>
            <p class="text-xs text-gray-600 uppercase tracking-widest mb-1">Writer</p>
            @foreach($writers as $w)
                <a href="{{ route('person', $w->id) }}" class="text-white font-medium hover:text-accent transition-colors">{{ $w->name }}</a>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- Guest stars --}}
    @if(!empty($episode->credits->guest_stars) && count((array)$episode->credits->guest_stars) > 0)
    @php $guests = array_slice((array) $episode->credits->guest_stars, 0, 12); @endphp
    <div class="mb-8">
        <div class="section-header mb-4">
            <h2 class="text-xl font-bold text-white mb-3">Guest Stars</h2>
            <div class="section-divider"></div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            @foreach($guests as $guest)
            <a href="{{ route('person', $guest->id) }}" class="flex items-center gap-3 rounded-xl bg-white/[0.03] border border-white/5 p-2.5 hover:bg-white/[0.07] transition-colors">
                <div class="flex-shrink-0 w-10 h-10 rounded-full overflow-hidden bg-white/[0.05]">
                    @if(!empty($guest->profile_path))
                        <img src="https://image.tmdb.org/t/p/w92{{ $guest->profile_path }}"
                             alt="{{ $guest->name }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-600 text-sm font-bold">
                            {{ strtoupper(substr($guest->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-white font-medium truncate">{{ $guest->name }}</p>
                    @if(!empty($guest->character))
                        <p class="text-xs text-gray-600 truncate">{{ $guest->character }}</p>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Cast --}}
    @if(!empty($episode->credits->cast) && count((array)$episode->credits->cast) > 0)
    @php $cast = array_slice((array) $episode->credits->cast, 0, 8); @endphp
    <div class="mb-8">
        <div class="section-header mb-4">
            <h2 class="text-xl font-bold text-white mb-3">Cast</h2>
            <div class="section-divider"></div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            @foreach($cast as $member)
            <a href="{{ route('person', $member->id) }}" class="flex items-center gap-3 rounded-xl bg-white/[0.03] border border-white/5 p-2.5 hover:bg-white/[0.07] transition-colors">
                <div class="flex-shrink-0 w-10 h-10 rounded-full overflow-hidden bg-white/[0.05]">
                    @if(!empty($member->profile_path))
                        <img src="https://image.tmdb.org/t/p/w92{{ $member->profile_path }}"
                             alt="{{ $member->name }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-600 text-sm font-bold">
                            {{ strtoupper(substr($member->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-white font-medium truncate">{{ $member->name }}</p>
                    @if(!empty($member->character))
                        <p class="text-xs text-gray-600 truncate">{{ $member->character }}</p>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Prev / Next episode navigation --}}
    <div class="flex items-center justify-between gap-4 border-t border-white/5 pt-6">
        <div>
            @if($prevEpisode)
                <a href="{{ route('tv.episode', ['id' => $showId, 'season' => $seasonNumber, 'episode' => $prevEpisode->episode_number]) }}"
                   class="btn-secondary text-sm">← E{{ $prevEpisode->episode_number }} {{ $prevEpisode->name }}</a>
            @endif
        </div>
        <div>
            @if($nextEpisode)
                <a href="{{ route('tv.episode', ['id' => $showId, 'season' => $seasonNumber, 'episode' => $nextEpisode->episode_number]) }}"
                   class="btn-secondary text-sm">E{{ $nextEpisode->episode_number }} {{ $nextEpisode->name }} →</a>
        @endif
        </div>
    </div>

</div>
@endsection
