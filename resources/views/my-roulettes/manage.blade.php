@extends('layouts.app')
@section('page_title', 'Manage My Roulettes')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white mb-1">My Roulettes</h1>
            <a href="{{ route('my-roulettes.index') }}" class="text-sm text-gray-500 hover:text-white transition-colors">← Back to my collection</a>
        </div>
        <a href="{{ route('my-roulettes.create') }}" class="btn-accent text-sm px-4 py-2">+ New Roulette</a>
    </div>

    @if(session('success'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-500/10 border border-green-500/20 text-green-400 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if($ordered->flatten()->isEmpty())
        <div class="text-center py-20">
            <p class="text-gray-500 mb-4">You haven't created any roulettes yet.</p>
            <a href="{{ route('my-roulettes.create') }}" class="btn-accent px-6 py-2.5 text-sm">Create your first roulette</a>
        </div>
    @else

    <div class="flex flex-col md:flex-row gap-6">

        {{-- Group nav: scrollable tabs on mobile, vertical sidebar on desktop --}}
        <div class="md:w-48 md:flex-shrink-0">
            <nav id="group-nav" class="flex overflow-x-auto gap-1 pb-1 md:flex-col md:overflow-visible md:pb-0 md:space-y-0.5 md:sticky md:top-20">
                @foreach($ordered as $groupName => $roulettes)
                    <button type="button"
                            class="group-btn flex-shrink-0 flex items-center justify-between gap-2 px-3 py-2 rounded-lg text-sm transition-colors text-gray-500 hover:text-white hover:bg-white/5 text-left whitespace-nowrap md:w-full md:whitespace-normal"
                            data-panel="{{ $loop->index }}">
                        <span class="truncate">{{ $groupName }}</span>
                        <span class="text-xs text-gray-600 flex-shrink-0">{{ $roulettes->count() }}</span>
                    </button>
                @endforeach
            </nav>

            {{-- Row management --}}
            <div class="mt-4 pt-4 border-t border-white/5 md:mt-6 md:pt-6">
                <p class="text-xs text-gray-600 uppercase tracking-widest mb-2">Rows</p>
                <div id="row-list" class="space-y-1 mb-3">
                    @foreach($rowOrder as $row)
                        <div class="row-item flex items-center gap-1.5 text-xs text-gray-500 group cursor-grab" data-row="{{ $row }}">
                            <svg class="w-3 h-3 text-gray-700 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 6a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm8-16a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4z"/>
                            </svg>
                            <span class="flex-1 truncate">{{ $row }}</span>
                            <button type="button" class="delete-row opacity-0 group-hover:opacity-100 text-gray-600 hover:text-red-400 transition-all">×</button>
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-1">
                    <input type="text" id="new-row-name" placeholder="New row…"
                           class="input-dark text-xs flex-1 py-1.5 px-2">
                    <button type="button" id="add-row-btn" class="btn-secondary text-xs px-2 py-1.5">+</button>
                </div>
                <p class="text-xs text-gray-700 mt-1" id="row-status"></p>
            </div>
        </div>

        {{-- Panels --}}
        <div class="flex-1 min-w-0">
            @foreach($ordered as $groupName => $roulettes)
                <div class="group-panel" data-panel="{{ $loop->index }}" style="display:none">
                    <h3 class="text-sm font-semibold text-white mb-3">{{ $groupName }}
                        <span class="text-gray-600 font-normal ml-1">{{ $roulettes->count() }}</span>
                    </h3>

                    @if($roulettes->isEmpty())
                        <p class="text-sm text-gray-600">No roulettes in this row yet. Create one and assign it here.</p>
                    @else
                    <div class="bg-white/3 rounded-xl overflow-hidden border border-white/5 divide-y divide-white/5">
                        @foreach($roulettes as $roulette)
                            @php $poster = ($roulette->poster_paths ?? [])[0] ?? null; @endphp
                            <div class="flex items-center gap-3 px-3 py-3 hover:bg-white/2 transition-colors">

                                {{-- Poster thumbnail --}}
                                <div class="w-9 h-[52px] flex-shrink-0">
                                    @if($poster)
                                        <img src="https://image.tmdb.org/t/p/w92{{ $poster }}"
                                             class="w-full h-full object-cover rounded">
                                    @else
                                        <div class="w-full h-full bg-white/5 rounded"></div>
                                    @endif
                                </div>

                                {{-- Name + tags --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-white font-medium text-sm">{{ $roulette->name }}</span>
                                        @if(($roulette->media_type ?? 'movie') === 'tv')
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-accent/15 text-accent border border-accent/20">TV</span>
                                        @endif
                                    </div>
                                    <div class="hidden sm:flex flex-wrap gap-1 mt-1">
                                        @foreach(collect($roulette->tags)->except(['without_genre'])->flatten() as $tag)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-white/5 text-gray-400 border border-white/10">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    {{-- Public toggle --}}
                                    <form method="POST" action="{{ route('my-roulettes.toggle', $roulette) }}" class="flex items-center">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="w-8 h-4 rounded-full transition-colors {{ $roulette->is_public ? 'bg-accent' : 'bg-white/10' }} relative inline-block mr-2"
                                                title="{{ $roulette->is_public ? 'Public — click to make private' : 'Private — click to make public' }}">
                                            <span class="absolute top-0.5 {{ $roulette->is_public ? 'right-0.5' : 'left-0.5' }} w-3 h-3 rounded-full bg-white shadow transition-all"></span>
                                        </button>
                                    </form>

                                    {{-- Roll --}}
                                    <a href="/roulettes/{{ $roulette->slug }}" target="_blank"
                                       class="p-2 text-gray-500 hover:text-white transition-colors" title="Roll">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ route('my-roulettes.edit', $roulette) }}"
                                       class="p-2 text-gray-400 hover:text-accent transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('my-roulettes.destroy', $roulette) }}"
                                          onsubmit="return confirm('Delete {{ addslashes($roulette->name) }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-600 hover:text-red-400 transition-colors" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>

                            </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            @endforeach
        </div>

    </div>
    @endif

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const btns   = document.querySelectorAll('.group-btn');
    const panels = document.querySelectorAll('.group-panel');

    function activate(index) {
        btns.forEach((b, i) => {
            b.classList.toggle('bg-white/10', i === index);
            b.classList.toggle('text-white',  i === index);
            b.classList.toggle('text-gray-500', i !== index);
        });
        panels.forEach((p, i) => p.style.display = i === index ? '' : 'none');
        localStorage.setItem('myRoulettePanel', index);
    }

    btns.forEach((btn, i) => btn.addEventListener('click', () => activate(i)));
    if (btns.length) {
        const saved = parseInt(localStorage.getItem('myRoulettePanel') ?? '0');
        activate(Math.min(saved, btns.length - 1));
    }

    // Row management
    const rowList  = document.getElementById('row-list');
    const rowStatus = document.getElementById('row-status');
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
    const REORDER_URL = '{{ route('my-roulettes.rows.reorder') }}';

    function saveRows() {
        const rows = rowList ? [...rowList.querySelectorAll('.row-item')].map(el => el.dataset.row) : [];
        return fetch(REORDER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ rows }),
        }).then(() => { if (rowStatus) rowStatus.textContent = 'Saved ✓'; });
    }

    if (rowList) {
        Sortable.create(rowList, {
            animation: 150,
            onEnd: saveRows,
        });

        rowList.addEventListener('click', e => {
            const btn = e.target.closest('.delete-row');
            if (!btn) return;
            const item = btn.closest('.row-item');
            if (!confirm(`Remove row "${item.dataset.row}"?`)) return;
            item.remove();
            saveRows().then(() => location.reload());
        });
    }

    document.getElementById('add-row-btn')?.addEventListener('click', () => {
        const input = document.getElementById('new-row-name');
        const name  = input.value.trim();
        if (!name) return;

        const exists = rowList && [...rowList.querySelectorAll('.row-item')].some(el => el.dataset.row === name);
        if (exists) { if (rowStatus) rowStatus.textContent = 'Already exists.'; return; }

        const div = document.createElement('div');
        div.className = 'row-item flex items-center gap-2 text-xs text-gray-500 group';
        div.dataset.row = name;
        div.innerHTML = `<span class="flex-1 truncate">${name}</span><button type="button" class="delete-row opacity-0 group-hover:opacity-100 text-gray-600 hover:text-red-400 transition-all">×</button>`;
        rowList?.appendChild(div);
        input.value = '';
        saveRows().then(() => location.reload());
    });

    document.getElementById('new-row-name')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') document.getElementById('add-row-btn').click();
    });
});
</script>
@endsection
