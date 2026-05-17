import 'jquery-flexdatalist/jquery.flexdatalist.min';

$(document).ready(function () {

    /* â”€â”€ Nav hamburger â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $('.hamburger').on('click', function () {
        $(this).toggleClass('active');
        $('#mobile-menu').slideToggle(200);
    });

    $(window).on('resize', function () {
        if ($(window).width() >= 768) {
            $('.hamburger').removeClass('active');
            $('#mobile-menu').hide();
        }
    });

    /* â”€â”€ Theme toggle â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    function applyTheme(theme) {
        $('body').attr('data-theme', theme);
        $('#theme-toggle').attr('data-theme', theme);
        document.cookie = 'theme=' + theme + ';max-age=' + (30 * 24 * 3600) + ';path=/';
    }

    $('#theme-toggle').on('click', function () {
        const current = $('body').attr('data-theme') === 'light' ? 'light' : 'dark';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    });

    /* â”€â”€ Custom modals â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $(document).on('click', '[data-modal-open]', function () {
        const id = $(this).data('modal-open');
        $('#' + id).removeClass('hidden');
        $('body').css('overflow', 'hidden');
    });

    $(document).on('click', '[data-modal-close], .modal-backdrop', function () {
        $(this).closest('.modal-wrap').addClass('hidden');
        $('body').css('overflow', '');
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('.modal-wrap').addClass('hidden');
            $('body').css('overflow', '');
            stopTrailer();
        }
    });

    function stopTrailer() {
        const $trailer = $('#trailer');
        if ($trailer.length) $trailer.attr('src', $trailer.attr('src'));
    }

    $(document).on('click', '#trailer-modal-close, #trailer-modal .modal-backdrop', function () {
        stopTrailer();
        $('#trailer-modal').addClass('hidden');
        $('body').css('overflow', '');
    });

    /* â”€â”€ Loading overlay â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    function showLoading(text) {
        $('.loading-text').text(text);
        $('.overlay').fadeIn(150);
        $('.loading-text').fadeIn(150);
        $('.loader').fadeIn(150);
    }

    $('#criteria').on('submit', function () {
        window.addEventListener('beforeunload', () => showLoading('Finding the best match for you!'), { once: true });
    });

    $('#movie-search').on('submit', function () {
        window.addEventListener('beforeunload', () => showLoading('Fetching the movie!'), { once: true });
    });

    $(document).on('click', '.long-single', function () {
        const handler = () => showLoading('Looking for a perfect movie!');
        window.addEventListener('beforeunload', handler, { once: true });
        setTimeout(() => window.removeEventListener('beforeunload', handler), 150);
    });

    $(document).on('click', '.long-movie', function () {
        const name = $(this).data('name');
        const handler = () => showLoading('Loading ' + name + '!');
        window.addEventListener('beforeunload', handler, { once: true });
        setTimeout(() => window.removeEventListener('beforeunload', handler), 150);
    });

    /* â”€â”€ Nav movie search (flexdatalist) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    if ($('.movie-search').length) {
        $('.movie-search').flexdatalist({
            minLength: 1,
            maxShownResults: 5,
            textProperty: '{title}',
            valueProperty: 'id',
            selectionRequired: true,
            visibleProperties: ['item-backdrop_path', 'meta'],
            searchContain: true,
            searchByWord: true,
            searchIn: 'title',
            multiple: false,
            cacheLifetime: 5,
            searchDelay: 600,
        }).on('show:flexdatalist.results', function (ev, result) {
            $.each(result, function (key, value) {
                if (value.backdrop_path) {
                    result[key]['item-backdrop_path'] = '<img src=”https://image.tmdb.org/t/p/w92' + value.backdrop_path + '”>';
                }
                if (value.title || value.release_date) {
                    result[key]['meta'] = '<div class=”movie-meta”><span class=”item”>' + value.title_highlight + '</span><span class=”item-meta”>' + (value.release_date || '') + '</span></div>';
                }
            });
        }).on('select:flexdatalist', function () {
            $('.submit-search').trigger('submit');
        });

        var searchTimer;
        $('#movie_search-flexdatalist').on('keyup', function () {
            clearTimeout(searchTimer);
            const val = $(this).val();
            searchTimer = setTimeout(function () {
                if (val.length > 0) {
                    $.getJSON('/tmdb/search/movies?q=' + encodeURIComponent(val))
                        .done(function (results) {
                            $('.movie-search').flexdatalist('data', results);
                        });
                }
            }, 500);
        });
    }

    /* â”€â”€ Auto-dismiss alerts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    setTimeout(function () {
        $('.alert-msg').fadeOut(400, function () { $(this).remove(); });
    }, 4000);
});
