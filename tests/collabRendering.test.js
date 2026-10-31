import test from 'node:test';
import assert from 'node:assert/strict';
import { escapeHtml, posterUrl } from '../resources/js/custom/collabRendering.js';

test('escapes participant and movie text before insertion into HTML or attributes', () => {
    assert.equal(escapeHtml('<img src=x onerror="alert(1)">'), '&lt;img src=x onerror=&quot;alert(1)&quot;&gt;');
    assert.equal(escapeHtml('A&B\'s "name"'), 'A&amp;B&#39;s &quot;name&quot;');
});

test('rejects injected attributes, external URLs, and CSS-breaking poster paths', () => {
    for (const path of ['/poster.jpg" onerror="alert(1)', 'https://evil.example/image.jpg', '/x);color:red', null, '<svg>']) {
        assert.equal(posterUrl(path), '');
    }
    assert.equal(posterUrl('/poster.jpg'), 'https://image.tmdb.org/t/p/w342/poster.jpg');
});
