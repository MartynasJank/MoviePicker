@php
    $currentTags = $roulette?->tags ?? [];
    $genres = [
        'action'=>'Action','adventure'=>'Adventure','animation'=>'Animation','comedy'=>'Comedy',
        'crime'=>'Crime','documentary'=>'Documentary','drama'=>'Drama','family'=>'Family',
        'fantasy'=>'Fantasy','history'=>'History','horror'=>'Horror','mystery'=>'Mystery',
        'romance'=>'Romance','sci-fi'=>'Sci-Fi','thriller'=>'Thriller','war'=>'War','western'=>'Western',
    ];
    $platforms = ['netflix'=>'Netflix','prime'=>'Prime Video','hbo'=>'HBO','disney'=>'Disney+','apple'=>'Apple TV+'];
    $eras = [
        'recent'=>'New Releases','2020s'=>'The 2020s','2010s'=>'The 2010s','2000s'=>'The 2000s',
        '1990s'=>'The Nineties','1980s'=>'The Eighties','1970s'=>'The Seventies',
        '1960s'=>'The Sixties','1950s'=>'The Fifties','pre-1950'=>'Classic Hollywood',
    ];
    $languages = [
        'ja'=>'Japanese','ko'=>'Korean','fr'=>'French','es'=>'Spanish','it'=>'Italian',
        'zh'=>'Chinese','hi'=>'Hindi','de'=>'German','tr'=>'Turkish','pt'=>'Portuguese','lt'=>'Lithuanian',
    ];
    $selectedPlatform  = $currentTags['platform'][0]  ?? null;
    $selectedGenres    = $currentTags['genre']         ?? [];
    $selectedEra       = $currentTags['era'][0]        ?? null;
    $selectedLanguage  = $currentTags['language'][0]   ?? null;
@endphp

<div class="space-y-6">

    {{-- Genre --}}
    <div>
        <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-3">Genre</label>
        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
            @foreach($genres as $value => $label)
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input type="checkbox" name="tags[genre][]" value="{{ $value }}"
                           class="w-4 h-4 rounded border-white/20 bg-white/5 text-accent focus:ring-accent/50"
                           {{ in_array($value, $selectedGenres) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('tags.genre') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        {{-- Platform --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Platform</label>
            <select name="tags[platform]" class="input-dark w-full">
                <option value="">None</option>
                @foreach($platforms as $value => $label)
                    <option value="{{ $value }}" {{ $selectedPlatform === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Era --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Era</label>
            <select name="tags[era]" class="input-dark w-full">
                <option value="">None</option>
                @foreach($eras as $value => $label)
                    <option value="{{ $value }}" {{ $selectedEra === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Language --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Language</label>
            <select name="tags[language]" class="input-dark w-full">
                <option value="">None</option>
                @foreach($languages as $value => $label)
                    <option value="{{ $value }}" {{ $selectedLanguage === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

    </div>

    @error('tags') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
</div>
