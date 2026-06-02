window.showSuccessToast = function (msg) {
    const el = document.createElement('div');
    el.className = 'fixed top-20 left-1/2 z-50 bg-green-900/80 border border-green-700/50 text-green-300 text-sm px-5 py-3 rounded-lg backdrop-blur-sm cursor-pointer whitespace-nowrap';
    el.style.transform = 'translateX(-50%)';
    el.textContent = msg;
    el.onclick = () => el.remove();
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3000);
};

window.showErrorToast = function (msg) {
    const el = document.createElement('div');
    el.className = 'fixed top-20 left-1/2 z-50 bg-red-900/80 border border-red-700/50 text-red-300 text-sm px-5 py-3 rounded-lg backdrop-blur-sm cursor-pointer whitespace-nowrap';
    el.style.transform = 'translateX(-50%)';
    el.textContent = msg;
    el.onclick = () => el.remove();
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 5000);
};

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

    /* ── Debug mode toggle (admin only) ───────────────────────────────── */
    function setDebug(on) {
        document.documentElement.classList.toggle('debug-mode', on);
        localStorage.setItem('debug_mode', on ? '1' : '0');
        ['debug-toggle-indicator', 'debug-toggle-indicator-mobile'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = on ? 'on' : 'off';
            el.style.background = on ? 'rgba(192,57,58,0.25)' : '';
            el.style.color      = on ? '#c0393a' : '';
        });
    }
    setDebug(localStorage.getItem('debug_mode') === '1');

    ['debug-toggle-btn', 'debug-toggle-btn-mobile'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', () => {
            setDebug(!document.documentElement.classList.contains('debug-mode'));
        });
    });

    /* ── Mobile nav expandable sections ───────────────────────────────── */
    $(document).on('click', '.mobile-nav-toggle', function () {
        const body    = $(this).siblings('.mobile-nav-body');
        const chevron = $(this).find('.mobile-nav-chevron');
        const open    = body.hasClass('hidden');
        body.toggleClass('hidden', !open).toggleClass('flex', open);
        chevron.css('transform', open ? 'rotate(180deg)' : '');
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
    let _modalScrollY = 0;
    function lockBodyScroll() {
        _modalScrollY = window.scrollY;
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = '-' + _modalScrollY + 'px';
        document.body.style.width = '100%';
    }
    function unlockBodyScroll() {
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        window.scrollTo(0, _modalScrollY);
    }

    $(document).on('click', '[data-modal-open]', function () {
        const id = $(this).data('modal-open');
        $('#' + id).removeClass('hidden');
        lockBodyScroll();
        if (id === 'trailer-modal' && typeof gtag !== 'undefined') {
            gtag('event', 'trailer_opened');
        }
    });

    window.shareOrCopy = function (url, title = document.title, btn = null) {
        const setLabel = (text) => {
            if (!btn) return;
            const orig = btn.textContent;
            btn.textContent = text;
            setTimeout(() => btn.textContent = orig, 2000);
        };

        if (navigator.share) {
            navigator.share({ title, url }).catch(() => {});
        } else if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(() => {
                setLabel('Copied ✓');
                window.showSuccessToast('Link copied!');
            }).catch(() => window.showErrorToast('Could not copy link.'));
        } else {
            const ta = document.createElement('textarea');
            ta.value = url;
            ta.style.cssText = 'position:fixed;opacity:0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); setLabel('Copied ✓'); window.showSuccessToast('Link copied!'); }
            catch { window.showErrorToast('Could not copy link.'); }
            document.body.removeChild(ta);
        }
    };

    $(document).on('click', '[data-share]', function () {
        const url   = $(this).data('share-url') || window.location.href;
        const title = $(this).data('share-title') || document.title;
        window.shareOrCopy(url, title, this);
    });

    $(document).on('click', '[data-modal-close], .modal-backdrop', function () {
        $(this).closest('.modal-wrap').addClass('hidden');
        unlockBodyScroll();
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('.modal-wrap').addClass('hidden');
            unlockBodyScroll();
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
        unlockBodyScroll();
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
    window.showProgress = showLoading;

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

    $('#criteria').on('submit', showLoading);

    $(document).on('click', '.long-single, .long-movie', function (e) {
        if (!e.ctrlKey && !e.metaKey && !e.shiftKey) showLoading();
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
