@php
    $currentTags  = $roulette?->tags ?? [];
    $currentMedia = $mediaType ?? 'movie';

    $movieGenres = [
        'action'=>'Action','adventure'=>'Adventure','animation'=>'Animation','comedy'=>'Comedy',
        'crime'=>'Crime','documentary'=>'Documentary','drama'=>'Drama','family'=>'Family',
        'fantasy'=>'Fantasy','history'=>'History','horror'=>'Horror','mystery'=>'Mystery',
        'romance'=>'Romance','sci-fi'=>'Sci-Fi','thriller'=>'Thriller','war'=>'War','western'=>'Western',
    ];

    // TV uses different TMDB genre IDs — action+adventure merge, sci-fi+fantasy merge,
    // thriller maps to crime; TV-only: Kids (10762) and Reality (10764)
    $tvGenres = [
        'action'      => 'Action & Adventure',
        'animation'   => 'Animation',
        'comedy'      => 'Comedy',
        'crime'       => 'Crime',
        'documentary' => 'Documentary',
        'drama'       => 'Drama',
        'family'      => 'Family',
        'fantasy'     => 'Sci-Fi & Fantasy',
        'history'     => 'History',
        'horror'      => 'Horror',
        'kids'        => 'Kids',
        'mystery'     => 'Mystery',
        'reality'     => 'Reality',
        'romance'     => 'Romance',
        'war'         => 'War & Politics',
        'western'     => 'Western',
    ];

    $platforms = ['netflix'=>'Netflix','prime'=>'Prime Video','hbo'=>'HBO','disney'=>'Disney+','apple'=>'Apple TV+'];
    $countries = [
        'US'=>'United States','JP'=>'Japan','KR'=>'South Korea','FR'=>'France','ES'=>'Spain',
        'IT'=>'Italy','CN'=>'China','IN'=>'India','DE'=>'Germany','TR'=>'Turkey',
        'PT'=>'Portugal','MX'=>'Mexico','SE'=>'Sweden','DK'=>'Denmark','LT'=>'Lithuania',
    ];
    $languages = [
        'en'=>'English','ja'=>'Japanese','ko'=>'Korean','fr'=>'French','es'=>'Spanish',
        'de'=>'German','it'=>'Italian','zh'=>'Chinese','hi'=>'Hindi','pt'=>'Portuguese',
        'ru'=>'Russian','tr'=>'Turkish','ar'=>'Arabic','sv'=>'Swedish','da'=>'Danish',
        'pl'=>'Polish','lt'=>'Lithuanian',
    ];
    $selectedPlatform      = $currentTags['platform'][0]      ?? null;
    $selectedGenres        = $currentTags['genre']             ?? [];
    $selectedWithoutGenres = $currentTags['without_genre']     ?? [];
    $selectedCountry       = $currentTags['country'][0]        ?? null;
    $selectedLanguage      = $currentTags['language'][0]       ?? null;
    $selectedYearFrom      = $currentTags['year_from']         ?? '';
    $selectedYearTo        = $currentTags['year_to']           ?? '';
@endphp

