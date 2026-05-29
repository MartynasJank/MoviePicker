import { runCaseOpening } from './caseOpening.js';

// ── Helpers ───────────────────────────────────────────────────────────

function setRollContext(source, backUrl, backLabel) {
    sessionStorage.setItem('rollSource',    source);
    sessionStorage.setItem('rollBackUrl',   backUrl);
    sessionStorage.setItem('rollBackLabel', backLabel);
}

function toCards(movies) {
    return movies
        .filter(m => m.poster_path)
        .map(m => ({
            url:        m.url,
            poster:     `https://image.tmdb.org/t/p/w342${m.poster_path}`,
            title:      m.title,
            rating:     m.vote_average,
            media_type: m.media_type || 'movie',
        }));
}

function rouletteBackContext() {
    const isMyRoulettes = window.location.pathname.startsWith('/my-roulettes');
    return {
        backUrl:   isMyRoulettes ? '/my-roulettes' : '/roulettes',
        backLabel: isMyRoulettes ? '← My Roulettes' : '← Roulettes',
    };
}

// ── Animation toggle (shared across all pages) ────────────────────────

function syncAnimToggles() {
    const on = localStorage.getItem('wl_animation') !== '0';
    document.querySelectorAll('[data-anim-toggle]').forEach(input => {
        input.checked = on;
        const label = input.closest('label');
        if (!label) return;
        const track = label.querySelector('[data-anim-track]');
        const thumb = label.querySelector('[data-anim-thumb]');
        if (track) track.style.backgroundColor = on ? '#c0393a' : 'rgba(255,255,255,0.1)';
        if (thumb) thumb.style.transform = on ? 'translateX(16px)' : 'translateX(0)';
    });
}

document.addEventListener('change', function (e) {
    if (e.target.matches('[data-anim-toggle]')) {
        localStorage.setItem('wl_animation', e.target.checked ? '1' : '0');
        syncAnimToggles();
    }
});

// ── On load: restore back button + hide criteria btn if needed ────────

document.addEventListener('DOMContentLoaded', function () {
    syncAnimToggles();

    // Clear stale roll context when landing on hub/index pages
    const ROLL_CHAIN_BREAKERS = ['/', '/roulettes', '/my-roulettes', '/criteria', '/tv/criteria'];
    if (ROLL_CHAIN_BREAKERS.includes(window.location.pathname)) {
        sessionStorage.removeItem('rollSource');
        sessionStorage.removeItem('rollBackUrl');
        sessionStorage.removeItem('rollBackLabel');
        sessionStorage.removeItem('sharedBatchCards');
    }

    const rollSource = sessionStorage.getItem('rollSource');
    if (rollSource === 'person' || rollSource === 'shared_batch') {
        document.querySelectorAll('.js-criteria-btn').forEach(el => el.classList.add('hidden'));
    }
    const backUrl   = sessionStorage.getItem('rollBackUrl');
    const backLabel = sessionStorage.getItem('rollBackLabel');
    document.querySelectorAll('.js-back-roulettes').forEach(el => {
        if (backUrl) {
            el.href = backUrl;
            el.classList.remove('hidden');
        }
        if (backLabel) el.textContent = backLabel;
    });

    // On shared batch pages, set context on any card click so direct clicks
    // (not just roll animation) carry the ← Shared Batch back button
    if (window.location.pathname.startsWith('/batch/share/')) {
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-batch-card]')) {
                const cards = getBatchCards('.swiper-multiple');
                sessionStorage.setItem('sharedBatchCards', JSON.stringify(cards));
                setRollContext('shared_batch', window.location.href, '← Shared Batch');
                sessionStorage.setItem('rollSource', 'shared_batch');
            }
        });
    }

    // On movie/TV detail pages reached from a shared batch — replace similar section
    // and override Roll to pick from the same batch
    if (rollSource === 'shared_batch') {
        const storedCards = JSON.parse(sessionStorage.getItem('sharedBatchCards') || '[]');
        const currentPath = window.location.pathname;
        const otherCards  = storedCards.filter(c => {
            try { return new URL(c.url, location.origin).pathname !== currentPath; }
            catch { return true; }
        });

        if (otherCards.length) {
            const section = document.getElementById('similar-section');
            if (section) {
                const esc = s => s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
                const items = otherCards.map(c => `
                    <div class="relative flex-shrink-0 w-44 sm:w-52"
                         data-batch-card data-title="${esc(c.title)}" data-rating="${c.rating}"
                         data-poster="${c.poster ? c.poster.replace('https://image.tmdb.org/t/p/w342','') : ''}"
                         data-media-type="${c.media_type}" data-url="${esc(c.url)}">
                        <a href="${esc(c.url)}" class="block group long-movie" data-name="${esc(c.title)}">
                            <div class="card card-hover overflow-hidden">
                                <div class="aspect-[2/3] bg-white/[0.03] overflow-hidden">
                                    ${c.poster ? `<img src="${esc(c.poster)}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" alt="${esc(c.title)}">` : '<div class="w-full h-full"></div>'}
                                </div>
                                <div class="p-2">
                                    <div class="text-xs font-medium text-white truncate">${esc(c.title)}</div>
                                    ${c.rating ? `<div class="text-xs text-gray-500 mt-0.5">★ ${Number(c.rating).toFixed(1)}</div>` : ''}
                                </div>
                            </div>
                        </a>
                    </div>`).join('');

                section.innerHTML = `
                    <div class="section-header">
                        <h2 class="text-xl font-bold text-white mb-3">From This Batch</h2>
                        <div class="section-divider"></div>
                    </div>
                    <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">${items}</div>`;
            }

            // Override Roll button to re-roll from the shared batch
            document.querySelectorAll('[data-roll="movie-criteria"],[data-roll="tv-criteria"]').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    setRollContext('shared_batch', sessionStorage.getItem('rollBackUrl'), '← Shared Batch');
                    rollCards(otherCards.length ? otherCards : storedCards, 'shared_batch');
                });
            });
        }
    }

});

