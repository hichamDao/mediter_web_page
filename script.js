/* ===== Plugin: Slider ===== */
class Slider {
    constructor(container) {
        this.container = container;
        this.slides = container.querySelectorAll('.slide');
        this.prevBtn = container.querySelector('.slider-btn--prev');
        this.nextBtn = container.querySelector('.slider-btn--next');
        this.dotsContainer = container.querySelector('.slider-dots');
        this.currentIndex = 0;
        this.total = this.slides.length;

        this.init();
    }

    init() {
        this.createDots();
        this.bindEvents();
        this.update();
    }

    createDots() {
        this.dotsContainer.innerHTML = '';
        for (let i = 0; i < this.total; i++) {
            const dot = document.createElement('button');
            dot.classList.add('slider-dot');
            dot.addEventListener('click', () => this.goTo(i));
            this.dotsContainer.appendChild(dot);
        }
        this.dots = this.dotsContainer.querySelectorAll('.slider-dot');
    }

    bindEvents() {
        if (this.prevBtn) this.prevBtn.addEventListener('click', () => this.prev());
        if (this.nextBtn) this.nextBtn.addEventListener('click', () => this.next());
    }

    goTo(index) {
        this.currentIndex = Math.max(0, Math.min(index, this.total - 1));
        this.update();
    }

    next() {
        this.currentIndex = (this.currentIndex + 1) % this.total;
        this.update();
    }

    prev() {
        this.currentIndex = (this.currentIndex - 1 + this.total) % this.total;
        this.update();
    }

    update() {
        const offset = -this.currentIndex * 100;
        this.container.querySelector('.slider').style.transform = `translateX(${offset}%)`;
        if (this.dots) {
            this.dots.forEach((d, i) => d.classList.toggle('active', i === this.currentIndex));
        }
    }
}

/* ===== Plugin: Accordion ===== */
class Accordion {
    constructor(accordion) {
        this.items = accordion.querySelectorAll('.accordion-item');
        this.bindEvents();
    }

    bindEvents() {
        this.items.forEach((item) => {
            const header = item.querySelector('.accordion-header');
            const content = item.querySelector('.accordion-content');

            header.addEventListener('click', () => {
                const isOpen = item.classList.contains('active');
                this.items.forEach((i) => {
                    i.classList.remove('active');
                    const c = i.querySelector('.accordion-content');
                    if (c) c.style.maxHeight = null;
                });

                if (!isOpen) {
                    item.classList.add('active');
                    if (content) {
                        content.style.maxHeight = content.scrollHeight + 'px';
                    }
                }
            });
        });
    }
}

/* ===== Plugin: Audio Player ===== */
class AudioPlayer {
    constructor(element) {
        this.element = element;
        this.audio = element.querySelector('.audio-player__element');
        this.playBtn = element.querySelector('.audio-play');
        this.progressBar = element.querySelector('.audio-progress');
        this.progressFilled = element.querySelector('.audio-progress__filled');
        this.timeDisplay = element.querySelector('.audio-time');
        this.volumeBtn = element.querySelector('.audio-volume');
        this.isPlaying = false;

        this.bindEvents();
    }

    bindEvents() {
        this.playBtn.addEventListener('click', () => this.togglePlay());
        this.audio.addEventListener('timeupdate', () => this.updateProgress());
        this.audio.addEventListener('ended', () => this.reset());
        this.progressBar.addEventListener('click', (e) => this.seek(e));
        this.volumeBtn.addEventListener('click', () => this.toggleVolume());
    }

    togglePlay() {
        if (this.isPlaying) {
            this.audio.pause();
            this.playBtn.textContent = '▶';
        } else {
            this.audio.play();
            this.playBtn.textContent = '⏸';
        }
        this.isPlaying = !this.isPlaying;
    }

    updateProgress() {
        const percent = (this.audio.currentTime / this.audio.duration) * 100;
        this.progressFilled.style.width = `${percent}%`;
        this.timeDisplay.textContent = `${this.formatTime(this.audio.currentTime)} / ${this.formatTime(this.audio.duration)}`;
    }

    seek(e) {
        const width = this.progressBar.offsetWidth;
        const clickX = e.offsetX;
        const duration = this.audio.duration;
        this.audio.currentTime = (clickX / width) * duration;
    }

    toggleVolume() {
        if (this.audio.muted) {
            this.audio.muted = false;
            this.volumeBtn.textContent = '🔊';
        } else {
            this.audio.muted = true;
            this.volumeBtn.textContent = '🔇';
        }
    }

