import test from 'node:test';
import assert from 'node:assert/strict';
import { createServer } from 'vite';

test('the dev pipeline supplies jQuery to each entry point that uses it', async () => {
    const server = await createServer({
        mode: 'test',
        logLevel: 'silent',
        server: { middlewareMode: true, hmr: false, watch: null },
        appType: 'custom',
    });
    try {
        for (const entry of ['app', 'custom/watchlist', 'custom/criteriaForm', 'custom/trailerModal',
            'custom/search', 'custom/roulettes', 'custom/rouletteForm']) {
            const result = await server.transformRequest(`/resources/js/${entry}.js`);
            assert.ok(result, `${entry} must compile`);
            assert.match(result.code, /import[^\n]*jquery/, `${entry} must receive its jQuery import`);
        }
    } finally {
        await server.close();
    }
});

test('the dev pipeline compiles Tailwind and preserves the shared rating styles', async () => {
    const server = await createServer({
        mode: 'test',
        logLevel: 'silent',
        server: { middlewareMode: true, hmr: false, watch: null },
        appType: 'custom',
    });
    try {
        const result = await server.transformRequest('/resources/css/app.css');
        assert.ok(result);
        assert.match(result.code, /\.rating-pill/);
        assert.match(result.code, /\.bg-accent/);
        assert.doesNotMatch(result.code, /@tailwind utilities/);
    } finally {
        await server.close();
    }
});
