import { runCaseOpening } from './caseOpening.js';

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

function rollCards(cards) {
    if (!cards.length) return false;
    const winnerIdx = Math.floor(Math.random() * cards.length);
    runCaseOpening(cards, winnerIdx, cards[winnerIdx].url);
    return true;
}

document.addEventListener('click', function (e) {

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

                if (!rollCards(cards)) window.location.href = `/roulettes/${slug}`;
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

    // ── Homepage single pick buttons ──────────────────────────────
    const link = e.target.closest('a[href]');
    if (!link) return;
    const href = link.getAttribute('href');

    if (href === '/movie?i=new' || href === '/tv/pick?i=new') {
        e.preventDefault();
        const endpoint = href === '/movie?i=new' ? '/movie/roll' : '/tv/roll';

        fetch(endpoint)
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
                if (!rollCards(cards)) window.location.href = href;
            })
            .catch(() => { window.location.href = href; });
        return;
    }
});