    reset() {
        this.isPlaying = false;
        this.playBtn.textContent = '▶';
        this.progressFilled.style.width = '0%';
        this.timeDisplay.textContent = '0:00 / 0:00';
    }

    formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return `${m}:${s.toString().padStart(2, '0')}`;
    }
}

/* ===== Plugin: Email Form ===== */
class EmailForm {
    constructor(form) {
        this.form = form;
        this.bindEvents();
    }

    bindEvents() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    handleSubmit(e) {
        e.preventDefault();
        const input = this.form.querySelector('input[type="email"]');
        const email = input.value;

        if (email) {
            alert(`Merci ! Votre email ${email} est enregistré.`);
            input.value = '';
        }
    }
}

/* ===== Plugin: Member Area / Module Unlocking ===== */
const MODULES = [
    { id: 1, title: "Poser le silence et apprendre à s'arrêter", subtitle: "Calmer le mental et créer un espace intérieur", img: "images/card-meditation.jpg", href: "lesson.html?m=1" },
    { id: 2, title: "Écouter son corps et ses émotions", subtitle: "Reconnectez-vous à vos ressenti·e·s", img: "images/card-developpement.jpg", href: "lesson.html?m=2" },
    { id: 3, title: "Reconnaître sa voix intérieure et son intuition", subtitle: "Distinguer intuition et mental", img: "images/card-coaching.jpg", href: "lesson.html?m=3" },
    { id: 4, title: "Lâcher ce qui pèse, pardonner, alléger", subtitle: "Libérer l'énergie bloquée", img: "images/card-retraites.jpg", href: "lesson.html?m=4" },
    { id: 5, title: "Cultiver la gratitude et la présence", subtitle: "Intégrer la gratitude au quotidien", img: "images/home-mission.jpg", href: "lesson.html?m=5" },
    { id: 6, title: "Construire sa pratique durable", subtitle: "Créer un rituel personnel", img: "images/module-6.jpg", href: "lesson.html?m=6" }
];

const DAYS_PER_MODULE = 7;
/* Séance de coaching n (1..3) débloquée à partir de la semaine indiquée (cf. texte des séances) */
const COACHING_WEEKS = [2, 4, 6];
const COACHING_INFO = [
    { id: 1, title: "Lever les blocages des premiers jours", subtitle: "Identifier votre blocage et construire un plan pour 7 jours", img: "images/card-coaching.jpg", href: "lesson.html?c=1" },
    { id: 2, title: "Traverser les émotions qui remontent", subtitle: "Les accueillir calmement quand on commence à lâcher prise", img: "images/card-developpement.jpg", href: "lesson.html?c=2" },
    { id: 3, title: "Ajuster votre rituel pour qu'il dure", subtitle: "Faire le bilan et stabiliser une pratique adaptée à votre vie", img: "images/card-retraites.jpg", href: "lesson.html?c=3" }
];
const TOTAL_MODULES = 6;

class MemberArea {
    constructor(container) { this.container = container; this.init(); }

    async init() {
        let me = null;
        try { me = await Api.me(true); } catch { /* API indisponible */ }
        if (!me || !me.loggedIn) { window.location.href = 'login.html'; return; }
        this.member = me;
        this.render();
        const lo = document.getElementById('logoutBtn');
        if (lo) lo.addEventListener('click', e => { e.preventDefault(); Api.logout(); });
        if (!me.paid) this.initPayPal();
    }

    render() {
        const m = this.member, paid = m.paid;
        const fill = document.getElementById('progressFill');
        if (fill) fill.style.width = paid ? `${(m.currentWeek / TOTAL_MODULES) * 100}%` : '0%';
        const w = document.getElementById('currentWeek'), t = document.getElementById('progressText');
        if (w) w.textContent = paid ? `Semaine ${m.currentWeek}` : 'Formation non débloquée';
        if (t) t.textContent = paid ? `${m.currentWeek} / ${TOTAL_MODULES} modules débloqués` : 'Paiement requis';
        const pb = document.getElementById('payBox'); if (pb) pb.hidden = paid;
        const em = document.getElementById('memberEmail'); if (em) em.textContent = m.name || m.email;
        const adm = document.getElementById('adminLink'); if (adm) adm.hidden = m.role !== 'admin';
        this.renderModules(); this.renderCoaching();
    }

