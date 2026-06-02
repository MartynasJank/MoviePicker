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

// ── Roll guard (prevents double animation) ────────────────────────────
let rollInProgress = false;
let winnerShown    = false;

// ── Local state ───────────────────────────────────────────────────────
let originalOrder = (window.collabMovies || []).map(m => m.id);

let state = {
    movies:         window.collabMovies       || [],
    graveyard:      window.collabGraveyard    || [],
    votes:          window.collabVotes        || {},
    restoreVotes:   window.collabRestore      || {},
    ready:          window.collabReady        || [],
    refreshVotes:   window.collabRefresh      || [],
    participants:   window.collabParticipants || [],
    tryAgainVotes:  [],
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
    applyDelta(e.eventType, e.byName || '', e.byId || '', e.movieTitle || '', e.delta || {});
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

// ── Invite — always copy to clipboard ────────────────────────────────
document.getElementById('invite-btn').addEventListener('click', function () {
    const url = this.dataset.url;
    navigator.clipboard.writeText(url).then(() => {
        showToast('Invite link copied!', 'green');
    }).catch(() => {
        showToast('Could not copy link.');
    });
});

// ── Join / Leave / Heartbeat ──────────────────────────────────────────
api('join');

// Heartbeat every 60s
setInterval(() => api('heartbeat'), 60_000);

// Leave on unload
window.addEventListener('beforeunload', () => {
    navigator.sendBeacon(`/batch/collab/${token}/leave`,
        new Blob([JSON.stringify({ userId: myId, _token: csrf() })], { type: 'application/json' }));
});

// ── Voting signal — debounced pointerdown ─────────────────────────────
document.getElementById('collab-grid').addEventListener('pointerdown', (e) => {
    const card = e.target.closest('.collab-card');
    if (!card) return;
    const movieId     = parseInt(card.dataset.id);
    const isRecalling = (state.votes[String(movieId)] || []).includes(myId);
    api('voting', { movieId, isRecalling });
});

// ── Card tap — single tap on poster = vote ────────────────────────────
document.getElementById('collab-grid').addEventListener('click', (e) => {
    const card = e.target.closest('.collab-card');
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
        if (data.rolled && data.winner) {
            state.movies.length === 1
                ? showWinner(data.winner)
                : triggerRoll(data.winner);
        }
    });
});

// ── New Batch vote ────────────────────────────────────────────────────
document.getElementById('refresh-btn')?.addEventListener('click', () => {
    api('refresh');
});

// ── Try Again (on winner screen) ──────────────────────────────────────
function updateTryAgainButton(needed = null) {
    const btn    = document.getElementById('winner-try-again');
    const pCount = needed ?? activeParticipantCount();
    const iVoted = state.tryAgainVotes.includes(myId);
    const count  = state.tryAgainVotes.length;
    btn.textContent = `Try Again (${count}/${pCount})`;
    btn.classList.toggle('btn-accent', iVoted);
    btn.classList.toggle('btn-secondary', !iVoted);
}

document.getElementById('winner-try-again').addEventListener('click', () => {
    api('restart').then(() => updateTryAgainButton());
});

