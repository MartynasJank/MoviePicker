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

    <form method="POST"
          action="{{ $roulette ? route('my-roulettes.update', $roulette) : route('my-roulettes.store') }}"
          class="space-y-6">
        @csrf
        @if($roulette) @method('PUT') @endif

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
</div>
@endsection
