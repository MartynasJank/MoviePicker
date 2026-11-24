export function appendAtCurrentPosition(feed, append) {
    const top = feed.scrollTop;
    append();
    feed.scrollTo({ top, behavior: 'instant' });
}

export class SwipeTrailerBuffer {
    constructor(prepare, radius = 2) {
        this.prepare = prepare;
        this.radius = radius;
        this.entries = new Map();
        this.active = null;
        this.suspended = false;
        this.pausedCards = new Set();
        this.muted = true;
    }

    update(cards, index) {
        this.active = cards[index] ?? null;
        const neighbours = this.active !== null
            ? cards.slice(Math.max(0, index - this.radius), index + this.radius + 1)
            : [];
        const wanted = new Set(neighbours);

        for (const [card, entry] of this.entries) {
            if (!wanted.has(card)) {
                this.entries.delete(card);
                entry.player?.destroy();
            } else if (card !== this.active) {
                entry.player?.pause();
            }
        }

        // Start the visible film first, then warm its nearest neighbours.
        const ordered = neighbours.sort((a, b) => Math.abs(cards.indexOf(a) - index) - Math.abs(cards.indexOf(b) - index));
        for (const card of ordered) {
            if (!this.entries.has(card)) {
                const entry = { player: null };
                this.entries.set(card, entry);
                entry.ready = Promise.resolve()
                    .then(() => this.prepare(card, () => this.entries.get(card) === entry))
                    .then(player => {
                        if (this.entries.get(card) !== entry) {
                            player?.destroy();
                            return;
                        }
                        if (!player) {
                            this.entries.delete(card);
                            return;
                        }
                        entry.player = player;
                        this.sync(card, player);
                    })
                    .catch(() => {
                        if (this.entries.get(card) === entry) this.entries.delete(card);
                    });
            } else if (card === this.active && this.entries.get(card).player) {
                this.sync(card, this.entries.get(card).player);
            }
        }
    }

    sync(card, player) {
        player.setMuted?.(this.muted);
        if (!this.suspended && card === this.active && !this.pausedCards.has(card)) player.play();
        else player.pause();
    }

    setPaused(card, paused) {
        if (paused) this.pausedCards.add(card);
        else this.pausedCards.delete(card);
        const player = this.entries.get(card)?.player;
        if (player) this.sync(card, player);
    }

    setMuted(muted) {
        this.muted = muted;
        for (const [card, entry] of this.entries) {
            if (entry.player) this.sync(card, entry.player);
        }
    }

    suspend(suspended) {
        this.suspended = suspended;
        for (const [card, entry] of this.entries) {
            if (entry.player) this.sync(card, entry.player);
        }
    }

    retry(card) {
        const entry = this.entries.get(card);
        this.entries.delete(card);
        entry?.player?.destroy();
    }
}
