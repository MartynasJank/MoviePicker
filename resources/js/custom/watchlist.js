import TomSelect from 'tom-select';
import { getTier, runCaseOpening } from './caseOpening.js';

let genreSelect   = null;
let excludeSelect = null;

$(document).ready(function () {

    const csrf = () => $('meta[name="csrf-token"]').attr('content');

    // Init genre multi-selects
    if (document.getElementById('genre-select')) {
        genreSelect = new TomSelect('#genre-select', {
            plugins: ['remove_button'],
            placeholder: 'Filter by genre…',
            onItemAdd() { this.setTextboxValue(''); this.refreshOptions(); },
            onChange() { applyFilters(); },
        });
    }
    if (document.getElementById('exclude-genre-select')) {
        excludeSelect = new TomSelect('#exclude-genre-select', {
            plugins: ['remove_button'],
            placeholder: 'Exclude genre…',
            onItemAdd() { this.setTextboxValue(''); this.refreshOptions(); },
            onChange() { applyFilters(); },
        });
    }

    // Disable Pick Together when fewer than 2 cards are visible
    $('#watchlist-collab').prop('disabled', $('.watchlist-card:visible').length <= 1);

    // Filter expand toggle
    $('#filter-expand-btn').on('click', function () {
        $('#filter-expand-body').toggleClass('hidden flex');
        $(this).toggleClass('text-accent bg-white/10');
    });

    // Mobile sticky bar proxies
    $('#watchlist-share-m').on('click', () => $('#watchlist-share').trigger('click'));
    $('#wl-swipe-btn-m').on('click', () => document.getElementById('wl-swipe-btn')?.click());
    $('#watchlist-collab-m').on('click', () => $('#watchlist-collab').trigger('click'));
    $('#watchlist-roll-m').on('click', () => $('#watchlist-roll').trigger('click'));

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
            if (typeof gtag !== 'undefined') {
                gtag('event', 'watchlist_saved', { action: res.saved ? 'save' : 'remove', media_type: btn.data('media-type') || 'movie' });
            }
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

    const svgChecked   = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>';
    const svgUnchecked = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/></svg>';

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
                btn.html(svgChecked)
                   .attr('title', 'Mark unwatched')
                   .removeClass('bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white')
                   .addClass('bg-white/10 text-white hover:bg-white/5 hover:text-gray-400');
                card.find('.watched-overlay').removeClass('hidden');
            } else {
                btn.html(svgUnchecked)
                   .attr('title', 'Mark watched')
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
                const remaining = $('.watchlist-card:visible').length;
                if (remaining === 0) location.reload();
                $('#watchlist-collab').prop('disabled', remaining <= 1);
            });
        });
    });

    // Combined filter: status tab + type tab + source tab + genre include/exclude selects
    function applyFilters() {
        const status          = $('.watchlist-filter.active').data('filter');
        const type            = $('.type-filter.active').data('type');
        const source          = $('.source-filter.active').data('source') || 'all';
        const activeGenres    = new Set(genreSelect   ? genreSelect.getValue()   : []);
        const excludedGenres  = new Set(excludeSelect ? excludeSelect.getValue() : []);

        $('.watchlist-card').each(function () {
            const cardStatus = $(this).attr('data-status');
            const cardType   = $(this).attr('data-type') || 'movie';
            const cardSource = $(this).attr('data-source') || '';
            const cardGenres = ($(this).attr('data-genres') || '').split(',').map(g => g.trim()).filter(Boolean);

            const statusMatch  = status === 'all' || cardStatus === status;
            const typeMatch    = type === 'all'   || cardType === type;
            const sourceMatch  = source === 'all' || (source === 'manual' ? cardSource !== 'swipe' : cardSource === source);
            const genreMatch   = activeGenres.size === 0
                || (cardGenres.length > 0 && [...activeGenres].every(g => cardGenres.includes(g)));
            const excludeMatch = excludedGenres.size === 0
                || !cardGenres.some(g => excludedGenres.has(g));

            $(this).toggle(statusMatch && typeMatch && sourceMatch && genreMatch && excludeMatch);
        });

        const visibleCount = $('.watchlist-card:visible').length;
        $('#wl-count').text('(' + visibleCount + ')');
        $('#watchlist-collab').prop('disabled', visibleCount <= 1);
    }

    $(document).on('click', '.watchlist-filter', function () {
        $('.watchlist-filter').removeClass('active').removeClass('text-accent').addClass('text-gray-400');
        $(this).addClass('active').addClass('text-accent').removeClass('text-gray-400');
        applyFilters();
    });

    $(document).on('click', '.type-filter', function () {
        $('.type-filter').removeClass('active').removeClass('text-accent').addClass('text-gray-400');
        $(this).addClass('active').addClass('text-accent').removeClass('text-gray-400');
        applyFilters();
    });

    $(document).on('click', '.source-filter', function () {
        $('.source-filter').removeClass('active').removeClass('text-accent').addClass('text-gray-400');
        $(this).addClass('active').addClass('text-accent').removeClass('text-gray-400');
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
    const _autoParams = new URLSearchParams(window.location.search);
    if (_autoParams.get('autoroll') === '1') {
        const _status    = _autoParams.get('wl_status') || 'all';
        const _type      = _autoParams.get('wl_type') || 'all';
        const _genres    = _autoParams.get('wl_genres') || '';
        const _exclude   = _autoParams.get('wl_exclude') || '';
        const _genreList = _genres ? _genres.split(',').map(g => g.trim()).filter(Boolean) : [];

        const eligible = $('.watchlist-card').filter(function () {
            const statusMatch = _status === 'all' || $(this).attr('data-status') === _status;
            const typeMatch   = _type === 'all'   || $(this).attr('data-type')   === _type;
            if (!statusMatch || !typeMatch) return false;
            if (_genreList.length === 0) return true;
            const cardGenres = ($(this).attr('data-genres') || '').split(',').map(g => g.trim()).filter(Boolean);
            return _genreList.every(g => cardGenres.includes(g));
        });

        if (eligible.length) {
            // Pick winner from non-excluded items; fall back to full pool if only 1 item
            const winnerPool = _exclude
                ? eligible.filter(function () {
                    return !($(this).find('a[href]').first().attr('href') || '').includes('/' + _exclude);
                })
                : eligible;
            const pickFrom   = winnerPool.length > 0 ? winnerPool : eligible;
            const picked     = pickFrom.eq(Math.floor(Math.random() * pickFrom.length));
            const winnerHref = picked.find('a[href]').first().attr('href');
            let   rollUrl    = winnerHref + '?wl_status=' + encodeURIComponent(_status);
            if (_genres) rollUrl += '&wl_genres=' + encodeURIComponent(_genres);
            if (_type !== 'all') rollUrl += '&wl_type=' + encodeURIComponent(_type);

            const rollCards = eligible.toArray().map(el => {
                const $el  = $(el);
                const img  = $el.find('img').first();
                return {
                    url:        $el.find('a[href]').first().attr('href'),
                    poster:     img.length ? (img.attr('src') || '').replace('w500', 'w342') : '',
                    title:      $el.data('title') || '',
                    rating:     parseFloat($el.data('rating')) || 0,
                    media_type: ($el.data('type') || 'movie') === 'tv' ? 'tv' : 'movie',
                };
            });

            const winnerIdx = rollCards.findIndex(c => c.url === winnerHref);
            setTimeout(() => {
                if (localStorage.getItem('wl_animation') !== '0') {
                    runCaseOpening(rollCards, winnerIdx >= 0 ? winnerIdx : 0, rollUrl, rollCards[0]?.media_type || 'movie');
                } else {
                    window.showProgress?.();
                    window.location.href = rollUrl;
                }
            }, 120);
        }
    }

    /* ── Case opening animation ────────────────────────────────────── */
    function buildCards() {
        const cards = [];
        $('.watchlist-card:visible').each(function () {
            const link      = $(this).find('a[href]').first();
            const img       = $(this).find('img').first();
            const title     = $(this).data('title') || '';
            const rating    = parseFloat($(this).data('rating')) || 0;
            const mediaType = ($(this).data('type') || 'movie') === 'tv' ? 'tv' : 'movie';
            if (link.length) {
                cards.push({
                    url:        link.attr('href'),
                    poster:     img.length ? (img.attr('src') || '').replace('w500', 'w342') : '',
                    title,
                    rating,
                    media_type: mediaType,
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
        const type      = $('.type-filter.active').data('type') || 'all';
        const genres    = genreSelect ? genreSelect.getValue().join(',') : '';
        const pickedIdx = Math.floor(Math.random() * visible.length);
        const picked    = visible.eq(pickedIdx);
        let url = picked.find('a').first().attr('href') + '?wl_status=' + status;
        if (genres) url += '&wl_genres=' + encodeURIComponent(genres);
        if (type !== 'all') url += '&wl_type=' + encodeURIComponent(type);

        if (typeof gtag !== 'undefined') gtag('event', 'watchlist_rolled');

        if (localStorage.getItem('wl_animation') !== '0') {
            const cards      = buildCards();
            const winnerHref = picked.find('a').first().attr('href');
            const winnerIdx  = cards.findIndex(c => c.url === winnerHref);
            const winner     = cards[winnerIdx >= 0 ? winnerIdx : 0];
            runCaseOpening(cards, winnerIdx >= 0 ? winnerIdx : 0, url, winner?.media_type || 'movie');
        } else {
            window.showProgress?.();
            window.location.href = url;
        }
    });

    /* ── Share visible watchlist as a batch ───────────────────────── */
    $('#watchlist-share').on('click', function () {
        const movies = [];
        $('.watchlist-card:visible').each(function () {
            const link      = $(this).find('a[href]').first();
            const img       = $(this).find('img').first();
            const href      = link.attr('href') || '';
            const idMatch   = href.match(/\/(?:movie|tv)\/(\d+)/);
            const id        = idMatch ? parseInt(idMatch[1]) : 0;
            if (!id) return;
            const posterSrc  = (img.attr('src') || '').replace('w500', 'w342');
            const posterPath = posterSrc.replace(/https:\/\/image\.tmdb\.org\/t\/p\/w\d+/, '');
            const title      = $(this).data('title') || '';
            const rating     = parseFloat($(this).data('rating')) || 0;
            const mediaType  = ($(this).data('type') || 'movie') === 'tv' ? 'tv' : 'movie';
            const year       = parseInt($(this).data('year')) || 2000;
            const dateStr    = year + '-01-01';
            movies.push({
                id,
                poster_path:    posterPath,
                title,
                name:           title,
                vote_average:   rating,
                media_type:     mediaType,
                release_date:   mediaType === 'movie' ? dateStr : null,
                first_air_date: mediaType === 'tv'    ? dateStr : null,
                genre_ids:      [],
            });
        });

        if (!movies.length) { window.showErrorToast('Nothing to share.'); return; }

        const hasTV  = movies.some(m => m.media_type === 'tv');
        const hasMov = movies.some(m => m.media_type === 'movie');
        const type   = hasTV && hasMov ? 'mixed' : (hasTV ? 'tv' : 'movie');

        const title = 'My Watchlist — MoviePickr';
        const csrf  = document.querySelector('meta[name="csrf-token"]').content;

        fetch('/batch/share', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ type, movies }),
        })
        .then(r => r.json())
        .then(data => {
            const url = window.location.origin + '/batch/share/' + data.token;
            if (navigator.share) {
                navigator.share({ title, url }).catch(() => {});
            } else if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => window.showSuccessToast('Link copied!'));
            } else {
                const ta = document.createElement('textarea');
                ta.value = url; ta.style.cssText = 'position:fixed;opacity:0';
                document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); window.showSuccessToast('Link copied!'); } catch {}
                document.body.removeChild(ta);
            }
        })
        .catch(() => window.showErrorToast('Could not create share link.'));
    });

    /* ── Pick Together from visible watchlist ─────────────────────── */
    $('#watchlist-collab').on('click', function () {
        const btn    = $(this);
        const movies = [];
        $('.watchlist-card:visible').each(function () {
            const link     = $(this).find('a[href]').first();
            const img      = $(this).find('img').first();
            const href     = link.attr('href') || '';
            const idMatch  = href.match(/\/(?:movie|tv)\/(\d+)/);
            const id       = idMatch ? parseInt(idMatch[1]) : 0;
            if (!id) return;
            const posterPath = (img.attr('src') || '').replace(/https:\/\/image\.tmdb\.org\/t\/p\/w\d+/, '');
            const title      = $(this).data('title') || '';
            const rating     = parseFloat($(this).data('rating')) || 0;
            const mediaType  = ($(this).data('type') || 'movie') === 'tv' ? 'tv' : 'movie';
            const year       = parseInt($(this).data('year')) || 2000;
            const dateStr    = year + '-01-01';
            const genres     = $(this).data('genres') || '';
            movies.push({
                id,
                poster_path:    posterPath,
                title,
                name:           title,
                vote_average:   rating,
                media_type:     mediaType,
                release_date:   mediaType === 'movie' ? dateStr : null,
                first_air_date: mediaType === 'tv'    ? dateStr : null,
                genre_ids:      [],
                genres,
            });
        });

        if (!movies.length) { window.showErrorToast('Nothing to pick from.'); return; }

        const hasTV     = movies.some(m => m.media_type === 'tv');
        const hasMov    = movies.some(m => m.media_type === 'movie');
        const mediaType = hasTV && hasMov ? 'mixed' : (hasTV ? 'tv' : 'movie');

        btn.text('Creating…').prop('disabled', true);

        fetch('/batch/collab', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            body:    JSON.stringify({ movies, media_type: mediaType, criteria: {} }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.token) window.location.href = '/batch/collab/' + data.token;
            else throw new Error();
        })
        .catch(() => {
            btn.text('Pick Together').prop('disabled', false);
            window.showErrorToast('Could not start session.');
        });
    });

});