<div class="space-y-6">

    {{-- Include Genre --}}
    <div>
        <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-3">Include Genre</label>

        {{-- Movie genres --}}
        <div data-genre-panel="movie" class="{{ $currentMedia === 'tv' ? 'hidden' : '' }} grid grid-cols-3 sm:grid-cols-4 gap-2">
            @foreach($movieGenres as $value => $label)
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input type="checkbox" name="tags[genre][]" value="{{ $value }}"
                           class="w-4 h-4 rounded border-white/20 bg-white/5 text-accent focus:ring-accent/50"
                           {{ in_array($value, $selectedGenres) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">{{ $label }}</span>
                </label>
            @endforeach
        </div>

        {{-- TV genres --}}
        <div data-genre-panel="tv" class="{{ $currentMedia === 'tv' ? '' : 'hidden' }} grid grid-cols-3 sm:grid-cols-4 gap-2">
            @foreach($tvGenres as $value => $label)
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

    {{-- Exclude Genre --}}
    <div>
        <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-3">Exclude Genre</label>

        {{-- Movie genres --}}
        <div data-exclude-panel="movie" class="{{ $currentMedia === 'tv' ? 'hidden' : '' }} grid grid-cols-3 sm:grid-cols-4 gap-2">
            @foreach($movieGenres as $value => $label)
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input type="checkbox" name="tags[without_genre][]" value="{{ $value }}"
                           class="w-4 h-4 rounded border-white/20 bg-white/5 text-red-400 focus:ring-red-400/50"
                           {{ in_array($value, $selectedWithoutGenres) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">{{ $label }}</span>
                </label>
            @endforeach
        </div>

        {{-- TV genres --}}
        <div data-exclude-panel="tv" class="{{ $currentMedia === 'tv' ? '' : 'hidden' }} grid grid-cols-3 sm:grid-cols-4 gap-2">
            @foreach($tvGenres as $value => $label)
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input type="checkbox" name="tags[without_genre][]" value="{{ $value }}"
                           class="w-4 h-4 rounded border-white/20 bg-white/5 text-red-400 focus:ring-red-400/50"
                           {{ in_array($value, $selectedWithoutGenres) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

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

        {{-- Country --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Country</label>
            <select name="tags[country]" class="input-dark w-full">
                <option value="">None</option>
                @foreach($countries as $value => $label)
                    <option value="{{ $value }}" {{ $selectedCountry === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

    </div>

    {{-- Year Range --}}
    <div>
        <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Year Range</label>
        <div class="flex flex-wrap gap-1.5 mb-3">
            <button type="button" class="decade-chip" data-from="" data-to="">Any</button>
            <button type="button" class="decade-chip" data-from="{{ date('Y') - 2 }}" data-to="">Recent</button>
            <button type="button" class="decade-chip" data-from="2020" data-to="">2020s</button>
            <button type="button" class="decade-chip" data-from="2010" data-to="2019">2010s</button>
            <button type="button" class="decade-chip" data-from="2000" data-to="2009">2000s</button>
            <button type="button" class="decade-chip" data-from="1990" data-to="1999">90s</button>
            <button type="button" class="decade-chip" data-from="1980" data-to="1989">80s</button>
            <button type="button" class="decade-chip" data-from="1970" data-to="1979">70s</button>
            <button type="button" class="decade-chip" data-from="" data-to="1969">Classic</button>
        </div>
        <div class="flex items-center gap-2">
            <input type="number" name="tags[year_from]" id="tag-year-from"
                   value="{{ $selectedYearFrom }}"
                   class="input-dark w-24" placeholder="1900" min="1900" max="{{ date('Y') }}">
            <span class="text-gray-500 text-sm">–</span>
            <input type="number" name="tags[year_to]" id="tag-year-to"
                   value="{{ $selectedYearTo }}"
                   class="input-dark w-24" placeholder="{{ date('Y') }}" min="1900" max="{{ date('Y') }}">
        </div>
    </div>

    @error('tags') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<style>
.decade-chip {
    padding: 3px 10px;
    font-size: 0.75rem;
    border-radius: 9999px;
    background: rgba(255,255,255,0.05);
    color: #9ca3af;
    border: 1px solid rgba(255,255,255,0.08);
    transition: background 0.15s, color 0.15s;
    cursor: pointer;
}
.decade-chip:hover { background: rgba(255,255,255,0.1); color: #fff; }
.decade-chip.active { background: rgba(192,57,58,0.25); color: #fff; border-color: rgba(192,57,58,0.5); }
</style>

<script>
(function () {
    // ── Decade chips ──────────────────────────────────────────────────
    const fromInput = document.getElementById('tag-year-from');
    const toInput   = document.getElementById('tag-year-to');

    function updateActiveChip() {
        const from = fromInput.value;
        const to   = toInput.value;
        document.querySelectorAll('.decade-chip').forEach(chip => {
            chip.classList.toggle('active',
                chip.dataset.from === from && chip.dataset.to === to);
        });
    }

    document.querySelectorAll('.decade-chip').forEach(chip => {
        chip.addEventListener('click', function () {
            fromInput.value = this.dataset.from;
            toInput.value   = this.dataset.to;
            updateActiveChip();
        });
    });

    fromInput.addEventListener('input', updateActiveChip);
    toInput.addEventListener('input', updateActiveChip);
    updateActiveChip(); // mark active chip on page load if values already set

    // ── Genre panel toggle ────────────────────────────────────────────
    function switchGenrePanel(type) {
        document.querySelectorAll('[data-genre-panel], [data-exclude-panel]').forEach(el => {
            const panel = el.dataset.genrePanel || el.dataset.excludePanel;
            el.classList.toggle('hidden', panel !== type);
            // uncheck all inputs in the hidden panel so they don't submit
            if (panel !== type) {
                el.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false);
            }
        });
    }

    document.querySelectorAll('input[name="media_type"]').forEach(radio => {
        radio.addEventListener('change', function () {
            switchGenrePanel(this.value);
        });
    });
})();
</script>
