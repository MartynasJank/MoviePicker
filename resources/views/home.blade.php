@extends('layouts.app')
@section('meta_description', 'MoviePickr rolls a random movie or TV show tailored to your mood. Filter by genre, streaming service, rating, and year — then let it decide.')
@section('scripts')
    @vite(['resources/js/custom/carousel.js', 'resources/js/custom/watchlist.js'])
@endsection
@section('content')

    {{-- Hero --}}
    <section class="relative min-h-[88vh] flex items-center justify-center overflow-hidden">

        {{-- Poster collage background --}}
        @php $bgPosters = array_values(array_filter(array_slice($trendingDay['results'] ?? [], 0, 12), fn($m) => !empty($m['poster_path']))); @endphp
        @if(count($bgPosters) >= 3)
            <div class="absolute inset-0 grid grid-cols-3 md:grid-cols-6 opacity-[0.18] pointer-events-none overflow-hidden" aria-hidden="true">
                @foreach($bgPosters as $m)
                    <img src="https://image.tmdb.org/t/p/w185{{ $m['poster_path'] }}"
                         alt=""
                         width="185" height="278"
                         class="w-full h-full object-cover {{ $loop->index >= 6 ? 'hidden md:block' : '' }}"
                         @if($loop->index < 6) fetchpriority="high" @else loading="lazy" @endif>
                @endforeach
            </div>
        @endif

        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(192,57,58,0.12)_0%,transparent_65%)]"></div>
        <div class="hero-fade absolute inset-0"></div>
        <div class="relative z-10 text-center px-4 py-12 sm:py-20 max-w-3xl mx-auto">
            <h1 class="text-4xl sm:text-5xl md:text-7xl font-bold tracking-tight mb-4 sm:mb-5 leading-tight">
                <span class="text-white">Random</span><br>
                <span class="text-accent">Movie Picker</span>
            </h1>
            <p class="text-gray-400 text-base sm:text-lg mb-7 sm:mb-10">Filter by genre, streaming service, or mood. Or just hit roll and see what comes up.</p>
            <div class="grid sm:grid-cols-2 gap-3 sm:gap-4 max-w-xl mx-auto w-full text-left">
                {{-- Movies card --}}
                <div class="bg-white/5 border border-white/8 rounded-2xl p-4 sm:p-6 flex flex-col gap-3 sm:gap-4 hover:bg-white/7 hover:border-white/15 transition-all duration-200 hover:shadow-[0_0_28px_rgba(192,57,58,0.12)]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-accent/15 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="2" y="4" width="20" height="16" rx="2"/>
                                <path d="M8 4v16M16 4v16M2 12h20M2 8h4M2 16h4M18 8h4M18 16h4"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-white">Movies</span>
                    </div>
                    <a href="/movie?i=new" class="btn-accent long-single text-center">Random Movie</a>
                    <div class="flex gap-2">
                        <a href="/multiple?i=new" class="btn-secondary long-single text-center text-sm flex-1">Batch</a>
                        <a href="/criteria" class="btn-secondary text-center text-sm flex-1">Filters</a>
                    </div>
                </div>
                {{-- TV Shows card --}}
                <div class="bg-white/5 border border-white/8 rounded-2xl p-4 sm:p-6 flex flex-col gap-3 sm:gap-4 hover:bg-white/7 hover:border-white/15 transition-all duration-200 hover:shadow-[0_0_28px_rgba(192,57,58,0.12)]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-accent/15 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="2" y="3" width="20" height="14" rx="2"/>
                                <path d="M8 21h8M12 17v4"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-white">TV Shows</span>
                    </div>
                    <a href="/tv/pick?i=new" class="btn-accent long-single text-center">Random TV Show</a>
                    <div class="flex gap-2">
                        <a href="/tv/multiple?i=new" class="btn-secondary long-single text-center text-sm flex-1">Batch</a>
                        <a href="/tv/criteria" class="btn-secondary text-center text-sm flex-1">Filters</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Mood shortcuts --}}
    <section class="max-w-7xl mx-auto px-4 py-8 sm:py-12 border-b border-white/5">
        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 mb-3">
            <h2 class="text-xl sm:text-2xl font-bold text-white">I'm in the mood for…</h2>
            <div class="flex gap-1 bg-white/5 p-1 rounded-lg shrink-0">
                <button id="mood-movies-btn" class="trend-toggle active text-xs px-3 py-1.5 rounded-md transition-all">Movies</button>
                <button id="mood-tv-btn" class="trend-toggle text-xs px-3 py-1.5 rounded-md transition-all text-gray-400">TV</button>
            </div>
        </div>
        <div class="section-divider"></div>

        {{-- Movie mood tiles --}}
        {{-- Genre IDs: Comedy 35, Thriller 53, Drama 18, Horror 27, Romance 10749, Action 28, Crime 80, Animation 16, Family 10751 --}}
        <div id="mood-movies" class="grid grid-cols-3 sm:grid-cols-6 gap-3 mt-6">

            {{-- Funny: Comedy, decent quality floor --}}
            <form method="POST" action="/movie?a=1">
                @csrf
                <input type="hidden" name="with_original_language" value="en">
                <input type="hidden" name="with_genres[]" value="35">
                <input type="hidden" name="without_genres[]" value="27">
                <input type="hidden" name="without_genres[]" value="53">
                <input type="hidden" name="vote_average_gte" value="6.0">
                <input type="hidden" name="vote_count_gte" value="200">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to laugh at!" data-mood="funny">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M8 13s1.5 3 4 3 4-3 4-3"/>
                        <line x1="9" y1="9" x2="9.01" y2="9"/>
                        <line x1="15" y1="9" x2="15.01" y2="9"/>
                    </svg>
                    <span>Funny</span>
                </button>
            </form>

            {{-- Intense: Thriller, no comedy/romance fluff --}}
            <form method="POST" action="/movie?a=1">
                @csrf
                <input type="hidden" name="with_original_language" value="en">
                <input type="hidden" name="with_genres[]" value="53">
                <input type="hidden" name="without_genres[]" value="35">
                <input type="hidden" name="without_genres[]" value="16">
                <input type="hidden" name="without_genres[]" value="10749">
                <input type="hidden" name="vote_average_gte" value="6.5">
                <input type="hidden" name="vote_count_gte" value="200">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to keep you on edge!" data-mood="intense">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                    <span>Intense</span>
                </button>
            </form>

            {{-- Feel-good: Drama, high quality bar --}}
            <form method="POST" action="/movie?a=1">
                @csrf
                <input type="hidden" name="with_original_language" value="en">
                <input type="hidden" name="with_genres[]" value="18">
                <input type="hidden" name="vote_average_gte" value="7.5">
                <input type="hidden" name="vote_count_gte" value="200">
                <input type="hidden" name="without_genres[]" value="27">
                <input type="hidden" name="without_genres[]" value="53">
                <input type="hidden" name="without_genres[]" value="80">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to warm your heart!" data-mood="feel-good">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    <span>Feel-good</span>
                </button>
            </form>

            {{-- Dark: Horror, filter out comedy/family/very low rated --}}
            <form method="POST" action="/movie?a=1">
                @csrf
                <input type="hidden" name="with_original_language" value="en">
                <input type="hidden" name="with_genres[]" value="27">
                <input type="hidden" name="without_genres[]" value="35">
                <input type="hidden" name="without_genres[]" value="16">
                <input type="hidden" name="without_genres[]" value="10751">
                <input type="hidden" name="vote_average_gte" value="6.0">
                <input type="hidden" name="vote_count_gte" value="200">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to haunt you!" data-mood="dark">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                    <span>Dark</span>
                </button>
            </form>

            {{-- Romantic: Romance, exclude action/horror/thriller --}}
            <form method="POST" action="/movie?a=1">
                @csrf
                <input type="hidden" name="with_original_language" value="en">
                <input type="hidden" name="with_genres[]" value="10749">
                <input type="hidden" name="without_genres[]" value="27">
                <input type="hidden" name="without_genres[]" value="53">
                <input type="hidden" name="without_genres[]" value="28">
                <input type="hidden" name="vote_average_gte" value="6.5">
                <input type="hidden" name="vote_count_gte" value="100">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to fall in love with!" data-mood="romantic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                    </svg>
                    <span>Romantic</span>
                </button>
            </form>

            {{-- Mindless: Action, exclude horror/heavy drama --}}
            <form method="POST" action="/movie?a=1">
                @csrf
                <input type="hidden" name="with_original_language" value="en">
                <input type="hidden" name="with_genres[]" value="28">
                <input type="hidden" name="without_genres[]" value="27">
                <input type="hidden" name="without_genres[]" value="18">
                <input type="hidden" name="vote_average_gte" value="6.0">
                <input type="hidden" name="vote_count_gte" value="200">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to just enjoy!" data-mood="mindless">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polygon points="10 8 16 12 10 16 10 8"/>
                    </svg>
                    <span>Mindless</span>
                </button>
            </form>

        </div>

        {{-- TV mood tiles --}}
        {{-- Genre IDs: Comedy 35, Crime 80, Drama 18, Mystery 9648, Action&Adventure 10759, Romance 10749, Animation 16, Family 10751 --}}
        <div id="mood-tv" class="hidden grid grid-cols-3 sm:grid-cols-6 gap-3 mt-6">

            {{-- Funny: Comedy, exclude animation (separate category) --}}
            <form method="POST" action="/tv/pick?a=1">
                @csrf
                <input type="hidden" name="with_original_language" value="en">
                <input type="hidden" name="with_genres[]" value="35">
                <input type="hidden" name="without_genres[]" value="16">
                <input type="hidden" name="vote_average_gte" value="7.0">
                <input type="hidden" name="vote_count_gte" value="100">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to laugh at!" data-mood="funny">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M8 13s1.5 3 4 3 4-3 4-3"/>
                        <line x1="9" y1="9" x2="9.01" y2="9"/>
                        <line x1="15" y1="9" x2="15.01" y2="9"/>
                    </svg>
                    <span>Funny</span>
                </button>
            </form>

            {{-- Intense: Crime or Action&Adventure, no comedy --}}
            <form method="POST" action="/tv/pick?a=1">
                @csrf
                <input type="hidden" name="with_original_language" value="en">
                <input type="hidden" name="with_genres[]" value="80">
                <input type="hidden" name="with_genres[]" value="10759">
                <input type="hidden" name="without_genres[]" value="35">
                <input type="hidden" name="without_genres[]" value="16">
                <input type="hidden" name="vote_average_gte" value="7.0">
                <input type="hidden" name="vote_count_gte" value="100">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to keep you on edge!" data-mood="intense">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                    <span>Intense</span>
                </button>
            </form>

            {{-- Feel-good: Drama, high quality bar, no crime/mystery --}}
            <form method="POST" action="/tv/pick?a=1">
                @csrf
                <input type="hidden" name="with_original_language" value="en">
                <input type="hidden" name="with_genres[]" value="18">
                <input type="hidden" name="vote_average_gte" value="7.5">
                <input type="hidden" name="vote_count_gte" value="100">
                <input type="hidden" name="without_genres[]" value="80">
                <input type="hidden" name="without_genres[]" value="9648">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to warm your heart!" data-mood="feel-good">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    <span>Feel-good</span>
                </button>
            </form>

            {{-- Dark: Mystery, no comedy/family --}}
            <form method="POST" action="/tv/pick?a=1">
                @csrf
                <input type="hidden" name="with_original_language" value="en">
                <input type="hidden" name="with_genres[]" value="9648">
                <input type="hidden" name="without_genres[]" value="35">
                <input type="hidden" name="without_genres[]" value="16">
                <input type="hidden" name="without_genres[]" value="10751">
                <input type="hidden" name="vote_average_gte" value="7.0">
                <input type="hidden" name="vote_count_gte" value="100">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to haunt you!" data-mood="dark">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                    <span>Dark</span>
                </button>
            </form>

            {{-- Romantic: Romance genre (was incorrectly Drama), no action/crime --}}
            <form method="POST" action="/tv/pick?a=1">
                @csrf
                <input type="hidden" name="with_original_language" value="en">
                <input type="hidden" name="with_genres[]" value="10749">
                <input type="hidden" name="without_genres[]" value="80">
                <input type="hidden" name="without_genres[]" value="9648">
                <input type="hidden" name="without_genres[]" value="10759">
                <input type="hidden" name="vote_average_gte" value="7.0">
                <input type="hidden" name="vote_count_gte" value="100">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to fall in love with!" data-mood="romantic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                    </svg>
                    <span>Romantic</span>
                </button>
            </form>

            {{-- Mindless: Action & Adventure, no heavy drama --}}
            <form method="POST" action="/tv/pick?a=1">
                @csrf
                <input type="hidden" name="with_original_language" value="en">
                <input type="hidden" name="with_genres[]" value="10759">
                <input type="hidden" name="without_genres[]" value="18">
                <input type="hidden" name="vote_average_gte" value="6.5">
                <input type="hidden" name="vote_count_gte" value="100">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to just enjoy!" data-mood="mindless">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polygon points="10 8 16 12 10 16 10 8"/>
                    </svg>
                    <span>Mindless</span>
                </button>
            </form>

        </div>
    </section>

    {{-- Trending --}}
    <section class="max-w-7xl mx-auto px-4 py-8 sm:py-12 border-b border-white/5">
        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 mb-3">
            <h2 class="text-xl sm:text-2xl font-bold text-white">Trending Today</h2>
            <div class="flex gap-1 bg-white/5 p-1 rounded-lg shrink-0">
                <button id="trend-movies" class="trend-toggle active text-xs px-3 py-1.5 rounded-md transition-all">Movies</button>
                <button id="trend-tv" class="trend-toggle text-xs px-3 py-1.5 rounded-md transition-all text-gray-400">TV</button>
            </div>
        </div>
        <div class="section-divider mb-4"></div>

        <div id="trending-movies">
            @include('includes.carousel', ['allMovies' => $trendingDay, 'name' => 'swiper-trending-movies', 'genres' => $trendingGenres, 'clearCriteria' => true, 'showScore' => true, 'showSave' => true, 'savedIds' => $savedIds, 'eagerCount' => 2])
        </div>
        <div id="trending-tv" class="hidden">
            @include('includes.carousel', ['allMovies' => $tvTrendingDay, 'name' => 'swiper-trending-tv', 'genres' => $tvTrendingGenres, 'clearCriteria' => true, 'showScore' => true, 'showSave' => true, 'savedIds' => $savedIds, 'linkBase' => 'tv', 'mediaType' => 'tv'])
        </div>
    </section>

    {{-- About --}}
    <section class="bg-white/[0.02] border-y border-white/5 py-10 sm:py-16">
        <div class="max-w-2xl mx-auto px-4 text-center">
            <h2 class="text-2xl font-bold text-white mb-3">About MoviePickr</h2>
            <div class="section-divider mb-6"></div>
            <p class="text-gray-400 leading-relaxed mb-8">
                Sometimes the best movie is one you never would have chosen yourself.
                Tell MoviePickr what you're in the mood for and it'll find something worth watching.
                Filter by genre, decade, streaming service, or cast, or skip the filters entirely and let it surprise you.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center items-stretch sm:items-center">
                <a href="/movie?i=new" class="btn-accent long-single text-center">Random Movie</a>
                <a href="/criteria" class="btn-secondary text-center">Set Preferences</a>
            </div>
        </div>
    </section>

    {{-- Featured Roulettes --}}
    @if($featuredRoulettes->isNotEmpty())
    @include('includes.roulette-labels')
    <section class="max-w-7xl mx-auto px-4 py-8 sm:py-12">
        <div class="flex flex-wrap items-end justify-between gap-3 mb-3">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-white">Curated Roulettes</h2>
                <p class="text-gray-500 text-sm mt-0.5">Handpicked collections — hit Roll and let fate decide.</p>
            </div>
            <a href="/roulettes" class="btn-secondary text-sm px-4 py-2 flex-shrink-0">Browse all roulettes →</a>
        </div>
        <div class="section-divider mb-5"></div>
        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
            @foreach($featuredRoulettes as $roulette)
                @php
                    $tags     = $roulette->tags;
                    $platform = $tags['platform'][0] ?? null;
                    $logo     = $platform ? ($platformLogos[$platform] ?? null) : null;
                    $poster   = $roulette->poster_paths[0] ?? null;
                    $isTv     = $roulette->media_type === 'tv';
                @endphp
                <div class="flex-shrink-0 w-36 md:w-44">
                    <a href="/roulettes/{{ $roulette->slug }}"
                       data-roulette-batch
                       class="group relative rounded-xl overflow-hidden block bg-slate-900 long-single">
                        <div class="aspect-[2/3] relative overflow-hidden">
                            @if($poster)
                                <img src="https://image.tmdb.org/t/p/w185{{ $poster }}"
                                     alt="{{ $roulette->name }}"
                                     width="185" height="278"
                                     class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                     loading="lazy">
                            @else
                                <div class="absolute inset-0 bg-gradient-to-br from-slate-700 to-slate-900"></div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-transparent"></div>
                            @if($logo)
                                <img src="{{ $logo }}" alt="{{ $platform }}" class="absolute top-2 right-2 h-5 drop-shadow-lg" loading="lazy">
                            @endif
                            @if($isTv)
                                <span class="absolute top-2 left-2 text-[10px] px-1.5 py-0.5 rounded-full bg-accent/80 text-white">TV</span>
                            @endif
                            <div class="absolute bottom-0 left-0 right-0 p-3">
                                <h3 class="text-sm font-semibold text-white leading-snug">{{ $roulette->name }}</h3>
                                <span class="text-xs text-accent font-medium mt-1 block group-hover:underline">Batch →</span>
                            </div>
                        </div>
                    </a>
                    <button class="w-full btn-accent text-xs py-1.5 mt-2" data-roulette-roll data-slug="{{ $roulette->slug }}">Roll</button>
                </div>
            @endforeach

            {{-- Create your own CTA card --}}
            <div class="flex-shrink-0 w-36 md:w-44">
                <a href="{{ auth()->check() ? route('my-roulettes.create') : route('auth.google') }}"
                   class="group relative rounded-xl overflow-hidden block long-single border border-dashed border-white/15 hover:border-accent/50 transition-colors bg-white/3 hover:bg-white/5">
                    <div class="aspect-[2/3] relative flex flex-col items-center justify-center p-4 text-center">
                        <div class="w-10 h-10 rounded-full bg-accent/10 group-hover:bg-accent/20 transition-colors flex items-center justify-center mb-3">
                            @auth
                                <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            @endauth
                        </div>
                        @auth
                            <p class="text-xs font-semibold text-white leading-snug mb-1">Create Your Own</p>
                            <p class="text-[10px] text-gray-500 leading-snug">Build a roulette from your favourite genres, platforms or actors</p>
                        @else
                            <p class="text-xs font-semibold text-white leading-snug mb-1">Sign in to Create</p>
                            <p class="text-[10px] text-gray-500 leading-snug">Save your own roulettes and roll them any time</p>
                        @endauth
                    </div>
                </a>
                <div class="h-[30px] mt-2"></div>{{-- spacer to align with Roll buttons --}}
            </div>
        </div>
    </section>
    @endif


    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const moviesBtn   = document.getElementById('mood-movies-btn');
        const tvBtn       = document.getElementById('mood-tv-btn');
        const moviesTiles = document.getElementById('mood-movies');
        const tvTiles     = document.getElementById('mood-tv');
        if (!moviesBtn) return;
        moviesBtn.addEventListener('click', function () {
            moviesBtn.classList.add('active');    moviesBtn.classList.remove('text-gray-400');
            tvBtn.classList.remove('active');     tvBtn.classList.add('text-gray-400');
            moviesTiles.classList.remove('hidden');
            tvTiles.classList.add('hidden');
        });
        tvBtn.addEventListener('click', function () {
            tvBtn.classList.add('active');        tvBtn.classList.remove('text-gray-400');
            moviesBtn.classList.remove('active'); moviesBtn.classList.add('text-gray-400');
            tvTiles.classList.remove('hidden');
            moviesTiles.classList.add('hidden');
        });

        const trendMoviesBtn = document.getElementById('trend-movies');
        const trendTvBtn     = document.getElementById('trend-tv');
        const trendMovies    = document.getElementById('trending-movies');
        const trendTv        = document.getElementById('trending-tv');
        if (trendMoviesBtn) {
            trendMoviesBtn.addEventListener('click', function () {
                trendMoviesBtn.classList.add('active');    trendMoviesBtn.classList.remove('text-gray-400');
                trendTvBtn.classList.remove('active');     trendTvBtn.classList.add('text-gray-400');
                trendMovies.classList.remove('hidden');
                trendTv.classList.add('hidden');
            });
            trendTvBtn.addEventListener('click', function () {
                trendTvBtn.classList.add('active');        trendTvBtn.classList.remove('text-gray-400');
                trendMoviesBtn.classList.remove('active'); trendMoviesBtn.classList.add('text-gray-400');
                trendTv.classList.remove('hidden');
                trendMovies.classList.add('hidden');
            });
        }
    });
    </script>

@endsection