// ── Apply delta from broadcast ────────────────────────────────────────
function applyDelta(eventType, byName, byId, movieTitle, delta) {
    const isMe = byId === myId;
    const who  = byName || 'Someone';

    // ── Toasts ──
    if (!isMe) {
        if      (eventType === 'vote_veto_on'     && movieTitle) showToast(`${who} voted to skip ${movieTitle}`);
        else if (eventType === 'vote_veto_off'    && movieTitle) showToast(`${who} changed their mind on ${movieTitle}`);
        else if (eventType === 'veto_threshold'   && movieTitle) showToast(`${movieTitle} was voted out`);
        else if (eventType === 'vote_restore_on'  && movieTitle) showToast(`${who} voted to restore ${movieTitle}`);
        else if (eventType === 'vote_restore_off' && movieTitle) showToast(`${who} changed their mind on restoring ${movieTitle}`);
        else if (eventType === 'restore_threshold'&& movieTitle) showToast(`${movieTitle} was restored`, 'green');
        else if (eventType === 'ready_on')                       showToast(`${who} is ready to roll`);
        else if (eventType === 'ready_off')                      showToast(`${who} is no longer ready`);
        else if (eventType === 'refresh_on')                     showToast(`${who} wants a new batch`);
        else if (eventType === 'refresh_off')                    showToast(`${who} changed their mind on new batch`);
        else if (eventType === 'rename')                         showToast(`${movieTitle || 'Someone'} is now ${who}`, 'green');
        else if (eventType === 'join') {
            if (leavePending.has(byId)) {
                clearTimeout(leavePending.get(byId));
                leavePending.delete(byId);
            } else {
                showToast(`${who} joined`, 'green');
            }
        } else if (eventType === 'leave') {
            const t = setTimeout(() => {
                leavePending.delete(byId);
                showToast(`${who} left`);
                api('remove-votes', { targetUserId: byId });
            }, 8000);
            leavePending.set(byId, t);
        }
    }

    const prevMovieCount = state.movies.length;

    // ── State updates ──
    switch (eventType) {

        case 'vote_veto_on':
        case 'vote_veto_off':
            if (delta.voters.length) {
                state.votes[String(delta.movieId)] = delta.voters;
            } else {
                delete state.votes[String(delta.movieId)];
            }
            updateAllVoteBars();
            break;

        case 'veto_threshold':
            state.movies = state.movies.filter(m => m.id !== delta.movieId);
            delete state.votes[String(delta.movieId)];
            state.graveyard.push(delta.movie);
            animateToGraveyard(delta.movieId);
            addCardToGraveyard(delta.movie);
            updateAllVoteBars();
            updateProgress();
            break;

        case 'vote_restore_on':
        case 'vote_restore_off':
            if (delta.voters.length) {
                state.restoreVotes[String(delta.movieId)] = delta.voters;
            } else {
                delete state.restoreVotes[String(delta.movieId)];
            }
            updateAllVoteBars();
            break;

        case 'restore_threshold':
            state.graveyard = state.graveyard.filter(m => m.id !== delta.movieId);
            delete state.restoreVotes[String(delta.movieId)];
            state.movies.push(delta.movie);
            removeGraveyardCard(delta.movieId);
            addCardToGrid(delta.movie, true);
            updateAllVoteBars();
            updateProgress();
            break;

        case 'join':
        case 'rename':
            state.participants = state.participants.filter(p => p.userId !== delta.participant.userId);
            state.participants.push(delta.participant);
            updateParticipants();
            updateReadyButton();
            updateRefreshButton();
            updateAllVoteBars();
            break;

        case 'leave':
            state.participants = state.participants.filter(p => p.userId !== byId);
            updateParticipants();
            updateReadyButton();
            updateRefreshButton();
            updateAllVoteBars();
            break;

        case 'ready_on':
        case 'ready_off':
            state.ready = delta.ready;
            updateReadyButton();
            break;

        case 'refresh_on':
        case 'refresh_off':
            state.refreshVotes = delta.refreshVotes;
            updateRefreshButton();
            break;

        case 'voting':
            if (!isMe) showVotingIndicator(delta.movieId, who, delta.isRecalling);
            break;

        case 'vote_cleanup':
            state.votes        = delta.votes;
            state.restoreVotes = delta.restoreVotes;
            updateAllVoteBars();
            break;

        case 'rolled':
            winnerShown = true;
            setTimeout(() => {
                state.movies.length === 1
                    ? showWinner(delta.winner)
                    : triggerRoll(delta.winner);
            }, 400);
            break;

        case 'try_again_on':
        case 'try_again_off':
            state.tryAgainVotes = delta.tryAgainVotes;
            updateTryAgainButton(delta.needed);
            if (!isMe) {
                const action = eventType === 'try_again_on' ? 'wants to try again' : 'changed their mind on try again';
                showToast(`${who} ${action}`);
            }
            break;

        case 'restarted':
            winnerShown = false;
            rollInProgress = false;
            document.getElementById('winner-overlay').classList.add('hidden');
            state.tryAgainVotes = [];
            state.graveyard    = [];
            state.votes        = {};
            state.restoreVotes = {};
            state.ready        = [];
            // Add graveyard cards back in original order
            delta.movies.filter(m => !state.movies.find(s => s.id === m.id)).forEach(m => {
                state.movies.push(m);
                addCardToGrid(m, true);
            });
            document.getElementById('graveyard-grid').innerHTML = '';
            document.getElementById('graveyard-section').classList.add('hidden');
            updateAllVoteBars();
            updateReadyButton();
            updateProgress();
            showToast(`${who} hit Try Again — all movies are back!`, 'green');
            break;

        case 'refreshed':
            winnerShown = false;
            originalOrder  = delta.movies.map(m => m.id);
            state.movies       = delta.movies;
            state.graveyard    = [];
            state.votes        = {};
            state.restoreVotes = {};
            state.ready        = [];
            state.refreshVotes = [];
            document.getElementById('collab-grid').innerHTML    = '';
            document.getElementById('graveyard-grid').innerHTML = '';
            document.getElementById('graveyard-section').classList.add('hidden');
            totalMovies = state.movies.length;
            document.getElementById('total-num').textContent = totalMovies;
            state.movies.forEach(movie => addCardToGrid(movie));
            updateAllVoteBars();
            updateReadyButton();
            updateRefreshButton();
            updateProgress();
            showToast('New batch loaded — let\'s go!', 'green');
            break;
    }

}

