import TomSelect from 'tom-select';
import { getTier, runCaseOpening } from './caseOpening.js';

let genreSelect   = null;
let excludeSelect = null;

$(document).ready(function () {

    const csrf = () => $('meta[name="csrf-token"]').attr('content');

    // Init genre multi-selects
    if (document.getElementById('genre-select')) {
        genreSelect = new TomSelect('#genre-select', {
            plugins: ['remove_button'],
            placeholder: 'Filter by genre…',
            onItemAdd() { this.setTextboxValue(''); this.refreshOptions(); },
            onChange() { applyFilters(); },
        });
    }
    if (document.getElementById('exclude-genre-select')) {
        excludeSelect = new TomSelect('#exclude-genre-select', {
            plugins: ['remove_button'],
            placeholder: 'Exclude genre…',
            onItemAdd() { this.setTextboxValue(''); this.refreshOptions(); },
            onChange() { applyFilters(); },
        });
    }

    // Disable Pick Together when fewer than 2 cards are visible
    $('#watchlist-collab').prop('disabled', $('.watchlist-card:visible').length <= 1);

    // Save / unsave toggle on movie detail page
    $(document).on('click', '.watchlist-toggle', function () {
        const btn = $(this);
        btn.prop('disabled', true);

        $.post('/watchlist/toggle', {
            _token:       csrf(),
            tmdb_id:      btn.data('tmdb-id'),
            title:        btn.data('title'),
            poster_path:  btn.data('poster') || null,
            year:         btn.data('year') || null,
            genres:       btn.data('genres') || null,
            vote_average: btn.data('rating') || null,
            type:         btn.data('media-type') || 'movie',
        })
        .done(function (res) {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'watchlist_saved', { action: res.saved ? 'save' : 'remove', media_type: btn.data('media-type') || 'movie' });
            }
            btn.data('saved', res.saved ? '1' : '0');
            const star = btn.data('format') === 'star';
            if (star) {
                btn.text(res.saved ? '★ Saved' : '☆');
                btn.toggleClass('bg-accent', res.saved)
                   .toggleClass('bg-black/70 hover:bg-black/90', !res.saved);
            } else {
                btn.text(res.saved ? '★ Saved' : '☆ Save');
            }
        })
        .always(function () { btn.prop('disabled', false); });
    });

    // Toggle watched / unwatched on watchlist page
    $(document).on('click', '.toggle-watched', function () {
        const btn = $(this);
        const tmdbId = btn.data('tmdb-id');
        const current = btn.data('status');
        const next = current === 'watched' ? 'saved' : 'watched';

        $.ajax({
            url: `/watchlist/${tmdbId}/status`,
            method: 'PATCH',
            data: { _token: csrf(), status: next },
        }).done(function () {
            const card = btn.closest('.watchlist-card');
            btn.data('status', next);
            card.attr('data-status', next);

            if (next === 'watched') {
                btn.text('✓ Watched')
                   .removeClass('bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white')
                   .addClass('bg-white/10 text-white hover:bg-white/5 hover:text-gray-400');
                card.find('.watched-overlay').removeClass('hidden');
            } else {
                btn.text('Mark watched')
                   .removeClass('bg-white/10 text-white hover:bg-white/5 hover:text-gray-400')
                   .addClass('bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white');
                card.find('.watched-overlay').addClass('hidden');
            }

            applyFilters();
        });
    });

    // Remove from watchlist page
    $(document).on('click', '.remove-from-watchlist', function () {
        const btn = $(this);
        const tmdbId = btn.data('tmdb-id');

        $.ajax({
            url: `/watchlist/${tmdbId}`,
            method: 'DELETE',
            data: { _token: csrf() },
        }).done(function () {
            btn.closest('.watchlist-card').fadeOut(200, function () {
                $(this).remove();
                const remaining = $('.watchlist-card:visible').length;
                if (remaining === 0) location.reload();
                $('#watchlist-collab').prop('disabled', remaining <= 1);
            });
        });
    });

    // Combined filter: status tab + type tab + genre include/exclude selects
    function applyFilters() {
        const status          = $('.watchlist-filter.active').data('filter');
        const type            = $('.type-filter.active').data('type');
        const activeGenres    = new Set(genreSelect   ? genreSelect.getValue()   : []);
        const excludedGenres  = new Set(excludeSelect ? excludeSelect.getValue() : []);

        $('.watchlist-card').each(function () {
            const cardStatus = $(this).attr('data-status');
            const cardType   = $(this).attr('data-type') || 'movie';
            const cardGenres = ($(this).attr('data-genres') || '').split(',').map(g => g.trim()).filter(Boolean);

            const statusMatch  = status === 'all' || cardStatus === status;
            const typeMatch    = type === 'all'   || cardType === type;
            // All selected include-genres must be present (AND logic)
            const genreMatch   = activeGenres.size === 0
                || (cardGenres.length > 0 && [...activeGenres].every(g => cardGenres.includes(g)));
            // No selected exclude-genre may be present
            const excludeMatch = excludedGenres.size === 0
                || !cardGenres.some(g => excludedGenres.has(g));

            $(this).toggle(statusMatch && typeMatch && genreMatch && excludeMatch);
        });

        const visibleCount = $('.watchlist-card:visible').length;
        $('#wl-count').text('(' + visibleCount + ')');
        $('#watchlist-collab').prop('disabled', visibleCount <= 1);
    }

    $(document).on('click', '.watchlist-filter', function () {
        $('.watchlist-filter').removeClass('active').removeClass('text-accent').addClass('text-gray-400');
        $(this).addClass('active').addClass('text-accent').removeClass('text-gray-400');
        applyFilters();
    });

    $(document).on('click', '.type-filter', function () {
        $('.type-filter').removeClass('active').removeClass('text-accent').addClass('text-gray-400');
        $(this).addClass('active').addClass('text-accent').removeClass('text-gray-400');
        applyFilters();
    });

    function applySort() {
        const val = $('#sort-select').val();
        if (!val) return;
        const [key, dir] = val.split('-');
        const grid = $('.watchlist-card').parent();
        const cards = $('.watchlist-card').get();

        cards.sort(function (a, b) {
            let av, bv;
            if (key === 'date') {
                av = parseInt($(a).data('date')) || 0;
                bv = parseInt($(b).data('date')) || 0;
            } else if (key === 'title') {
                av = ($(a).data('title') || '').toLowerCase();
                bv = ($(b).data('title') || '').toLowerCase();
            } else if (key === 'year') {
                av = parseInt($(a).data('year')) || 0;
                bv = parseInt($(b).data('year')) || 0;
            } else if (key === 'rating') {
                av = parseFloat($(a).data('rating')) || 0;
                bv = parseFloat($(b).data('rating')) || 0;
            }
            if (av < bv) return dir === 'asc' ? -1 : 1;
            if (av > bv) return dir === 'asc' ? 1 : -1;
            return 0;
        });

        grid.append(cards);
    }

    $('#sort-select').on('change', applySort);

    /* ── Auto-roll when returning from movie page ──────────────────── */
    if (sessionStorage.getItem('wl_autoroll') === '1') {
        sessionStorage.removeItem('wl_autoroll');
        setTimeout(() => $('#watchlist-roll').trigger('click'), 120);
    }

    /* ── Case opening animation ────────────────────────────────────── */
    function buildCards() {
        const cards = [];
        $('.watchlist-card:visible').each(function () {
            const link      = $(this).find('a[href]').first();
            const img       = $(this).find('img').first();
            const title     = $(this).data('title') || '';
            const rating    = parseFloat($(this).data('rating')) || 0;
            const mediaType = ($(this).data('type') || 'movie') === 'tv' ? 'tv' : 'movie';
            if (link.length && img.length) {
                cards.push({
                    url:        link.attr('href'),
                    poster:     (img.attr('src') || '').replace('w500', 'w342'),
                    title,
                    rating,
                    media_type: mediaType,
                });
            }
        });
        return cards;
    }

    /* ── Roll button ───────────────────────────────────────────────── */
    $('#watchlist-roll').on('click', function () {
        const visible = $('.watchlist-card:visible');
        if (!visible.length) {
            const btn = $(this);
            const orig = btn.text();
            btn.text('Nothing to roll!').prop('disabled', true);
            setTimeout(() => btn.text(orig).prop('disabled', false), 1500);
            return;
        }

        const status    = $('.watchlist-filter.active').data('filter') || 'all';
        const genres    = genreSelect ? genreSelect.getValue().join(',') : '';
        const pickedIdx = Math.floor(Math.random() * visible.length);
        const picked    = visible.eq(pickedIdx);
        let url = picked.find('a').first().attr('href') + '?wl_status=' + status;
        if (genres) url += '&wl_genres=' + encodeURIComponent(genres);

        if (typeof gtag !== 'undefined') gtag('event', 'watchlist_rolled');

        if (localStorage.getItem('wl_animation') !== '0') {
            const cards      = buildCards();
            const winnerHref = picked.find('a').first().attr('href');
            const winnerIdx  = cards.findIndex(c => c.url === winnerHref);
            const winner     = cards[winnerIdx >= 0 ? winnerIdx : 0];
            runCaseOpening(cards, winnerIdx >= 0 ? winnerIdx : 0, url, winner?.media_type || 'movie');
        } else {
            window.showProgress?.();
            window.location.href = url;
        }
    });

    /* ── Share visible watchlist as a batch ───────────────────────── */
    $('#watchlist-share').on('click', function () {
        const movies = [];
        $('.watchlist-card:visible').each(function () {
            const link      = $(this).find('a[href]').first();
            const img       = $(this).find('img').first();
            const href      = link.attr('href') || '';
            const idMatch   = href.match(/\/(?:movie|tv)\/(\d+)/);
            const id        = idMatch ? parseInt(idMatch[1]) : 0;
            if (!id) return;
            const posterSrc  = (img.attr('src') || '').replace('w500', 'w342');
            const posterPath = posterSrc.replace(/https:\/\/image\.tmdb\.org\/t\/p\/w\d+/, '');
            const title      = $(this).data('title') || '';
            const rating     = parseFloat($(this).data('rating')) || 0;
            const mediaType  = ($(this).data('type') || 'movie') === 'tv' ? 'tv' : 'movie';
            const year       = parseInt($(this).data('year')) || 2000;
            const dateStr    = year + '-01-01';
            movies.push({
                id,
                poster_path:    posterPath,
                title,
                name:           title,
                vote_average:   rating,
                media_type:     mediaType,
                release_date:   mediaType === 'movie' ? dateStr : null,
                first_air_date: mediaType === 'tv'    ? dateStr : null,
                genre_ids:      [],
            });
        });

        if (!movies.length) { window.showErrorToast('Nothing to share.'); return; }

        const hasTV  = movies.some(m => m.media_type === 'tv');
        const hasMov = movies.some(m => m.media_type === 'movie');
        const type   = hasTV && hasMov ? 'mixed' : (hasTV ? 'tv' : 'movie');

        const title = 'My Watchlist — MoviePickr';
        const csrf  = document.querySelector('meta[name="csrf-token"]').content;

        fetch('/batch/share', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ type, movies }),
        })
        .then(r => r.json())
        .then(data => {
            const url = window.location.origin + '/batch/share/' + data.token;
            if (navigator.share) {
                navigator.share({ title, url }).catch(() => {});
            } else if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => window.showSuccessToast('Link copied!'));
            } else {
                const ta = document.createElement('textarea');
                ta.value = url; ta.style.cssText = 'position:fixed;opacity:0';
                document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); window.showSuccessToast('Link copied!'); } catch {}
                document.body.removeChild(ta);
            }
        })
        .catch(() => window.showErrorToast('Could not create share link.'));
    });

    /* ── Pick Together from visible watchlist ─────────────────────── */
    $('#watchlist-collab').on('click', function () {
        const btn    = $(this);
        const movies = [];
        $('.watchlist-card:visible').each(function () {
            const link     = $(this).find('a[href]').first();
            const img      = $(this).find('img').first();
            const href     = link.attr('href') || '';
            const idMatch  = href.match(/\/(?:movie|tv)\/(\d+)/);
            const id       = idMatch ? parseInt(idMatch[1]) : 0;
            if (!id) return;
            const posterPath = (img.attr('src') || '').replace(/https:\/\/image\.tmdb\.org\/t\/p\/w\d+/, '');
            const title      = $(this).data('title') || '';
            const rating     = parseFloat($(this).data('rating')) || 0;
            const mediaType  = ($(this).data('type') || 'movie') === 'tv' ? 'tv' : 'movie';
            const year       = parseInt($(this).data('year')) || 2000;
            const dateStr    = year + '-01-01';
            const genres     = $(this).data('genres') || '';
            movies.push({
                id,
                poster_path:    posterPath,
                title,
                name:           title,
                vote_average:   rating,
                media_type:     mediaType,
                release_date:   mediaType === 'movie' ? dateStr : null,
                first_air_date: mediaType === 'tv'    ? dateStr : null,
                genre_ids:      [],
                genres,
            });
        });

        if (!movies.length) { window.showErrorToast('Nothing to pick from.'); return; }

        const hasTV     = movies.some(m => m.media_type === 'tv');
        const hasMov    = movies.some(m => m.media_type === 'movie');
        const mediaType = hasTV && hasMov ? 'mixed' : (hasTV ? 'tv' : 'movie');

        btn.text('Creating…').prop('disabled', true);

        fetch('/batch/collab', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            body:    JSON.stringify({ movies, media_type: mediaType, criteria: {} }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.token) window.location.href = '/batch/collab/' + data.token;
            else throw new Error();
        })
        .catch(() => {
            btn.text('Pick Together').prop('disabled', false);
            window.showErrorToast('Could not start session.');
        });
    });

});