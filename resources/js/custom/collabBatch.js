import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// ── Identity ──────────────────────────────────────────────────────────
const COLORS   = ['Red','Blue','Green','Purple','Orange','Pink','Teal','Gold'];
const ANIMALS  = ['Fox','Panda','Wolf','Bear','Hawk','Lynx','Otter','Raven'];

function getIdentity() {
    let id = localStorage.getItem('collab_identity');
    if (!id) {
        id = COLORS[Math.floor(Math.random() * COLORS.length)] + ' ' +
             ANIMALS[Math.floor(Math.random() * ANIMALS.length)];
        localStorage.setItem('collab_identity', id);
    }
    return id;
}

const identity = getIdentity();
const token    = window.collabToken;
let   remaining = (window.collabMovies || []).length;
const total     = remaining;

// ── Reverb / Echo ─────────────────────────────────────────────────────
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

// Track movie IDs we vetoed ourselves so we skip the bounce-back broadcast
const myVetoes = new Set();

echo.channel('batch.' + token)
    .listen('MovieVetoed', (e) => {
        if (myVetoes.has(e.movieId)) {
            myVetoes.delete(e.movieId);
        } else {
            removeCard(e.movieId, e.vetoedBy, false);
        }
        updateRemaining(e.remaining.length);
    })
    .listen('BatchComplete', (e) => {
        if (myVetoes.has(e.winner.id)) {
            myVetoes.delete(e.winner.id);
        } else {
            removeCard(e.winner.id, e.decidedBy, false);
        }
        showWinner(e.winner, e.decidedBy);
    })
    .subscribed(() => {
        // Track participant count (best-effort via presence isn't set up here,
        // so just show a connected indicator)
    });

// ── Veto ──────────────────────────────────────────────────────────────
document.querySelectorAll('.veto-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const movieId = parseInt(this.dataset.id);
        doVeto(movieId);
    });
});

async function doVeto(movieId) {
    myVetoes.add(movieId);
    removeCard(movieId, 'You', true);

    const res = await fetch(`/batch/collab/${token}/${movieId}`, {
        method:  'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept':       'application/json',
            'X-Socket-ID':  echo.socketId(),
        },
        body: JSON.stringify({ identity }),
    });

    const data = await res.json();

    if (data.remaining === 1 && data.winner) {
        showWinner(data.winner, 'You');
    } else {
        updateRemaining(data.remaining);
    }
}

// ── Card removal animation ────────────────────────────────────────────
function removeCard(movieId, vetoedBy, isSelf) {
    const card = document.querySelector(`.collab-card[data-id="${movieId}"]`);
    if (!card) return;

    // Shake then slide out
    card.style.transition = 'transform 0.15s, opacity 0.3s';
    card.style.transform  = 'scale(0.95)';
    setTimeout(() => {
        card.style.transform = 'scale(0) rotate(-5deg)';
        card.style.opacity   = '0';
        setTimeout(() => card.remove(), 300);
    }, 100);

    showVetoToast(movieId, vetoedBy, isSelf);
}

function showVetoToast(movieId, vetoedBy, isSelf) {
    const card  = document.querySelector(`.collab-card[data-id="${movieId}"]`);
    const title = card?.querySelector('.text-xs.font-medium')?.textContent || 'a movie';
    const name  = isSelf ? 'You' : vetoedBy;

    const el = document.createElement('div');
    el.className = 'bg-black/80 border border-white/10 text-white text-sm px-4 py-2.5 rounded-lg backdrop-blur-sm whitespace-nowrap pointer-events-auto';
    el.textContent = `${name} vetoed ${title}`;
    document.getElementById('veto-toasts').appendChild(el);
    setTimeout(() => el.remove(), 3000);
}

// ── Progress bar & count ──────────────────────────────────────────────
function updateRemaining(count) {
    remaining = count;
    document.getElementById('remaining-num').textContent = count;
    document.getElementById('remaining-count').querySelector('span + span') &&
        (document.getElementById('remaining-count').lastChild.textContent = count === 1 ? ' movie remaining' : ' movies remaining');
    const pct = total > 0 ? (count / total) * 100 : 0;
    document.getElementById('progress-veto').style.width = pct + '%';
}

// ── Winner ────────────────────────────────────────────────────────────
function showWinner(movie, decidedBy) {
    const overlay  = document.getElementById('winner-overlay');
    const isTv     = window.collabMediaType === 'tv' || movie.media_type === 'tv';
    const url      = (isTv ? '/tv/' : '/movie/') + movie.id;
    const title    = movie.title ?? movie.name ?? '';
    const poster   = movie.poster_path
        ? `<img src="https://image.tmdb.org/t/p/w342${movie.poster_path}" class="w-full h-full object-cover">`
        : '';

    document.getElementById('winner-poster').innerHTML    = poster;
    document.getElementById('winner-title').textContent   = title;
    document.getElementById('winner-link').href           = url;
    document.getElementById('winner-decided-by').textContent = decidedBy === 'You' ? 'You made the final call!' : `${decidedBy} made the final call!`;

    overlay.classList.remove('hidden');
    confetti();
}

function confetti() {
    const container = document.getElementById('winner-overlay');
    for (let i = 0; i < 60; i++) {
        const dot = document.createElement('div');
        dot.style.cssText = `
            position:absolute;
            width:8px;height:8px;border-radius:50%;
            left:${Math.random()*100}%;
            top:-10px;
            background:hsl(${Math.random()*360},80%,60%);
            animation:confetti-fall ${1.5+Math.random()}s linear ${Math.random()*0.5}s forwards;
        `;
        container.appendChild(dot);
        setTimeout(() => dot.remove(), 2500);
    }
}

// ── Invite / share button ─────────────────────────────────────────────
document.getElementById('collab-share-btn').addEventListener('click', () => {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({ title: 'Pick a movie with me!', url }).catch(() => {});
    } else if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(url).then(() => window.showSuccessToast?.('Invite link copied!'));
    } else {
        const ta = document.createElement('textarea');
        ta.value = url; ta.style.cssText = 'position:fixed;opacity:0';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); window.showSuccessToast?.('Invite link copied!'); } catch {}
        document.body.removeChild(ta);
    }
});
