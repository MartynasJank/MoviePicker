function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// ── State ─────────────────────────────────────────────────────────────
const SESSION_KEY = 'swipe_session';

if (new URLSearchParams(window.location.search).get('reset') === '1') {
    sessionStorage.removeItem(SESSION_KEY);
    localStorage.removeItem('swipe_liked');
}

const saved    = JSON.parse(sessionStorage.getItem(SESSION_KEY) || 'null');
let queue      = saved?.queue?.length ? saved.queue : [...window.swipeMovies];
let seenPages  = saved?.seenPages || [window.swipePage];
let history    = [];
let liked      = JSON.parse(localStorage.getItem('swipe_liked') || '[]');
let fetching   = false;
let animating  = false;
const isLoggedIn = window.swipeLoggedIn;
const THRESHOLD  = 90;

function saveSession() {
    sessionStorage.setItem(SESSION_KEY, JSON.stringify({ queue, seenPages }));
}

// ── DOM refs ──────────────────────────────────────────────────────────
const stack          = document.getElementById('card-stack');
const overlayLike    = document.getElementById('overlay-like');
const overlaySkip    = document.getElementById('overlay-skip');
const resultsOverlay = document.getElementById('results-overlay');
const resultsGrid    = document.getElementById('results-grid');

function updateEndBtn() {
    const badge = document.getElementById('liked-badge');
    if (liked.length > 0) {
        badge.textContent = liked.length > 9 ? '9+' : liked.length;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

// ── Build card element ────────────────────────────────────────────────
function buildCard(movie) {
    const title = movie.title ?? movie.name ?? '';
    const year  = (movie.release_date || movie.first_air_date || '').slice(0, 4);
    const isTv  = movie.media_type === 'tv';
    const url   = (isTv ? '/tv/' : '/movie/') + movie.id;

    const el = document.createElement('div');
    el.className = 'swipe-card absolute inset-0 rounded-2xl overflow-hidden shadow-2xl bg-[#1a1a1a] cursor-grab active:cursor-grabbing select-none';
    el.dataset.id = movie.id;
    el.style.cssText = 'touch-action:none;will-change:transform;';
    el.innerHTML = `
        <div class="absolute inset-0">
            ${movie.poster_path
                ? `<img src="https://image.tmdb.org/t/p/w500${movie.poster_path}" class="w-full h-full object-cover" draggable="false">`
                : `<div class="w-full h-full bg-white/5 flex items-center justify-center text-gray-600 text-sm px-4 text-center">${title}</div>`}
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/10 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-5">
                <a href="${url}" target="_blank" rel="noopener" class="block" onclick="event.stopPropagation()">
                    <h2 class="text-2xl font-bold text-white leading-tight">${title}</h2>
                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                        ${year ? `<span class="text-sm text-gray-300">${year}</span>` : ''}
                        ${movie.vote_average ? `<span class="text-sm text-gray-300">★ ${Number(movie.vote_average).toFixed(1)}</span>` : ''}
                    </div>
                    ${movie.genres ? `<div class="flex flex-wrap gap-1 mt-2">${movie.genres.split(', ').map(g => `<span class="text-xs bg-white/15 text-white/80 px-2 py-0.5 rounded-full">${g}</span>`).join('')}</div>` : ''}
                </a>
            </div>
        </div>`;
    return el;
}

// ── Initial render ────────────────────────────────────────────────────
function renderStack() {
    stack.innerHTML = '';
    if (queue.length === 0) { showEmpty(); return; }

    if (queue.length > 1) {
        const behind = buildCard(queue[1]);
        behind.style.cssText += 'z-index:1;pointer-events:none;transform:scale(0.94) translateY(10px);';
        stack.appendChild(behind);
    }

    const top = buildCard(queue[0]);
    top.style.zIndex = '2';
    stack.appendChild(top);
    attachDrag(top);

    if (queue.length <= 3 && !fetching) prefetch();
}

// ── Advance stack after swipe (no full rebuild) ───────────────────────
function advanceStack() {
    queue.shift();
    saveSession();

    // Remove the old top card (already animated away)
    const oldTop = stack.querySelector('.swipe-card:last-child');
    if (oldTop) oldTop.remove();

    // Trigger prefetch early enough
    if (queue.length <= 5 && !fetching) prefetch();

    if (queue.length === 0) {
        showLoading();
        return;
    }

    // Animate the behind card (now first child) forward to become the new top
    const newTop = stack.querySelector('.swipe-card');
    if (newTop) {
        newTop.style.transition    = 'transform 0.25s ease-out';
        newTop.style.transform     = 'scale(1) translateY(0)';
        newTop.style.zIndex        = '2';
        newTop.style.pointerEvents = '';
        attachDrag(newTop);
    }

    // Add a new behind card if there's one more in queue
    if (queue.length > 1) {
        const behind = buildCard(queue[1]);
        behind.style.cssText += 'z-index:1;pointer-events:none;transform:scale(0.94) translateY(10px);opacity:0;';
        stack.insertBefore(behind, newTop);
        requestAnimationFrame(() => {
            behind.style.transition = 'opacity 0.2s ease';
            behind.style.opacity    = '1';
        });
    }
}

// ── Undo entrance (fly card back in from the side) ────────────────────
function renderUndo(movie, direction) {
    // After queue.unshift(), queue[0]=undone, queue[1]=what was top
    // DOM should end up as: [behind(queue[1]), top(undone)]
    // Remove any stale behind card so we don't accumulate extras
    const cards = stack.querySelectorAll('.swipe-card');
    if (cards.length >= 2) {
        // More than one card — remove all but the last (current top)
        for (let i = 0; i < cards.length - 1; i++) cards[i].remove();
    }

    // Demote current top to behind position
    const currentTop = stack.querySelector('.swipe-card:last-child');
    if (currentTop) {
        currentTop.style.transition    = 'transform 0.25s ease-out';
        currentTop.style.transform     = 'scale(0.94) translateY(10px)';
        currentTop.style.zIndex        = '1';
        currentTop.style.pointerEvents = 'none';
    }

    // Fly the undone card back in as new top
    const card = buildCard(movie);
    const startX = direction * (window.innerWidth + 200);
    card.style.zIndex     = '2';
    card.style.transform  = `translateX(${startX}px) rotate(${direction * 20}deg)`;
    card.style.transition = 'none';
    stack.appendChild(card);
    attachDrag(card);
    requestAnimationFrame(() => {
        card.style.transition = 'transform 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        card.style.transform  = 'translateX(0) rotate(0deg)';
    });
}

function getTopCard() {
    return stack.querySelector('.swipe-card:last-child');
}

// ── Drag logic ────────────────────────────────────────────────────────
function attachDrag(card) {
    if (card.dataset.dragAttached) return;
    card.dataset.dragAttached = '1';
    let startX = 0, startY = 0, dx = 0;
    let active = false;

    const onStart = (e) => {
        if (animating) return;
        active = true;
        const pt = e.touches ? e.touches[0] : e;
        startX = pt.clientX;
        startY = pt.clientY;
        card.style.transition = 'none';
    };

    const onMove = (e) => {
        if (!active) return;
        e.preventDefault();
        const pt = e.touches ? e.touches[0] : e;
        dx = pt.clientX - startX;
        const dy = pt.clientY - startY;
        const rotate = dx * 0.08;
        card.style.transform = `translate(${dx}px, ${dy * 0.3}px) rotate(${rotate}deg)`;

        const ratio = Math.min(Math.abs(dx) / THRESHOLD, 1);
        if (dx > 0) {
            overlayLike.style.opacity = ratio;
            overlaySkip.style.opacity = 0;
        } else {
            overlaySkip.style.opacity = ratio;
            overlayLike.style.opacity = 0;
        }
    };

    const onEnd = () => {
        if (!active) return;
        active = false;
        overlayLike.style.opacity = 0;
        overlaySkip.style.opacity = 0;

        if (Math.abs(dx) >= THRESHOLD) {
            dx > 0 ? triggerLike(card) : triggerSkip(card);
        } else {
            // Snap back
            card.style.transition = 'transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            card.style.transform  = 'translate(0,0) rotate(0deg)';
        }
        dx = 0;
    };

    card.addEventListener('pointerdown', onStart);
    card.addEventListener('pointermove', onMove, { passive: false });
    card.addEventListener('pointerup', onEnd);
    card.addEventListener('pointercancel', onEnd);
}

// ── Undo button state ─────────────────────────────────────────────────
function updateUndo() {
    const btn = document.getElementById('btn-undo');
    btn.disabled = history.length === 0;
    btn.classList.toggle('text-gray-500', history.length === 0);
    btn.classList.toggle('text-yellow-400', history.length > 0);
}

// ── Actions ───────────────────────────────────────────────────────────
function triggerLike(card) {
    const movie = queue[0];
    history.push({ movie, action: 'like' });
    flyOut(card, 1, () => { advanceStack(); updateUndo(); });
    saveLike(movie);
}

function triggerSkip(card) {
    const movie = queue[0];
    history.push({ movie, action: 'skip' });
    flyOut(card, -1, () => { advanceStack(); updateUndo(); });
}

function triggerUndo() {
    if (history.length === 0 || animating) return;
    const last = history.pop();
    queue.unshift(last.movie);

    if (last.action === 'like') {
        liked = liked.filter(m => m.id !== last.movie.id);
        localStorage.setItem('swipe_liked', JSON.stringify(liked));
        updateEndBtn();
        if (isLoggedIn) {
            const isTv = last.movie.media_type === 'tv';
            const year = (last.movie.release_date || last.movie.first_air_date || '').slice(0, 4);
            fetch('/watchlist/toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({
                    tmdb_id: last.movie.id, title: last.movie.title ?? last.movie.name ?? '',
                    poster_path: last.movie.poster_path, year: year ? parseInt(year) : null,
                    genres: last.movie.genres ?? '', vote_average: last.movie.vote_average,
                    type: isTv ? 'tv' : 'movie',
                }),
            }).catch(() => {});
        }
    }

    saveSession();
    renderUndo(last.movie, last.action === 'like' ? 1 : -1);
    updateUndo();
}

