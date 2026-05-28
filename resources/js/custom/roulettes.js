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
    }

    const rollSource = sessionStorage.getItem('rollSource');
    if (rollSource === 'person') {
        document.querySelectorAll('.js-criteria-btn').forEach(el => el.classList.add('hidden'));
    }
    const backUrl   = sessionStorage.getItem('rollBackUrl');
    const backLabel = sessionStorage.getItem('rollBackLabel');
    document.querySelectorAll('.js-back-roulettes').forEach(el => {
        if (backUrl)   el.href        = backUrl;
        if (backLabel) el.textContent = backLabel;
    });
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

function rollCards(cards, source) {
    if (!cards.length) return false;
    document.querySelectorAll('.modal-wrap').forEach(m => m.classList.add('hidden'));
    const rollSource = source || sessionStorage.getItem('rollSource');
    if (rollSource) sessionStorage.setItem('rollSource', rollSource);
    const winnerIdx  = Math.floor(Math.random() * cards.length);
    const mediaType  = cards[winnerIdx].media_type || 'movie';
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

    const endpoint = isMovie ? '/movie/roll/criteria' : '/tv/roll/criteria';
    const fallback = formaction;
    setRollContext('batch', isMovie ? '/multiple?from=roll' : '/tv/multiple?from=roll', '← Batch');

    const body = new FormData(form);
    if (btn) { btn.textContent = 'Loading…'; btn.disabled = true; }

    fetch(endpoint, { method: 'POST', body })
        .then(r => r.json())
        .then(movies => {
            if (btn) { btn.textContent = isMovie ? 'Find Movie' : 'Find Show'; btn.disabled = false; }
            if (!rollCards(toCards(movies))) window.location.href = fallback;
        })
        .catch(() => { window.location.href = fallback; });
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

        fetch(`/roulettes/${slug}/movies`)
            .then(r => r.json())
            .then(movies => {
                rouletteBtn.textContent = orig;
                rouletteBtn.disabled = false;
                setRollContext('roulette', `/roulettes/${slug}?from=roll`, '← Batch');
                if (!rollCards(toCards(movies))) window.location.href = `/roulettes/${slug}`;
            })
            .catch(() => { window.location.href = `/roulettes/${slug}`; });
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
        fetch(endpoint)
            .then(r => r.json())
            .then(movies => { if (!rollCards(toCards(movies))) window.location.href = fallback; })
            .catch(() => { window.location.href = fallback; });
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

    const isPersonRoll  = rollType === 'person-movie' || rollType === 'person-tv';
    const isKeywordRoll = rollType === 'keyword-movie' || rollType === 'keyword-tv';
    const jsonUrl = (isPersonRoll || isKeywordRoll) ? link.dataset.jsonUrl : (rollType ? endpoints[rollType] : null);

    if (jsonUrl) {
        e.preventDefault();
        const fallback = link.href;
        if (isPersonRoll) {
            setRollContext('person', window.location.href, link.dataset.backLabel || '← Back');
        } else if (isKeywordRoll) {
            const batchUrl = rollType === 'keyword-tv' ? '/tv/multiple?from=roll' : '/multiple?from=roll';
            setRollContext('batch', batchUrl, '← Batch');
        } else {
            const batchUrl = (rollType === 'tv' || rollType === 'tv-criteria') ? '/tv/multiple?from=roll' : '/multiple?from=roll';
            setRollContext('batch', batchUrl, '← Batch');
        }

        const origText = link.textContent;
        link.textContent = 'Loading…';

        fetch(jsonUrl)
            .then(r => r.json())
            .then(resp => {
                link.textContent = origText;
                const movies = isKeywordRoll ? resp.cards : resp;
                if (isKeywordRoll && resp._debug) console.log('[keyword roll]', resp._debug);
                if (!rollCards(toCards(movies))) window.location.href = fallback;
            })
            .catch(() => { window.location.href = fallback; });
        return;
    }
});
