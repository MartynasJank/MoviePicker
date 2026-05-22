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

    <div class="flex gap-6">

        {{-- Sidebar --}}
        <div class="w-48 flex-shrink-0">
            <nav id="group-nav" class="space-y-0.5 sticky top-20">
                @foreach($ordered as $groupName => $roulettes)
                    <button type="button"
                            class="group-btn w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors text-gray-500 hover:text-white hover:bg-white/5 text-left"
                            data-panel="{{ $loop->index }}">
                        <span class="truncate">{{ $groupName }}</span>
                        <span class="ml-2 text-xs text-gray-600 flex-shrink-0">{{ $roulettes->count() }}</span>
                    </button>
                @endforeach
            </nav>

            {{-- Row management --}}
            <div class="mt-6 pt-6 border-t border-white/5">
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
                    <div class="bg-white/3 rounded-xl overflow-hidden border border-white/5">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/5 text-left">
                                    <th class="py-2.5 px-4 text-xs font-medium text-gray-500">Name</th>
                                    <th class="py-2.5 px-4 text-xs font-medium text-gray-500 hidden sm:table-cell">Tags</th>
                                    <th class="py-2.5 px-4 text-xs font-medium text-gray-500 text-center">Public</th>
                                    <th class="py-2.5 px-4 text-xs font-medium text-gray-500 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roulettes as $roulette)
                                    <tr class="border-b border-white/5 last:border-0 hover:bg-white/2 transition-colors">
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2">
                                                <span class="text-white font-medium">{{ $roulette->name }}</span>
                                                @if(($roulette->media_type ?? 'movie') === 'tv')
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-blue-500/15 text-blue-400 border border-blue-500/20">TV</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 hidden sm:table-cell">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach(collect($roulette->tags)->flatten() as $tag)
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-white/5 text-gray-400 border border-white/10">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <form method="POST" action="{{ route('my-roulettes.toggle', $roulette) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                        class="w-8 h-4 rounded-full transition-colors {{ $roulette->is_public ? 'bg-accent' : 'bg-white/10' }} relative inline-block"
                                                        title="{{ $roulette->is_public ? 'Public — click to make private' : 'Private — click to make public' }}">
                                                    <span class="absolute top-0.5 {{ $roulette->is_public ? 'right-0.5' : 'left-0.5' }} w-3 h-3 rounded-full bg-white shadow transition-all"></span>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="/roulettes/{{ $roulette->slug }}" target="_blank"
                                                   class="text-xs text-gray-500 hover:text-white transition-colors">Roll</a>
                                                <a href="{{ route('my-roulettes.edit', $roulette) }}"
                                                   class="text-xs text-gray-400 hover:text-accent transition-colors">Edit</a>
                                                <form method="POST" action="{{ route('my-roulettes.destroy', $roulette) }}"
                                                      onsubmit="return confirm('Delete {{ addslashes($roulette->name) }}?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-xs text-gray-600 hover:text-red-400 transition-colors">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
