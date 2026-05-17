@if ($errors->any())
    @foreach ($errors->all() as $error)
        <div class="alert-msg flex items-center justify-between gap-3 px-4 py-3 mt-2 bg-red-900/30 border border-red-500/30 text-red-300 text-sm rounded-lg" role="alert">
            <span>{{ $error }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-200 flex-shrink-0 text-base leading-none">✕</button>
        </div>
    @endforeach
@endif
