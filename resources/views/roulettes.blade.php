@extends('layouts.app')
@section('page_title', 'Roulettes — MoviePickr')
@section('meta_description', 'Browse curated movie and TV roulettes by streaming service, genre, decade, and more. Roll to get a random pick from any collection.')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">

    <div class="mb-8">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <h1 id="roulettes-heading" class="text-3xl font-bold text-white">Movie Roulettes</h1>
            <div class="flex gap-1 bg-white/5 p-1 rounded-lg">
                <button class="roulette-tab active text-xs px-4 py-1.5 rounded-md transition-all font-medium" data-tab="movies">Movies</button>
                <button class="roulette-tab text-xs px-4 py-1.5 rounded-md transition-all font-medium text-gray-400" data-tab="tv">TV Shows</button>
            </div>
        </div>
        <p class="text-gray-500 text-sm mt-1">Curated movie and TV collections by streaming service, genre, and decade. Hit Roll for an instant random pick.</p>
        <div class="section-divider mt-3"></div>
    </div>

    @include('includes.roulette-labels')


    {{-- Movies panel --}}
    <div id="roulette-panel-movies">
        @include('includes.roulette-grid', ['grouped' => $movieGrouped])
    </div>

    {{-- TV Shows panel --}}
    <div id="roulette-panel-tv" class="hidden">
        <a href="/swipe/tv?reset=1" class="group flex items-center justify-between bg-gradient-to-r from-accent/20 to-accent/5 border border-accent/20 hover:border-accent/40 rounded-2xl p-4 mb-8 transition-all duration-200 hover:shadow-[0_0_32px_rgba(192,57,58,0.2)]">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xl">📺</span>
                    <span class="font-bold text-white">TV Show Swipe</span>
                </div>
                <p class="text-xs text-gray-400">Swipe through TV shows one by one. Like what you want to watch, skip the rest.</p>
            </div>
            <svg class="w-5 h-5 text-accent/60 flex-shrink-0 ml-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        @include('includes.roulette-grid', ['grouped' => $tvGrouped])
    </div>

</div>

<script>
    (function () {
        var tabs   = document.querySelectorAll('.roulette-tab');
        var panels = {
            movies: document.getElementById('roulette-panel-movies'),
            tv:     document.getElementById('roulette-panel-tv')
        };
        var urlTab = new URLSearchParams(location.search).get('tab');
        var stored = urlTab || localStorage.getItem('roulette_tab') || 'movies';

        function activate(tab) {
            tabs.forEach(function (t) {
                var active = t.dataset.tab === tab;
                t.classList.toggle('active', active);
                t.classList.toggle('text-gray-400', !active);
            });
            Object.keys(panels).forEach(function (key) {
                panels[key].classList.toggle('hidden', key !== tab);
            });
            localStorage.setItem('roulette_tab', tab);
            document.getElementById('roulettes-heading').textContent = tab === 'tv' ? 'TV Roulettes' : 'Movie Roulettes';
        }

        activate(stored);
        tabs.forEach(function (t) {
            t.addEventListener('click', function () { activate(t.dataset.tab); });
        });
    })();

</script>

@endsection
