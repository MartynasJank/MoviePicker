import test from 'node:test';
import assert from 'node:assert/strict';
import { SwipeTrailerCandidates } from '../resources/js/custom/swipeV2Candidates.js';

test('adds only movies with confirmed trailers and passes their details through to the feed', async () => {
    const candidates = new SwipeTrailerCandidates(async movie => ({ trailer: movie.id === 2 ? 'official123' : null, overview: 'Synopsis' }));
    const visible = [];

    const result = await candidates.check([{ id: 1 }, { id: 2 }, { id: 3 }], (movie, details) => visible.push({ movie, details }));

    assert.deepEqual(visible, [{ movie: { id: 2 }, details: { trailer: 'official123', overview: 'Synopsis' } }]);
    assert.deepEqual(result, { accepted: 1, failed: [] });
});

test('does not add any cards when an entire batch lacks trailers', async () => {
    const candidates = new SwipeTrailerCandidates(async () => ({ trailer: null }));
    const visible = [];

    const result = await candidates.check([{ id: 1 }, { id: 2 }], movie => visible.push(movie));

    assert.deepEqual(visible, []);
    assert.deepEqual(result, { accepted: 0, failed: [] });
});

test('reuses successful checks including missing trailers and deduplicates candidates', async () => {
    const calls = [];
    const candidates = new SwipeTrailerCandidates(async movie => {
        calls.push(movie.id);
        return { trailer: movie.id === 2 ? 'official123' : null };
    });
    const movies = [{ id: 1 }, { id: 2 }, { id: 1 }];

    await candidates.check(movies, () => {});
    await candidates.check(movies, () => {});

    assert.deepEqual(calls, [1, 2]);
});

test('keeps temporary failures retryable instead of classifying them as missing trailers', async () => {
    let calls = 0;
    const candidates = new SwipeTrailerCandidates(async () => {
        if (++calls === 1) throw new Error('Service unavailable');
        return { trailer: 'official123' };
    });
    const visible = [];
    const first = await candidates.check([{ id: 1 }], movie => visible.push(movie));
    assert.deepEqual(first, { accepted: 0, failed: [{ id: 1 }] });
    assert.deepEqual(visible, []);

    const retry = await candidates.check(first.failed, movie => visible.push(movie));

    assert.deepEqual(retry, { accepted: 1, failed: [] });
    assert.deepEqual(visible, [{ id: 1 }]);
    assert.equal(calls, 2);
});

test('shows a confirmed movie before slower checks finish and limits parallel requests', async () => {
    const finish = new Map();
    let pending = 0;
    let peak = 0;
    const candidates = new SwipeTrailerCandidates(movie => {
        peak = Math.max(peak, ++pending);
        return new Promise(resolve => finish.set(movie.id, details => { pending--; resolve(details); }));
    }, 2);
    const visible = [];
    const checking = candidates.check([{ id: 1 }, { id: 2 }, { id: 3 }], movie => visible.push(movie.id));

    finish.get(2)({ trailer: 'official123' });
    await Promise.resolve();
    assert.deepEqual(visible, [2]);
    assert.equal(finish.has(3), true);
    finish.get(1)({ trailer: null });
    finish.get(3)({ trailer: 'fan12345678' });
    await checking;

    assert.deepEqual(visible, [2, 3]);
    assert.equal(peak, 2);
});
