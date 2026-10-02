/* Formulaires newsletter du pied de page + page de désinscription. Autonome (n'a pas besoin de js/api.js). */
const Newsletter = (() => {
    async function post(path, body) {
        const t = (await (await fetch('api/auth.php', { credentials: 'same-origin' })).json()).csrf;
        const r = await fetch('api/' + path, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': t }, body: JSON.stringify(body) });
        const j = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(j.error || 'Une erreur est survenue, réessayez.');
        return j;
    }
    document.querySelectorAll('form.nl-form').forEach(f => {
        const msg = f.parentElement.querySelector('.nl-msg');
        f.addEventListener('submit', async e => {
            e.preventDefault(); const btn = f.querySelector('button'); btn.disabled = true; msg.textContent = ''; msg.className = 'nl-msg';
            try { const j = await post('newsletter.php', { email: f.email.value, website: f.website.value }); msg.textContent = j.message; f.reset(); }
            catch (er) { msg.textContent = er.message; msg.classList.add('err'); }
            btn.disabled = false;
        });
    });
    return { post };
})();
