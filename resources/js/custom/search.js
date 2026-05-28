$(document).ready(function () {

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    const TYPE_LABELS = { movie: 'Movie', tv: 'TV', person: 'Person' };

    function buildResult(r) {
        const type = r.media_type;
        const badge = `<span class="text-[10px] font-semibold uppercase tracking-wider text-gray-600 flex-shrink-0">${TYPE_LABELS[type] || ''}</span>`;

        if (type === 'person') {
            const photo = r.profile_path
                ? `<img src="https://image.tmdb.org/t/p/w92${escHtml(r.profile_path)}" class="w-8 h-8 rounded-full object-cover flex-shrink-0" loading="lazy">`
                : `<div class="w-8 h-8 rounded-full bg-white/5 flex-shrink-0 flex items-center justify-center text-gray-600 text-xs font-bold">${escHtml(r.name.charAt(0).toUpperCase())}</div>`;
            const sub = r.known_for_department ? escHtml(r.known_for_department) : '';
            return `<a href="/person/${r.id}" class="search-result-item flex items-center gap-3 px-3 py-2 hover:bg-white/5 transition-colors">
                ${photo}
                <div class="min-w-0 flex-1">
                    <div class="text-sm text-white truncate">${escHtml(r.name)}</div>
                    ${sub ? `<div class="text-xs text-gray-500">${sub}</div>` : ''}
                </div>
                ${badge}
            </a>`;
        }

        const href  = type === 'tv' ? `/tv/${r.id}` : `/movie/${r.id}`;
        const title = r.title || r.name || '';
        const year  = r.release_date ? r.release_date.substring(0, 4) : '';
        const thumb = r.poster_path
            ? `<img src="https://image.tmdb.org/t/p/w92${escHtml(r.poster_path)}" class="w-8 h-12 object-cover rounded flex-shrink-0" loading="lazy">`
            : `<div class="w-8 h-12 bg-white/5 rounded flex-shrink-0"></div>`;

        return `<a href="${href}" class="search-result-item flex items-center gap-3 px-3 py-2 hover:bg-white/5 transition-colors">
            ${thumb}
            <div class="min-w-0 flex-1">
                <div class="text-sm text-white truncate">${escHtml(title)}</div>
                ${year ? `<div class="text-xs text-gray-500">${year}</div>` : ''}
            </div>
            ${badge}
        </a>`;
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
                    const html = (resp || []).map(buildResult).join('');
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
