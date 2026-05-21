$(document).ready(function () {

    /* ── Nav hamburger ─────────────────────────────────────────────────── */
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

    $(document).on('click', function (e) {
        if ($('#mobile-menu').is(':visible') && !$(e.target).closest('nav').length) {
            $('.hamburger').removeClass('active');
            $('#mobile-menu').slideUp(200);
        }
    });

    /* ── Theme toggle ──────────────────────────────────────────────────── */
    function applyTheme(theme) {
        document.documentElement.dataset.theme = theme;
        document.cookie = 'theme=' + theme + ';max-age=' + (30 * 24 * 3600) + ';path=/';
    }

    $(document).on('click', '.theme-toggle', function () {
        const current = document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    });

    /* ── Custom modals ─────────────────────────────────────────────────── */
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

    /* ── Loading overlay ───────────────────────────────────────────────── */
    function showLoading(text) {
        $('.loading-text').text(text);
        $('.overlay').fadeIn(150);
        $('.loading-text').fadeIn(150);
        $('.loader').fadeIn(150);
    }

    $('#criteria').on('submit', function () {
        window.addEventListener('beforeunload', () => showLoading('Finding the best match for you!'), { once: true });
    });

    $(document).on('click', '.long-single', function () {
        const text = $(this).data('loading') || 'Looking for a perfect movie!';
        const handler = () => showLoading(text);
        window.addEventListener('beforeunload', handler, { once: true });
        setTimeout(() => window.removeEventListener('beforeunload', handler), 150);
    });

    $(document).on('click', '.long-movie', function () {
        const name = $(this).data('name');
        const handler = () => showLoading('Loading ' + name + '!');
        window.addEventListener('beforeunload', handler, { once: true });
        setTimeout(() => window.removeEventListener('beforeunload', handler), 150);
    });

    /* ── Roulette row drag-to-scroll ───────────────────────────────────── */
    document.querySelectorAll('.roulette-row').forEach(function (row) {
        let isDown = false, startX, scrollLeft, hasDragged;

        row.addEventListener('dragstart', function (e) { e.preventDefault(); });

        row.addEventListener('mousedown', function (e) {
            isDown = true;
            hasDragged = false;
            startX = e.pageX - row.offsetLeft;
            scrollLeft = row.scrollLeft;
            row.classList.add('cursor-grabbing');
        });

        row.addEventListener('wheel', function (e) {
            const delta = Math.abs(e.deltaX) >= 5 ? e.deltaX : e.deltaY;
            if (Math.abs(delta) < 5) return;
            e.preventDefault();
            row.scrollLeft += delta;
        }, { passive: false });

        document.addEventListener('mouseup', function () {
            isDown = false;
            row.classList.remove('cursor-grabbing');
        });

        document.addEventListener('mousemove', function (e) {
            if (!isDown) return;
            const x = e.pageX - row.offsetLeft;
            const walk = x - startX;
            if (Math.abs(walk) > 4) hasDragged = true;
            row.scrollLeft = scrollLeft - walk;
        });

        row.addEventListener('click', function (e) {
            if (hasDragged) e.preventDefault();
        }, true);
    });

    /* ── Accordion ─────────────────────────────────────────────────────── */
    $(document).on('click', '.accordion-header', function () {
        $(this).closest('.accordion-section').toggleClass('accordion-open');
    });

    /* ── Auto-dismiss alerts ───────────────────────────────────────────── */
    setTimeout(function () {
        $('.alert-msg').fadeOut(400, function () { $(this).remove(); });
    }, 4000);
});
