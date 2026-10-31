export function escapeHtml(value) {
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

export function posterUrl(path) {
    return typeof path === 'string' && /^\/[a-zA-Z0-9._/-]+$/.test(path)
        ? `https://image.tmdb.org/t/p/w342${path}`
        : '';
}