// ── Animate card to graveyard ─────────────────────────────────────────
function animateToGraveyard(movieId) {
    const card = document.querySelector(`.collab-card[data-id="${movieId}"]`);
    if (!card) return;
    const inner = card.querySelector('.card');
    if (inner) inner.style.animation = 'collab-red-flash 0.4s ease-out';
    card.style.animation = 'collab-veto-shake 0.35s ease-in-out';
    setTimeout(() => {
        card.style.animation = 'collab-veto-out 0.3s ease-in forwards';
        setTimeout(() => card.remove(), 300);
    }, 350);
}

function removeGraveyardCard(movieId) {
    const card = document.querySelector(`.graveyard-card[data-id="${movieId}"]`);
    if (card) {
        card.style.animation = 'collab-veto-out 0.25s ease-in forwards';
        setTimeout(() => card.remove(), 250);
    }
}

function buildCard(movie, isGraveyard = false) {
    const title  = movie.title ?? movie.name ?? '';
    const year   = (movie.release_date || movie.first_air_date || '').slice(0, 4);
    const imgCls = `w-full h-full object-cover transition-all duration-300${isGraveyard ? ' grayscale' : ''}`;
    const poster = movie.poster_path
        ? `<img src="https://image.tmdb.org/t/p/w342${movie.poster_path}" alt="${esc(title)}" class="${imgCls}" loading="lazy">`
        : `<div class="w-full h-full flex items-center justify-center text-gray-600 text-xs px-2 text-center">${esc(title)}</div>`;

    const posterOverlays = isGraveyard ? `
        <div class="absolute top-2 right-2 w-5 h-5 rounded-full bg-black/70 flex items-center justify-center text-xs">↩</div>
        <div class="absolute bottom-0 left-0 right-0">
            <div class="vote-info hidden px-2 py-1 flex items-center gap-1.5 bg-black/50">
                <div class="vote-avatars flex -space-x-1.5"></div>
                <span class="vote-count text-xs text-green-300"></span>
            </div>
            <div class="h-1 bg-white/10">
                <div class="restore-bar h-full bg-green-500 transition-all duration-300" style="width:0%"></div>
            </div>
        </div>` : `
        <div class="vote-heat absolute inset-0 bg-red-900/0 transition-all duration-500 pointer-events-none"></div>
        <div class="voted-overlay absolute inset-0 hidden pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 right-0">
            <div class="vote-info hidden px-2 py-1 flex items-center gap-1.5 bg-black/50">
                <div class="vote-avatars flex -space-x-1.5"></div>
                <span class="vote-count text-xs text-red-300"></span>
            </div>
            <div class="h-1 bg-white/10">
                <div class="vote-bar h-full bg-red-500 transition-all duration-300" style="width:0%"></div>
            </div>
        </div>`;

    const titleCls  = isGraveyard ? 'text-xs font-medium text-gray-400 truncate' : 'text-xs font-medium text-white truncate';
    const ratingCls = isGraveyard ? 'text-xs text-gray-600 mt-0.5' : 'text-xs text-gray-500 mt-0.5';
    const metaCls   = 'text-xs text-gray-600 mt-0.5';

    const el = document.createElement('div');
    el.className = isGraveyard
        ? 'graveyard-card relative select-none cursor-pointer opacity-50 hover:opacity-75 active:opacity-90 transition-opacity'
        : 'collab-card relative group cursor-pointer select-none';
    el.dataset.id = movie.id;
    el.innerHTML = `
        <div class="card overflow-hidden transition-all duration-200">
            <div class="${isGraveyard ? 'aspect-[2/3]' : 'vote-target aspect-[2/3]'} bg-white/[0.03] overflow-hidden relative${isGraveyard ? '' : ' cursor-pointer'}">
                ${poster}
                ${posterOverlays}
            </div>
            <div class="p-2">
                <div class="${titleCls}">${esc(title)}</div>
                ${movie.vote_average ? `<div class="${ratingCls}">★ ${Number(movie.vote_average).toFixed(1)}</div>` : ''}
                ${year ? `<div class="${metaCls}">${year}</div>` : ''}
                ${!isGraveyard && movie.genres ? `<div class="${metaCls} truncate">${esc(movie.genres)}</div>` : ''}
            </div>
        </div>`;

    return el;
}

