@extends('layouts.app')
@section('page_title', 'Admin — Roulettes')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">

    <div class="mb-8 flex items-center gap-3">
        <h1 class="text-2xl font-bold text-white">Admin</h1>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 border border-red-500/20">Admin</span>
    </div>

    @include('admin._nav')

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-semibold text-white">Roulettes</h2>
            {{-- Movies / TV toggle --}}
            <div class="flex gap-1 bg-white/5 p-1 rounded-lg">
                <a href="{{ route('admin.roulettes.index', array_filter(['type' => 'movie', 'q' => $q])) }}"
                   class="text-xs px-3 py-1.5 rounded-md transition-all font-medium {{ $mediaType === 'movie' ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white' }}">Movies</a>
                <a href="{{ route('admin.roulettes.index', array_filter(['type' => 'tv', 'q' => $q])) }}"
                   class="text-xs px-3 py-1.5 rounded-md transition-all font-medium {{ $mediaType === 'tv' ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white' }}">TV Shows</a>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="type" value="{{ $mediaType }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Search…"
                       class="input-dark text-sm w-36 sm:w-44">
                <button type="submit" class="btn-secondary text-sm px-3 py-2">Search</button>
                @if($q) <a href="{{ route('admin.roulettes.index', ['type' => $mediaType]) }}" class="text-sm text-gray-500 hover:text-white">Clear</a> @endif
            </form>
            <a href="{{ route('admin.roulettes.create', ['type' => $mediaType]) }}" class="btn-accent text-sm px-4 py-2">+ New</a>
        </div>
    </div>

    @if($q)
        {{-- Search: flat list across all groups --}}
        @foreach($ordered as $groupName => $roulettes)
            @if($roulettes->isNotEmpty())
            <div class="mb-6">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">{{ $groupName }}</h3>
                @include('admin.roulettes._table', ['roulettes' => $roulettes, 'sortable' => false])
            </div>
            @endif
        @endforeach
    @else
        {{-- Sidebar + panel layout --}}
        <div class="flex flex-col md:flex-row gap-6">

            {{-- Sidebar --}}
            <div class="md:w-48 flex-shrink-0">
                <nav id="group-nav" class="flex md:flex-col gap-1 overflow-x-auto pb-1 md:pb-0 md:sticky md:top-20">
                    @foreach($ordered as $groupName => $roulettes)
                        <button type="button"
                                class="group-btn flex-shrink-0 flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors text-gray-500 hover:text-white hover:bg-white/5 text-left whitespace-nowrap"
                                data-panel="{{ $loop->index }}">
                            <span>{{ $groupName }}</span>
                            <span class="ml-2 text-xs text-gray-600">{{ $roulettes->count() }}</span>
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Panels --}}
            <div class="flex-1 min-w-0">
                @foreach($ordered as $groupName => $roulettes)
                    <div class="group-panel" data-panel="{{ $loop->index }}" style="display:none">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-white">{{ $groupName }}
                                <span class="text-gray-600 font-normal ml-1">{{ $roulettes->count() }}</span>
                            </h3>
                            @if($groupName === 'Ungrouped')
                                <span class="text-xs text-yellow-500/80">These roulettes belong to a deleted row — reassign them via Edit.</span>
                            @endif
                        </div>
                        @include('admin.roulettes._table', ['roulettes' => $roulettes, 'sortable' => $groupName !== 'Ungrouped'])
                    </div>
                @endforeach
            </div>

        </div>
    @endif

</div>
@endsection

@section('scripts')
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Poster roll buttons
    document.addEventListener('click', e => {
        const btn = e.target.closest('.roll-poster-btn');
        if (!btn) return;

        const id  = btn.dataset.id;
        const svg = btn.querySelector('svg');
        btn.disabled = true;
        svg.style.animation = 'spin 0.6s linear infinite';

        fetch(`/admin/roulettes/${id}/refresh-poster`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        })
        .then(r => r.json())
        .then(data => {
            if (!data.poster_path) return;
            const url = `https://image.tmdb.org/t/p/w92${data.poster_path}`;
            const cell = btn.closest('td');
            // Replace placeholder div with img if needed
            let img = cell.querySelector('.roulette-poster');
            const placeholder = cell.querySelector('.roulette-poster-placeholder');
            if (placeholder) {
                img = document.createElement('img');
                img.className = 'roulette-poster w-full h-full object-cover rounded';
                img.dataset.id = id;
                placeholder.replaceWith(img);
                btn.classList.remove('opacity-100');
                btn.classList.add('opacity-0', 'group-hover:opacity-100');
            }
            img.src = url;
        })
        .finally(() => {
            btn.disabled = false;
            svg.style.animation = '';
        });
    });


    @if(!$q)
    const btns   = document.querySelectorAll('.group-btn');
    const panels = document.querySelectorAll('.group-panel');

    function activate(index) {
        btns.forEach((b, i) => {
            b.classList.toggle('bg-white/10', i === index);
            b.classList.toggle('text-white',  i === index);
            b.classList.toggle('text-gray-500', i !== index);
        });
        panels.forEach((p, i) => p.style.display = i === index ? '' : 'none');
        localStorage.setItem('adminRoulettePanel', index);
    }

    btns.forEach((btn, i) => btn.addEventListener('click', () => activate(i)));

    const saved = parseInt(localStorage.getItem('adminRoulettePanel') ?? '0');
    activate(Math.min(saved, btns.length - 1));

    document.querySelectorAll('.sortable-tbody').forEach(el => {
        Sortable.create(el, {
            handle: '.drag-handle',
            animation: 150,
            onEnd() {
                const items = [...el.querySelectorAll('tr[data-id]')].map((row, i) => ({
                    id: parseInt(row.dataset.id),
                    sort_order: i,
                }));
                fetch('{{ route('admin.roulettes.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ items }),
                });
            },
        });
    });
    @endif
});
</script>
@endsection
