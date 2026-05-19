$(document).ready(function () {

    const csrf = () => $('meta[name="csrf-token"]').attr('content');

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
                btn.text('✓ Watched');
                card.find('.watched-overlay').removeClass('hidden');
            } else {
                btn.text('Mark watched');
                card.find('.watched-overlay').addClass('hidden');
            }

            applyFilter($('.watchlist-filter.active').data('filter'));
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

    // Filter tabs
    function applyFilter(filter) {
        $('.watchlist-card').each(function () {
            $(this).toggle(filter === 'all' || $(this).data('status') === filter);
        });
    }

    $(document).on('click', '.watchlist-filter', function () {
        $('.watchlist-filter').removeClass('active');
        $(this).addClass('active');
        applyFilter($(this).data('filter'));
    });

});