/* ── Watchlist Swipe Mode ──────────────────────────────────────────────── */
(function () {
    const overlay       = document.getElementById('wl-swipe-overlay');
    const cardStack     = document.getElementById('wl-card-stack');
    const overlayKeep   = document.getElementById('wl-overlay-keep');
    const overlayRemove = document.getElementById('wl-overlay-remove');
    const doneScreen    = document.getElementById('wl-swipe-done');
    const doneTitle     = document.getElementById('wl-done-title');
    const doneStats     = document.getElementById('wl-done-stats');
    const counterEl     = document.getElementById('wl-swipe-counter');
    const undoBtn       = document.getElementById('wl-btn-undo');
    if (!overlay) return;

    const THRESHOLD     = 70;
    const VEL_THRESHOLD = 0.25;
    const MIN_FLICK_DX  = 20;

    let queue        = [];
    let history      = [];
    let toRemove     = [];
    let totalItems   = 0;
    let keptCount    = 0;
    let removedCount = 0;
    let animating    = false;

    const csrf = () => document.querySelector('meta[name=csrf-token]').content;

    const style = document.createElement('style');
    style.textContent = '@keyframes wlConfettiFall{from{top:-20px;opacity:1}to{top:110%;opacity:0}}';
    document.head.appendChild(style);

    function shuffle(arr) {
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
    }

    function buildQueue() {
        queue = [];
        document.querySelectorAll('.watchlist-card').forEach(card => {
            if (getComputedStyle(card).display === 'none') return;
            const link    = card.querySelector('a[href]');
            const img     = card.querySelector('img');
            const href    = link?.getAttribute('href') || '';
            const idMatch = href.match(/\/(?:movie|tv)\/(\d+)/);
            if (!idMatch) return;
            queue.push({
                id:     parseInt(idMatch[1]),
                type:   card.dataset.type || 'movie',
                title:  card.dataset.title || '',
                year:   card.dataset.year || '',
                rating: card.dataset.rating || '',
                genres: card.dataset.genres || '',
                poster: img ? img.src : '',
                href,
                el:     card,
            });
        });
        shuffle(queue);
        totalItems = queue.length;
    }

    function buildCard(item) {
        const el = document.createElement('div');
        el.className = 'wl-swipe-card absolute inset-0 rounded-2xl overflow-hidden shadow-2xl bg-[#1a1a1a] cursor-grab active:cursor-grabbing select-none';
        el.style.cssText = 'touch-action:none;will-change:transform;';
        el.innerHTML = `
            <div class="absolute inset-0">
                ${item.poster
                    ? `<img src="${item.poster}" class="w-full h-full object-cover" draggable="false">`
                    : `<div class="w-full h-full bg-white/5 flex items-center justify-center text-gray-600 text-sm px-4 text-center">${item.title}</div>`}
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/10 to-transparent"></div>
                <div class="absolute bottom-0 left-0 right-0 p-5">
                    <a href="${item.href}" target="_blank" rel="noopener" class="inline-block" onclick="event.stopPropagation()">
                        <h2 class="text-2xl font-bold text-white leading-tight hover:underline">${item.title}</h2>
                    </a>
                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                        ${item.year   ? `<span class="text-sm text-gray-300">${item.year}</span>` : ''}
                        ${item.rating ? `<span class="text-sm text-gray-300">★ ${Number(item.rating).toFixed(1)}</span>` : ''}
                        <span class="text-xs bg-white/15 text-white/80 px-2 py-0.5 rounded-full">${item.type === 'tv' ? 'TV' : 'Film'}</span>
                    </div>
                    ${item.genres ? `<div class="flex flex-wrap gap-1 mt-2">${item.genres.split(',').map(g => g.trim()).filter(Boolean).map(g => `<span class="text-xs bg-white/15 text-white/80 px-2 py-0.5 rounded-full">${g}</span>`).join('')}</div>` : ''}
                </div>
            </div>`;
        return el;
    }

    function renderStack() {
        cardStack.innerHTML = '';
        if (queue.length === 0) { showDone(); return; }

        if (queue.length > 1) {
            const behind = buildCard(queue[1]);
            behind.style.cssText += 'z-index:1;pointer-events:none;transform:scale(0.94) translateY(10px);';
            cardStack.appendChild(behind);
        }
        const top = buildCard(queue[0]);
        top.style.zIndex = '2';
        cardStack.appendChild(top);
        attachDrag(top);
        updateCounter();
        updateUndoBtn();
    }

    function advanceStack(action) {
        const item = queue.shift();
        history.push({ item, action });

        if (action === 'remove') {
            removedCount++;
            item.el.style.display = 'none';
            toRemove.push(item);
        } else {
            keptCount++;
        }

        const oldTop = cardStack.querySelector('.wl-swipe-card:last-child');
        if (oldTop) oldTop.remove();

        if (queue.length === 0) { showDone(); return; }

        const newTop = cardStack.querySelector('.wl-swipe-card');
        if (newTop) {
            newTop.style.transition    = 'transform 0.25s ease-out';
            newTop.style.transform     = 'scale(1) translateY(0)';
            newTop.style.zIndex        = '2';
            newTop.style.pointerEvents = '';
            attachDrag(newTop);
        }

        if (queue.length > 1) {
            const behind = buildCard(queue[1]);
            behind.style.cssText += 'z-index:1;pointer-events:none;transform:scale(0.94) translateY(10px);opacity:0;';
            cardStack.insertBefore(behind, newTop);
            requestAnimationFrame(() => {
                behind.style.transition = 'opacity 0.2s ease';
                behind.style.opacity    = '1';
            });
        }
        updateCounter();
        updateUndoBtn();
    }

    function triggerUndo() {
        if (!history.length || animating) return;
        const last = history.pop();
        queue.unshift(last.item);

        if (last.action === 'remove') {
            removedCount--;
            last.item.el.style.display = '';
            toRemove = toRemove.filter(i => i.id !== last.item.id);
        } else {
            keptCount--;
        }

        // Demote current top to behind
        const cards = cardStack.querySelectorAll('.wl-swipe-card');
        if (cards.length >= 2) {
            for (let i = 0; i < cards.length - 1; i++) cards[i].remove();
        }
        const currentTop = cardStack.querySelector('.wl-swipe-card:last-child');
        if (currentTop) {
            currentTop.style.transition    = 'transform 0.25s ease-out';
            currentTop.style.transform     = 'scale(0.94) translateY(10px)';
            currentTop.style.zIndex        = '1';
            currentTop.style.pointerEvents = 'none';
        }

        // Fly undone card back in from direction it left
        const card     = buildCard(last.item);
        const startX   = (last.action === 'keep' ? 1 : -1) * (window.innerWidth + 200);
        card.style.zIndex     = '2';
        card.style.transform  = `translateX(${startX}px) rotate(${(last.action === 'keep' ? 1 : -1) * 20}deg)`;
        card.style.transition = 'none';
        cardStack.appendChild(card);
        attachDrag(card);
        requestAnimationFrame(() => {
            card.style.transition = 'transform 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
            card.style.transform  = 'translateX(0) rotate(0deg)';
        });

        updateCounter();
        updateUndoBtn();
    }

    function updateCounter() {
        if (!counterEl) return;
        const pos = totalItems - queue.length + 1;
        counterEl.textContent = queue.length > 0 ? `${pos} / ${totalItems}` : `${totalItems} / ${totalItems}`;
    }

    function updateUndoBtn() {
        if (!undoBtn) return;
        undoBtn.disabled = history.length === 0;
        undoBtn.classList.toggle('text-gray-500', history.length === 0);
        undoBtn.classList.toggle('text-yellow-400', history.length > 0);
    }

    function attachDrag(card) {
        if (card.dataset.dragAttached) return;
        card.dataset.dragAttached = '1';
        let startX = 0, startY = 0, dx = 0, startTime = 0, active = false;

        const onStart = (e) => {
            if (animating) return;
            active    = true;
            dx        = 0;
            const pt  = e.touches ? e.touches[0] : e;
            startX    = pt.clientX;
            startY    = pt.clientY;
            startTime = Date.now();
            card.style.transition = 'none';
        };

        const onMove = (e) => {
            if (!active) return;
            e.preventDefault();
            const pt = e.touches ? e.touches[0] : e;
            dx       = pt.clientX - startX;
            const dy = pt.clientY - startY;
            card.style.transform = `translate(${dx}px, ${dy * 0.3}px) rotate(${dx * 0.08}deg)`;
            const ratio = Math.min(Math.abs(dx) / THRESHOLD, 1);
            if (dx > 0) {
                overlayKeep.style.opacity   = ratio;
                overlayRemove.style.opacity = 0;
            } else {
                overlayRemove.style.opacity = ratio;
                overlayKeep.style.opacity   = 0;
            }
        };

        const onEnd = () => {
            if (!active) return;
            active = false;
            overlayKeep.style.opacity   = 0;
            overlayRemove.style.opacity = 0;
            const velocity = Math.abs(dx) / Math.max(Date.now() - startTime, 1);
            const isFlick  = velocity > VEL_THRESHOLD && Math.abs(dx) > MIN_FLICK_DX;
            if (Math.abs(dx) >= THRESHOLD || isFlick) {
                dx > 0 ? triggerKeep(card) : triggerRemove(card);
            } else {
                card.style.transition = 'transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                card.style.transform  = 'translate(0,0) rotate(0deg)';
            }
            dx = 0;
        };

        card.addEventListener('pointerdown', onStart);
        card.addEventListener('pointermove', onMove, { passive: false });
        card.addEventListener('pointerup',   onEnd);
        card.addEventListener('pointercancel', onEnd);
    }

    function flyOut(card, direction, cb) {
        animating = true;
        const x = direction * (window.innerWidth + 200);
        card.style.transition = 'transform 0.35s ease-in, opacity 0.35s ease-in';
        card.style.transform  = `translate(${x}px, 20px) rotate(${direction * 30}deg)`;
        card.style.opacity    = '0';
        setTimeout(() => { animating = false; cb(); }, 360);
    }

    function triggerKeep(card)   { flyOut(card,  1, () => advanceStack('keep')); }
    function triggerRemove(card) { flyOut(card, -1, () => advanceStack('remove')); }

    function showDone() {
        const total = keptCount + removedCount;
        const icon  = document.getElementById('wl-done-icon');
        if (removedCount === total && total > 0) {
            doneTitle.textContent = 'Watchlist Cleared!';
            icon.textContent = '🏆';
        } else if (removedCount === 0) {
            doneTitle.textContent = 'You kept everything!';
            icon.textContent = '👍';
        } else {
            doneTitle.textContent = 'All done!';
            icon.textContent = '🎉';
        }
        doneStats.textContent = `${removedCount} removed · ${keptCount} kept`;
        doneScreen.classList.remove('hidden');
        spawnConfetti();
        syncCount();
    }

    function spawnConfetti() {
        const container = document.getElementById('wl-done-confetti');
        if (!container) return;
        container.innerHTML = '';
        const colors = ['#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#8b5cf6', '#ec4899'];
        for (let i = 0; i < 70; i++) {
            const el     = document.createElement('div');
            const isRect = Math.random() > 0.5;
            const size   = Math.random() * 8 + 5;
            el.style.cssText = [
                'position:absolute',
                `width:${size}px`,
                `height:${isRect ? size * 0.4 : size}px`,
                `background:${colors[i % colors.length]}`,
                `border-radius:${isRect ? '2px' : '50%'}`,
                `left:${Math.random() * 100}%`,
                'top:-20px',
                `animation:wlConfettiFall ${Math.random() * 2 + 2}s ${Math.random() * 1.5}s linear forwards`,
            ].join(';');
            container.appendChild(el);
        }
    }

    function syncCount() {
        let visible = 0;
        document.querySelectorAll('.watchlist-card').forEach(c => {
            if (getComputedStyle(c).display !== 'none') visible++;
        });
        const countEl = document.getElementById('wl-count');
        if (countEl) countEl.textContent = `(${visible})`;
    }

    function fireDeletes() {
        toRemove.forEach(item => {
            fetch(`/watchlist/${item.id}`, {
                method:  'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf() },
            }).catch(() => {});
        });
        toRemove = [];
    }

    function closeOverlay() {
        fireDeletes();
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
        syncCount();
        if (!document.querySelector('.watchlist-card:not([style*="display: none"])')) location.reload();
    }

    document.getElementById('wl-swipe-btn')?.addEventListener('click', () => {
        buildQueue();
        if (!queue.length) return;
        history      = [];
        toRemove     = [];
        keptCount    = 0;
        removedCount = 0;
        doneScreen.classList.add('hidden');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        renderStack();
    });

    document.getElementById('wl-swipe-close')?.addEventListener('click', closeOverlay);
    document.getElementById('wl-done-close')?.addEventListener('click', closeOverlay);

    document.getElementById('wl-btn-keep')?.addEventListener('click', () => {
        if (animating || !queue.length) return;
        triggerKeep(cardStack.querySelector('.wl-swipe-card:last-child'));
    });

    document.getElementById('wl-btn-remove')?.addEventListener('click', () => {
        if (animating || !queue.length) return;
        triggerRemove(cardStack.querySelector('.wl-swipe-card:last-child'));
    });

    undoBtn?.addEventListener('click', triggerUndo);

    document.addEventListener('keydown', (e) => {
        if (overlay.classList.contains('hidden')) return;
        if (e.key === 'ArrowRight') document.getElementById('wl-btn-keep')?.click();
        if (e.key === 'ArrowLeft')  document.getElementById('wl-btn-remove')?.click();
        if (e.key === 'ArrowDown')  undoBtn?.click();
    });
})();