@extends('layouts.app')
@section('page_title', ($roulette ? 'Edit' : 'New') . ' Roulette')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">

    <div class="mb-8">
        <a href="{{ route('my-roulettes.manage') }}" class="text-gray-500 hover:text-white transition-colors text-sm">← Manage</a>
        <h1 class="text-2xl font-bold text-white mt-2">{{ $roulette ? 'Edit: ' . $roulette->name : 'New Roulette' }}</h1>
    </div>

    @if($errors->any())
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-8">

    {{-- Poster sidebar (edit only) --}}
    @if($roulette)
    @php $poster = ($roulette->poster_paths ?? [])[0] ?? null; @endphp
    <div class="lg:w-40 flex-shrink-0">
        <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Poster</label>
        <div class="relative group w-full" style="aspect-ratio:2/3">
            @if($poster)
                <img id="edit-poster-img"
                     src="https://image.tmdb.org/t/p/w342{{ $poster }}"
                     alt="{{ $roulette->name }}"
                     class="w-full h-full object-cover rounded-xl">
            @else
                <div id="edit-poster-placeholder"
                     class="w-full h-full bg-white/5 rounded-xl flex items-center justify-center text-gray-700">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5M4.5 3h15A1.5 1.5 0 0121 4.5v15A1.5 1.5 0 0119.5 21h-15A1.5 1.5 0 013 19.5v-15A1.5 1.5 0 014.5 3z"/>
                    </svg>
                </div>
            @endif
        </div>
        <button type="button" id="roll-poster-btn"
                class="mt-2 w-full flex items-center justify-center gap-1.5 text-xs text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 rounded-lg py-2 transition-colors"
                data-id="{{ $roulette->id }}">
            <svg id="roll-poster-icon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Roll poster
        </button>
    </div>
    @endif

    {{-- Form --}}
    <div class="flex-1 min-w-0">

    <form method="POST"
          action="{{ $roulette ? route('my-roulettes.update', $roulette) : route('my-roulettes.store') }}"
          class="space-y-6">
        @csrf
        @if($roulette) @method('PUT') @endif

        {{-- Type --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Type</label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="media_type" value="movie" class="text-accent"
                           {{ old('media_type', $roulette?->media_type ?? 'movie') === 'movie' ? 'checked' : '' }}>
                    <span class="text-sm text-gray-300">Movie</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="media_type" value="tv" class="text-accent"
                           {{ old('media_type', $roulette?->media_type) === 'tv' ? 'checked' : '' }}>
                    <span class="text-sm text-gray-300">TV Show</span>
                </label>
            </div>
        </div>

        {{-- Name --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Name</label>
            <input type="text" name="name"
                   value="{{ old('name', $roulette?->name) }}"
                   class="input-dark w-full" required maxlength="80">
            @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Description <span class="text-gray-600 normal-case font-normal">(optional)</span></label>
            <textarea name="description" rows="2" class="input-dark w-full" maxlength="500">{{ old('description', $roulette?->description) }}</textarea>
        </div>

        {{-- Row --}}
        @if(count($rowOrder))
        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Row</label>
            <select name="row" class="input-dark w-full max-w-xs">
                <option value="">— None —</option>
                @foreach($rowOrder as $rowName)
                    <option value="{{ $rowName }}" {{ old('row', $roulette?->row) === $rowName ? 'selected' : '' }}>{{ $rowName }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Tags --}}
        <div class="bg-white/3 border border-white/5 rounded-xl p-5">
            <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-500 mb-4">Tags</h3>
            @include('roulettes._tag_form', ['roulette' => $roulette])
        </div>

        {{-- Public toggle --}}
        <div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_public" value="1"
                       class="w-4 h-4 rounded border-white/20 bg-white/5 text-accent"
                       {{ old('is_public', $roulette?->is_public ?? false) ? 'checked' : '' }}>
                <span class="text-sm text-gray-300">Public — anyone with the link can roll this</span>
            </label>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn-accent px-6 py-2.5 text-sm">
                {{ $roulette ? 'Save Changes' : 'Create Roulette' }}
            </button>
            <a href="{{ route('my-roulettes.manage') }}" class="btn-secondary px-4 py-2.5 text-sm">Cancel</a>
        </div>

    </form>

    </div>{{-- /form col --}}
    </div>{{-- /outer flex --}}
</div>
@endsection

@section('scripts')
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
<script>
@if($roulette)
document.addEventListener('DOMContentLoaded', () => {
    const rollBtn  = document.getElementById('roll-poster-btn');
    const rollIcon = document.getElementById('roll-poster-icon');

    if (rollBtn) {
        rollBtn.addEventListener('click', () => {
            rollBtn.disabled = true;
            rollIcon.style.animation = 'spin 0.6s linear infinite';

            fetch(`/my-roulettes/manage/{{ $roulette->id }}/refresh-poster`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            })
            .then(r => r.json())
            .then(data => {
                if (!data.poster_path) return;
                const url = `https://image.tmdb.org/t/p/w342${data.poster_path}`;
                let img = document.getElementById('edit-poster-img');
                const placeholder = document.getElementById('edit-poster-placeholder');
                if (placeholder) {
                    img = document.createElement('img');
                    img.id = 'edit-poster-img';
                    img.className = 'w-full h-full object-cover rounded-xl';
                    img.alt = '{{ addslashes($roulette->name) }}';
                    placeholder.replaceWith(img);
                }
                img.src = url;
            })
            .finally(() => {
                rollBtn.disabled = false;
                rollIcon.style.animation = '';
            });
        });
    }
});
@endif
</script>
@endsection
