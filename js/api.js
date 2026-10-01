/* Client de l'API PHP/MySQL (cookies de session + jeton CSRF) */
const Api = (() => {
    let csrf = null, meCache = null;
    async function raw(path, { method = 'GET', body } = {}) {
        if (method !== 'GET' && !csrf) await me(true);
        const r = await fetch('api/' + path, {
            method, credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', ...(csrf ? { 'X-CSRF-Token': csrf } : {}) },
            body: body !== undefined ? JSON.stringify(body) : undefined
        });
        const j = await r.json().catch(() => ({}));
        if (j && j.csrf) csrf = j.csrf;
        if (!r.ok) { const e = new Error(j.error || 'Erreur réseau'); e.status = r.status; e.data = j; throw e; }
        return j;
    }
    async function me(force) { if (meCache && !force) return meCache; return (meCache = await raw('auth.php')); }
    /* Affiche un texte brut en paragraphes (ligne vide = nouveau paragraphe, **gras**), sans HTML injecté */
    function renderText(el, text) {
        el.textContent = '';
        String(text || '').split(/\n\s*\n/).forEach(par => {
            const p = document.createElement('p');
            par.split(/\*\*(.+?)\*\*/g).forEach((part, i) => {
                if (!part) return;
                if (i % 2) { const b = document.createElement('strong'); b.textContent = part; p.appendChild(b); }
                else p.appendChild(document.createTextNode(part));
            });
            el.appendChild(p);
        });
    }
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    return {
        get: p => raw(p), post: (p, b) => raw(p, { method: 'POST', body: b ?? {} }),
        put: (p, b) => raw(p, { method: 'PUT', body: b ?? {} }), del: p => raw(p, { method: 'DELETE' }),
        me, renderText, esc,
        async logout() { await raw('auth.php?action=logout', { method: 'POST', body: {} }); meCache = null; location.href = 'index.html'; }
    };
})();
