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

    <form method="POST"
          action="{{ $roulette ? route('admin.roulettes.update', $roulette) : route('admin.roulettes.store') }}"
          class="space-y-6">
        @csrf
        @if($roulette) @method('PUT') @endif

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
</div>
@endsection

@section('scripts')
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
});
</script>
@endsection
