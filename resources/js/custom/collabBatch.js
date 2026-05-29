import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { runCaseOpening } from './caseOpening.js';

// ── Identity ──────────────────────────────────────────────────────────
const COLORS  = ['Red','Blue','Green','Purple','Orange','Pink','Teal','Gold'];
const ANIMALS = ['Fox','Panda','Wolf','Bear','Hawk','Lynx','Otter','Raven'];


const myId = (() => {
    let id = localStorage.getItem('collab_user_id');
    if (!id) {
        id = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
        localStorage.setItem('collab_user_id', id);
    }
    return id;
})();
let myName = (() => {
    let name = localStorage.getItem('collab_identity');
    if (!name) {
        name = COLORS[Math.floor(Math.random() * COLORS.length)] + ' ' +
               ANIMALS[Math.floor(Math.random() * ANIMALS.length)];
        localStorage.setItem('collab_identity', name);
    }
    return name;
})();
const token     = window.collabToken;
const mediaType = window.collabMediaType;
const hasCriteria = window.collabHasCriteria;

// ── Grace period for leave toasts ────────────────────────────────────
const leavePending = new Map(); // userId → timeout

// ── Local state ───────────────────────────────────────────────────────
let state = {
    movies:       window.collabMovies       || [],
    graveyard:    window.collabGraveyard    || [],
    votes:        window.collabVotes        || {},
    restoreVotes: window.collabRestore      || {},
    ready:        window.collabReady        || [],
    refreshVotes: window.collabRefresh      || [],
    participants: window.collabParticipants || [],
};
let totalMovies = state.movies.length + state.graveyard.length;


// ── Reverb ───────────────────────────────────────────────────────────
window.Pusher = Pusher;
const echo = new Echo({
    broadcaster:       'reverb',
    key:               import.meta.env.VITE_REVERB_APP_KEY,
    wsHost:            import.meta.env.VITE_REVERB_HOST,
    wsPort:            import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort:           import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS:          (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
});

echo.channel('batch.' + token).listen('.CollabStateUpdated', (e) => {
    applyState(e.state, e.eventType, e.byName, e.byId, e.movieTitle, e.winner || null);
});

// ── API helpers ───────────────────────────────────────────────────────
const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

function api(path, body = {}) {
    return fetch(`/batch/collab/${token}/${path}`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body:    JSON.stringify({ userId: myId, name: myName, ...body }),
    }).then(r => r.json());
}

// ── Identity input ────────────────────────────────────────────────────
const identityInput = document.getElementById('identity-input');
const identitySaved = document.getElementById('identity-saved');
identityInput.value = myName;

function saveName() {
    const newName = identityInput.value.trim();
    if (!newName || newName === myName) return;
    myName = newName;
    localStorage.setItem('collab_identity', newName);
    identitySaved.textContent = '✓ saved';
    setTimeout(() => identitySaved.textContent = '', 2000);
    api('heartbeat');
}

identityInput.addEventListener('blur', saveName);
identityInput.addEventListener('keydown', e => { if (e.key === 'Enter') { identityInput.blur(); } });

// ── Join / Leave / Heartbeat ──────────────────────────────────────────
api('join');

// Heartbeat every 60s
setInterval(() => api('heartbeat'), 60_000);

// Leave on unload
window.addEventListener('beforeunload', () => {
    navigator.sendBeacon(`/batch/collab/${token}/leave`,
        new Blob([JSON.stringify({ userId: myId, _token: csrf() })], { type: 'application/json' }));
});

// ── Card tap — single tap on poster = vote ────────────────────────────
document.getElementById('collab-grid').addEventListener('click', (e) => {
    const target = e.target.closest('.vote-target');
    if (!target) return;
    const card = target.closest('.collab-card');
    if (!card) return;
    castVote(parseInt(card.dataset.id), 'veto');
});

// Graveyard restore tap
document.getElementById('graveyard-grid').addEventListener('click', (e) => {
    const card = e.target.closest('.graveyard-card');
    if (!card) return;
    castVote(parseInt(card.dataset.id), 'restore');
});

// ── Vote ──────────────────────────────────────────────────────────────
function castVote(movieId, type) {
    api(`vote/${movieId}`, { type });
}

// ── Ready to Roll ─────────────────────────────────────────────────────
document.getElementById('ready-btn').addEventListener('click', () => {
    api('ready').then(data => {
        if (data.rolled && data.winner) triggerRoll(data.winner);
    });
});