// ── Core roll function ────────────────────────────────────────────────

function getBatchCards(rowSelector) {
    const cards = [];
    document.querySelectorAll(`${rowSelector} [data-batch-card]`).forEach(el => {
        if (!el.dataset.poster) return;
        cards.push({
            url:        el.dataset.url,
            poster:     `https://image.tmdb.org/t/p/w342${el.dataset.poster}`,
            title:      el.dataset.title,
            rating:     parseFloat(el.dataset.rating) || 0,
            media_type: el.dataset.mediaType || 'movie',
        });
    });
    return cards;
}

function trackRoll(mediaType, source, extra = {}) {
    if (typeof gtag === 'undefined') return;
    gtag('event', mediaType === 'tv' ? 'tv_rolled' : 'movie_rolled', { source, ...extra });
}

function rollCards(cards, source) {
    if (!cards.length) return false;
    document.querySelectorAll('.modal-wrap').forEach(m => m.classList.add('hidden'));
    const rollSource = source || sessionStorage.getItem('rollSource');
    if (rollSource) sessionStorage.setItem('rollSource', rollSource);
    const winnerIdx  = Math.floor(Math.random() * cards.length);
    const mediaType  = cards[winnerIdx].media_type || 'movie';
    trackRoll(mediaType, rollSource || 'unknown');
    if (localStorage.getItem('wl_animation') !== '0') {
        runCaseOpening(cards, winnerIdx, cards[winnerIdx].url, mediaType);
    } else {
        window.location.href = cards[winnerIdx].url;
    }
    return true;
}

