$(document).ready(function () {

    // Save / unsave toggle on movie detail page
    $(document).on('click', '.watchlist-toggle', function () {
        const btn = $(this);
        const data = {
            _token:      $('meta[name="csrf-token"]').attr('content'),
            tmdb_id:     btn.data('tmdb-id'),
            title:       btn.data('title'),
            poster_path: btn.data('poster') || null,
            year:        btn.data('year') || null,
            genres:      btn.data('genres') || null,
        };

        btn.prop('disabled', true);

        $.post('/watchlist/toggle', data)
            .done(function (res) {
                btn.data('saved', res.saved ? '1' : '0');
                btn.text(res.saved ? '★ Saved' : '☆ Save');
            })
            .always(function () {
                btn.prop('disabled', false);
            });
    });

    // Mark watched on watchlist page
    $(document).on('click', '.mark-watched', function () {
        const btn = $(this);
        const tmdbId = btn.data('tmdb-id');

        $.ajax({
            url: `/watchlist/${tmdbId}/watched`,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
        }).done(function () {
            const card = btn.closest('.watchlist-card');
            card.attr('data-status', 'watched');

            // add watched overlay
            card.find('.aspect-\\[2\\/3\\]').append(
                '<div class="absolute inset-0 bg-black/50 flex items-center justify-center"><span class="text-white text-2xl">✓</span></div>'
            );
            btn.replaceWith('<span class="flex-1 text-xs py-1.5 px-2 text-center text-gray-600">Watched</span>');
            applyFilter($('.watchlist-filter.active').data('filter'));
        });
    });

    // Remove from watchlist page
    $(document).on('click', '.remove-from-watchlist', function () {
        const btn = $(this);
        const tmdbId = btn.data('tmdb-id');

        $.post('/watchlist/toggle', {
            _token:  $('meta[name="csrf-token"]').attr('content'),
            tmdb_id: tmdbId,
            title:   '',
        }).done(function () {
            btn.closest('.watchlist-card').fadeOut(200, function () {
                $(this).remove();
                if ($('.watchlist-card').length === 0) location.reload();
            });
        });
    });

    // Filter tabs
    function applyFilter(filter) {
        $('.watchlist-card').each(function () {
            const status = $(this).data('status');
            $(this).toggle(filter === 'all' || filter === status);
        });
    }

    $(document).on('click', '.watchlist-filter', function () {
        $('.watchlist-filter').removeClass('active');
        $(this).addClass('active');
        applyFilter($(this).data('filter'));
    });

});