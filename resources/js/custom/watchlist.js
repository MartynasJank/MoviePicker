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

    /* ── Animation toggle ──────────────────────────────────────────── */
    function getAnimEnabled() {
        return localStorage.getItem('wl_animation') !== '0';
    }

    function syncToggle() {
        const on = getAnimEnabled();
        document.getElementById('anim-toggle').checked = on;
        document.getElementById('anim-track').style.backgroundColor = on ? '#c0393a' : 'rgba(255,255,255,0.1)';
        document.getElementById('anim-thumb').style.transform = on ? 'translateX(16px)' : 'translateX(0)';
    }

    syncToggle();

    $('#anim-toggle').on('change', function () {
        localStorage.setItem('wl_animation', this.checked ? '1' : '0');
        syncToggle();
    });

    /* ── Case opening animation ────────────────────────────────────── */
    const CARD_W   = 140;
    const CARD_H   = 200;
    const CARD_GAP = 8;
    const STEP     = CARD_W + CARD_GAP;
    const STRIP_LEN  = 65;
    const WINNER_POS = 52;
    const START_POS  = 4;

    const TIERS = [
        { min: 8.5, color: '#f59e0b', label: 'Masterpiece' },
        { min: 7.5, color: '#c0393a', label: 'Excellent'   },
        { min: 6.5, color: '#8b5cf6', label: 'Great'       },
        { min: 5.5, color: '#3b82f6', label: 'Good'        },
        { min: 0,   color: '#6b7280', label: 'Mixed'       },
    ];

    function getTier(rating) {
        return TIERS.find(t => (rating || 0) >= t.min) || TIERS[TIERS.length - 1];
    }

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

    function runCaseOpening(cards, winnerIdx, fullUrl) {
        const winner = cards[winnerIdx];
        const others = cards.filter((_, i) => i !== winnerIdx);
        const pool = [];
        while (pool.length < STRIP_LEN) {
            pool.push(...[...others].sort(() => Math.random() - 0.5));
        }
        const strip = pool.slice(0, STRIP_LEN);
        strip[WINNER_POS] = winner;

        const overlay    = document.getElementById('case-overlay');
        const stripEl    = document.getElementById('case-strip');
        const titleEl    = document.getElementById('case-winner-title');
        const tierEl     = document.getElementById('case-winner-tier');
        const raysEl     = document.getElementById('case-rays');
        const raysInner  = document.getElementById('case-rays-inner');
        const glowEl     = document.getElementById('case-glow');
        const viewport   = document.getElementById('case-viewport');

        // Populate strip — each card gets a subtle tier-colored border
        stripEl.innerHTML = '';
        strip.forEach((card, i) => {
            const tier = getTier(card.rating);
            const el = document.createElement('div');
            el.className = 'case-card flex-shrink-0 rounded-lg overflow-hidden relative';
            el.style.cssText = `width:${CARD_W}px;height:${CARD_H}px;box-shadow:0 0 10px 2px ${tier.color}44;outline:1px solid ${tier.color}55`;
            if (i === WINNER_POS) el.id = 'case-winner-card';
            el.innerHTML = `
                <img src="${card.poster}" alt="" class="w-full h-full object-cover" loading="eager">
                <div style="position:absolute;inset:0;background:linear-gradient(to top, ${tier.color}cc 0%, ${tier.color}33 35%, transparent 65%);pointer-events:none"></div>
                <div style="position:absolute;bottom:0;left:0;right:0;padding:5px 6px;text-align:center">
                    <span style="font-size:9px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#fff;text-shadow:0 1px 4px rgba(0,0,0,0.8)">${tier.label}</span>
                </div>
            `;
            stripEl.appendChild(el);
        });

        overlay.classList.remove('hidden');
        titleEl.classList.add('opacity-0');
        tierEl.classList.add('opacity-0');
        titleEl.textContent    = '';
        tierEl.textContent     = '';
        raysEl.style.opacity   = '0';
        glowEl.style.opacity   = '0';

        const cw     = viewport.clientWidth;
        const center = cw / 2;
        const startX = center - (START_POS  * STEP + CARD_W / 2);
        const endX   = center - (WINNER_POS * STEP + CARD_W / 2);

        // Prefetch winner page so it loads instantly after animation
        const prefetchLink = document.createElement('link');
        prefetchLink.rel  = 'prefetch';
        prefetchLink.href = fullUrl;
        document.head.appendChild(prefetchLink);

        stripEl.style.transition = 'none';
        stripEl.style.transform  = `translateX(${startX}px)`;

        requestAnimationFrame(() => requestAnimationFrame(() => {
            stripEl.style.transition = 'transform 7s cubic-bezier(0.12, 0.9, 0.1, 1)';
            stripEl.style.transform  = `translateX(${endX}px)`;
        }));

        // Reveal winner with tier color
        setTimeout(() => {
            const winner = cards[winnerIdx];
            const tier   = getTier(winner.rating);
            const winnerEl = document.getElementById('case-winner-card');

            // Winner card glow
            if (winnerEl) {
                winnerEl.style.outline    = `2px solid ${tier.color}`;
                winnerEl.style.boxShadow  = `0 0 32px 10px ${tier.color}66`;
                winnerEl.style.transform  = 'scale(1.06)';
                winnerEl.style.transition = 'transform 0.25s ease, box-shadow 0.25s ease';
            }

            // Sunburst rays
            raysInner.style.background = `repeating-conic-gradient(
                from 0deg at 50% 50%,
                ${tier.color}55 0deg 7deg,
                transparent 7deg 20deg
            )`;
            raysInner.style.maskImage        = 'radial-gradient(circle, white 15%, transparent 68%)';
            raysInner.style.webkitMaskImage  = 'radial-gradient(circle, white 15%, transparent 68%)';
            raysEl.style.transition  = 'opacity 0.9s ease';
            raysEl.style.opacity     = '1';

            // Central glow
            glowEl.style.background = `radial-gradient(circle, ${tier.color}50 0%, transparent 70%)`;
            glowEl.style.transition = 'opacity 0.9s ease';
            glowEl.style.opacity    = '1';

            // Tier badge + title
            tierEl.textContent = tier.label;
            tierEl.style.color = tier.color;
            tierEl.classList.remove('opacity-0');
            titleEl.textContent = winner.title;
            titleEl.classList.remove('opacity-0');
        }, 7050);

        // Navigate
        setTimeout(() => { window.location.href = fullUrl; }, 8500);

        // Escape to cancel
        document.addEventListener('keydown', function onEsc(e) {
            if (e.key === 'Escape') {
                overlay.classList.add('hidden');
                document.removeEventListener('keydown', onEsc);
            }
        });
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

        if (getAnimEnabled()) {
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