function flyOut(card, direction, cb) {
    animating = true;
    const x = direction * (window.innerWidth + 200);
    card.style.transition = 'transform 0.35s ease-in, opacity 0.35s ease-in';
    card.style.transform  = `translate(${x}px, 20px) rotate(${direction * 30}deg)`;
    card.style.opacity    = '0';
    setTimeout(() => { animating = false; cb(); }, 360);
}

// ── Like / save ───────────────────────────────────────────────────────
function saveLike(movie) {
    const already = liked.find(m => m.id === movie.id);
    if (!already) {
        liked.push(movie);
        localStorage.setItem('swipe_liked', JSON.stringify(liked));
    }
    updateEndBtn();

    if (isLoggedIn) {
        const isTv = movie.media_type === 'tv';
        const year = (movie.release_date || movie.first_air_date || '').slice(0, 4);
        fetch('/watchlist/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({
                tmdb_id:      movie.id,
                title:        movie.title ?? movie.name ?? '',
                poster_path:  movie.poster_path,
                year:         year ? parseInt(year) : null,
                genres:       movie.genres ?? '',
                vote_average: movie.vote_average,
                type:         isTv ? 'tv' : 'movie',
                source:       'swipe',
            }),
        }).catch(() => {});
    }
}

// ── Prefetch ──────────────────────────────────────────────────────────
function prefetch() {
    fetching = true;
    fetch('/swipe/next', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ seen_pages: seenPages }),
    })
        .then(r => r.json())
        .then(data => {
            if (data.movies?.length) {
                const wasEmpty = queue.length === 0;
                queue.push(...data.movies);
                seenPages.push(data.page);
                saveSession();
                if (wasEmpty) {
                    stack.innerHTML = '';
                    renderStack();
                }
            } else {
                showEmpty();
            }
        })
        .catch(() => showEmpty())
        .finally(() => { fetching = false; });
}

