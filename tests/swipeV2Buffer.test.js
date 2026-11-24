import test from 'node:test';
import assert from 'node:assert/strict';
import { appendAtCurrentPosition, SwipeTrailerBuffer } from '../resources/js/custom/swipeV2Buffer.js';

function fakePlayer() {
    return {
        playing: false,
        destroyed: false,
        muted: true,
        play() { this.playing = true; },
        pause() { this.playing = false; },
        destroy() { this.destroyed = true; this.playing = false; },
        setMuted(muted) { this.muted = muted; },
    };
}

async function settled(buffer) {
    await Promise.all([...buffer.entries.values()].map(entry => entry.ready));
}

test('shows the first movie instead of following the loading message as cards are inserted', () => {
    const feed = {
        scrollTop: 0,
        scrollTo({ top, behavior }) {
            assert.equal(behavior, 'instant');
            this.scrollTop = top;
        },
    };

    appendAtCurrentPosition(feed, () => { feed.scrollTop = 12000; });

    assert.equal(feed.scrollTop, 0);
});

test('keeps the current position when another page is appended', () => {
    const feed = { scrollTop: 1800, scrollTo({ top }) { this.scrollTop = top; } };

    appendAtCurrentPosition(feed, () => { feed.scrollTop = 24000; });

    assert.equal(feed.scrollTop, 1800);
});

test('prepares two films in both directions while playing only the visible trailer', async () => {
    const players = new Map();
    const buffer = new SwipeTrailerBuffer(card => {
        const player = fakePlayer();
        players.set(card, player);
        return player;
    });

    buffer.update([0, 1, 2, 3, 4, 5, 6], 3);
    await settled(buffer);

    assert.deepEqual([...players.keys()], [3, 2, 4, 1, 5]);
    assert.deepEqual([...players].filter(([, player]) => player.playing).map(([card]) => card), [3]);
});

test('reuses trailers on forward and backward swipes and releases distant players', async () => {
    const players = new Map();
    const calls = [];
    const cards = [0, 1, 2, 3, 4, 5, 6];
    const buffer = new SwipeTrailerBuffer(card => {
        calls.push(card);
        const player = fakePlayer();
        players.set(card, player);
        return player;
    });
    buffer.update(cards, 2);
    await settled(buffer);
    const previous = players.get(2);

    buffer.update(cards, 3);
    await settled(buffer);
    assert.equal(players.get(0).destroyed, true);
    assert.equal(previous.playing, false);
    assert.equal(players.get(3).playing, true);
    buffer.update(cards, 2);
    await settled(buffer);

    assert.equal(players.get(2), previous);
    assert.equal(previous.playing, true);
    assert.equal(calls.filter(card => card === 2).length, 1);
    assert.equal(buffer.entries.size, 5);
});

test('does not duplicate pending loads or play trailers that finish loading after a fast swipe', async () => {
    let finish;
    let calls = 0;
    const latePlayer = fakePlayer();
    const buffer = new SwipeTrailerBuffer(() => {
        calls++;
        return new Promise(resolve => { finish = resolve; });
    }, 0);
    buffer.update([0], 0);
    const pending = [...buffer.entries.values()][0].ready;
    buffer.update([0], 0);
    await Promise.resolve();
    buffer.update([0], -1);

    finish(latePlayer);
    await pending;

    assert.equal(calls, 1);
    assert.equal(latePlayer.playing, false);
    assert.equal(latePlayer.destroyed, true);
    assert.equal(buffer.entries.size, 0);
});

test('pauses all trailers in a hidden tab and resumes only the active one', async () => {
    const players = [];
    const buffer = new SwipeTrailerBuffer(() => {
        const player = fakePlayer();
        players.push(player);
        return player;
    });
    buffer.update([0, 1, 2], 0);
    await settled(buffer);

    buffer.suspend(true);
    assert.equal(players.some(player => player.playing), false);
    buffer.suspend(false);

    assert.equal(players[0].playing, true);
    assert.equal(players.slice(1).some(player => player.playing), false);
});

test('allows a failed preload to be retried', async () => {
    let calls = 0;
    const player = fakePlayer();
    const buffer = new SwipeTrailerBuffer(() => {
        if (++calls === 1) throw new Error('Offline');
        return player;
    });
    buffer.update([0], 0);
    await settled(buffer);

    buffer.update([0], 0);
    await settled(buffer);

    assert.equal(calls, 2);
    assert.equal(player.playing, true);
});

test('keeps a manually paused trailer paused across preloading, tab switches and back-swipes', async () => {
    const players = new Map();
    const cards = [0, 1, 2];
    const buffer = new SwipeTrailerBuffer(card => {
        const player = fakePlayer();
        players.set(card, player);
        return player;
    });
    buffer.update(cards, 0);
    await settled(buffer);

    buffer.setPaused(0, true);
    buffer.update(cards, 0);
    buffer.suspend(true);
    buffer.suspend(false);
    buffer.update(cards, 1);
    buffer.update(cards, 0);

    assert.equal(players.get(0).playing, false);
    assert.equal(players.get(1).playing, false);
    buffer.setPaused(0, false);
    assert.equal(players.get(0).playing, true);
    assert.equal(players.get(1).playing, false);
});

test('keeps the sound preference across swipes and applies it to newly prepared players', async () => {
    const players = new Map();
    const cards = [0, 1, 2, 3];
    const buffer = new SwipeTrailerBuffer(card => {
        const player = fakePlayer();
        players.set(card, player);
        return player;
    });
    buffer.update(cards, 0);
    await settled(buffer);

    buffer.setMuted(false);
    buffer.update(cards, 1);
    await settled(buffer);

    assert.equal([...players.values()].every(player => !player.muted), true);
    assert.equal(players.get(0).playing, false);
    assert.equal(players.get(1).playing, true);
    buffer.setMuted(true);
    assert.equal([...players.values()].every(player => player.muted), true);
});
