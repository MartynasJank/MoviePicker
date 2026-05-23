@extends('layouts.app')
@section('page_title', ($show->name ?? 'TV Show').' — Season '.($season->season_number ?? '').' — MoviePickr')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="/tv/{{ $showId }}" class="hover:text-white transition-colors">{{ $show->name ?? 'Show' }}</a>
        <span class="text-gray-700">›</span>
        <span class="text-gray-300">{{ $season->name ?? 'Season '.$seasonNumber }}</span>
    </div>

    {{-- Season header --}}
    <div class="flex gap-5 mb-8">
        <div class="flex-shrink-0 w-32 sm:w-40">
            @if(!empty($season->poster_path))
                <img src="https://image.tmdb.org/t/p/w300{{ $season->poster_path }}"
                     alt="{{ $season->name }}"
                     class="w-full rounded-xl border border-white/10 object-cover">
            @else
                <div class="w-full aspect-[2/3] rounded-xl bg-white/[0.03] border border-white/5 flex items-center justify-center text-gray-600 text-xs text-center px-2">No poster</div>
            @endif
        </div>
        <div class="min-w-0">
            <span class="inline-block text-xs font-medium px-2.5 py-0.5 rounded-full bg-accent/15 text-accent border border-accent/20 mb-2">TV Series</span>
            <h1 class="text-2xl sm:text-3xl font-bold text-white leading-tight">{{ $season->name ?? 'Season '.$seasonNumber }}</h1>
            <p class="text-gray-500 text-sm mt-1">
                {{ $show->name ?? '' }}
                @if(!empty($season->air_date))
                    · {{ substr($season->air_date, 0, 4) }}
                @endif
                @if(!empty($season->episodes))
                    · {{ count($season->episodes) }} episodes
                @endif
            </p>
            @if(!empty($season->overview))
                <p class="text-gray-400 text-sm mt-3 leading-relaxed">{{ $season->overview }}</p>
            @endif
        </div>
    </div>

    {{-- Episode list --}}
    @if(!empty($season->episodes))
    <div class="mb-10">
        <div class="section-header mb-4">
            <h2 class="text-xl font-bold text-white mb-3">Episodes</h2>
            <div class="section-divider"></div>
        </div>
        <div class="flex flex-col gap-3">
            @foreach($season->episodes as $episode)
            <a href="{{ route('tv.episode', ['id' => $showId, 'season' => $seasonNumber, 'episode' => $episode->episode_number]) }}"
               class="flex gap-4 rounded-xl bg-white/[0.03] border border-white/5 p-3 hover:bg-white/[0.06] transition-colors">
                {{-- Still --}}
                <div class="flex-shrink-0 w-32 sm:w-40 aspect-video rounded-lg overflow-hidden bg-white/[0.03]">
                    @if(!empty($episode->still_path))
                        <img src="https://image.tmdb.org/t/p/w300{{ $episode->still_path }}"
                             alt="{{ $episode->name }}"
                             class="w-full h-full object-cover" loading="lazy">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-700 text-xs">No image</div>
                    @endif
                </div>
                {{-- Info --}}
                <div class="min-w-0 flex flex-col justify-center">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="text-xs text-gray-600 font-medium flex-shrink-0">E{{ $episode->episode_number }}</span>
                        <span class="text-sm font-semibold text-white leading-snug">{{ $episode->name }}</span>
                    </div>
                    <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-600">
                        @if(!empty($episode->air_date))
                            <span>{{ \Carbon\Carbon::parse($episode->air_date)->format('M j, Y') }}</span>
                        @endif
                        @if(!empty($episode->runtime))
                            <span>· {{ $episode->runtime }} min</span>
                        @endif
                        @if(!empty($episode->vote_average) && $episode->vote_average > 0)
                            <span>· ★ {{ number_format($episode->vote_average, 1) }}</span>
                        @endif
                    </div>
                    @if(!empty($episode->overview))
                        <p class="text-xs text-gray-500 mt-1.5 leading-relaxed line-clamp-2">{{ $episode->overview }}</p>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Cast --}}
    @if(!empty($season->credits->cast))
    @php $cast = array_slice((array) $season->credits->cast, 0, 12); @endphp
    <div class="mb-10">
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
                        <div class="w-full h-full flex items-center justify-center text-gray-600 text-sm">?</div>
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

    {{-- Season navigation --}}
    @php
        $allSeasons = collect($show->seasons ?? [])
            ->where('season_number', '>', 0)
            ->sortBy('season_number')
            ->values();
        $currentIndex = $allSeasons->search(fn($s) => $s->season_number == $seasonNumber);
        $prevSeason   = $currentIndex > 0 ? $allSeasons[$currentIndex - 1] : null;
        $nextSeason   = $currentIndex !== false && $currentIndex < $allSeasons->count() - 1 ? $allSeasons[$currentIndex + 1] : null;
    @endphp
    @if($prevSeason || $nextSeason)
    <div class="flex items-center justify-between gap-4 border-t border-white/5 pt-6">
        <div>
            @if($prevSeason)
                <a href="{{ route('tv.season', ['id' => $showId, 'season' => $prevSeason->season_number]) }}"
                   class="btn-secondary text-sm">← {{ $prevSeason->name }}</a>
            @endif
        </div>
        <div>
            @if($nextSeason)
                <a href="{{ route('tv.season', ['id' => $showId, 'season' => $nextSeason->season_number]) }}"
                   class="btn-secondary text-sm">{{ $nextSeason->name }} →</a>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
