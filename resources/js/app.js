$(document).ready(function () {

    /* ── Nav hamburger ─────────────────────────────────────────────────── */
    function closeMenu() {
        $('.hamburger').removeClass('active');
        $('#mobile-menu').slideUp(200);
        $('body').css('overflow', '');
    }

    $('.hamburger').on('click', function () {
        $(this).toggleClass('active');
        const opening = $(this).hasClass('active');
        $('#mobile-menu')[opening ? 'slideDown' : 'slideUp'](200);
        $('body').css('overflow', opening ? 'hidden' : '');
    });

    $(window).on('resize', function () {
        if ($(window).width() >= 768) {
            closeMenu();
        }
    });

    $(document).on('click', function (e) {
        if ($('#mobile-menu').is(':visible') && !$(e.target).closest('nav, #mobile-menu').length) {
            closeMenu();
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

    /* ── Progress bar ───────────────────────────────────────────────────── */
    const progressBar = document.getElementById('progress-bar');
    let pendingFetches = 0;

    function showLoading() {
        progressBar.classList.remove('finishing');
        progressBar.classList.remove('active');
        void progressBar.offsetWidth;
        progressBar.classList.add('active');
    }

    function hideLoading() {
        progressBar.classList.remove('active');
        progressBar.classList.add('finishing');
        setTimeout(() => {
            progressBar.classList.remove('finishing');
        }, 450);
    }

    // Intercept fetch only — XHR (used by TomSelect autocomplete) is intentionally ignored
    const _fetch = window.fetch;
    window.fetch = function (...args) {
        pendingFetches++;
        if (pendingFetches === 1) showLoading();
        return _fetch.apply(this, args).finally(() => {
            pendingFetches = Math.max(0, pendingFetches - 1);
            if (pendingFetches === 0) hideLoading();
        });
    };

    // Hide when restored from bfcache (browser back/forward)
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) hideLoading();
    });

    $('#criteria').on('submit', function () {
        window.addEventListener('beforeunload', showLoading, { once: true });
    });

    $(document).on('click', '.long-single, .long-movie', function () {
        window.addEventListener('beforeunload', showLoading, { once: true });
        setTimeout(() => window.removeEventListener('beforeunload', showLoading), 150);
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
