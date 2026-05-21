$(document).ready(function () {

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function buildResults(data) {
        if (!data.length) return '';
        return data.map(function (m) {
            const year   = m.release_date ? m.release_date.substring(0, 4) : '';
            const poster = m.poster_path
                ? `<img src="https://image.tmdb.org/t/p/w92${escHtml(m.poster_path)}" class="w-8 h-12 object-cover rounded flex-shrink-0" loading="lazy">`
                : `<div class="w-8 h-12 bg-white/5 rounded flex-shrink-0"></div>`;
            return `<a href="/movie/${m.id}" class="search-result-item flex items-center gap-3 px-3 py-2 hover:bg-white/5 transition-colors">
                ${poster}
                <div class="min-w-0">
                    <div class="text-sm text-white truncate">${escHtml(m.title)}</div>
                    ${year ? `<div class="text-xs text-gray-500">${year}</div>` : ''}
                </div>
            </a>`;
        }).join('');
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
                $.getJSON('/tmdb/search/movies', { q: q })
                    .done(function (data) {
                        const html = buildResults(data);
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
