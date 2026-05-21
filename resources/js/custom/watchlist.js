import TomSelect from 'tom-select';

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
            _token:      csrf(),
            tmdb_id:     btn.data('tmdb-id'),
            title:       btn.data('title'),
            poster_path: btn.data('poster') || null,
            year:        btn.data('year') || null,
            genres:      btn.data('genres') || null,
        })
        .done(function (res) {
            btn.data('saved', res.saved ? '1' : '0');
            btn.text(res.saved ? '★ Saved' : '☆ Save');
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

    // Combined filter: status tab + genre select
    function applyFilters() {
        const status = $('.watchlist-filter.active').data('filter');
        const activeGenres = new Set(genreSelect ? genreSelect.getValue() : []);

        $('.watchlist-card').each(function () {
            const cardStatus = $(this).attr('data-status');
            const cardGenres = ($(this).attr('data-genres') || '').split(',').map(g => g.trim());

            const statusMatch = status === 'all' || cardStatus === status;
            const genreMatch  = activeGenres.size === 0 || cardGenres.some(g => activeGenres.has(g));

            $(this).toggle(statusMatch && genreMatch);
        });

        $('#wl-count').text('(' + $('.watchlist-card:visible').length + ')');
    }

    $(document).on('click', '.watchlist-filter', function () {
        $('.watchlist-filter').removeClass('active');
        $(this).addClass('active');
        applyFilters();
    });

    $('#watchlist-roll').on('click', function () {
        const visible = $('.watchlist-card:visible');
        if (!visible.length) {
            const btn = $(this);
            const orig = btn.text();
            btn.text('Nothing to roll!').prop('disabled', true);
            setTimeout(() => btn.text(orig).prop('disabled', false), 1500);
            return;
        }
        const status = $('.watchlist-filter.active').data('filter') || 'all';
        const genres = genreSelect ? genreSelect.getValue().join(',') : '';
        const picked = visible.eq(Math.floor(Math.random() * visible.length));
        let url = picked.find('a').first().attr('href') + '?wl_status=' + status;
        if (genres) url += '&wl_genres=' + encodeURIComponent(genres);
        window.location.href = url;
    });

});