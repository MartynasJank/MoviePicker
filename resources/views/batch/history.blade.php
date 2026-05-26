@extends('layouts.app')
@section('page_title', 'Batch — MoviePickr')
@section('footer_pb', 'pb-20')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 pb-24">

    <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-white">Batch</h1>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 swiper-multiple" id="batch-history-grid"></div>

</div>

{{-- Sticky bottom bar --}}
<div class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 px-4 z-40 sticky-bar-safe">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
        <div class="flex-shrink-0"></div>
        <div class="flex items-center gap-3">
            @include('includes.anim-toggle')
            <button id="batch-roll-btn" class="btn-accent flex-1 sm:flex-none">Roll</button>
        </div>
    </div>
</div>

<script>
(function () {
    var raw = sessionStorage.getItem('lastBatchCards');
    if (!raw) { window.location.href = '/'; return; }
    var movies;
    try { movies = JSON.parse(raw); } catch (e) { window.location.href = '/'; return; }
    if (!movies.length) { window.location.href = '/'; return; }

    var grid = document.getElementById('batch-history-grid');

    movies.forEach(function (m) {
        if (!m.poster_path) return;

        var isTv = m.url && m.url.indexOf('/tv/') !== -1;

        var div = document.createElement('div');
        div.setAttribute('data-batch-card', '1');
        div.setAttribute('data-poster', m.poster_path);
        div.setAttribute('data-title', m.title || '');
        div.setAttribute('data-rating', m.vote_average || 0);
        div.setAttribute('data-url', m.url);

        var score = m.vote_average ? '<div class="absolute top-2 left-2 bg-black/70 text-accent text-xs font-semibold px-1.5 py-0.5 rounded pointer-events-none">★ ' + parseFloat(m.vote_average).toFixed(1) + '</div>' : '';
        var badge = isTv
            ? '<div class="absolute top-2 right-2 bg-accent/80 text-white text-xs font-semibold px-1.5 py-0.5 rounded pointer-events-none">TV</div>'
            : '';

        var a = document.createElement('a');
        a.href = m.url;
        a.className = 'block group long-movie';
        a.innerHTML =
            '<div class="aspect-[2/3] rounded-xl overflow-hidden relative bg-white/[0.03]">' +
                '<img src="https://image.tmdb.org/t/p/w342' + m.poster_path + '" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">' +
                '<div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/10 to-transparent pointer-events-none">' +
                    '<div class="absolute bottom-0 left-0 right-0 p-3">' +
                        '<h4 class="text-sm font-semibold text-white leading-snug line-clamp-2"></h4>' +
                    '</div>' +
                '</div>' +
                score + badge +
            '</div>';

        a.querySelector('h4').textContent = m.title || '';
        div.appendChild(a);
        grid.appendChild(div);
    });
})();
</script>

@endsection