// ── New Batch vote ────────────────────────────────────────────────────
document.getElementById('refresh-btn')?.addEventListener('click', () => {
    api('refresh');
});

// ── Apply state from broadcast ────────────────────────────────────────
function applyState(newState, eventType, byName = '', byId = '', movieTitle = '', e_winner = null) {
    const isMe = byId === myId || byName === myName;
    const who  = byName || 'Someone';

    if (!isMe && who) {
        if      (eventType === 'vote_veto_on'     && movieTitle) showToast(`${who} voted to skip ${movieTitle}`);
        else if (eventType === 'vote_veto_off'    && movieTitle) showToast(`${who} changed their mind on ${movieTitle}`);
        else if (eventType === 'vote_restore_on'  && movieTitle) showToast(`${who} voted to restore ${movieTitle}`);
        else if (eventType === 'vote_restore_off' && movieTitle) showToast(`${who} changed their mind on restoring ${movieTitle}`);
        else if (eventType === 'ready_on')                   showToast(`${who} is ready to roll`);
        else if (eventType === 'ready_off')                  showToast(`${who} is no longer ready`);
        else if (eventType === 'refresh_on')                 showToast(`${who} wants a new batch`);
        else if (eventType === 'refresh_off')                showToast(`${who} changed their mind on new batch`);
        else if (eventType === 'rename')                     showToast(`${movieTitle || 'Someone'} is now ${who}`, 'green');
        else if (eventType === 'join') {
            // Cancel pending leave toast — they just refreshed
            if (leavePending.has(byId)) {
                clearTimeout(leavePending.get(byId));
                leavePending.delete(byId);
            } else {
                showToast(`${who} joined`, 'green');
            }
        } else if (eventType === 'leave') {
            // Delay 8s — if they rejoin (page refresh) the toast and vote cleanup are cancelled
            const t = setTimeout(() => {
                leavePending.delete(byId);
                showToast(`${who} left`);
                api('remove-votes', { targetUserId: byId });
            }, 8000);
            leavePending.set(byId, t);
        }
    }
    const prevMovieIds    = new Set(state.movies.map(m => m.id));
    const prevGraveyardIds = new Set(state.graveyard.map(m => m.id));

    state = {
        movies:       newState.movies        || [],
        graveyard:    newState.graveyard      || [],
        votes:        newState.votes          || {},
        restoreVotes: newState.restore_votes  || {},
        ready:        newState.ready          || [],
        refreshVotes: newState.refresh_votes  || [],
        participants: newState.participants    || [],
    };

    const newMovieIds     = new Set(state.movies.map(m => m.id));
    const newGraveyardIds = new Set(state.graveyard.map(m => m.id));

    // Cards moved to graveyard
    prevMovieIds.forEach(id => {
        if (!newMovieIds.has(id)) animateToGraveyard(id);
    });

    // Cards restored from graveyard
    prevGraveyardIds.forEach(id => {
        if (!newGraveyardIds.has(id)) removeGraveyardCard(id);
    });

    // Add newly restored cards to grid
    state.movies.forEach(movie => {
        if (!prevMovieIds.has(movie.id)) addCardToGrid(movie);
    });

    // Add new graveyard cards
    state.graveyard.forEach(movie => {
        if (!prevGraveyardIds.has(movie.id)) addCardToGraveyard(movie);
    });

    updateAllVoteBars();
    updateParticipants();
    updateReadyButton();
    updateRefreshButton();
    updateProgress();

    if (eventType === 'rolled' && e_winner) {
        setTimeout(() => triggerRoll(e_winner), 400);
    }

    if (eventType === 'refreshed') {
        // Full re-render — wipe grid and graveyard, add all new cards
        document.getElementById('collab-grid').innerHTML = '';
        document.getElementById('graveyard-grid').innerHTML = '';
        document.getElementById('graveyard-section').classList.add('hidden');

        totalMovies = state.movies.length;
        document.getElementById('total-num').textContent = totalMovies;

        state.movies.forEach(movie => addCardToGrid(movie));
        showToast('New batch loaded — let\'s go!', 'green');
    }
}

// ── Animate card to graveyard ─────────────────────────────────────────
function animateToGraveyard(movieId) {
    const card = document.querySelector(`.collab-card[data-id="${movieId}"]`);
    if (!card) return;
    card.style.transition = 'transform 0.2s, opacity 0.3s';
    card.style.transform  = 'scale(0.8)';
    card.style.opacity    = '0';
    setTimeout(() => card.remove(), 320);
}

