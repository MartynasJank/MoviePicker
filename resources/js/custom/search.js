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
            const year    = m.release_date ? m.release_date.substring(0, 4) : '';
            const endYear = m.last_air_date ? m.last_air_date.substring(0, 4) : '';
            const active  = ['Returning Series', 'In Production', 'Planned', 'Pilot'].includes(m.tv_status);
            const sub     = isTv
                ? (year
                    ? (active
                        ? year + ' – present'
                        : endYear && endYear !== year ? year + ' – ' + endYear : 'Since ' + year)
                    : 'TV Series')
                : year;
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
                $.when(
                    $.getJSON('/tmdb/search/movies', { q: q }),
                    $.getJSON('/tmdb/search/tv',     { q: q })
                ).done(function (movieResp, tvResp) {
                    const movies = (movieResp[0] || []).slice(0, 4);
                    const shows  = (tvResp[0]   || []).slice(0, 4);
                    const html   = buildSection('Movies', movies, 'movie', false) +
                                   buildSection('TV Shows', shows, 'tv', true);
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