function addCardToGrid(movie, animate = false) {
    const el   = buildCard(movie, false);
    el.style.opacity = '0';
    const grid = document.getElementById('collab-grid');
    const pos  = originalOrder.indexOf(movie.id);
    const after = [...grid.querySelectorAll('.collab-card')].find(card =>
        originalOrder.indexOf(parseInt(card.dataset.id)) > pos
    );
    after ? grid.insertBefore(el, after) : grid.appendChild(el);
    requestAnimationFrame(() => {
        if (animate) {
            el.style.animation = 'collab-restore-in 0.4s ease-out forwards';
            const inner = el.querySelector('.card');
            if (inner) inner.style.animation = 'collab-green-flash 0.5s ease-out';
        } else {
            el.style.transition = 'opacity 0.2s';
        }
        el.style.opacity = '1';
    });
}

function addCardToGraveyard(movie) {
    const el = buildCard(movie, true);
    el.style.opacity = '0';
    document.getElementById('graveyard-grid').appendChild(el);
    document.getElementById('graveyard-section').classList.remove('hidden');
    requestAnimationFrame(() => {
        el.style.animation = 'collab-restore-in 0.4s ease-out forwards';
        el.style.opacity = '1';
        const inner = el.querySelector('.card');
        if (inner) inner.style.animation = 'collab-red-flash 0.5s ease-out';
    });
}

// ── Voting indicator ──────────────────────────────────────────────────
const VOTING_MAX = 10;

function getVotingContainer(voteTarget) {
    let container = voteTarget.querySelector('.voting-badges');
    if (!container) {
        container = document.createElement('div');
        container.className = 'voting-badges absolute top-1.5 left-0 right-0 flex flex-col items-center gap-0.5 pointer-events-none z-20';
        voteTarget.appendChild(container);
    }
    return container;
}

