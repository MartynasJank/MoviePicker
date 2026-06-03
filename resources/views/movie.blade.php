@extends('layouts.app')
@section('page_title', ($tmdbInfo->title ?? $omdbInfo->Title ?? 'Movie').' — MoviePickr')
@section('og_title', ($tmdbInfo->title ?? $omdbInfo->Title ?? 'Movie').' — MoviePickr')
@section('og_description', Str::limit($tmdbInfo->overview ?? 'Watch this movie picked by MoviePickr.', 200))
@section('og_image', $tmdbInfo->backdrop_path ? 'https://image.tmdb.org/t/p/w1280'.$tmdbInfo->backdrop_path : ($tmdbInfo->poster_path ? 'https://image.tmdb.org/t/p/w500'.$tmdbInfo->poster_path : ''))
@section('footer_pb', 'pb-32')
@section('scripts')
    @vite(['resources/js/custom/showMore.js', 'resources/js/custom/carousel.js', 'resources/js/custom/trailerModal.js', 'resources/js/custom/criteriaForm.js'])
@endsection
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 pb-4">

    @if (isset($trailer))
        @include('includes.trailer-modal')
    @endif
    @include('includes.criteria-modal')

    {{-- Title row --}}
    <div class="flex items-start justify-between gap-4 mb-4 flex-wrap">
        <div>
            <span class="inline-block text-xs font-medium px-2.5 py-0.5 rounded-full bg-white/8 text-gray-400 border border-white/10 mb-2">Movie</span>
            <h1 class="text-3xl md:text-4xl font-bold text-white leading-tight">
                {{ $tmdbInfo->title ?? $omdbInfo->Title }}
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                {{ $omdbInfo->Year ?? date('Y', strtotime($tmdbInfo->release_date ?? '')) }}
                @if($genres ?? false)
                    · {{ $genres }}
                @endif
                @if(($omdbInfo->Rated ?? null) && $omdbInfo->Rated !== 'N/A')
                    · <span class="text-gray-600">{{ $omdbInfo->Rated }}</span>
                @endif
            </p>
            @if(!empty($keywords))
            <div class="flex flex-wrap gap-1.5 mt-2">
                @foreach($keywords as $kw)
                    @php $kw = (object) $kw; @endphp
                    <span class="text-xs px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-gray-500">{{ $kw->name }}</span>
                @endforeach
            </div>
            @endif
        </div>
        {{-- Save buttons --}}
        <div class="flex flex-col items-end gap-2 flex-shrink-0">
            <div class="flex items-center gap-2">
                <button type="button" class="btn-secondary flex-shrink-0 text-sm"
                    data-share
                    data-share-url="{{ url()->current() }}"
                    data-share-title="{{ $tmdbInfo->title ?? '' }} — MoviePickr"
                    title="Share">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
                </button>
                @auth
                    <button type="button" id="title-save-roulette-btn"
                            class="btn-secondary text-sm flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/>
                        </svg>
                        + Roulette
                    </button>
                    <button type="button" class="btn-secondary flex-shrink-0 watchlist-toggle"
                        data-tmdb-id="{{ $tmdbInfo->id }}"
                        data-title="{{ $tmdbInfo->title ?? $omdbInfo->Title }}"
                        data-poster="{{ $tmdbInfo->poster_path ?? '' }}"
                        data-year="{{ $omdbInfo->Year ?? date('Y', strtotime($tmdbInfo->release_date ?? '')) }}"
                        data-genres="{{ $genres ?? '' }}"
                        data-rating="{{ $tmdbInfo->vote_average ?? '' }}"
                        data-saved="{{ auth()->user()->watchlist()->where('tmdb_id', $tmdbInfo->id)->exists() ? '1' : '0' }}">
                        {{ auth()->user()->watchlist()->where('tmdb_id', $tmdbInfo->id)->exists() ? '★ Saved' : '☆ Save' }}
                    </button>
                @else
                    <a href="{{ route('auth.google') }}" class="btn-secondary flex-shrink-0 text-center text-sm">☆ Save</a>
                @endauth
            </div>
            @auth
            {{-- Save as Roulette modal --}}
            <div id="save-roulette-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="save-roulette-backdrop"></div>
                <div class="relative bg-[#1a1a1a] border border-white/10 rounded-2xl p-6 w-full max-w-sm shadow-2xl">
                    <h2 class="text-lg font-bold text-white mb-4">Save as Roulette</h2>
                    <form method="POST" action="{{ route('my-roulettes.from-criteria') }}">
                        @csrf
                        <input type="hidden" name="media_type" value="movie">
                        <div class="mb-4">
                            <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Name</label>
                            <input type="text" name="name" required maxlength="80"
                                   class="input-dark w-full" placeholder="e.g. Sci-Fi on Netflix…"
                                   id="save-roulette-name">
                        </div>
                        <div class="mb-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_public" value="1"
                                       class="w-4 h-4 rounded border-white/20 bg-white/5 text-accent">
                                <span class="text-sm text-gray-300">Public — anyone with the link can roll this</span>
                            </label>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="btn-accent flex-1 py-2.5 text-sm">Save</button>
                            <button type="button" id="save-roulette-cancel" class="btn-secondary px-5 py-2.5 text-sm">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            <script>
            (function () {
                const modal = document.getElementById('save-roulette-modal');
                const inp   = document.getElementById('save-roulette-name');
                document.getElementById('title-save-roulette-btn').addEventListener('click', function () {
                    modal.classList.remove('hidden'); inp.focus();
                });
                document.getElementById('save-roulette-cancel').addEventListener('click', function () {
                    modal.classList.add('hidden');
                });
                document.getElementById('save-roulette-backdrop').addEventListener('click', function () {
                    modal.classList.add('hidden');
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') modal.classList.add('hidden');
                });
            })();
            </script>
            @endauth
        </div>
    </div>

    {{-- Main content grid --}}
    <div class="flex gap-3 md:grid md:grid-cols-[220px_1fr] md:gap-6 mb-8">

        {{-- Poster + trailer button --}}
        <div class="w-[38%] md:w-auto flex-shrink-0 flex flex-col gap-2">
            @if($tmdbInfo->poster_path)
                <img src="https://image.tmdb.org/t/p/original{{ $tmdbInfo->poster_path }}"
                    alt="{{ $tmdbInfo->title }}"
                    class="w-full rounded-xl border border-white/10 object-cover">
            @elseif(isset($omdbInfo->Poster) && $omdbInfo->Poster !== 'N/A')
                <img src="{{ $omdbInfo->Poster }}" class="w-full rounded-xl border border-white/10 object-cover">
            @else
                <div class="w-full aspect-[2/3] card flex items-center justify-center text-gray-600 text-xs text-center px-2">
                    No poster available
                </div>
            @endif
            @if(isset($trailer))
                <button type="button" class="btn-accent text-sm w-full" data-modal-open="trailer-modal">▶ Trailer</button>
            @endif
        </div>

        {{-- Ratings + Plot --}}
        <div class="flex-1 min-w-0 flex flex-col gap-3 md:gap-6">

            {{-- Ratings --}}
            @if(isset($omdbInfo->Ratings) && count($omdbInfo->Ratings) > 0)
            <div>
                <h3 class="text-xs sm:text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2 md:mb-3">Ratings</h3>
                @foreach ($omdbInfo->Ratings as $rating)
                    @php $url = $urls[$rating->Source] ?? '#'; @endphp
                    <a class="rating-pill" href="{{ $url }}"
                        {{ $url !== '#' ? 'target="_blank"' : '' }}>
                        <span class="text-gray-400 text-xs sm:text-sm truncate">{{ $rating->Source }}</span>
                        <span class="ml-auto font-semibold text-accent text-xs sm:text-sm flex-shrink-0">{{ $rating->Value }}</span>
                    </a>
                @endforeach
            </div>
            @endif

            {{-- Streaming --}}
            @if ($watchProviders != null)
                @php
                    $title    = $tmdbInfo->title ?? $omdbInfo->Title ?? '';
                    $year     = substr($tmdbInfo->release_date ?? '', 0, 4);
                    $tmdbLink = $watchProviders->link ?? null;
                    $jwSearch = mb_strlen($title) >= 5
                        ? 'https://www.justwatch.com/' . strtolower($country) . '/search?q=' . urlencode($title)
                        : null;
                    $amzTag   = config('api.amazon_affiliate_tag');

                    // Reliable deep-link URLs by TMDB provider ID
                    $providerLinks = [
                        8   => 'https://www.netflix.com/search?q=' . urlencode($title),
                        175 => 'https://www.netflix.com/search?q=' . urlencode($title),
                        337 => 'https://www.disneyplus.com/search/' . urlencode($title),
                        350 => 'https://tv.apple.com/search?term=' . urlencode($title),
                        2   => 'https://tv.apple.com/search?term=' . urlencode($title),
                        3   => 'https://play.google.com/store/search?q=' . urlencode($title) . '&c=movies',
                        283 => 'https://www.crunchyroll.com/search?q=' . urlencode($title),
                        11  => 'https://mubi.com/search/' . urlencode($title),
                        538 => 'https://app.plex.tv/desktop/#!/search?query=' . urlencode($title),
                    ];

                    // Amazon affiliate by name (covers both provider IDs)
                    $resolveProviderUrl = function($id, $name) use ($providerLinks, $title, $year, $amzTag, $jwSearch, $tmdbLink) {
                        if (str_contains(strtolower($name), 'amazon') && $amzTag) {
                            return 'https://www.amazon.com/s?k=' . urlencode($title . ' ' . $year) . '&i=instant-video&tag=' . $amzTag;
                        }
                        return $providerLinks[$id] ?? $jwSearch ?? $tmdbLink;
                    };

                    $providerGroups = [
                        'Streaming'     => collect($watchProviders->flatrate ?? []),
                        'Free'          => collect($watchProviders->free ?? []),
                        'Free with Ads' => collect($watchProviders->ads ?? []),
                        'Rent'          => collect($watchProviders->rent ?? []),
                        'Buy'           => collect($watchProviders->buy ?? []),
                    ];
                @endphp
                <div class="flex flex-col gap-2">
                    @foreach($providerGroups as $label => $providers)
                        @if($providers->isNotEmpty())
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs text-gray-500 w-20 flex-shrink-0">{{ $label }}</span>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @foreach($providers as $stream)
                                @php $url = $resolveProviderUrl($stream->provider_id, $stream->provider_name); @endphp
                                @if($url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" title="{{ $stream->provider_name }}">
                                    <img src="https://image.tmdb.org/t/p/w45{{ $stream->logo_path }}"
                                        class="h-8 w-8 rounded-md border border-white/10 hover:border-white/30 transition-colors">
                                </a>
                                @else
                                <img src="https://image.tmdb.org/t/p/w45{{ $stream->logo_path }}"
                                    class="h-8 w-8 rounded-md border border-white/10 opacity-60"
                                    title="{{ $stream->provider_name }}">
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Plot --}}
            <div>
                <h3 class="text-xs sm:text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Plot</h3>
                <p class="text-gray-300 leading-relaxed text-sm">
                    {{ ($omdbInfo->Plot ?? $tmdbInfo->overview) ?? 'No description available.' }}
                </p>
            </div>

            {{-- Director --}}
            @php
                $directors = collect($tmdbInfo->credits->crew ?? [])->where('job', 'Director')->values();
            @endphp
            @if($directors->isNotEmpty())
            <div class="flex flex-wrap gap-x-6 gap-y-2">
                <div>
                    <p class="text-xs text-gray-600 uppercase tracking-widest mb-1">Director</p>
                    <div class="flex flex-wrap gap-x-3">
                        @foreach($directors as $d)
                            <a href="{{ route('person', $d->id) }}" class="text-sm text-white font-medium hover:text-accent transition-colors">{{ $d->name }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    @if ($watchProviders != null)
        <p class="text-xs text-gray-600 mb-6">Streaming availability by JustWatch. Amazon links are affiliate links — we may earn a small commission at no extra cost to you.</p>
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

        {{-- Production --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Production</h3>
            @if(!empty($tmdbInfo->production_companies))
                <ul class="production-list flex flex-col gap-1.5">
                    @foreach ($tmdbInfo->production_companies as $company)
                        <li class="text-sm text-gray-300">{{ $company->name }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">No info</p>
            @endif
        </div>
    </div>

    {{-- Details row --}}
    <div class="grid md:grid-cols-3 gap-4 mb-10">

        {{-- General info --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Details</h3>
            <ul class="flex flex-col gap-1.5 text-sm">
                <li class="flex justify-between"><span class="text-gray-500">Budget</span><span class="text-gray-300">{{ $tmdbInfo->budget == 0 ? '—' : '$'.number_format($tmdbInfo->budget) }}</span></li>
                <li class="flex justify-between"><span class="text-gray-500">Revenue</span><span class="text-gray-300">{{ $tmdbInfo->revenue == 0 ? '—' : '$'.number_format($tmdbInfo->revenue) }}</span></li>
                <li class="flex justify-between"><span class="text-gray-500">Runtime</span><span class="text-gray-300">{{ $omdbInfo->Runtime ?? ($tmdbInfo->runtime == 0 ? '—' : $tmdbInfo->runtime.' min') }}</span></li>
                <li class="flex justify-between"><span class="text-gray-500">IMDB Votes</span><span class="text-gray-300">{{ $omdbInfo->imdbVotes ?? '—' }}</span></li>
                <li class="flex justify-between"><span class="text-gray-500">TMDB Score</span><span class="text-accent font-semibold">{{ $tmdbInfo->vote_average ?? '—' }}</span></li>
                <li class="flex justify-between"><span class="text-gray-500">Awards</span><span class="text-gray-300 text-right max-w-[55%]">{{ $omdbInfo->Awards ?? '—' }}</span></li>
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
                    @foreach ($tmdbInfo->production_countries as $country)
                        <li class="text-sm text-gray-300">{{ $country->name }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">No info</p>
            @endif
        </div>
    </div>

    {{-- Collection --}}
    @if ($collection)
    <div class="mb-6">
        <div class="section-header">
            <h2 class="text-xl font-bold text-white mb-3">{{ $collection->name }}</h2>
            <div class="section-divider"></div>
        </div>
        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
            @foreach ($collection->parts as $part)
                @php $isCurrent = $part->id == $tmdbInfo->id; @endphp
                <a href="{{ url('movie/' . $part->id) }}"
                   class="flex-shrink-0 w-28 group {{ $isCurrent ? 'opacity-50 pointer-events-none' : '' }}">
                    <div class="aspect-[2/3] rounded-lg overflow-hidden bg-white/[0.03] relative">
                        @if (!empty($part->poster_path))
                            <img src="https://image.tmdb.org/t/p/w185{{ $part->poster_path }}"
                                 alt="{{ $part->title }}"
                                 class="w-full h-full object-cover {{ $isCurrent ? '' : 'group-hover:scale-105 transition-transform duration-300' }}"
                                 loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs text-center px-2">No poster</div>
                        @endif
                        @if ($isCurrent)
                            <div class="absolute inset-0 flex items-end justify-center pb-2">
                                <span class="text-xs text-white bg-black/60 px-2 py-0.5 rounded-full">This film</span>
                            </div>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5 line-clamp-2 leading-snug">{{ $part->title }}</p>
                    @if (!empty($part->release_date))
                        <p class="text-xs text-gray-600">{{ substr($part->release_date, 0, 4) }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Similar movies --}}
    @if ($similarMovies != null)
    <div id="similar-section">
        <div class="section-header">
            <h2 class="text-xl font-bold text-white mb-3">{{ $similarTitle }}</h2>
            <div class="section-divider"></div>
        </div>
        @include('includes.carousel', ['allMovies' => $similarMovies, 'name' => 'swiper-similar', 'genres' => [], 'linkSuffix' => $linkSuffix, 'showScore' => true, 'showSave' => true, 'savedIds' => $savedIds])
    </div>
    @endif

</div>

{{-- Sticky bottom bar --}}
<div class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 z-40 sticky-bar-safe">

    {{-- Mobile --}}
    <div class="md:hidden flex px-3 py-1.5 gap-2">
        @if(request()->query('wl_status'))
            <a href="{{ route('watchlist') }}" class="btn-nav-tab">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Watchlist
            </a>
            <button id="wl-roll-btn-m" class="btn-nav-tab accent">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>
                Roll
            </button>
        @else
            @if($batchUrl)
            <a href="{{ $batchUrl }}" class="btn-nav-tab js-back-roulettes">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Batch
            </a>
            @endif
            <button type="button" class="btn-nav-tab js-criteria-btn" data-modal-open="modal-form">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M7 12h10M11 18h2"/></svg>
                Filters
            </button>
            <a href="/movie" class="btn-nav-tab accent" data-roll="movie-criteria">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>
                Roll
            </a>
        @endif
    </div>

    {{-- Desktop --}}
    <div class="hidden md:flex max-w-7xl mx-auto px-4 items-center justify-between gap-3">
        <div class="flex-shrink-0">
            @if(request()->query('wl_status'))
                <a href="{{ route('watchlist') }}" class="btn-secondary text-center">← Watchlist</a>
            @else
                <a href="{{ $batchUrl ?? '#' }}" class="btn-secondary text-center js-back-roulettes {{ $batchUrl ? '' : 'hidden' }}">← Batch</a>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if(request()->query('wl_status'))
                <button id="wl-roll-btn" class="btn-accent">Roll</button>
            @else
                <button type="button" class="btn-secondary js-criteria-btn" data-modal-open="modal-form">Filters</button>
                <a href="/movie" class="btn-accent long-single text-center" data-roll="movie-criteria">Roll</a>
            @endif
        </div>
    </div>

</div>
@if(request()->query('wl_status'))
<script>document.getElementById('wl-roll-btn-m')?.addEventListener('click',()=>document.getElementById('wl-roll-btn').click());</script>
@endif

@if(request()->query('wl_status'))
<script>
document.getElementById('wl-roll-btn').addEventListener('click', function () {
    const params  = new URLSearchParams(window.location.search);
    const status  = params.get('wl_status') || 'all';
    const type    = params.get('wl_type') || 'all';
    const genres  = params.get('wl_genres') || '';
    const exclude = (window.location.pathname.match(/\/movie\/(\d+)/) || [])[1] || '';
    let url = '{{ route('watchlist') }}?autoroll=1&wl_status=' + encodeURIComponent(status);
    if (genres)  url += '&wl_genres='  + encodeURIComponent(genres);
    if (type !== 'all') url += '&wl_type=' + encodeURIComponent(type);
    if (exclude) url += '&wl_exclude=' + exclude;
    window.location.href = url;
});
</script>
@endif

@endsection
