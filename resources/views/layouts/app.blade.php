<!DOCTYPE html>
<html lang="en" translate="no" data-theme="dark">
<head>
    <title>@yield('page_title', 'MoviePickr')</title>
    <link rel="icon" href="{{ URL::asset('/images/icon.png') }}"/>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="description" content="Random Movie Picker — find the perfect film for tonight.">
    <script>!function(){var m=document.cookie.match(/(?:^|; )theme=([^;]+)/);if(m)document.documentElement.dataset.theme=m[1]}()</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                <a href="/roulettes" class="nav-link text-sm">Roulettes</a>
                <a href="/criteria"  class="nav-link text-sm">Criteria</a>
                <a href="/movie?i=new" class="nav-link text-sm long-single">Random</a>
                <a href="/multiple?i=new" class="nav-link text-sm">Batch</a>
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

        {{-- Mobile menu --}}
        <div id="mobile-menu" class="hidden md:hidden bg-[#0f0f0f]/98 border-b border-white/5">
            <div class="max-w-7xl mx-auto px-4 flex flex-col">
                <a href="/roulettes" class="py-4 text-sm text-gray-300 hover:text-white transition-colors border-b border-white/5">Roulettes</a>
                <a href="/criteria"  class="py-4 text-sm text-gray-300 hover:text-white transition-colors border-b border-white/5">Criteria</a>
                <a href="/movie?i=new" class="py-4 text-sm text-gray-300 hover:text-white transition-colors border-b border-white/5 long-single">Random Movie</a>
                <a href="/multiple?i=new" class="py-4 text-sm text-gray-300 hover:text-white transition-colors border-b border-white/5">Random Batch</a>
                <div class="py-3 flex items-center justify-between">
                    <span class="text-xs text-gray-600">Appearance</span>
                    <button class="theme-toggle flex items-center gap-2 text-xs text-gray-400 hover:text-white transition-colors py-1.5 px-3 rounded-lg hover:bg-white/5" aria-label="Toggle theme">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                        </svg>
                        Toggle theme
                    </button>
                </div>
            </div>
        </div>
    </nav>

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

    <footer class="border-t border-white/5 mt-8 pt-6 @yield('footer_pb', 'pb-6') text-center text-xs text-gray-600">
        © Martynas Jankauskas {{ date('Y') }}
    </footer>

</body>
</html>
