$(document).ready(function () {

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function buildSection(label, items, linkBase, isTv) {
        if (!items.length) return '';
        const rows = items.map(function (m) {
            const year = m.release_date ? m.release_date.substring(0, 4) : '';
            const sub  = isTv ? (year ? 'TV Series · ' + year : 'TV Series') : year;
            const poster = m.poster_path
                ? `<img src="https://image.tmdb.org/t/p/w92${escHtml(m.poster_path)}" class="w-8 h-12 object-cover rounded flex-shrink-0" loading="lazy">`
                : `<div class="w-8 h-12 bg-white/5 rounded flex-shrink-0"></div>`;
            return `<a href="/${linkBase}/${m.id}" class="search-result-item flex items-center gap-3 px-3 py-2 hover:bg-white/5 transition-colors">
                ${poster}
                <div class="min-w-0">
                    <div class="text-sm text-white truncate">${escHtml(m.title)}</div>
                    ${sub ? `<div class="text-xs text-gray-500">${sub}</div>` : ''}
                </div>
            </a>`;
        }).join('');
        return `<div class="px-3 pt-2 pb-1">
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-600">${label}</span>
                </div>${rows}`;
    }

    function buildPeopleSection(people) {
        if (!people.length) return '';
        const rows = people.map(function (p) {
            const photo = p.profile_path
                ? `<img src="https://image.tmdb.org/t/p/w92${escHtml(p.profile_path)}" class="w-8 h-8 rounded-full object-cover flex-shrink-0" loading="lazy">`
                : `<div class="w-8 h-8 rounded-full bg-white/5 flex-shrink-0 flex items-center justify-center text-gray-600 text-xs font-bold">${escHtml(p.name.charAt(0).toUpperCase())}</div>`;
            return `<a href="/person/${p.id}" class="search-result-item flex items-center gap-3 px-3 py-2 hover:bg-white/5 transition-colors">
                ${photo}
                <div class="min-w-0">
                    <div class="text-sm text-white truncate">${escHtml(p.name)}</div>
                    ${p.known_for_department ? `<div class="text-xs text-gray-500">${escHtml(p.known_for_department)}</div>` : ''}
                </div>
            </a>`;
        }).join('');
        return `<div class="px-3 pt-2 pb-1">
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-600">People</span>
                </div>${rows}`;
    }

    function initSearch(inputSel, resultsSel) {
        const $input   = $(inputSel);
        const $results = $(resultsSel);
        if (!$input.length) return;

        let timer;

        $input.on('input', function () {
            clearTimeout(timer);
            const q = $(this).val().trim();
            if (!q) { $results.addClass('hidden').empty(); return; }

            timer = setTimeout(function () {
                $.getJSON('/tmdb/search/all', { q: q }).done(function (resp) {
                    const movies = resp.movies || [];
                    const shows  = resp.shows  || [];
                    const people = resp.people || [];
                    const html   = buildSection('Movies', movies, 'movie', false) +
                                   buildSection('TV Shows', shows, 'tv', true) +
                                   buildPeopleSection(people);
                    if (!html) { $results.addClass('hidden').empty(); return; }
                    $results.html(html).removeClass('hidden');
                });
            }, 300);
        });

        $input.on('keydown', function (e) {
            if (e.key === 'Escape') {
                $results.addClass('hidden').empty();
                $input.val('').blur();
            }
        });
    }

    initSearch('#desktop-search-input', '#desktop-search-results');
    initSearch('#mobile-search-input',  '#mobile-search-results');

    // Close desktop results when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#desktop-search-wrap').length) {
            $('#desktop-search-results').addClass('hidden').empty();
        }
    });
});