function removeGraveyardCard(movieId) {
    const card = document.querySelector(`.graveyard-card[data-id="${movieId}"]`);
    if (card) {
        card.style.transition = 'opacity 0.3s';
        card.style.opacity = '0';
        setTimeout(() => card.remove(), 320);
    }
}

function addCardToGrid(movie) {
    const isTv   = mediaType === 'tv' || movie.media_type === 'tv';
    const url    = (isTv ? '/tv/' : '/movie/') + movie.id;
    const title  = movie.title ?? movie.name ?? '';
    const poster = movie.poster_path
        ? `<img src="https://image.tmdb.org/t/p/w342${movie.poster_path}" alt="${esc(title)}" class="w-full h-full object-cover transition-all duration-300" loading="lazy">`
        : `<div class="w-full h-full flex items-center justify-center text-gray-600 text-xs px-2 text-center">${esc(title)}</div>`;

    const el = document.createElement('div');
    el.className = 'collab-card relative group cursor-pointer select-none';
    el.dataset.id = movie.id;
    el.style.opacity = '0';
    el.style.transform = 'scale(0.8)';
    el.innerHTML = `
        <div class="card overflow-hidden transition-all duration-200" id="card-inner-${movie.id}">
            <div class="vote-target aspect-[2/3] bg-white/[0.03] overflow-hidden relative cursor-pointer">
                ${poster}
                <div class="vote-heat absolute inset-0 bg-red-900/0 transition-all duration-500 pointer-events-none"></div>
                <div class="voted-overlay absolute inset-0 border-2 border-red-500/70 hidden pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-white/10">
                    <div class="vote-bar h-full bg-red-500 transition-all duration-300" style="width:0%"></div>
                </div>
                <div class="vote-pips absolute top-2 left-0 right-0 flex justify-center gap-1 hidden"></div>
            </div>
            <div class="p-2">
                <div class="text-xs font-medium text-white truncate">${esc(title)}</div>
                ${movie.vote_average ? `<div class="text-xs text-gray-500 mt-0.5">★ ${Number(movie.vote_average).toFixed(1)}</div>` : ''}
            </div>
        </div>`;

    document.getElementById('collab-grid').appendChild(el);
    requestAnimationFrame(() => {
        el.style.transition = 'transform 0.3s, opacity 0.3s';
        el.style.transform = 'scale(1)';
        el.style.opacity = '1';
    });
}

function addCardToGraveyard(movie) {
    const title = movie.title ?? movie.name ?? '';
    const poster = movie.poster_path
        ? `<img src="https://image.tmdb.org/t/p/w185${movie.poster_path}" alt="${esc(title)}" class="w-full h-full object-cover grayscale">`
        : '';

    const el = document.createElement('div');
    el.className = 'graveyard-card relative flex-shrink-0 w-28 sm:w-32 cursor-pointer opacity-50 hover:opacity-80 transition-opacity group';
    el.dataset.id = movie.id;
    el.innerHTML = `
        <div class="aspect-[2/3] rounded-lg overflow-hidden bg-white/[0.03] relative">
            ${poster}
            <div class="restore-overlay absolute inset-0 bg-black/70 flex flex-col items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <span class="text-lg">↩</span>
                <span class="text-xs text-gray-300">Restore</span>
            </div>
            <div class="restore-vote-bar absolute bottom-0 left-0 right-0 h-1 bg-white/10">
                <div class="restore-bar h-full bg-green-500 transition-all duration-300" style="width:0%"></div>
            </div>
        </div>
        <div class="text-xs text-gray-500 truncate mt-1 text-center">${esc(title)}</div>`;

    document.getElementById('graveyard-grid').appendChild(el);
    document.getElementById('graveyard-section').classList.remove('hidden');
}

