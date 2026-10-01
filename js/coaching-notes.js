/* Éditeur du carnet de coaching : enregistrement automatique, un carnet par séance (1 à 3). */
const CoachingNotes = (() => {
    const CSS = `.cn{background:#fff;border-radius:16px;box-shadow:0 6px 20px rgba(36,58,45,.08);padding:1.5rem clamp(1rem,3vw,2rem);max-width:760px;margin:1.5rem auto}
.cn h2{font-size:1.3rem;margin:0 0 .3rem}.cn .cn-help{font-size:.9rem;color:#6f7a72;margin:0 0 1rem}
.cn textarea{width:100%;min-height:240px;resize:vertical;border:1px solid #d8dccf;border-radius:12px;padding:1rem;font:inherit;line-height:1.6;background:#fdfcf8;box-sizing:border-box}
.cn textarea:focus{outline:2px solid #3b5b45;outline-offset:1px}
.cn-bar{display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;margin-top:.8rem}.cn-status{font-size:.85rem;color:#6f7a72;flex:1;min-width:10rem}
.cn-status.err{color:#a33}.cn-count{font-size:.8rem;color:#8a9189}
.cn button{font:inherit;cursor:pointer;border-radius:999px;padding:.55rem 1.2rem;border:1px solid #3b5b45;background:#3b5b45;color:#fff}
.cn button.ghost{background:#fff;color:#3b5b45}.cn button:disabled{opacity:.5;cursor:default}`;
    if (!document.getElementById('cn-css')) { const s = document.createElement('style'); s.id = 'cn-css'; s.textContent = CSS; document.head.appendChild(s); }
    const MAX = 20000, hhmm = d => d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    const parseUtc = s => new Date(String(s).replace(' ', 'T') + 'Z');

    /* Monte l'éditeur dans `el` pour la séance `n`. `initial` = {content, updatedAt} déjà chargé (facultatif). */
    async function mount(el, n, initial) {
        el.classList.add('cn'); el.textContent = '';
        const h = document.createElement('h2'); h.textContent = `Mon carnet — Séance ${n}`;
        const help = document.createElement('p'); help.className = 'cn-help';
        help.textContent = 'Notez vos réponses, vos prises de conscience et votre plan d’action. Vous seul pouvez lire ces notes. Elles s’enregistrent automatiquement.';
        const ta = document.createElement('textarea'); ta.maxLength = MAX; ta.setAttribute('aria-label', `Notes de la séance ${n}`);
        ta.placeholder = 'Écrivez ici…';
        const bar = document.createElement('div'); bar.className = 'cn-bar';
        const status = document.createElement('span'); status.className = 'cn-status'; status.setAttribute('aria-live', 'polite');
        const count = document.createElement('span'); count.className = 'cn-count';
        const save = document.createElement('button'); save.type = 'button'; save.textContent = 'Enregistrer';
        const clear = document.createElement('button'); clear.type = 'button'; clear.className = 'ghost'; clear.textContent = 'Effacer';
        bar.append(status, count, clear, save); el.append(h, help, ta, bar);

        let saved = '', timer = null, busy = false;
        const setCount = () => { count.textContent = `${ta.value.length} / ${MAX}`; };
        const say = (t, err) => { status.textContent = t; status.classList.toggle('err', !!err); };
        async function flush() {
            clearTimeout(timer); if (busy || ta.value === saved) return; busy = true; const val = ta.value; say('Enregistrement…');
            try {
                const r = await Api.put(`coaching_notes.php?session=${n}`, { content: val });
                saved = val; say(val.trim() ? `Enregistré à ${hhmm(r.updatedAt ? parseUtc(r.updatedAt) : new Date())} ✓` : 'Carnet vide');
            } catch (e) { say(e.message || 'Échec de l’enregistrement', true); }
            busy = false; if (ta.value !== saved) { timer = setTimeout(flush, 1200); }
        }
        ta.addEventListener('input', () => { setCount(); say('Modifications non enregistrées…'); clearTimeout(timer); timer = setTimeout(flush, 1200); });
        ta.addEventListener('blur', flush); save.addEventListener('click', flush);
        clear.addEventListener('click', async () => {
            if (!ta.value.trim() || !confirm('Effacer toutes les notes de cette séance ?')) return;
            ta.value = ''; setCount(); await flush();
        });
        window.addEventListener('beforeunload', e => { if (ta.value !== saved) { e.preventDefault(); e.returnValue = ''; } });

        let note = initial;
        if (!note) { try { note = (await Api.get('coaching_notes.php')).notes[String(n)]; } catch (e) { say(e.message, true); ta.disabled = true; return ta; } }
        if (note) { ta.value = saved = note.content; say(`Dernier enregistrement à ${hhmm(parseUtc(note.updatedAt))}`); }
        setCount(); return { textarea: ta, flush, getValue: () => ta.value };
    }
    return { mount };
})();