// ── Empty / loading states ────────────────────────────────────────────
function showLoading() {
    stack.innerHTML = `<div class="absolute inset-0 flex items-center justify-center">
        <div class="text-gray-600 text-sm">Loading more movies…</div>
    </div>`;
}

function showEmpty() {
    stack.innerHTML = `<div class="absolute inset-0 flex flex-col items-center justify-center gap-4 px-6 text-center">
        <p class="text-gray-400 text-sm">No movies found for these filters</p>
        <p class="text-gray-600 text-xs">Try adjusting your criteria</p>
        <button id="empty-criteria-btn" class="btn-secondary text-sm px-4 py-2">Change Criteria</button>
    </div>`;
    document.getElementById('empty-criteria-btn')?.addEventListener('click', () => {
        document.querySelector('#modal-form')?.classList.remove('hidden');
    });
}

// ── Results overlay ───────────────────────────────────────────────────
function renderResults() {
    resultsGrid.innerHTML = liked.length ? liked.map(movie => {
        const isTv  = movie.media_type === 'tv';
        const url   = (isTv ? '/tv/' : '/movie/') + movie.id;
        const title = movie.title ?? movie.name ?? '';
        const year  = (movie.release_date || movie.first_air_date || '').slice(0, 4);
        return `
            <a href="${url}" target="_blank" rel="noopener" class="block rounded-2xl overflow-hidden bg-[#1a1a1a] relative select-none">
                <div class="aspect-[2/3] relative">
                    ${movie.poster_path
                        ? `<img src="https://image.tmdb.org/t/p/w342${movie.poster_path}" class="w-full h-full object-cover">`
                        : `<div class="w-full h-full bg-white/5 flex items-center justify-center text-gray-600 text-xs p-2 text-center">${esc(title)}</div>`}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/10 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-2">
                        <p class="text-xs font-bold text-white leading-tight">${esc(title)}</p>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            ${year ? `<span class="text-[10px] text-gray-400">${year}</span>` : ''}
                            ${movie.vote_average ? `<span class="text-[10px] text-gray-400">★ ${Number(movie.vote_average).toFixed(1)}</span>` : ''}
                        </div>
                        ${movie.genres ? `<div class="flex flex-wrap gap-0.5 mt-1">${movie.genres.split(', ').slice(0, 2).map(g => `<span class="text-[9px] bg-white/15 text-white/80 px-1.5 py-0.5 rounded-full">${esc(g)}</span>`).join('')}</div>` : ''}
                    </div>
                </div>
            </a>`;
    }).join('') : '<p class="col-span-3 text-center text-gray-500 text-sm pt-8">No liked movies yet</p>';
}