// ── Vote bars & badges ────────────────────────────────────────────────
function updateAllVoteBars() {
    const pCount = activeParticipantCount();
    const vetoNeeded = vetoThreshold(pCount);

    // Main grid
    document.querySelectorAll('.collab-card').forEach(card => {
        const movieId    = String(card.dataset.id);
        const voters     = state.votes[movieId] || [];
        const votes      = voters.length;
        const pct        = pCount > 0 ? Math.round((votes / vetoNeeded) * 100) : 0;
        const iVoted     = voters.includes(myId);
        const bar        = card.querySelector('.vote-bar');
        const pips       = card.querySelector('.vote-pips');
        const heat       = card.querySelector('.vote-heat');
        const votedOvl   = card.querySelector('.voted-overlay');
        const voteTarget = card.querySelector('.vote-target');

        if (bar) bar.style.width = Math.min(pct, 100) + '%';

        // Pip dots — one per required vote, filled red if voted
        if (pips) {
            if (votes > 0 || vetoNeeded <= 4) {
                pips.classList.remove('hidden');
                pips.innerHTML = Array.from({ length: vetoNeeded }, (_, i) =>
                    `<span class="w-2 h-2 rounded-full border border-white/40 transition-colors duration-200
                        ${i < votes ? 'bg-red-500 border-red-500' : 'bg-white/10'}"></span>`
                ).join('');
            } else {
                pips.classList.add('hidden');
            }
        }

        // Heat overlay — visible to everyone, intensity scales with vote progress
        if (heat) {
            const opacity = votes === 0 ? 0 : Math.round((votes / vetoNeeded) * 50);
            heat.style.backgroundColor = `rgba(127,29,29,${opacity / 100})`;
        }

        if (votedOvl) votedOvl.classList.toggle('hidden', !iVoted);
        if (voteTarget) {
            voteTarget.classList.toggle('ring-2', iVoted);
            voteTarget.classList.toggle('ring-red-500/60', iVoted);
        }
    });

    // Graveyard
    document.querySelectorAll('.graveyard-card').forEach(card => {
        const movieId = String(card.dataset.id);
        const votes   = (state.restoreVotes[movieId] || []).length;
        const pct     = pCount > 0 ? Math.round((votes / vetoNeeded) * 100) : 0;
        const bar     = card.querySelector('.restore-bar');
        if (bar) bar.style.width = Math.min(pct, 100) + '%';
    });

    // Update threshold info
    const graveyardRule = document.getElementById('graveyard-restore-rule');
    if (graveyardRule) graveyardRule.textContent = `${vetoNeeded} of ${pCount}`;

    const graveyardCount = document.getElementById('graveyard-count');
    if (graveyardCount) graveyardCount.textContent = state.graveyard.length;
}

// ── Participants ──────────────────────────────────────────────────────
function activeParticipantCount() {
    return Math.max(1, state.participants.length);
}

function vetoThreshold(n)    { return Math.floor(n / 2) + 1; }
function readyThreshold(n)   { return Math.ceil(n * 0.75); }
function refreshThreshold(n) { return n; }

function updateParticipants() {
    const pCount = activeParticipantCount();
    const vThr   = vetoThreshold(pCount);
    const rThr   = readyThreshold(pCount);
    const list   = document.getElementById('participant-list');

    // Rules card
    const ruleVeto = document.getElementById('rule-veto');
    const ruleRoll = document.getElementById('rule-roll');
    if (ruleVeto) ruleVeto.textContent = `${vThr} of ${pCount}`;
    if (ruleRoll) ruleRoll.textContent = `${rThr} of ${pCount}`;

    // Participant avatars with pulsing online dot
    list.innerHTML = state.participants.slice(0, 6).map(p => {
        const isMe     = p.userId === myId;
        const initials = (p.name || '?').slice(0, 2).toUpperCase();
        return `<div class="relative flex-shrink-0" title="${esc(p.name)}">
            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                ${isMe ? 'bg-accent text-white' : 'bg-white/10 text-gray-300'}">${initials}</div>
            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-400 rounded-full border-2 border-[#0f0f0f] pulse-dot"></span>
        </div>`;
    }).join('');

    if (state.participants.length === 0) {
        list.innerHTML = `<div class="relative flex-shrink-0">
            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold bg-accent text-white">${myName.slice(0,2).toUpperCase()}</div>
            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-400 rounded-full border-2 border-[#0f0f0f] pulse-dot"></span>
        </div>`;
    }
}

// ── Ready button ──────────────────────────────────────────────────────
function updateReadyButton() {
    const btn     = document.getElementById('ready-btn');
    const countEl = document.getElementById('ready-count');
    const pCount  = activeParticipantCount();
    const needed  = readyThreshold(pCount);
    const iReady  = state.ready.includes(myId);

    btn.textContent = iReady ? 'Unready' : 'Ready to Roll';
    const span = document.createElement('span');
    span.className = 'text-xs opacity-70 ml-1';
    span.textContent = `${state.ready.length}/${needed}`;
    btn.appendChild(span);
    btn.classList.toggle('btn-secondary', iReady);
    btn.classList.toggle('btn-accent', !iReady);
}

