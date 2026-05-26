@extends('layouts.app')
@section('page_title', 'Roulettes — MoviePickr')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">

    <div class="mb-8">
        <div class="flex items-center gap-4 flex-wrap">
            <h1 class="text-3xl font-bold text-white">Roulettes</h1>
            <div class="flex gap-1 bg-white/5 p-1 rounded-lg">
                <button class="roulette-tab active text-xs px-4 py-1.5 rounded-md transition-all font-medium" data-tab="movies">Movies</button>
                <button class="roulette-tab text-xs px-4 py-1.5 rounded-md transition-all font-medium text-gray-400" data-tab="tv">TV Shows</button>
            </div>
        </div>
        <p class="text-gray-500 text-sm mt-1">Curated collections — just hit Roll.</p>
        <div class="section-divider mt-3"></div>
    </div>

    @include('includes.roulette-labels')

    {{-- Movies panel --}}
    <div id="roulette-panel-movies">
        @include('includes.roulette-grid', ['grouped' => $movieGrouped])
    </div>

    {{-- TV Shows panel --}}
    <div id="roulette-panel-tv" class="hidden">
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
        var stored = localStorage.getItem('roulette_tab') || 'movies';

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
        }

        activate(stored);
        tabs.forEach(function (t) {
            t.addEventListener('click', function () { activate(t.dataset.tab); });
        });
    })();

</script>

@endsection