// ── Criteria form submit → roll animation ─────────────────────────────

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.tagName !== 'FORM') return;

    const btn        = e.submitter;
    const formaction = (btn && btn.getAttribute('formaction')) || form.getAttribute('action') || '';
    const isMovie    = /^\/movie(\?|$)/.test(formaction);
    const isTv       = /^\/tv\/pick(\?|$)/.test(formaction);
    if (!isMovie && !isTv) return;

    e.preventDefault();

    const endpoint  = isMovie ? '/movie/roll/criteria' : '/tv/roll/criteria';
    const fallback  = formaction;
    const moodName  = btn && btn.dataset.mood;
    const rollSource = moodName ? 'mood' : 'criteria';
    setRollContext(rollSource, isMovie ? '/multiple?from=roll' : '/tv/multiple?from=roll', '← Batch');

    if (moodName && typeof gtag !== 'undefined') {
        gtag('event', 'mood_selected', { mood: moodName, media_type: isMovie ? 'movie' : 'tv' });
    } else if (!moodName && typeof gtag !== 'undefined') {
        gtag('event', 'criteria_submitted', { media_type: isMovie ? 'movie' : 'tv' });
    }

    const body = new FormData(form);
    if (btn) { btn.textContent = 'Loading…'; btn.disabled = true; }

    fetch(endpoint, { method: 'POST', body, headers: { 'Accept': 'application/json' } })
        .then(r => {
            if (r.status === 429) throw { throttled: true };
            return r.json();
        })
        .then(movies => {
            if (btn) { btn.textContent = isMovie ? 'Find Movie' : 'Find Show'; btn.disabled = false; }
            if (!rollCards(toCards(movies))) window.location.href = fallback;
        })
        .catch(err => {
            if (err && err.throttled) {
                if (btn) { btn.textContent = isMovie ? 'Find Movie' : 'Find Show'; btn.disabled = false; }
                window.showErrorToast('Rolling too fast — slow down a bit and try again.');
                return;
            }
            window.location.href = fallback;
        });
});

// ── Click delegation ──────────────────────────────────────────────────