// ── Refresh button ────────────────────────────────────────────────────
function updateRefreshButton() {
    const btn = document.getElementById('refresh-btn');
    if (!btn) return;
    const pCount  = activeParticipantCount();
    const needed  = refreshThreshold(pCount);
    const iVoted  = state.refreshVotes.includes(myId);
    const countEl = document.getElementById('refresh-votes-count');
    if (countEl) countEl.textContent = `${state.refreshVotes.length}/${needed}`;
    btn.classList.toggle('opacity-50', !iVoted);
}

// ── Progress ──────────────────────────────────────────────────────────
function updateProgress() {
    const remaining = state.movies.length;
    const pct = totalMovies > 0 ? (remaining / totalMovies) * 100 : 0;
    document.getElementById('remaining-num').textContent = remaining;
    document.getElementById('veto-progress').style.width = pct + '%';
}

// ── Roll animation then winner overlay ───────────────────────────────
function triggerRoll(winner) {
    const allCards = [...state.movies, winner].filter((m, i, arr) =>
        m.poster_path && arr.findIndex(x => x.id === m.id) === i
    );

    const toCard = m => ({
        url:        ((mediaType === 'tv' || m.media_type === 'tv') ? '/tv/' : '/movie/') + m.id,
        poster:     `https://image.tmdb.org/t/p/w342${m.poster_path}`,
        title:      m.title ?? m.name ?? '',
        rating:     m.vote_average ?? 0,
        media_type: mediaType,
    });

    const cards     = allCards.map(toCard);
    const winnerUrl = toCard(winner).url;
    let   winnerIdx = cards.findIndex(c => c.url === winnerUrl);
    if (winnerIdx < 0) { cards.push(toCard(winner)); winnerIdx = cards.length - 1; }

    runCaseOpening(cards, winnerIdx, winnerUrl, mediaType, () => showWinner(winner));
}

// ── Winner overlay ────────────────────────────────────────────────────
function showWinner(movie) {
    const isTv  = mediaType === 'tv' || movie.media_type === 'tv';
    const url   = (isTv ? '/tv/' : '/movie/') + movie.id;
    const title = movie.title ?? movie.name ?? '';

    document.getElementById('winner-poster').innerHTML = movie.poster_path
        ? `<img src="https://image.tmdb.org/t/p/w342${movie.poster_path}" class="w-full h-full object-cover">`
        : '';
    document.getElementById('winner-title').textContent = title;
    document.getElementById('winner-rating').textContent = movie.vote_average
        ? `★ ${Number(movie.vote_average).toFixed(1)}` : '';
    document.getElementById('winner-details-link').href = url;
    document.getElementById('winner-overlay').classList.remove('hidden');
    confetti();
}

document.getElementById('winner-dismiss').addEventListener('click', () => {
    document.getElementById('winner-overlay').classList.add('hidden');
    api('ready'); // toggle ready off so session can continue
});

// ── Toasts ────────────────────────────────────────────────────────────
function showToast(msg, color = 'white') {
    const el = document.createElement('div');
    const cls = color === 'green'
        ? 'bg-green-900/80 border-green-700/50 text-green-300'
        : 'bg-black/80 border-white/10 text-white';
    el.className = `${cls} border text-sm px-4 py-2.5 rounded-lg backdrop-blur-sm pointer-events-auto max-w-[90vw] text-center`;
    el.textContent = msg;
    document.getElementById('veto-toasts').appendChild(el);
    setTimeout(() => el.remove(), 3000);
}

// ── Confetti ──────────────────────────────────────────────────────────
function confetti() {
    const container = document.getElementById('winner-overlay');
    for (let i = 0; i < 60; i++) {
        const dot = document.createElement('div');
        dot.style.cssText = `position:absolute;width:8px;height:8px;border-radius:50%;
            left:${Math.random()*100}%;top:-10px;
            background:hsl(${Math.random()*360},80%,60%);
            animation:confetti-fall ${1.5+Math.random()}s linear ${Math.random()*0.5}s forwards;`;
        container.appendChild(dot);
        setTimeout(() => dot.remove(), 2500);
    }
}

// ── Util ──────────────────────────────────────────────────────────────
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
}

// ── Init ──────────────────────────────────────────────────────────────
updateParticipants();
updateAllVoteBars();
updateReadyButton();
updateRefreshButton();
updateProgress();
document.getElementById('total-num').textContent = totalMovies;
