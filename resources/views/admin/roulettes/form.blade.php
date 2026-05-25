@extends('layouts.app')
@section('page_title', ($roulette ? 'Edit' : 'New') . ' Roulette — Admin')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">

    <div class="mb-8 flex items-center gap-3">
        <h1 class="text-2xl font-bold text-white">Admin</h1>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 border border-red-500/20">Admin</span>
    </div>

    @include('admin._nav')

    <h2 class="text-lg font-semibold text-white mb-6">{{ $roulette ? 'Edit: ' . $roulette->name : 'New Roulette' }}</h2>

    <div class="flex flex-col sm:flex-row gap-4 lg:gap-8">

    {{-- Poster section (edit only) --}}
    @if($roulette)
    @php
        $allPosters = $roulette->poster_paths ?? [];
        $poster     = $allPosters[0] ?? null;
    @endphp
    <div class="sm:w-32 lg:w-44 flex-shrink-0">

        {{-- Label + roll button --}}
        <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-semibold uppercase tracking-widest text-gray-500">Poster</label>
            <button type="button" id="roll-poster-btn"
                    class="flex items-center gap-1 text-xs text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 rounded-lg px-2 py-1 transition-colors">
                <svg id="roll-poster-icon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                New batch
            </button>
        </div>

        {{-- Main poster (desktop only) --}}
        <div class="hidden sm:block w-full rounded-xl overflow-hidden mb-2" style="aspect-ratio:2/3">
            @if($poster)
                <img id="edit-poster-img"
                     src="https://image.tmdb.org/t/p/w342{{ $poster }}"
                     alt="{{ $roulette->name }}"
                     class="w-full h-full object-cover">
            @else
                <div id="edit-poster-placeholder"
                     class="w-full h-full bg-white/5 flex items-center justify-center text-gray-700">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5M4.5 3h15A1.5 1.5 0 0121 4.5v15A1.5 1.5 0 0119.5 21h-15A1.5 1.5 0 013 19.5v-15A1.5 1.5 0 014.5 3z"/>
                    </svg>
                </div>
            @endif
        </div>

        {{-- Thumbnail grid (desktop 4-col) / scroll strip (mobile) --}}
        @if(count($allPosters) > 1)
        <div id="poster-grid" class="grid grid-cols-4 gap-1">
            @foreach($allPosters as $i => $path)
                <button type="button"
                        class="poster-thumb relative rounded overflow-hidden {{ $i === 0 ? 'ring-2 ring-accent' : 'opacity-50 hover:opacity-100' }} transition-opacity"
                        style="aspect-ratio:2/3"
                        data-path="{{ $path }}">
                    <img src="https://image.tmdb.org/t/p/w185{{ $path }}" class="w-full h-full object-cover">
                </button>
            @endforeach
        </div>
        @else
            <div id="poster-grid" class="grid grid-cols-4 gap-1"></div>
        @endif
    </div>
    @endif

    {{-- Form --}}
    <div class="flex-1 min-w-0">

    <form method="POST"
          action="{{ $roulette ? route('admin.roulettes.update', $roulette) : route('admin.roulettes.store') }}"
          class="space-y-6">
        @csrf
        @if($roulette) @method('PUT') @endif

        {{-- Type --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Type</label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="media_type" value="movie"
                           class="text-accent"
                           {{ old('media_type', $roulette?->media_type ?? 'movie') === 'movie' ? 'checked' : '' }}>
                    <span class="text-sm text-gray-300">Movie</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="media_type" value="tv"
                           class="text-accent"
                           {{ old('media_type', $roulette?->media_type) === 'tv' ? 'checked' : '' }}>
                    <span class="text-sm text-gray-300">TV Show</span>
                </label>
            </div>
        </div>

        {{-- Name + Slug --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Name</label>
                <input type="text" name="name" id="name-input"
                       value="{{ old('name', $roulette?->name) }}"
                       class="input-dark w-full" required maxlength="80">
                @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Slug</label>
                <input type="text" name="slug" id="slug-input"
                       value="{{ old('slug', $roulette?->slug) }}"
                       class="input-dark w-full font-mono text-sm" required maxlength="100">
                @error('slug') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Description</label>
            <textarea name="description" rows="2" class="input-dark w-full" maxlength="500">{{ old('description', $roulette?->description) }}</textarea>
        </div>

        {{-- Row assignment --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Row</label>
            <select name="row" class="input-dark w-full max-w-xs">
                <option value="">— Auto (derived from tags) —</option>
                @foreach($rowOrder as $rowName)
                    <option value="{{ $rowName }}" {{ old('row', $roulette?->row) === $rowName ? 'selected' : '' }}>
                        {{ $rowName }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-600 mt-1">Override which row this roulette appears in. Leave blank to auto-detect from tags.</p>
        </div>

        {{-- Tags --}}
        <div class="bg-white/3 border border-white/5 rounded-xl p-5">
            <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-500 mb-4">Tags</h3>
            @include('roulettes._tag_form', ['roulette' => $roulette])
        </div>

        {{-- Options --}}
        <div class="flex items-center gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_public" value="1"
                       class="w-4 h-4 rounded border-white/20 bg-white/5 text-accent"
                       {{ old('is_public', $roulette?->is_public ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-300">Public</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_system" value="1"
                       class="w-4 h-4 rounded border-white/20 bg-white/5 text-accent"
                       {{ old('is_system', $roulette?->is_system) ? 'checked' : '' }}>
                <span class="text-sm text-gray-300">System roulette</span>
            </label>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn-accent px-6 py-2.5 text-sm">
                {{ $roulette ? 'Save Changes' : 'Create Roulette' }}
            </button>
            <a href="{{ route('admin.roulettes.index') }}" class="btn-secondary px-4 py-2.5 text-sm">Cancel</a>
        </div>

    </form>

    </div>{{-- /form flex col --}}
    </div>{{-- /outer flex row --}}
</div>
@endsection

@section('scripts')
<style>
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 639px) {
    #poster-grid { display: flex; overflow-x: auto; gap: 0.5rem; padding-bottom: 0.375rem; }
    #poster-grid .poster-thumb { flex-shrink: 0; width: 8rem; }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    @if(!$roulette)
    const nameInput = document.getElementById('name-input');
    const slugInput = document.getElementById('slug-input');
    let slugEdited = false;

    slugInput.addEventListener('input', () => { slugEdited = true; });
    nameInput.addEventListener('input', () => {
        if (slugEdited) return;
        slugInput.value = nameInput.value
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    });
    @endif

    @if($roulette)
    const CSRF      = document.querySelector('meta[name="csrf-token"]').content;
    const POSTER_URL = `/admin/roulettes/{{ $roulette->id }}/refresh-poster`;
    const rollBtn   = document.getElementById('roll-poster-btn');
    const rollIcon  = document.getElementById('roll-poster-icon');
    const grid      = document.getElementById('poster-grid');

    function setMainPoster(path) {
        const url = `https://image.tmdb.org/t/p/w342${path}`;
        let img = document.getElementById('edit-poster-img');
        const placeholder = document.getElementById('edit-poster-placeholder');
        if (placeholder) {
            img = document.createElement('img');
            img.id = 'edit-poster-img';
            img.className = 'w-full h-full object-cover';
            img.alt = '{{ addslashes($roulette->name) }}';
            placeholder.replaceWith(img);
        }
        img.src = url;
    }

    function setActiveThumb(activePath) {
        grid.querySelectorAll('.poster-thumb').forEach(btn => {
            const isActive = btn.dataset.path === activePath;
            btn.classList.toggle('ring-2',        isActive);
            btn.classList.toggle('ring-accent',   isActive);
            btn.classList.toggle('opacity-50',    !isActive);
            btn.classList.toggle('hover:opacity-100', !isActive);
        });
    }

    function buildGrid(paths, activePath) {
        grid.innerHTML = '';
        paths.forEach(path => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'poster-thumb relative rounded overflow-hidden transition-opacity ' +
                (path === activePath ? 'ring-2 ring-accent' : 'opacity-50 hover:opacity-100');
            btn.style.aspectRatio = '2/3';
            btn.dataset.path = path;
            btn.innerHTML = `<img src="https://image.tmdb.org/t/p/w185${path}" class="w-full h-full object-cover">`;
            grid.appendChild(btn);
        });
    }

    // Thumbnail click — select poster, keep grid positions fixed
    grid.addEventListener('click', e => {
        const btn = e.target.closest('.poster-thumb');
        if (!btn) return;
        const path = btn.dataset.path;

        fetch(POSTER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ path }),
        })
        .then(r => r.json())
        .then(data => {
            if (!data.poster_path) return;
            setMainPoster(data.poster_path);
            setActiveThumb(data.poster_path);
        });
    });

    // Roll — fetch fresh batch, rebuild grid with new posters
    if (rollBtn) {
        rollBtn.addEventListener('click', () => {
            rollBtn.disabled = true;
            rollIcon.style.animation = 'spin 0.6s linear infinite';

            fetch(POSTER_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF },
            })
            .then(r => r.json())
            .then(data => {
                if (!data.poster_path) return;
                setMainPoster(data.poster_path);
                buildGrid(data.all_paths, data.poster_path);
            })
            .finally(() => {
                rollBtn.disabled = false;
                rollIcon.style.animation = '';
            });
        });
    }
    @endif
});
</script>
@endsection
