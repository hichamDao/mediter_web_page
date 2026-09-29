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
    { id: 1, title: "Poser le silence et apprendre à s'arrêter", subtitle: "Calmer le mental et créer un espace intérieur", img: "images/course-beginner.jpg", href: "lesson.html?m=1" },
    { id: 2, title: "Écouter son corps et ses émotions", subtitle: "Reconnectez-vous à vos ressenti·e·s", img: "images/course-sleep.jpg", href: "lesson.html?m=2" },
    { id: 3, title: "Reconnaître sa voix intérieure et son intuition", subtitle: "Distinguer intuition et mental", img: "images/course-relaxation.jpg", href: "lesson.html?m=3" },
    { id: 4, title: "Lâcher ce qui pèse, pardonner, alléger", subtitle: "Libérer l'énergie bloquée", img: "images/course-energy.jpg", href: "lesson.html?m=4" },
    { id: 5, title: "Cultiver la gratitude et la présence", subtitle: "Intégrer la gratitude au quotidien", img: "images/feature-meditation.jpg", href: "lesson.html?m=5" },
    { id: 6, title: "Construire sa pratique durable", subtitle: "Créer un rituel personnel", img: "images/feature-responsive.jpg", href: "lesson.html?m=6" }
];

const DAYS_PER_MODULE = 7;
const TOTAL_MODULES = 6;

class MemberArea {
    constructor(container) {
        this.container = container;
        this.init();
    }

    init() {
        const member = this.getMemberData();
        if (!member) {
            window.location.href = 'login.html';
            return;
        }

        this.member = member;
        this.updateProgressBar();
        this.renderModules();
        this.bindLogout();
        this.bindCoachingAccess();
    }

    getMemberData() {
        const data = localStorage.getItem('eveilInterieur_member');
        if (!data) return null;

        try {
            const parsed = JSON.parse(data);
            const daysElapsed = Math.floor((Date.now() - new Date(parsed.startDate).getTime()) / (1000 * 60 * 60 * 24));
            const currentWeek = Math.min(Math.floor(daysElapsed / DAYS_PER_MODULE) + 1, TOTAL_MODULES);
            return {
                ...parsed,
                daysElapsed,
                currentWeek,
                weeksRemaining: Math.max(TOTAL_MODULES - currentWeek, 0)
            };
        } catch {
            return null;
        }
    }

    getUnlockedModules() {
        const week = this.member.currentWeek;
        return MODULES.filter(m => m.id <= week);
    }

    updateProgressBar() {
        const fill = this.container.querySelector('#progressFill');
        const weekEl = this.container.querySelector('#currentWeek');
        const textEl = this.container.querySelector('#progressText');

        if (fill) {
            const percent = (this.member.currentWeek / TOTAL_MODULES) * 100;
            fill.style.width = `${percent}%`;
        }

        if (weekEl) weekEl.textContent = `Semaine ${this.member.currentWeek}`;
        if (textEl) textEl.textContent = `${this.member.currentWeek} / ${TOTAL_MODULES} modules débloqués`;
    }

    renderModules() {
        const grid = this.container.querySelector('#modulesGrid');
        if (!grid) return;

        const unlocked = this.getUnlockedModules();

        grid.innerHTML = MODULES.map(mod => {
            const isUnlocked = mod.id <= this.member.currentWeek;
            const isCoaching = [2, 4, 6].includes(mod.id);
            const nextUnlock = !isUnlocked && mod.id === this.member.currentWeek + 1;
            const daysRemaining = nextUnlock
                ? DAYS_PER_MODULE - (this.member.daysElapsed % DAYS_PER_MODULE)
                : 0;

            return `
                <div class="module-card ${isUnlocked ? 'unlocked' : 'locked'}">
                    <img src="${mod.img}" alt="${mod.title}" class="module-img" />
                    <div class="module-card__content">
                        <span class="module-number">${mod.id}</span>
                        ${isUnlocked
                            ? `<h3>${mod.title}</h3>
                               <p class="subtext">${mod.subtitle}</p>
                               ${isCoaching ? '<span class="coaching-badge">Coaching inclus</span>' : ''}
                               <a href="${mod.href}" class="btn btn-primary btn-small">Commencer</a>`
                            : `<h3>${mod.title}</h3>
                               <p class="subtext">${mod.subtitle}</p>
                               ${nextUnlock
                                   ? `<span class="unlock-timer">Déblocage dans ${daysRemaining} jour${daysRemaining > 1 ? 's' : ''}</span>`
                                   : `<span class="lock-icon">🔒 Verrouillé</span>`}
                               <button class="btn btn-small" disabled>Verrouillé</button>`}
                    </div>
                </div>
            `;
        }).join('');

        const emailEl = this.container.querySelector?.('#memberEmail') || document.getElementById('memberEmail');
        if (emailEl && this.member.email) {
            emailEl.textContent = this.member.email;
        }
    }

    bindLogout() {
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                localStorage.removeItem('eveilInterieur_member');
                window.location.href = 'login.html';
            });
        }
    }

    bindCoachingAccess() {
        document.querySelectorAll('.coaching-link').forEach(link => {
            const moduleId = parseInt(link.dataset.module);
            const unlocked = moduleId <= this.member.currentWeek;
            if (!unlocked) {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const daysRemaining = DAYS_PER_MODULE - (this.member.daysElapsed % DAYS_PER_MODULE);
                    alert(`Ce module de coaching sera débloqué la semaine ${moduleId} (dans ${daysRemaining} jour${daysRemaining > 1 ? 's' : ''}).`);
                });
            }
        });
    }
}

/* ===== Plugin: Login Form ===== */
class LoginForm {
    constructor(form) {
        this.form = form;
        this.bindEvents();
    }

    bindEvents() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    handleSubmit(e) {
        e.preventDefault();
        const emailInput = this.form.querySelector('#email');
        const passwordInput = this.form.querySelector('#password');
        const email = emailInput.value.trim();
        const password = passwordInput.value;

        if (!email || password.length < 6) {
            alert('Veuillez saisir une adresse email valide et un mot de passe de 6 caractères minimum.');
            return;
        }

        const memberData = {
            email: email,
            startDate: new Date().toISOString(),
            createdAt: Date.now()
        };

        localStorage.setItem('eveilInterieur_member', JSON.stringify(memberData));
        window.location.href = 'member-area.html';
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

    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        new LoginForm(loginForm);
    }
});
