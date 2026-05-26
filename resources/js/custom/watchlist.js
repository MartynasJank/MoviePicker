import TomSelect from 'tom-select';
import { getTier, runCaseOpening } from './caseOpening.js';

let genreSelect = null;

$(document).ready(function () {

    const csrf = () => $('meta[name="csrf-token"]').attr('content');

    // Init genre multi-select
    if (document.getElementById('genre-select')) {
        genreSelect = new TomSelect('#genre-select', {
            plugins: ['remove_button'],
            placeholder: 'Filter by genre…',
            onItemAdd() { this.setTextboxValue(''); this.refreshOptions(); },
            onChange() { applyFilters(); },
        });
    }

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
                if ($('.watchlist-card:visible').length === 0) location.reload();
            });
        });
    });

    // Combined filter: status tab + type tab + genre select
    function applyFilters() {
        const status = $('.watchlist-filter.active').data('filter');
        const type   = $('.type-filter.active').data('type');
        const activeGenres = new Set(genreSelect ? genreSelect.getValue() : []);

        $('.watchlist-card').each(function () {
            const cardStatus = $(this).attr('data-status');
            const cardType   = $(this).attr('data-type') || 'movie';
            const cardGenres = ($(this).attr('data-genres') || '').split(',').map(g => g.trim());

            const statusMatch = status === 'all' || cardStatus === status;
            const typeMatch   = type === 'all'   || cardType === type;
            const genreMatch  = activeGenres.size === 0 || cardGenres.some(g => activeGenres.has(g));

            $(this).toggle(statusMatch && typeMatch && genreMatch);
        });

        $('#wl-count').text('(' + $('.watchlist-card:visible').length + ')');
    }

    $(document).on('click', '.watchlist-filter', function () {
        $('.watchlist-filter').removeClass('active');
        $(this).addClass('active');
        applyFilters();
    });

    $(document).on('click', '.type-filter', function () {
        $('.type-filter').removeClass('active').addClass('text-gray-400');
        $(this).addClass('active').removeClass('text-gray-400');
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
            const link   = $(this).find('a[href]').first();
            const img    = $(this).find('img').first();
            const title  = $(this).data('title') || '';
            const rating = parseFloat($(this).data('rating')) || 0;
            if (link.length && img.length) {
                cards.push({
                    url:    link.attr('href'),
                    poster: (img.attr('src') || '').replace('w500', 'w342'),
                    title,
                    rating,
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

        if (localStorage.getItem('wl_animation') !== '0') {
            const cards      = buildCards();
            const winnerHref = picked.find('a').first().attr('href');
            const winnerIdx  = cards.findIndex(c => c.url === winnerHref);
            runCaseOpening(cards, winnerIdx >= 0 ? winnerIdx : 0, url);
        } else {
            window.showProgress?.();
            window.location.href = url;
        }
    });

});