function showVotingIndicator(movieId, name, isRecalling = false) {
    const card = document.querySelector(`.collab-card[data-id="${movieId}"]`);
    if (!card) return;

    const voteTarget = card.querySelector('.vote-target');
    if (!voteTarget) return;

    const container = getVotingContainer(voteTarget);

    // Enforce limit — remove oldest instantly
    while (container.children.length >= VOTING_MAX) {
        const oldest = container.lastElementChild;
        if (oldest._timeout) clearTimeout(oldest._timeout);
        oldest.remove();
    }

    const badgeBg = isRecalling ? 'rgba(21,128,61,0.9)' : 'rgba(192,57,58,0.9)';
    const label   = isRecalling ? `${esc(name)} reconsidering…` : `${esc(name)} is vetoing…`;

    const badge = document.createElement('div');
    badge.style.cssText = `font-size:9px;font-weight:600;color:#fff;background:${badgeBg};padding:2px 8px;border-radius:9999px;white-space:nowrap`;
    badge.textContent = label;

    container.prepend(badge);

    badge._timeout = setTimeout(() => badge.remove(), 2000);
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
        const heat       = card.querySelector('.vote-heat');
        const votedOvl   = card.querySelector('.voted-overlay');
        const voteTarget = card.querySelector('.vote-target');

        if (bar) bar.style.width = Math.min(pct, 100) + '%';

        // Vote info row in card info section
        const voteInfo    = card.querySelector('.vote-info');
        const voteAvatars = card.querySelector('.vote-avatars');
        const voteCount   = card.querySelector('.vote-count');
        if (voteInfo) {
            if (votes > 0) {
                voteInfo.classList.remove('hidden');
                const visible = voters.slice(0, 3);
                const overflow = voters.length - visible.length;
                voteAvatars.innerHTML = visible.map(vid => {
                    const p       = state.participants.find(p => p.userId === vid);
                    const initial = p ? p.name.slice(0, 1).toUpperCase() : '?';
                    const isMe    = vid === myId;
                    return `<span class="w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-bold text-white ring-1 ring-black
                        ${isMe ? 'bg-accent' : 'bg-red-500'}" title="${p ? esc(p.name) : ''}">${initial}</span>`;
                }).join('') + (overflow > 0 ? `<span class="w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-bold text-white bg-white/20 ring-1 ring-black">+${overflow}</span>` : '');
                voteCount.textContent = `${votes}/${vetoNeeded} to veto`;
            } else {
                voteInfo.classList.add('hidden');
            }
        }

        // Compound veto overlay — desaturation + gradient + pulsing glow
        const intensity = votes / vetoNeeded; // 0 to 1
        const img       = card.querySelector('img');
        const inner     = card.querySelector('.card');

        if (img) {
            img.style.filter = votes > 0 ? `grayscale(${Math.round(intensity * 75)}%)` : '';
        }

        if (heat) {
            heat.style.background = votes > 0
                ? `linear-gradient(to top, rgba(200,0,0,${(0.3 + intensity * 0.6).toFixed(2)}) 0%, rgba(180,0,0,${(intensity * 0.25).toFixed(2)}) 50%, transparent 80%)`
                : '';
        }


        // "You" badge — shows when current user has voted on this card
        if (votedOvl) {
            if (iVoted) {
                votedOvl.classList.remove('hidden');
                votedOvl.innerHTML = '<span class="absolute top-2 left-2 text-[9px] font-bold text-white bg-accent px-1.5 py-0.5 rounded-full">✓ You</span>';
            } else {
                votedOvl.classList.add('hidden');
                votedOvl.innerHTML = '';
            }
        }
    });

    // Graveyard
    document.querySelectorAll('.graveyard-card').forEach(card => {
        const movieId     = String(card.dataset.id);
        const voters      = state.restoreVotes[movieId] || [];
        const votes       = voters.length;
        const pct         = pCount > 0 ? Math.round((votes / vetoNeeded) * 100) : 0;
        const bar         = card.querySelector('.restore-bar');
        const voteInfo    = card.querySelector('.vote-info');
        const voteAvatars = card.querySelector('.vote-avatars');
        const voteCount   = card.querySelector('.vote-count');

        if (bar) bar.style.width = Math.min(pct, 100) + '%';

        if (voteInfo) {
            if (votes > 0) {
                voteInfo.classList.remove('hidden');
                const visible  = voters.slice(0, 3);
                const overflow = voters.length - visible.length;
                voteAvatars.innerHTML = visible.map(vid => {
                    const p       = state.participants.find(p => p.userId === vid);
                    const initial = p ? p.name.slice(0, 1).toUpperCase() : '?';
                    const isMe    = vid === myId;
                    return `<span class="w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-bold text-white ring-1 ring-black
                        ${isMe ? 'bg-accent' : 'bg-green-500'}" title="${p ? esc(p.name) : ''}">${initial}</span>`;
                }).join('') + (overflow > 0 ? `<span class="w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-bold text-white bg-white/20 ring-1 ring-black">+${overflow}</span>` : '');
                voteCount.textContent = `${votes}/${vetoNeeded} to restore`;
            } else {
                voteInfo.classList.add('hidden');
            }
        }
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
    const btn    = document.getElementById('ready-btn');
    const pCount = activeParticipantCount();
    const needed = readyThreshold(pCount);
    const iReady = state.ready.includes(myId);
    const isDeciding = !iReady && state.ready.length === needed - 1;

    btn.textContent = iReady ? 'Unready' : 'Ready to Roll';
    const span = document.createElement('span');
    span.className = 'text-xs opacity-70 ml-1';
    span.textContent = `${state.ready.length}/${needed}`;
    btn.appendChild(span);
    btn.classList.toggle('btn-secondary', iReady);
    btn.classList.toggle('btn-accent', !iReady);
    btn.classList.toggle('collab-btn-glow', isDeciding);
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
    updateTensionMode(remaining);
}

// ── Tension mode ──────────────────────────────────────────────────────
function updateTensionMode(remaining) {
    const isTension  = remaining <= 3 && remaining > 0;
    const isLocked   = remaining === 1;
    const vignette   = document.getElementById('tension-vignette');
    const title      = document.getElementById('collab-title');
    const bar        = document.getElementById('veto-progress');
    const graveyard  = document.getElementById('graveyard-section');
    const grid       = document.getElementById('collab-grid');
    const gravGrid   = document.getElementById('graveyard-grid');
    if (isTension) {
        vignette.classList.remove('hidden');
        vignette.classList.add('tension-vignette');

        const labels = { 3: 'Final 3 🔥', 2: 'Final 2 🔥', 1: 'Last One Standing 🔥' };
        if (title) title.textContent = labels[remaining];

        bar.classList.add('tension-progress', '!bg-red-600');
        if (graveyard) graveyard.style.opacity = isLocked ? '0.2' : '0.35';
    } else {
        vignette.classList.add('hidden');
        vignette.classList.remove('tension-vignette');

        if (title) title.textContent = 'Pick Together';

        bar.classList.remove('tension-progress', '!bg-red-600');
        if (graveyard) graveyard.style.opacity = '';
    }

    // Lock/unlock voting
    grid.style.pointerEvents     = isLocked ? 'none' : '';
    gravGrid.style.pointerEvents = isLocked ? 'none' : '';
    document.getElementById('ready-btn').classList.toggle('hidden', isLocked);
    if (isLocked) updateTryAgainButton();

    // Auto-show winner when 1 card left (once only)
    if (isLocked && state.movies.length === 1 && !winnerShown) {
        winnerShown = true;
        setTimeout(() => showWinner(state.movies[0]), 800);
    }
}

// ── Roll animation then winner overlay ───────────────────────────────
function triggerRoll(winner) {
    if (rollInProgress) return;
    rollInProgress = true;
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

    // Blurred background
    if (movie.poster_path) {
        document.getElementById('winner-bg').style.backgroundImage
            = `url(https://image.tmdb.org/t/p/w342${movie.poster_path})`;
    }

    document.getElementById('winner-poster').innerHTML = movie.poster_path
        ? `<img src="https://image.tmdb.org/t/p/w342${movie.poster_path}" class="w-full h-full object-cover">`
        : '';
    document.getElementById('winner-title').textContent = title;
    document.getElementById('winner-rating').textContent = movie.vote_average
        ? `★ ${Number(movie.vote_average).toFixed(1)}` : '';
    const year = (movie.release_date || movie.first_air_date || '').slice(0, 4);
    const meta = [year, movie.genres].filter(Boolean).join(' · ');
    document.getElementById('winner-meta').textContent = meta;
    document.getElementById('winner-details-link').href = url;
    document.getElementById('winner-overlay').classList.remove('hidden');
    confetti();
}

document.getElementById('winner-share').addEventListener('click', () => {
    const link = document.getElementById('winner-details-link').href;
    navigator.clipboard.writeText(link).then(() => showToast('Link copied!', 'green'));
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
    const shapes    = ['50%', '0', '0'];
    for (let i = 0; i < 80; i++) {
        const piece = document.createElement('div');
        const w     = 6 + Math.random() * 6;
        const h     = 10 + Math.random() * 8;
        const shape = shapes[Math.floor(Math.random() * shapes.length)];
        const dur   = 1.4 + Math.random() * 1.2;
        const delay = Math.random() * 0.8;
        const spin  = Math.random() * 720 - 360;
        piece.style.cssText = `position:absolute;width:${w}px;height:${h}px;border-radius:${shape};
            left:${Math.random()*100}%;top:-16px;pointer-events:none;
            background:hsl(${Math.random()*360},85%,60%);
            animation:confetti-fall ${dur}s ease-in ${delay}s forwards;
            transform:rotate(${Math.random()*360}deg);`;
        container.appendChild(piece);
        setTimeout(() => piece.remove(), (dur + delay) * 1000 + 100);
    }
}

// ── Util ──────────────────────────────────────────────────────────────
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
}

// ── Tap to skip hint ──────────────────────────────────────────────────
if (!localStorage.getItem('collab_hint_seen')) {
    const firstCard = document.querySelector('.collab-card .vote-target');
    if (firstCard) {
        const hint = document.createElement('div');
        hint.className = 'absolute inset-0 flex items-center justify-center bg-black/50 pointer-events-none z-10';
        hint.innerHTML = '<span class="text-white text-xs font-medium bg-black/60 px-2 py-1 rounded-full">Tap to skip</span>';
        firstCard.appendChild(hint);
        setTimeout(() => hint.remove(), 3000);
    }
}

document.getElementById('collab-grid').addEventListener('click', () => {
    localStorage.setItem('collab_hint_seen', '1');
}, { once: true });

// ── Init ──────────────────────────────────────────────────────────────
updateParticipants();
updateAllVoteBars();
updateReadyButton();
updateRefreshButton();
updateProgress();
updateTensionMode(state.movies.length);
document.getElementById('total-num').textContent = totalMovies;
