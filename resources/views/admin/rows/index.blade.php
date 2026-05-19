@extends('layouts.app')
@section('page_title', 'Admin — Row Order')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">

    <div class="mb-8 flex items-center gap-3">
        <h1 class="text-2xl font-bold text-white">Admin</h1>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 border border-red-500/20">Admin</span>
    </div>

    @include('admin._nav')

    <h2 class="text-lg font-semibold text-white mb-4">Row Order</h2>

    <p class="text-sm text-gray-500 mb-6">Drag to reorder how rows appear on the <a href="/roulettes" class="text-accent hover:underline">/roulettes</a> page. Rows with 0 roulettes are hidden on the public page.</p>

    <div id="row-list" class="space-y-2 mb-4">
        @foreach($rowOrder as $row)
            <div class="row-item flex items-center gap-3 bg-white/3 border border-white/5 rounded-xl px-4 py-3"
                 data-row="{{ $row }}">
                <svg class="w-4 h-4 text-gray-600 flex-shrink-0 cursor-grab" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 6a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm8-16a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4z"/>
                </svg>
                <span class="text-white font-medium flex-1">{{ $row }}</span>
                <span class="text-xs text-gray-600 w-20 text-right">{{ $counts[$row] ?? 0 }} roulettes</span>
                <button type="button" class="delete-row ml-2 text-gray-600 hover:text-red-400 transition-colors" title="Remove row">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endforeach
    </div>

    {{-- Add new row --}}
    <div class="flex items-center gap-2 mt-6">
        <input type="text" id="new-row-name" placeholder="New row name…"
               class="input-dark text-sm flex-1 max-w-xs">
        <button type="button" id="add-row-btn" class="btn-accent text-sm px-4 py-2">Add Row</button>
    </div>

    <p class="text-xs text-gray-600 mt-4" id="save-status">Drag to reorder — saves automatically.</p>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('row-list');
    const status = document.getElementById('save-status');
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const REORDER_URL = '{{ route('admin.rows.reorder') }}';

    function save() {
        const rows = [...list.querySelectorAll('.row-item')].map(el => el.dataset.row);
        status.textContent = 'Saving…';
        fetch(REORDER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ rows }),
        }).then(() => { status.textContent = 'Saved ✓'; });
    }

    function makeRowEl(name) {
        const div = document.createElement('div');
        div.className = 'row-item flex items-center gap-3 bg-white/3 border border-white/5 rounded-xl px-4 py-3';
        div.dataset.row = name;
        div.innerHTML = `
            <svg class="w-4 h-4 text-gray-600 flex-shrink-0 cursor-grab" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 6a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm8-16a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4z"/>
            </svg>
            <span class="text-white font-medium flex-1">${name}</span>
            <span class="text-xs text-gray-600 w-20 text-right">0 roulettes</span>
            <button type="button" class="delete-row ml-2 text-gray-600 hover:text-red-400 transition-colors" title="Remove row">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>`;
        return div;
    }

    // Drag to reorder
    Sortable.create(list, {
        animation: 150,
        handle: '.cursor-grab',
        onEnd: save,
    });

    // Delete row
    list.addEventListener('click', e => {
        const btn = e.target.closest('.delete-row');
        if (!btn) return;
        const item = btn.closest('.row-item');
        const name = item.dataset.row;
        if (!confirm(`Remove row "${name}"? Roulettes in this row won't disappear — they'll just be ungrouped.`)) return;
        item.remove();
        save();
    });

    // Add new row
    document.getElementById('add-row-btn').addEventListener('click', () => {
        const input = document.getElementById('new-row-name');
        const name = input.value.trim();
        if (!name) return;

        // Prevent duplicates
        const exists = [...list.querySelectorAll('.row-item')].some(el => el.dataset.row === name);
        if (exists) { status.textContent = 'Row already exists.'; return; }

        list.appendChild(makeRowEl(name));
        input.value = '';
        save();
    });

    // Also add on Enter key
    document.getElementById('new-row-name').addEventListener('keydown', e => {
        if (e.key === 'Enter') document.getElementById('add-row-btn').click();
    });
});
</script>
@endsection