function showResults() {
    renderResults();
    resultsOverlay.classList.remove('hidden');
}

// ── Button handlers ───────────────────────────────────────────────────
document.getElementById('btn-like').addEventListener('click', () => {
    if (animating || queue.length === 0) return;
    triggerLike(getTopCard());
});

document.getElementById('btn-skip').addEventListener('click', () => {
    if (animating || queue.length === 0) return;
    triggerSkip(getTopCard());
});

document.getElementById('btn-undo').addEventListener('click', triggerUndo);
document.getElementById('swipe-end-btn').addEventListener('click', showResults);
document.getElementById('results-close').addEventListener('click', () => resultsOverlay.classList.add('hidden'));
document.getElementById('results-continue').addEventListener('click', () => resultsOverlay.classList.add('hidden'));
document.getElementById('results-end').addEventListener('click', () => {
    liked = [];
    localStorage.removeItem('swipe_liked');
    sessionStorage.removeItem(SESSION_KEY);
    window.location.href = '/';
});

// ── Hijack criteria modal for swipe ──────────────────────────────────
const swipeCriteriaForm = document.getElementById('modal-criteria');
if (swipeCriteriaForm) {
    // Change action so roulettes.js doesn't trigger case-opening animation
    swipeCriteriaForm.action = '/swipe/load';

    // Replace footer buttons with single Load button
    const modalFooter = swipeCriteriaForm.querySelector('.modal-sticky-footer');
    if (modalFooter) {
        const resetBtn = modalFooter.querySelector('#modal-btn-reset');
        modalFooter.innerHTML = '';
        if (resetBtn) modalFooter.appendChild(resetBtn);
        const loadBtn = document.createElement('button');
        loadBtn.type = 'submit';
        loadBtn.className = 'btn-accent text-sm';
        loadBtn.textContent = 'Load Movies';
        modalFooter.appendChild(loadBtn);
    }
}

// ── Intercept criteria form submit ────────────────────────────────────
document.addEventListener('submit', async (e) => {
    const form = e.target.closest('#modal-criteria');
    if (!form) return;
    e.preventDefault();

    const submitBtn = e.submitter;
    const origText  = submitBtn?.textContent;
    if (submitBtn) { submitBtn.textContent = 'Loading…'; submitBtn.disabled = true; }

    const data = new FormData(form);

    try {
        const res  = await fetch('/swipe/load', { method: 'POST', body: data });
        const json = await res.json();
        if (json.movies?.length) {
            queue     = [...json.movies];
            seenPages = [json.page];
            history   = [];
            saveSession();
            stack.innerHTML = '';
            renderStack();
            updateUndo();
            document.querySelector('#modal-form')?.classList.add('hidden');
        } else {
            showEmpty();
            document.querySelector('#modal-form')?.classList.add('hidden');
        }
    } finally {
        if (submitBtn) { submitBtn.textContent = origText; submitBtn.disabled = false; }
    }
});

// ── Keyboard ──────────────────────────────────────────────────────────
document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight') document.getElementById('btn-like').click();
    if (e.key === 'ArrowLeft')  document.getElementById('btn-skip').click();
});

// ── Init ──────────────────────────────────────────────────────────────
updateEndBtn();
updateUndo();
renderStack();
