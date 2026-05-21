<!DOCTYPE html>
<html lang="en" translate="no" data-theme="dark">
<head>
    <title>@yield('page_title', 'MoviePickr')</title>
    <link rel="icon" href="{{ URL::asset('/images/icon.png') }}"/>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="description" content="Random Movie Picker — find the perfect film for tonight.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>!function(){var m=document.cookie.match(/(?:^|; )theme=([^;]+)/);if(m)document.documentElement.dataset.theme=m[1]}()</script>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/custom/watchlist.js', 'resources/js/custom/search.js'])
    @yield('scripts', '')
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-204204564-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'UA-204204564-1');
    </script>
</head>
<body>

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
                @auth
                    @if(auth()->user()->email === config('api.admin_email'))
                        <a href="{{ route('admin.dashboard') }}" class="text-xs font-medium px-2 py-1 rounded-md bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">Admin</a>
                    @endif
                @endauth
                <a href="/roulettes" class="nav-link text-sm">Roulettes</a>

                {{-- User section --}}
                @auth
                    <a href="{{ route('my-roulettes.index') }}" class="nav-link text-sm">My Roulettes</a>
                    <a href="{{ route('watchlist') }}" class="nav-link text-sm">Watchlist</a>
                    <form method="POST" action="{{ route('logout') }}" class="flex items-center">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors">
                            @if(Auth::user()->avatar)
                                <img src="{{ Auth::user()->avatar }}" class="w-6 h-6 rounded-full ring-1 ring-white/10" alt="">
                            @endif
                            Sign out
                        </button>
                    </form>
                @else
                    <a href="{{ route('auth.google') }}" class="text-sm px-3 py-1.5 rounded-lg border border-white/10 text-gray-300 hover:text-white hover:border-white/25 transition-all">Sign in</a>
                @endauth

                {{-- Search --}}
                <div id="desktop-search-wrap" class="relative">
                    <input id="desktop-search-input" type="text" placeholder="Search movies…" autocomplete="off"
                        class="w-40 focus:w-56 bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-sm text-white placeholder-gray-500 outline-none focus:border-white/20 transition-all duration-200">
                    <div id="desktop-search-results" class="hidden absolute right-0 top-full mt-1 w-72 bg-[#1a1a1a] border border-white/10 rounded-xl overflow-hidden shadow-2xl z-50 divide-y divide-white/5"></div>
                </div>

                {{-- Theme toggle --}}
                <button id="theme-toggle"
                    class="theme-toggle p-1.5 rounded-lg text-gray-500 hover:text-white hover:bg-white/5 transition-all"
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

    </nav>

    {{-- Mobile menu overlay --}}
    <div id="mobile-menu" class="hidden fixed inset-x-0 top-16 bottom-0 bg-[#0f0f0f] z-50 overflow-y-auto md:hidden">
        <div class="px-4 py-4 flex flex-col gap-1 min-h-full">

            {{-- Search --}}
            <div class="relative mb-2">
                <input id="mobile-search-input" type="text" placeholder="Search movies…" autocomplete="off"
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 outline-none focus:border-white/20 transition-colors">
                <div id="mobile-search-results" class="hidden mt-1 bg-[#1a1a1a] border border-white/10 rounded-xl overflow-hidden divide-y divide-white/5"></div>
            </div>

            {{-- Primary actions --}}
            <a href="/movie?i=new" class="long-single flex items-center justify-between px-4 py-3.5 rounded-xl bg-white/5 hover:bg-white/8 text-white font-medium text-sm transition-colors">
                Random Movie
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            </a>
            <a href="/multiple?i=new" class="flex items-center justify-between px-4 py-3.5 rounded-xl bg-white/5 hover:bg-white/8 text-white font-medium text-sm transition-colors">
                Random Batch
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            </a>

            {{-- Nav links --}}
            <div class="h-px bg-white/5 my-2"></div>
            <a href="/roulettes" class="flex items-center gap-2 px-4 py-3 rounded-xl text-sm text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                Roulettes
            </a>
            <a href="/criteria" class="flex items-center justify-between px-4 py-3 rounded-xl text-sm text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                Criteria
            </a>

            {{-- Account --}}
            <div class="h-px bg-white/5 my-2"></div>
            @auth
                <div class="flex items-center gap-3 px-4 py-3">
                    @if(Auth::user()->avatar)
                        <img src="{{ Auth::user()->avatar }}" class="w-9 h-9 rounded-full ring-1 ring-white/10 flex-shrink-0" alt="">
                    @else
                        <div class="w-9 h-9 rounded-full bg-accent/20 flex items-center justify-center text-accent text-sm font-semibold flex-shrink-0">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="text-sm text-white font-medium truncate">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</div>
                    </div>
                </div>

                @if(auth()->user()->email === config('api.admin_email'))
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-4 py-3 rounded-xl text-sm text-red-400 hover:text-red-300 hover:bg-red-500/5 transition-colors">Admin</a>
                @endif
                <a href="{{ route('my-roulettes.index') }}" class="flex items-center justify-between px-4 py-3 rounded-xl text-sm text-gray-300 hover:text-white hover:bg-white/5 transition-colors">My Roulettes</a>
                <a href="{{ route('watchlist') }}" class="flex items-center justify-between px-4 py-3 rounded-xl text-sm text-gray-300 hover:text-white hover:bg-white/5 transition-colors">Watchlist</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-3 rounded-xl text-sm text-gray-400 hover:text-white hover:bg-white/5 transition-colors">Sign out</button>
                </form>
            @else
                <a href="{{ route('auth.google') }}" class="flex items-center justify-between px-4 py-3.5 rounded-xl bg-white/5 hover:bg-white/8 text-sm text-white font-medium transition-colors">
                    Sign in with Google
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            @endauth

            {{-- Theme toggle pinned to bottom --}}
            <div class="mt-auto pt-4">
                <div class="h-px bg-white/5 mb-2"></div>
                <button class="theme-toggle w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm text-gray-400 hover:text-white hover:bg-white/5 transition-colors" aria-label="Toggle theme">
                    <span>Toggle theme</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                </button>
            </div>

        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert-msg fixed top-20 left-1/2 -translate-x-1/2 z-50 bg-green-900/80 border border-green-700/50 text-green-300 text-sm px-5 py-3 rounded-lg backdrop-blur-sm cursor-pointer"
             onclick="this.remove()">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert-msg fixed top-20 left-1/2 -translate-x-1/2 z-50 bg-red-900/80 border border-red-700/50 text-red-300 text-sm px-5 py-3 rounded-lg backdrop-blur-sm cursor-pointer"
             onclick="this.remove()">
            {{ session('error') }}
        </div>
    @endif

    {{-- Page content --}}
    <main class="pt-16">
        @yield('content')
    </main>

    @unless(View::hasSection('hide_footer'))
    <footer class="border-t border-white/5 mt-8 pt-6 @yield('footer_pb', 'pb-6') text-center text-xs text-gray-600">
        © Martynas Jankauskas {{ date('Y') }}
        <div class="mt-3 flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
            <a href="https://www.themoviedb.org" target="_blank" class="flex items-center gap-1.5 hover:opacity-80 transition-opacity">
                <img src="https://www.themoviedb.org/assets/2/v4/logos/v2/blue_short-8e7b30f73a4020692ccca9c88bafe5dcb6f8a62a4c6bc55cd9ba82bb2cd95f6c.svg" alt="TMDB" class="h-3">
            </a>
            <span class="text-gray-700">This product uses the TMDB API but is not endorsed or certified by TMDB.</span>
            <span class="text-gray-700">Watch provider data by <a href="https://www.justwatch.com" target="_blank" class="hover:text-gray-500 transition-colors">JustWatch</a>.</span>
        </div>
    </footer>
    @endunless

</body>
</html>