document.addEventListener('click', function (e) {

    // Back to Roulettes — clear context on navigate away
    if (e.target.closest('.js-back-roulettes')) {
        sessionStorage.removeItem('rollSource');
        sessionStorage.removeItem('rollBackUrl');
        sessionStorage.removeItem('rollBackLabel');
        return;
    }

    // Roulette card "Batch →" link — set context before navigating
    const batchLink = e.target.closest('[data-roulette-batch]');
    if (batchLink) {
        const { backUrl, backLabel } = rouletteBackContext();
        setRollContext('roulette', backUrl, backLabel);
        return;
    }

    // Roulette card Roll button
    const rouletteBtn = e.target.closest('[data-roulette-roll]');
    if (rouletteBtn) {
        e.preventDefault();
        const slug = rouletteBtn.dataset.slug;
        const orig = rouletteBtn.textContent;
        rouletteBtn.textContent = 'Loading…';
        rouletteBtn.disabled = true;

        fetch(`/roulettes/${slug}/movies`, { headers: { 'Accept': 'application/json' } })
            .then(r => {
                if (r.status === 429) throw { throttled: true };
                return r.json();
            })
            .then(movies => {
                rouletteBtn.textContent = orig;
                rouletteBtn.disabled = false;
                setRollContext('roulette', `/roulettes/${slug}?from=roll`, '← Batch');
                if (typeof gtag !== 'undefined') gtag('event', 'roulette_rolled', { roulette_slug: slug });
                if (!rollCards(toCards(movies))) window.location.href = `/roulettes/${slug}`;
            })
            .catch(err => {
                if (err && err.throttled) {
                    rouletteBtn.textContent = orig;
                    rouletteBtn.disabled = false;
                    window.showErrorToast('Rolling too fast — slow down a bit and try again.');
                    return;
                }
                window.location.href = `/roulettes/${slug}`;
            });
        return;
    }

    // Batch page Roll button — fetch a fresh pick from the server using current session criteria
    const batchRollBtn = e.target.closest('#batch-roll-btn');
    if (batchRollBtn) {
        e.preventDefault();
        const isTv    = batchRollBtn.dataset.mediaType === 'tv';
        const endpoint = isTv ? '/tv/roll/criteria' : '/movie/roll/criteria';
        const fallback = isTv ? '/tv/pick' : '/movie';
        setRollContext('batch', window.location.pathname + '?from=roll', '← Batch');
        fetch(endpoint, { headers: { 'Accept': 'application/json' } })
            .then(r => {
                if (r.status === 429) throw { throttled: true };
                return r.json();
            })
            .then(movies => { if (!rollCards(toCards(movies))) window.location.href = fallback; })
            .catch(err => {
                if (err && err.throttled) { window.showErrorToast('Rolling too fast — slow down a bit and try again.'); return; }
                window.location.href = fallback;
            });
        return;
    }

    // Shared batch Roll — picks from cards already on the page, back goes to shared batch URL
    const sharedRollBtn = e.target.closest('#shared-batch-roll-btn');
    if (sharedRollBtn) {
        e.preventDefault();
        const cards = getBatchCards('.swiper-multiple');
        if (!cards.length) return;
        sessionStorage.setItem('sharedBatchCards', JSON.stringify(cards));
        setRollContext('shared_batch', window.location.href, '← Shared Batch');
        rollCards(cards, 'shared_batch');
        return;
    }

    // Any [data-roll] or homepage single pick buttons
    const link = e.target.closest('a[href]');
    if (!link) return;
    const href     = link.getAttribute('href');
    const rollType = link.dataset.roll
        || (href === '/movie?i=new' ? 'movie' : null)
        || (href === '/tv/pick?i=new' ? 'tv' : null);

    const endpoints = {
        'movie':          '/movie/roll',
        'tv':             '/tv/roll',
        'movie-criteria': '/movie/roll/criteria',
        'tv-criteria':    '/tv/roll/criteria',
    };

    const isPersonRoll = rollType === 'person-movie' || rollType === 'person-tv';
    const jsonUrl = isPersonRoll ? link.dataset.jsonUrl : (rollType ? endpoints[rollType] : null);

    if (jsonUrl) {
        e.preventDefault();
        const fallback = link.href;
        if (isPersonRoll) {
            setRollContext('person', window.location.href, link.dataset.backLabel || '← Back');
        } else {
            const batchUrl = (rollType === 'tv' || rollType === 'tv-criteria') ? '/tv/multiple?from=roll' : '/multiple?from=roll';
            const src = (rollType === 'movie' || rollType === 'tv') ? 'homepage' : 'batch';
            setRollContext(src, batchUrl, '← Batch');
        }

        const origText = link.textContent;
        link.textContent = 'Loading…';

        fetch(jsonUrl, { headers: { 'Accept': 'application/json' } })
            .then(r => {
                if (r.status === 429) throw { throttled: true };
                return r.json();
            })
            .then(movies => {
                link.textContent = origText;
                if (!rollCards(toCards(movies))) window.location.href = fallback;
            })
            .catch(err => {
                if (err && err.throttled) {
                    link.textContent = origText;
                    window.showErrorToast('Rolling too fast — slow down a bit and try again.');
                    return;
                }
                window.location.href = fallback;
            });
        return;
    }

});

// ── Pick Together — create collab session and redirect ────────────────
$(document).on('click', '#collab-start-btn', function () {
    const btn       = $(this);
    const mediaType = btn.data('media-type') || 'movie';
    btn.text('Creating…').prop('disabled', true);

    const movies = getBatchCards('.swiper-multiple').map(c => {
        const idMatch = (c.url || '').match(/\/(?:movie|tv)\/(\d+)/);
        return {
            id:           idMatch ? parseInt(idMatch[1]) : 0,
            poster_path:  (c.poster || '').replace(/https:\/\/image\.tmdb\.org\/t\/p\/w\d+/, ''),
            title:        c.title,
            name:         c.title,
            vote_average: c.rating,
            media_type:   c.media_type,
        };
    }).filter(m => m.id > 0);

    $.ajax({
        url:         '/batch/collab',
        method:      'POST',
        contentType: 'application/json',
        headers:     { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data:        JSON.stringify({ movies, media_type: mediaType, criteria: window.batchCriteria ?? {} }),
        success:     function (data) {
            if (data.token) window.location.href = '/batch/collab/' + data.token;
        },
        error: function () {
            btn.text('Pick Together').prop('disabled', false);
            window.showErrorToast('Could not start session.');
        },
    });
});
