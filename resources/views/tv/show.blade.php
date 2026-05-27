@extends('layouts.app')
@section('page_title', ($tmdbInfo->name ?? 'TV Show').' — MoviePickr')
@section('footer_pb', 'pb-32')
@section('scripts')
    @vite(['resources/js/custom/showMore.js', 'resources/js/custom/carousel.js', 'resources/js/custom/trailerModal.js', 'resources/js/custom/criteriaForm.js'])
@endsection
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 pb-36 sm:pb-20">

    @if (isset($trailer))
        @include('includes.trailer-modal')
    @endif
    @include('tv.criteria-modal')

    {{-- Title row --}}
    <div class="flex items-start justify-between gap-4 mb-4 flex-wrap">
        <div>
            <span class="inline-block text-xs font-medium px-2.5 py-0.5 rounded-full bg-accent/15 text-accent border border-accent/20 mb-2">TV Series</span>
            <h1 class="text-3xl md:text-4xl font-bold text-white leading-tight">
                {{ $tmdbInfo->name }}
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                {{ substr($tmdbInfo->first_air_date ?? '', 0, 4) }}
                @if($genres ?? false)
                    · {{ $genres }}
                @endif
                @if(!empty($tmdbInfo->status))
                    · <span class="text-gray-600">{{ $tmdbInfo->status }}</span>
                @endif
            </p>
        </div>
        {{-- Save button in title row --}}
        @auth
            @php $isSaved = auth()->user()->watchlist()->where('tmdb_id', $tmdbInfo->id)->exists(); @endphp
            <button type="button" class="btn-secondary flex-shrink-0 watchlist-toggle"
                data-tmdb-id="{{ $tmdbInfo->id }}"
                data-title="{{ $tmdbInfo->name }}"
                data-poster="{{ $tmdbInfo->poster_path ?? '' }}"
                data-year="{{ substr($tmdbInfo->first_air_date ?? '', 0, 4) }}"
                data-genres="{{ $genres ?? '' }}"
                data-rating="{{ $tmdbInfo->vote_average ?? '' }}"
                data-media-type="tv"
                data-saved="{{ $isSaved ? '1' : '0' }}">
                {{ $isSaved ? '★ Saved' : '☆ Save' }}
            </button>
        @else
            <a href="{{ route('auth.google') }}" class="btn-secondary flex-shrink-0 text-center text-sm">☆ Save</a>
        @endauth
    </div>

    {{-- Main content grid --}}
    <div class="flex gap-3 md:grid md:grid-cols-[220px_1fr] md:gap-6 mb-8">

        {{-- Poster + trailer button --}}
        <div class="w-[38%] md:w-auto flex-shrink-0 flex flex-col gap-2">
            @if($tmdbInfo->poster_path)
                <img src="https://image.tmdb.org/t/p/original{{ $tmdbInfo->poster_path }}"
                    alt="{{ $tmdbInfo->name }}"
                    class="w-full rounded-xl border border-white/10 object-cover">
            @else
                <div class="w-full aspect-[2/3] card flex items-center justify-center text-gray-600 text-xs text-center px-2">
                    No poster available
                </div>
            @endif
            @if(isset($trailer))
                <button type="button" class="btn-accent text-sm w-full" data-modal-open="trailer-modal">▶ Trailer</button>
            @endif
        </div>

        {{-- Overview + Streaming --}}
        <div class="flex-1 min-w-0 flex flex-col gap-3 md:gap-6">

            {{-- Streaming --}}
            @if ($watchProviders != null)
                @php $jwUrl = 'https://www.justwatch.com/' . strtolower($country) . '/search?q=' . urlencode($tmdbInfo->name ?? ''); @endphp
                <div class="flex items-center gap-2 flex-wrap">
                    @foreach($watchProviders->flatrate as $stream)
                        <a href="{{ $jwUrl }}" target="_blank" title="Find on JustWatch">
                            <img src="https://image.tmdb.org/t/p/w45{{ $stream->logo_path }}"
                                class="h-8 w-8 rounded-md border border-white/10 hover:border-white/30 transition-colors">
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Plot --}}
            <div>
                <h3 class="text-xs sm:text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Overview</h3>
                <p class="text-gray-300 leading-relaxed text-sm">
                    {{ $tmdbInfo->overview ?? 'No description available.' }}
                </p>
            </div>

            {{-- Created by --}}
            @if(!empty($tmdbInfo->created_by) && count((array)$tmdbInfo->created_by) > 0)
            <div>
                <p class="text-xs text-gray-600 uppercase tracking-widest mb-1">Created by</p>
                <div class="flex flex-wrap gap-x-3">
                    @foreach($tmdbInfo->created_by as $creator)
                        <a href="{{ route('person', $creator->id) }}" class="text-sm text-white font-medium hover:text-accent transition-colors">{{ $creator->name }}</a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>

    @if ($watchProviders != null)
        <p class="text-xs text-gray-600 mb-6">Streaming availability by JustWatch. Links open JustWatch search for your region.</p>
    @endif

    {{-- Last aired episode --}}
    @if(!empty($tmdbInfo->last_episode_to_air))
        @php $last = $tmdbInfo->last_episode_to_air; @endphp
        <a href="{{ route('tv.episode', ['id' => $tmdbInfo->id, 'season' => $last->season_number, 'episode' => $last->episode_number]) }}"
           class="flex items-center gap-3 bg-white/[0.03] border border-white/5 rounded-xl px-4 py-3 mb-3 hover:bg-white/[0.06] transition-colors">
            <span class="text-gray-500 text-sm">🎬</span>
            <div class="flex-1">
                <p class="text-gray-300 text-sm font-medium">
                    Last episode: S{{ str_pad($last->season_number, 2, '0', STR_PAD_LEFT) }}E{{ str_pad($last->episode_number, 2, '0', STR_PAD_LEFT) }}
                    @if(!empty($last->name)) — {{ $last->name }}@endif
                </p>
                <p class="text-gray-600 text-xs">Aired {{ \Carbon\Carbon::parse($last->air_date)->format('M j, Y') }}</p>
            </div>
            <span class="text-gray-700 text-sm">›</span>
        </a>
    @endif

    {{-- Next episode callout --}}
    @if(!empty($tmdbInfo->next_episode_to_air))
        @php $next = $tmdbInfo->next_episode_to_air; @endphp
        <a href="{{ route('tv.episode', ['id' => $tmdbInfo->id, 'season' => $next->season_number, 'episode' => $next->episode_number]) }}"
           class="flex items-center gap-3 bg-blue-500/10 border border-blue-500/20 rounded-xl px-4 py-3 mb-6 hover:bg-blue-500/15 transition-colors">
            <span class="text-blue-400 text-sm">📅</span>
            <div class="flex-1">
                <p class="text-blue-300 text-sm font-medium">
                    Next episode: S{{ str_pad($next->season_number, 2, '0', STR_PAD_LEFT) }}E{{ str_pad($next->episode_number, 2, '0', STR_PAD_LEFT) }}
                    @if(!empty($next->name)) — {{ $next->name }}@endif
                </p>
                <p class="text-blue-400/60 text-xs">Airs {{ \Carbon\Carbon::parse($next->air_date)->format('M j, Y') }}</p>
            </div>
            <span class="text-blue-400/40 text-sm">›</span>
        </a>
    @endif

    {{-- Info cards --}}
    <div class="grid md:grid-cols-3 gap-4 mb-8">

        {{-- Cast --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Cast</h3>
            @if(!empty($tmdbInfo->credits->cast))
                <ul class="cast-list flex flex-col gap-1.5">
                    @foreach ($tmdbInfo->credits->cast as $member)
                        <li class="text-sm text-gray-300">
                            <a href="{{ route('person', $member->id) }}" class="hover:text-white transition-colors">{{ $member->name }}</a>
                            @if($member->character)
                                <span class="text-gray-500"> as {{ $member->character }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">No info</p>
            @endif
        </div>

        {{-- Crew --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Crew</h3>
            @if(!empty($tmdbInfo->credits->crew))
                <ul class="crew-list flex flex-col gap-1.5">
                    @foreach ($tmdbInfo->credits->crew as $member)
                        <li class="text-sm text-gray-300">
                            <span class="text-gray-500">{{ $member->job }}:</span> <a href="{{ route('person', $member->id) }}" class="hover:text-white transition-colors">{{ $member->name }}</a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">No info</p>
            @endif
        </div>

        {{-- Networks --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Networks</h3>
            @if(!empty($tmdbInfo->networks))
                <ul class="production-list flex flex-col gap-1.5">
                    @foreach ($tmdbInfo->networks as $network)
                        <li class="text-sm text-gray-300">{{ $network->name }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">No info</p>
            @endif
        </div>
    </div>

    {{-- Details row --}}
    <div class="grid md:grid-cols-3 gap-4 mb-10">

        {{-- Show info --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Details</h3>
            <ul class="flex flex-col gap-1.5 text-sm">
                @if(!empty($tmdbInfo->created_by))
                    <li class="flex justify-between gap-2">
                        <span class="text-gray-500 flex-shrink-0">Created By</span>
                        <span class="text-gray-300 text-right">{{ implode(', ', array_column((array) $tmdbInfo->created_by, 'name')) }}</span>
                    </li>
                @endif
                @if(!empty($tmdbInfo->type))
                    <li class="flex justify-between">
                        <span class="text-gray-500">Type</span>
                        <span class="text-gray-300">{{ $tmdbInfo->type }}</span>
                    </li>
                @endif
                <li class="flex justify-between">
                    <span class="text-gray-500">Seasons</span>
                    <span class="text-gray-300">{{ $tmdbInfo->number_of_seasons ?? '—' }}</span>
                </li>
                <li class="flex justify-between">
                    <span class="text-gray-500">Episodes</span>
                    <span class="text-gray-300">{{ $tmdbInfo->number_of_episodes ?? '—' }}</span>
                </li>
                <li class="flex justify-between">
                    <span class="text-gray-500">Ep. Runtime</span>
                    <span class="text-gray-300">
                        @if(!empty($tmdbInfo->episode_run_time))
                            {{ $tmdbInfo->episode_run_time[0] }} min
                        @else
                            —
                        @endif
                    </span>
                </li>
                <li class="flex justify-between">
                    <span class="text-gray-500">First Aired</span>
                    <span class="text-gray-300">{{ !empty($tmdbInfo->first_air_date) ? \Carbon\Carbon::parse($tmdbInfo->first_air_date)->format('M j, Y') : '—' }}</span>
                </li>
                @if(!empty($tmdbInfo->last_air_date))
                    <li class="flex justify-between">
                        <span class="text-gray-500">Last Aired</span>
                        <span class="text-gray-300">{{ \Carbon\Carbon::parse($tmdbInfo->last_air_date)->format('M j, Y') }}</span>
                    </li>
                @endif
                <li class="flex justify-between">
                    <span class="text-gray-500">In Production</span>
                    <span class="{{ ($tmdbInfo->in_production ?? false) ? 'text-green-400' : 'text-gray-500' }}">
                        {{ ($tmdbInfo->in_production ?? false) ? 'Yes' : 'No' }}
                    </span>
                </li>
                <li class="flex justify-between">
                    <span class="text-gray-500">TMDB Score</span>
                    <span class="text-accent font-semibold">{{ $tmdbInfo->vote_average ?? '—' }}</span>
                </li>
                <li class="flex justify-between">
                    <span class="text-gray-500">Vote Count</span>
                    <span class="text-gray-300">{{ number_format($tmdbInfo->vote_count ?? 0) }}</span>
                </li>
            </ul>
        </div>

        {{-- Languages --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Languages</h3>
            @if(!empty($tmdbInfo->spoken_languages))
                <ul class="flex flex-col gap-1.5">
                    @foreach ($tmdbInfo->spoken_languages as $language)
                        <li class="text-sm text-gray-300">{{ $language->name }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">No info</p>
            @endif
        </div>

        {{-- Countries --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Countries</h3>
            @if(!empty($tmdbInfo->production_countries))
                <ul class="flex flex-col gap-1.5">
                    @foreach ($tmdbInfo->production_countries as $c)
                        <li class="text-sm text-gray-300">{{ $c->name }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">No info</p>
            @endif
        </div>
    </div>

    {{-- Seasons --}}
    @php
        $seasons = collect($tmdbInfo->seasons ?? [])
            ->sortBy('season_number')
            ->values();
        $regularSeasons = $seasons->where('season_number', '>', 0)->values();
        $specialsSeason = $seasons->firstWhere('season_number', 0);
    @endphp
    @if($regularSeasons->isNotEmpty())
    <div class="mb-10">
        <div class="section-header">
            <h2 class="text-xl font-bold text-white mb-3">Seasons</h2>
            <div class="section-divider"></div>
        </div>
        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide mt-4">
            @foreach($regularSeasons as $season)
            <a href="{{ route('tv.season', ['id' => $tmdbInfo->id, 'season' => $season->season_number]) }}" class="flex-shrink-0 w-28 group">
                <div class="aspect-[2/3] rounded-lg overflow-hidden bg-white/[0.03] group-hover:ring-1 group-hover:ring-accent/50 transition-all">
                    @if(!empty($season->poster_path))
                        <img src="https://image.tmdb.org/t/p/w185{{ $season->poster_path }}"
                             alt="{{ $season->name }}"
                             class="w-full h-full object-cover" loading="lazy">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs text-center px-2">No poster</div>
                    @endif
                </div>
                <p class="text-xs text-gray-300 mt-1.5 font-medium leading-snug group-hover:text-white transition-colors">{{ $season->name }}</p>
                <p class="text-xs text-gray-600">
                    @if(!empty($season->air_date)){{ substr($season->air_date, 0, 4) }} · @endif{{ $season->episode_count ?? '?' }} ep
                </p>
            </a>
            @endforeach
            @if($specialsSeason)
            <a href="{{ route('tv.season', ['id' => $tmdbInfo->id, 'season' => 0]) }}" class="flex-shrink-0 w-28 group">
                <div class="aspect-[2/3] rounded-lg overflow-hidden bg-white/[0.03] group-hover:ring-1 group-hover:ring-accent/50 transition-all">
                    @if(!empty($specialsSeason->poster_path))
                        <img src="https://image.tmdb.org/t/p/w185{{ $specialsSeason->poster_path }}"
                             alt="{{ $specialsSeason->name }}"
                             class="w-full h-full object-cover" loading="lazy">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs text-center px-2">No poster</div>
                    @endif
                </div>
                <p class="text-xs text-gray-300 mt-1.5 font-medium leading-snug group-hover:text-white transition-colors">{{ $specialsSeason->name }}</p>
                <p class="text-xs text-gray-600">
                    @if(!empty($specialsSeason->air_date)){{ substr($specialsSeason->air_date, 0, 4) }} · @endif{{ $specialsSeason->episode_count ?? '?' }} ep
                </p>
            </a>
            @endif
        </div>
    </div>
    @endif

    {{-- Similar shows --}}
    @if ($similarShows != null)
    <div>
        <div class="section-header">
            <h2 class="text-xl font-bold text-white mb-3">{{ $similarTitle }}</h2>
            <div class="section-divider"></div>
        </div>
        @include('includes.carousel', [
            'allMovies' => $similarShows,
            'name'      => 'swiper-similar',
            'genres'    => [],
            'showScore' => true,
            'showSave'  => true,
            'savedIds'  => $savedIds,
            'linkBase'  => 'tv',
            'mediaType' => 'tv',
        ])
    </div>
    @endif

</div>

{{-- Sticky bottom bar --}}
<div class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 px-4 z-40 sticky-bar-safe">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
        {{-- Back button --}}
        <div class="flex-shrink-0">
            @if(request()->query('wl_status'))
                <a href="{{ route('watchlist') }}" class="btn-secondary text-center">← Watchlist</a>
            @elseif($batchUrl)
                <a href="{{ $batchUrl }}" class="btn-secondary text-center js-back-roulettes">← Batch</a>
            @endif
        </div>
        {{-- Right actions --}}
        <div class="flex items-center gap-3">
            @if(!request()->query('wl_status') && !session('tvPersonRollIds'))
                <button type="button" class="btn-secondary js-criteria-btn" data-modal-open="modal-form">Criteria</button>
            @endif
            @if(request()->query('wl_status'))
                @php
                    $wlParams = ['status' => request()->query('wl_status')];
                    if (request()->query('wl_genres')) $wlParams['genres'] = request()->query('wl_genres');
                @endphp
                <a href="{{ route('watchlist.roll', $wlParams) }}" class="btn-accent long-single text-center">Roll</a>
            @elseif(session('tvPersonRollIds'))
                <a href="{{ route('person.roll.tv.next') }}" class="btn-accent long-single text-center">Roll</a>
            @else
                <a href="/tv/pick" class="btn-accent long-single text-center" data-roll="tv-criteria">Roll</a>
            @endif
        </div>
    </div>
</div>

@endsection
