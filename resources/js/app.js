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

    /* ── Share button ──────────────────────────────────────────────────── */
    $('#share-btn').on('click', function () {
        const $btn = $(this);
        const url  = window.location.href;
        const icon = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>';

        function onCopied() {
            $btn.text('Copied!');
            setTimeout(function () { $btn.html(icon); }, 2000);
        }

        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(onCopied);
        } else {
            const ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.opacity  = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            onCopied();
        }
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
