import { appendAtCurrentPosition, SwipeTrailerBuffer } from './swipeV2Buffer.js';
import { SwipeTrailerCandidates } from './swipeV2Candidates.js';

let youtubeApi;

function loadYouTubeApi() {
    if (window.YT?.Player) return Promise.resolve(window.YT);
    if (youtubeApi) return youtubeApi;
    youtubeApi = new Promise((resolve, reject) => {
        const previousReady = window.onYouTubeIframeAPIReady;
        const script = document.createElement('script');
        const timeout = setTimeout(() => reject(new Error('YouTube timed out')), 15000);
        window.onYouTubeIframeAPIReady = () => {
            clearTimeout(timeout);
            previousReady?.();
            resolve(window.YT);
        };
        script.src = 'https://www.youtube.com/iframe_api';
        script.onerror = () => {
            clearTimeout(timeout);
            reject(new Error('YouTube unavailable'));
        };
        document.head.append(script);
    }).catch(error => {
        youtubeApi = null;
        throw error;
    });
    return youtubeApi;
}

const root = document.getElementById('swipe-v2');

if (root) {
    const feed = document.getElementById('trailer-feed');
    const status = document.getElementById('feed-status');
    const message = document.getElementById('feed-message');
    const retry = document.getElementById('feed-retry');
    const template = document.getElementById('trailer-card-template');
    const notice = document.getElementById('swipe-notice');
    const saved = new Set(JSON.parse(root.dataset.saved).map(Number));
    const seen = new Set();
    const cards = [];
    const failedCandidates = [];
    let previousPage = null;
    let hasMore = true;
    let loading = false;
    let active = null;
    let noticeTimeout;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const mobileView = window.matchMedia('(max-width: 639px)');
    const trailerBuffer = new SwipeTrailerBuffer(preparePlayer);
    const trailerCandidates = new SwipeTrailerCandidates(movie => request(root.dataset.detailUrl.replace(/\/0$/, `/${movie.id}`)));
    trailerBuffer.suspend(document.hidden);

    async function request(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            signal: AbortSignal.timeout(20000),
        });
        if (!response.ok) throw new Error(`Request failed (${response.status})`);
        return response.json();
    }

    function announce(text) {
        clearTimeout(noticeTimeout);
        notice.textContent = text;
        noticeTimeout = setTimeout(() => { notice.textContent = ''; }, 4000);
    }

    function saveLabel(card) {
        const button = card.element.querySelector('[data-save]');
        const isSaved = saved.has(card.movie.id);
        button.querySelector('[data-save-label]').textContent = isSaved ? 'Saved' : 'Save';
        button.dataset.saved = String(isSaved);
        button.setAttribute('aria-label', isSaved ? 'Saved to your watchlist' : 'Save to watchlist');
        button.disabled = isSaved || !card.details;
    }

    async function preparePlayer(card, isRetained) {
        if (!card.details?.trailer || !isRetained()) return null;
        try {
            const youtube = await loadYouTubeApi();
            if (!isRetained()) return null;
            return createPlayer(card, youtube, isRetained);
        } catch (error) {
            if (isRetained()) {
                showPlayerError(card);
            }
            throw error;
        }
    }

    function showPlayerError(card) {
        card.element.querySelector('[data-play]').classList.add('hidden');
        card.element.querySelector('[data-mute]').classList.add('hidden');
        card.element.querySelector('[data-trailer-status]').classList.remove('hidden');
        card.element.querySelector('[data-trailer-message]').textContent = 'Trailer could not load. Retry or watch on YouTube.';
        card.element.querySelector('[data-retry]').classList.remove('hidden');
    }

    function createPlayer(card, youtube, isRetained) {
        const slot = card.element.querySelector('[data-player]');
        const mount = document.createElement('div');
        slot.append(mount);
        let ready = false;
        let shouldPlay = false;
        let destroyed = false;
        let muted = trailerBuffer.muted;
        const fail = () => {
            if (!isRetained()) return;
            showPlayerError(card);
            trailerBuffer.retry(card);
        };
        const timeout = setTimeout(fail, 15000);
        const player = new youtube.Player(mount, {
            host: 'https://www.youtube-nocookie.com',
            videoId: card.details.trailer,
            playerVars: {
                autoplay: 0, mute: 1, playsinline: 1, rel: 0, origin: window.location.origin,
                controls: mobileView.matches ? 0 : 1,
            },
            events: {
                onReady: event => {
                    clearTimeout(timeout);
                    if (destroyed) return;
                    ready = true;
                    if (muted) event.target.mute();
                    else event.target.unMute();
                    card.element.querySelector('[data-play]').classList.remove('hidden');
                    card.element.querySelector('[data-mute]').classList.remove('hidden');
                    updateSoundButton(card);
                    if (shouldPlay) event.target.playVideo();
                },
                onError: fail,
                onStateChange: event => {
                    card.playing = event.data === 1 || event.data === 3;
                    const button = card.element.querySelector('[data-play]');
                    button.setAttribute('aria-label', card.playing ? 'Pause trailer' : 'Play trailer');
                    button.querySelector('[data-play-icon]').classList.toggle('hidden', card.playing);
                    if (event.data === 1 && (card !== active || document.hidden)) event.target.pauseVideo();
                },
            },
        });
        const iframe = player.getIframe();
        iframe.title = `${card.movie.title} trailer`;
        iframe.className = 'pointer-events-none absolute inset-0 h-full w-full border-0 sm:pointer-events-auto';
        iframe.tabIndex = mobileView.matches ? -1 : 0;
        iframe.referrerPolicy = 'strict-origin-when-cross-origin';
        return {
            play: () => {
                shouldPlay = true;
                if (ready && !destroyed) player.playVideo();
            },
            pause: () => {
                shouldPlay = false;
                if (ready && !destroyed) player.pauseVideo();
            },
            setMuted: value => {
                muted = value;
                if (ready && !destroyed) {
                    if (muted) player.mute();
                    else player.unMute();
                }
                updateSoundButton(card);
            },
            destroy: () => {
                destroyed = true;
                card.playing = false;
                card.element.querySelector('[data-play]').classList.add('hidden');
                card.element.querySelector('[data-mute]').classList.add('hidden');
                clearTimeout(timeout);
                player.destroy();
            },
        };
    }

    function updateSoundButton(card) {
        const muted = trailerBuffer.muted;
        const button = card.element.querySelector('[data-mute]');
        button.setAttribute('aria-label', muted ? 'Unmute trailer' : 'Mute trailer');
        button.setAttribute('aria-pressed', String(!muted));
        button.querySelector('[data-sound-off]').classList.toggle('hidden', !muted);
        button.querySelector('[data-sound-on]').classList.toggle('hidden', muted);
    }

    function renderDetails(card) {
        const element = card.element;
        const details = card.details;
        if (details.poster_path) {
            const backdrop = element.querySelector('[data-backdrop]');
            backdrop.src = `https://image.tmdb.org/t/p/w500${details.poster_path}`;
            backdrop.classList.remove('hidden');
        }
        element.querySelector('[data-meta]').textContent = [details.year, details.runtime ? `${details.runtime} min` : null, details.genres].filter(Boolean).join(' · ');
        element.querySelector('[data-overview]').textContent = details.overview || 'No synopsis available yet.';
        element.querySelector('[data-rating]').textContent = details.vote_average ? `★ ${Number(details.vote_average).toFixed(1)} TMDB` : 'Unrated';
        element.querySelector('[data-trailer-status]').classList.add('hidden');
        const youtube = element.querySelector('[data-youtube]');
        youtube.href = `https://www.youtube.com/watch?v=${details.trailer}`;
        youtube.classList.remove('hidden');
        saveLabel(card);
    }

    function activate(card) {
        if (active === card) return;
        active = card;
        trailerBuffer.update(cards, cards.indexOf(card));
        if (cards.indexOf(card) >= cards.length - 3) loadFeed();
    }

    const observer = new IntersectionObserver(entries => {
        for (const entry of entries) {
            if (entry.isIntersecting && entry.intersectionRatio >= 0.6) {
                activate(cards.find(card => card.element === entry.target));
            }
        }
    }, { root: feed, threshold: 0.6 });

    async function move(direction) {
        const index = active ? cards.indexOf(active) : cards.length;
        if (direction > 0 && index === cards.length - 1) await loadFeed();
        const target = cards[index + direction];
        if (target) target.element.scrollIntoView({ behavior: reducedMotion ? 'instant' : 'smooth', block: 'start' });
        else if (direction > 0) status.scrollIntoView({ behavior: reducedMotion ? 'instant' : 'smooth' });
    }

    function addCard(movie, details) {
        if (seen.has(movie.id) || !details?.trailer) return;
        seen.add(movie.id);
        const element = template.content.firstElementChild.cloneNode(true);
        const card = { movie, element, details, playing: false };
        cards.push(card);
        element.querySelector('[data-title]').textContent = movie.title;
        element.querySelector('[data-position]').textContent = String(cards.length).padStart(2, '0');
        element.querySelector('[data-details]').href = movie.url;
        element.querySelector('[data-next]').addEventListener('click', () => move(1));
        element.querySelector('[data-play]').addEventListener('click', () => {
            if (card === active) trailerBuffer.setPaused(card, card.playing);
        });
        element.querySelector('[data-mute]').addEventListener('click', () => {
            if (card === active) trailerBuffer.setMuted(!trailerBuffer.muted);
        });
        element.querySelector('[data-retry]').addEventListener('click', () => {
            trailerBuffer.retry(card);
            element.querySelector('[data-trailer-status]').classList.add('hidden');
            trailerBuffer.update(cards, cards.indexOf(active));
        });
        element.querySelector('[data-save]').addEventListener('click', async event => {
            const button = event.currentTarget;
            button.disabled = true;
            try {
                await request(root.dataset.saveUrl, {
                    method: 'POST',
                    body: JSON.stringify({
                        tmdb_id: movie.id, title: movie.title, type: 'movie', source: 'swipe',
                        poster_path: card.details.poster_path, year: card.details.year,
                        genres: card.details.genres, vote_average: card.details.vote_average,
                    }),
                });
                saved.add(movie.id);
                announce('Saved to your watchlist');
            } catch {
                announce('Could not save this movie. Please try again.');
            } finally {
                saveLabel(card);
            }
        });
        renderDetails(card);
        feed.insertBefore(element, status);
        observer.observe(element);
    }

    async function loadFeed() {
        if (loading || (!hasMore && failedCandidates.length === 0)) return;
        loading = true;
        retry.classList.add('hidden');
        message.textContent = 'Finding something worth watching…';
        try {
            let accepted = 0;
            for (let attempt = 0; attempt < 3 && accepted === 0; attempt++) {
                let candidates = failedCandidates.splice(0);
                if (candidates.length === 0) {
                    const query = previousPage === null ? '' : `?exclude_page=${previousPage}`;
                    const data = await request(`${root.dataset.feedUrl}${query}`);
                    previousPage = data.page;
                    hasMore = data.has_more;
                    candidates = data.movies;
                }
                const result = await trailerCandidates.check(candidates.filter(movie => !seen.has(movie.id)), (movie, details) => {
                    const firstLoad = cards.length === 0;
                    appendAtCurrentPosition(feed, () => addCard(movie, details));
                    if (firstLoad) activate(cards[0]);
                    else if (active) trailerBuffer.update(cards, cards.indexOf(active));
                });
                accepted += result.accepted;
                failedCandidates.push(...result.failed);
                if (!hasMore || failedCandidates.length) break;
            }
            if (failedCandidates.length) {
                message.textContent = 'Some trailers could not be checked. Try again.';
                retry.classList.remove('hidden');
            } else if (accepted === 0 && hasMore) {
                message.textContent = 'No new trailers found in these batches. Try more films.';
                retry.classList.remove('hidden');
            } else {
                message.textContent = !hasMore
                    ? (cards.length ? 'You’re all caught up. Scroll back to revisit a film.' : 'No movies with trailers found. Try again later.')
                    : 'Scroll for more films';
            }
        } catch {
            message.textContent = 'Could not load movies. Please try again.';
            retry.classList.remove('hidden');
        } finally {
            loading = false;
        }
    }

    const endObserver = new IntersectionObserver(entries => {
        if (entries.some(entry => entry.isIntersecting)) {
            active = null;
            trailerBuffer.update(cards, -1);
            if (retry.classList.contains('hidden')) loadFeed();
        }
    }, { root: feed, threshold: 0.6 });
    endObserver.observe(status);
    retry.addEventListener('click', loadFeed);
    root.querySelectorAll('[data-direction]').forEach(button => button.addEventListener('click', () => move(Number(button.dataset.direction))));
    document.addEventListener('keydown', event => {
        if (!['ArrowDown', 'ArrowUp'].includes(event.key) || event.target.closest('input, textarea, select, button, a, [contenteditable="true"]')) return;
        event.preventDefault();
        move(event.key === 'ArrowDown' ? 1 : -1);
    });
    document.addEventListener('visibilitychange', () => {
        trailerBuffer.suspend(document.hidden);
    });
    mobileView.addEventListener('change', () => {
        for (const card of [...trailerBuffer.entries.keys()]) trailerBuffer.retry(card);
        trailerBuffer.update(cards, cards.indexOf(active));
    });
    loadFeed();
}
