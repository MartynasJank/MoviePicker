import { runCaseOpening } from './caseOpening.js';

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

document.addEventListener('DOMContentLoaded', function () {
    syncAnimToggles();
    const rollSource = sessionStorage.getItem('rollSource');
    if (rollSource === 'roulette' || rollSource === 'person') {
        document.querySelectorAll('.js-criteria-btn').forEach(el => el.classList.add('hidden'));
        const backUrl   = sessionStorage.getItem('rollBackUrl')   || '/roulettes';
        const backLabel = sessionStorage.getItem('rollBackLabel') || '← Roulettes';
        document.querySelectorAll('.js-back-roulettes').forEach(el => {
            el.href = backUrl;
            el.textContent = backLabel;
            el.classList.remove('hidden');
        });
    }
});

function getBatchCards(rowSelector) {
    const cards = [];
    document.querySelectorAll(`${rowSelector} [data-batch-card]`).forEach(el => {
        if (!el.dataset.poster) return;
        cards.push({
            url:    el.dataset.url,
            poster: `https://image.tmdb.org/t/p/w342${el.dataset.poster}`,
            title:  el.dataset.title,
            rating: parseFloat(el.dataset.rating) || 0,
        });
    });
    return cards;
}

function rollCards(cards, source) {
    if (!cards.length) return false;
    document.querySelectorAll('.modal-wrap').forEach(m => m.classList.add('hidden'));
    const rollSource = source || sessionStorage.getItem('rollSource');
    if (rollSource) sessionStorage.setItem('rollSource', rollSource);
    const winnerIdx = Math.floor(Math.random() * cards.length);
    if (localStorage.getItem('wl_animation') !== '0') {
        runCaseOpening(cards, winnerIdx, cards[winnerIdx].url);
    } else {
        window.location.href = cards[winnerIdx].url;
    }
    return true;
}

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.tagName !== 'FORM') return;

    const btn        = e.submitter;
    const formaction = (btn && btn.getAttribute('formaction')) || form.getAttribute('action') || '';
    const isMovie    = /^\/movie(\?|$)/.test(formaction);
    const isTv       = /^\/tv\/pick(\?|$)/.test(formaction);
    if (!isMovie && !isTv) return;

    e.preventDefault();

    const endpoint = isMovie ? '/movie/roll/criteria' : '/tv/roll/criteria';
    const fallback = formaction;
    sessionStorage.removeItem('rollSource');
    sessionStorage.removeItem('rollBackUrl');
    sessionStorage.removeItem('rollBackLabel');
    const body = new FormData(form);

    if (btn) { btn.textContent = 'Loading…'; btn.disabled = true; }

    fetch(endpoint, { method: 'POST', body })
        .then(r => r.json())
        .then(movies => {
            if (btn) { btn.textContent = isMovie ? 'Find Movie' : 'Find Show'; btn.disabled = false; }
            const cards = movies
                .filter(m => m.poster_path)
                .map(m => ({
                    url:    m.url,
                    poster: `https://image.tmdb.org/t/p/w342${m.poster_path}`,
                    title:  m.title,
                    rating: m.vote_average,
                }));
            if (!rollCards(cards)) window.location.href = fallback;
        })
        .catch(() => { window.location.href = fallback; });
});

document.addEventListener('click', function (e) {

    // ── Back to Roulettes ─────────────────────────────────────────
    if (e.target.closest('.js-back-roulettes')) {
        sessionStorage.removeItem('rollSource');
        sessionStorage.removeItem('rollBackUrl');
        sessionStorage.removeItem('rollBackLabel');
        return;
    }

    // ── Roulette card "Batch →" link ──────────────────────────────
    const batchLink = e.target.closest('[data-roulette-batch]');
    if (batchLink) {
        const isMyRoulettes = window.location.pathname.startsWith('/my-roulettes');
        sessionStorage.setItem('rollSource',    'roulette');
        sessionStorage.setItem('rollBackUrl',   isMyRoulettes ? '/my-roulettes' : '/roulettes');
        sessionStorage.setItem('rollBackLabel', isMyRoulettes ? '← My Roulettes' : '← Roulettes');
        return;
    }

    // ── Roulette card Roll button ─────────────────────────────────
    const rouletteBtn = e.target.closest('[data-roulette-roll]');
    if (rouletteBtn) {
        e.preventDefault();
        const slug = rouletteBtn.dataset.slug;
        const orig = rouletteBtn.textContent;
        rouletteBtn.textContent = 'Loading…';
        rouletteBtn.disabled = true;

        fetch(`/roulettes/${slug}/movies`)
            .then(r => r.json())
            .then(movies => {
                const cards = movies
                    .filter(m => m.poster_path)
                    .map(m => ({
                        url:    m.url,
                        poster: `https://image.tmdb.org/t/p/w342${m.poster_path}`,
                        title:  m.title,
                        rating: m.vote_average,
                    }));

                rouletteBtn.textContent = orig;
                rouletteBtn.disabled = false;

                const isMyRoulettes = window.location.pathname.startsWith('/my-roulettes');
                sessionStorage.setItem('rollBackUrl',   isMyRoulettes ? '/my-roulettes' : '/roulettes');
                sessionStorage.setItem('rollBackLabel', isMyRoulettes ? '← My Roulettes' : '← Roulettes');
                if (!rollCards(cards, 'roulette')) window.location.href = `/roulettes/${slug}`;
            })
            .catch(() => { window.location.href = `/roulettes/${slug}`; });
        return;
    }

    // ── Batch page Roll button ────────────────────────────────────
    if (e.target.closest('#batch-roll-btn')) {
        e.preventDefault();
        rollCards(getBatchCards('.swiper-multiple'));
        return;
    }

    // ── Any [data-roll] or homepage single pick buttons ───────────
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
            sessionStorage.setItem('rollSource',    'person');
            sessionStorage.setItem('rollBackUrl',   window.location.href);
            sessionStorage.setItem('rollBackLabel', link.dataset.backLabel || '← Back');
        } else if (rollType === 'movie' || rollType === 'tv') {
            sessionStorage.removeItem('rollSource');
            sessionStorage.removeItem('rollBackUrl');
            sessionStorage.removeItem('rollBackLabel');
        }

        const origText = link.textContent;
        link.textContent = 'Loading…';

        fetch(jsonUrl)
            .then(r => r.json())
            .then(movies => {
                link.textContent = origText;
                const cards = movies
                    .filter(m => m.poster_path)
                    .map(m => ({
                        url:    m.url,
                        poster: `https://image.tmdb.org/t/p/w342${m.poster_path}`,
                        title:  m.title,
                        rating: m.vote_average,
                    }));
                if (!rollCards(cards)) window.location.href = fallback;
            })
            .catch(() => { window.location.href = fallback; });
        return;
    }
});
