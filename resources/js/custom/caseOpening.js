const CARD_W    = 140;
const CARD_H    = 200;
const CARD_GAP  = 8;
const STEP      = CARD_W + CARD_GAP;
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

// TV scores skew higher — use stricter thresholds
const TIERS_TV = [
    { min: 9.0, color: '#f59e0b', label: 'Masterpiece' },
    { min: 8.2, color: '#c0393a', label: 'Excellent'   },
    { min: 7.5, color: '#8b5cf6', label: 'Great'       },
    { min: 6.5, color: '#3b82f6', label: 'Good'        },
    { min: 0,   color: '#6b7280', label: 'Mixed'       },
];

export function getTier(rating, mediaType = 'movie') {
    const table = mediaType === 'tv' ? TIERS_TV : TIERS;
    return table.find(t => (rating || 0) >= t.min) || table[table.length - 1];
}

export function runCaseOpening(cards, winnerIdx, fullUrl, mediaType = 'movie', onComplete = null) {
    // Single card: no animation needed
    if (cards.length <= 1) {
        if (onComplete) { onComplete(); } else { window.showProgress?.(); window.location.href = fullUrl; }
        return;
    }

    const winner     = cards[winnerIdx];
    const nonWinners = cards.filter((_, i) => i !== winnerIdx);

    // Always use the full card pool so the winner can appear as fake-outs
    const pool = [];
    while (pool.length < STRIP_LEN) {
        pool.push(...[...cards].sort(() => Math.random() - 0.5));
    }
    const strip = pool.slice(0, STRIP_LEN);
    strip[WINNER_POS] = winner;

    // Winner must not be its own immediate neighbour at the landing position
    for (const offset of [-1, 1]) {
        const pos = WINNER_POS + offset;
        if (pos >= 0 && pos < strip.length && strip[pos] === winner) {
            strip[pos] = nonWinners[Math.floor(Math.random() * nonWinners.length)];
        }
    }

    const overlay   = document.getElementById('case-overlay');
    const stripEl   = document.getElementById('case-strip');
    const titleEl   = document.getElementById('case-winner-title');
    const tierEl    = document.getElementById('case-winner-tier');
    const raysEl    = document.getElementById('case-rays');
    const raysInner = document.getElementById('case-rays-inner');
    const glowEl    = document.getElementById('case-glow');
    const viewport  = document.getElementById('case-viewport');

    stripEl.innerHTML = '';
    strip.forEach((card, i) => {
        const tier = getTier(card.rating, card.media_type || mediaType);
        const el = document.createElement('div');
        el.className = 'case-card flex-shrink-0 rounded-lg overflow-hidden relative';
        el.style.cssText = `width:${CARD_W}px;height:${CARD_H}px;box-shadow:0 0 10px 2px ${tier.color}44;outline:1px solid ${tier.color}55`;
        if (i === WINNER_POS) el.id = 'case-winner-card';
        const score = card.rating ? Number(card.rating).toFixed(1) : null;
        el.innerHTML = `
            <img src="${card.poster}" alt="" class="w-full h-full object-cover" loading="eager">
            <div style="position:absolute;inset:0;background:linear-gradient(to top,${tier.color}cc 0%,${tier.color}33 35%,transparent 65%);pointer-events:none"></div>
            ${score ? `<div style="position:absolute;top:5px;left:6px;background:rgba(0,0,0,0.7);border-radius:4px;padding:2px 6px">
                <span style="font-size:12px;font-weight:600;color:#c0393a">★ ${score}</span>
            </div>` : ''}
        `;
        stripEl.appendChild(el);
    });

    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    titleEl.classList.add('opacity-0');
    tierEl.classList.add('opacity-0');
    titleEl.textContent  = '';
    tierEl.textContent   = '';
    raysEl.style.opacity = '0';
    glowEl.style.opacity = '0';

    const cw     = viewport.clientWidth;
    const center = cw / 2;
    const startX  = center - (START_POS  * STEP + CARD_W / 2);
    const centerX = center - (WINNER_POS * STEP + CARD_W / 2);
    const landOffset = Math.floor(Math.random() * (CARD_W - 20)) - (CARD_W - 20) / 2;
    const landX   = centerX + landOffset;

    const prefetchLink = document.createElement('link');
    prefetchLink.rel  = 'prefetch';
    prefetchLink.href = fullUrl;
    document.head.appendChild(prefetchLink);

    stripEl.style.transition = 'none';
    stripEl.style.transform  = `translateX(${startX}px)`;

    requestAnimationFrame(() => requestAnimationFrame(() => {
        stripEl.style.transition = 'transform 7s cubic-bezier(0.12, 0.9, 0.1, 1)';
        stripEl.style.transform  = `translateX(${landX}px)`;
    }));

    setTimeout(() => {
        const tier     = getTier(winner.rating, winner.media_type || mediaType);
        const winnerEl = document.getElementById('case-winner-card');

        stripEl.style.transition = 'transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        stripEl.style.transform  = `translateX(${centerX}px)`;

        if (winnerEl) {
            winnerEl.style.outline    = `2px solid ${tier.color}`;
            winnerEl.style.boxShadow  = `0 0 32px 10px ${tier.color}66`;
            winnerEl.style.transform  = 'scale(1.06)';
            winnerEl.style.transition = 'transform 0.25s ease, box-shadow 0.25s ease';
        }

        raysInner.style.background = `repeating-conic-gradient(from 0deg at 50% 50%,${tier.color}55 0deg 7deg,transparent 7deg 20deg)`;
        raysInner.style.maskImage        = 'radial-gradient(circle, white 15%, transparent 68%)';
        raysInner.style.webkitMaskImage  = 'radial-gradient(circle, white 15%, transparent 68%)';
        raysEl.style.transition = 'opacity 0.9s ease';
        raysEl.style.opacity    = '1';

        glowEl.style.background = `radial-gradient(circle, ${tier.color}50 0%, transparent 70%)`;
        glowEl.style.transition = 'opacity 0.9s ease';
        glowEl.style.opacity    = '1';

        tierEl.textContent = tier.label;
        tierEl.style.color = tier.color;
        tierEl.classList.remove('opacity-0');
        titleEl.textContent = winner.title;
        titleEl.classList.remove('opacity-0');
    }, 7050);

    setTimeout(() => {
        document.body.style.overflow = '';
        if (onComplete) {
            overlay.classList.add('hidden');
            onComplete();
        } else {
            window.location.href = fullUrl;
        }
    }, 8500);

    document.addEventListener('keydown', function onEsc(e) {
        if (e.key === 'Escape') {
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
            document.removeEventListener('keydown', onEsc);
        }
    });
}
