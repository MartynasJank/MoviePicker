<!DOCTYPE html>
<html lang="en" translate="no">
<head>
    <title>@yield('page_title', 'MoviePickr')</title>
    <link rel="icon" href="{{ URL::asset('/images/icon.png') }}"/>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="description" content="Random Movie Picker — find the perfect film for tonight.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>window.TMDB_API_KEY = '{{ config('api.TMDB') }}';</script>
    @yield('scripts', '')
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-204204564-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'UA-204204564-1');
    </script>
</head>
<body data-theme="dark">

    {{-- Loading overlay --}}
    <div class="overlay"></div>
    <div class="loader">
        <div class="loading-text"></div>
        <div class="loader-dots">
            <div class="loader-dot"></div>
            <div class="loader-dot"></div>
            <div class="loader-dot"></div>
            <div class="loader-dot"></div>
            <div class="loader-dot"></div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="fixed top-0 left-0 right-0 z-50 h-16 bg-[#0f0f0f]/90 backdrop-blur-lg border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 h-full flex items-center justify-between gap-4">

            {{-- Logo --}}
            <a href="/" class="text-lg font-bold flex-shrink-0">
                <span class="text-white">Movie</span><span class="text-accent">Pickr</span>
            </a>

            {{-- Desktop nav --}}
            <div class="hidden md:flex items-center gap-5 flex-1 justify-end">
                {{-- Search --}}
                <form action="/movie" method="POST" class="submit-search relative" id="movie-search">
                    @csrf
                    <input
                        type="text"
                        id="movie_search"
                        name="movie_search"
                        placeholder="Search movies…"
                        class="movie-search bg-white/5 border border-white/10 rounded-lg pl-3 pr-3 py-1.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/40 w-44 transition-all focus:w-52"
                        autocomplete="off"
                    >
                </form>
                <a href="/roulettes" class="nav-link text-sm">Roulettes</a>
                <a href="/criteria"  class="nav-link text-sm">Criteria</a>
                <a href="/movie?i=new" class="nav-link text-sm long-single">Random</a>
                <a href="/multiple?i=new" class="nav-link text-sm">Batch</a>
                <button id="theme-toggle"
                    class="p-1.5 rounded-lg text-gray-500 hover:text-white hover:bg-white/5 transition-all"
                    aria-label="Toggle theme" title="Toggle theme">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                </button>
            </div>

            {{-- Mobile hamburger --}}
            <button class="hamburger md:hidden" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>

        {{-- Mobile menu --}}
        <div id="mobile-menu" class="hidden md:hidden bg-[#0f0f0f]/98 border-b border-white/5">
            <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col gap-1">
                <form action="/movie" method="POST" class="submit-search" id="movie-search-mobile">
                    @csrf
                    <input
                        type="text"
                        id="movie_search_mobile"
                        name="movie_search"
                        placeholder="Search movies…"
                        class="movie-search input-dark text-sm w-full mb-2"
                        autocomplete="off"
                    >
                </form>
                <a href="/roulettes" class="py-2 text-sm text-gray-400 hover:text-white transition-colors">Roulettes</a>
                <a href="/criteria"  class="py-2 text-sm text-gray-400 hover:text-white transition-colors">Criteria</a>
                <a href="/movie?i=new" class="py-2 text-sm text-gray-400 hover:text-white transition-colors long-single">Random Movie</a>
                <a href="/multiple?i=new" class="py-2 text-sm text-gray-400 hover:text-white transition-colors">Random Batch</a>
            </div>
        </div>
    </nav>

    {{-- Page content --}}
    <main class="pt-16">
        @yield('content')
    </main>

    <footer class="border-t border-white/5 mt-16 py-6 text-center text-xs text-gray-600">
        © Martynas Jankauskas 2024
    </footer>

</body>
</html>