    card(img, num, title, subtitle, badge, state) {
        // state : { ok, wait }  wait = libellé de verrouillage
        const cta = state.ok ? `<a href="${state.href}" class="btn btn-primary btn-small">Commencer</a>`
            : (this.member.paid ? `<span class="lock-icon">🔒 ${state.wait}</span><button class="btn btn-small" disabled>Verrouillé</button>`
                : `<span class="lock-icon">🔒 Paiement requis</span><a href="#payBox" class="btn btn-primary btn-small">Débloquer</a>`);
        return `<div class="module-card ${state.ok ? 'unlocked' : 'locked'}">
            <img src="${img}" alt="${title}" class="module-img" />
            <div class="module-card__content"><span class="module-number">${num}</span>
                <h3>${title}</h3><p class="subtext">${subtitle}</p>${badge || ''}${cta}</div></div>`;
    }

    renderModules() {
        const grid = document.getElementById('modulesGrid'); if (!grid) return;
        const m = this.member, wk = m.paid ? m.currentWeek : 0;
        grid.innerHTML = MODULES.map(mod => {
            const ci = COACHING_WEEKS.indexOf(mod.id);
            const badge = ci !== -1 ? `<span class="coaching-badge">Séance de coaching ${ci + 1} incluse</span>` : '';
            const left = DAYS_PER_MODULE - (m.daysElapsed % DAYS_PER_MODULE);
            const wait = mod.id === wk + 1 ? `Déblocage dans ${left} jour${left > 1 ? 's' : ''}` : 'Verrouillé';
            return this.card(mod.img, mod.id, mod.title, mod.subtitle, badge, { ok: mod.id <= wk, href: mod.href, wait });
        }).join('');
    }

    renderCoaching() {
        const grid = document.getElementById('coachingGrid'); if (!grid) return;
        const wk = this.member.paid ? this.member.currentWeek : 0;
        grid.innerHTML = COACHING_INFO.map((c, i) => this.card(c.img, c.id, `Séance ${c.id} : ${c.title}`, c.subtitle, '',
            { ok: wk >= COACHING_WEEKS[i], href: c.href, wait: `Semaine ${COACHING_WEEKS[i]}` })).join('');
    }

    /* Bouton PayPal : le prix et la validation sont gérés par api/paypal.php (côté serveur) */
    async initPayPal() {
        const box = document.getElementById('paypal-button-container'), msg = document.getElementById('payMsg');
        if (!box) return;
        try {
            const cfg = await Api.get('paypal.php?action=config');
            const price = document.getElementById('payPrice'); if (price) price.textContent = `${cfg.amount} ${cfg.currency}`;
            await new Promise((ok, ko) => {
                const s = document.createElement('script');
                s.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(cfg.clientId)}&currency=${cfg.currency}&intent=capture`;
                s.onload = ok; s.onerror = ko; document.head.appendChild(s);
            });
            paypal.Buttons({
                style: { layout: 'vertical', shape: 'pill', label: 'pay' },
                createOrder: async () => {
                    try { msg.textContent = ''; return (await Api.post('paypal.php?action=create')).id; }
                    catch (e) { msg.textContent = e.message + (e.data && e.data.detail ? ' (' + e.data.detail + ')' : ''); console.error('PayPal create', e); throw e; }
                },
                onApprove: async data => {
                    msg.textContent = 'Validation du paiement…';
                    try { await Api.post('paypal.php?action=capture', { orderID: data.orderID }); location.reload(); }
                    catch (e) { msg.textContent = e.message + (e.data && e.data.detail ? ' (' + e.data.detail + ')' : '') + ' — si vous avez été débité, contactez-nous.'; console.error('PayPal capture', e); }
                },
                onError: err => { console.error('PayPal SDK', err); if (!msg.textContent) msg.textContent = 'Le paiement a échoué. Réessayez ou contactez-nous.'; }
            }).render('#paypal-button-container');
        } catch (e) { msg.textContent = 'Le paiement PayPal est momentanément indisponible.'; }
    }
}

/* ===== Initialize all plugins when DOM is ready ===== */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.slider-container').forEach((el) => new Slider(el));
    document.querySelectorAll('.accordion').forEach((el) => new Accordion(el));
    document.querySelectorAll('.audio-player-plugin').forEach((el) => new AudioPlayer(el));
    document.querySelectorAll('.email-form').forEach((el) => new EmailForm(el));

    if (document.querySelector('.member-modules')) {
        new MemberArea(document.querySelector('.member-modules').parentElement);
    }

});
