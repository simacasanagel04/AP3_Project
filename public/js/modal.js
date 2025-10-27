document.addEventListener('DOMContentLoaded', function () {

    /* ----- SMOOTH SCROLL ----- */
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', e => {
            const href = link.getAttribute('href');
            if (href === '#') return;
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
                const navbar = document.querySelector('.navbar-collapse');
                if (navbar && navbar.classList.contains('show')) {
                    new bootstrap.Collapse(navbar).hide();
                }
            }
        });
    });

    /* ----- FADE-IN ANIMATION ----- */
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = 1;
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.procedure-card, .specialist-card, .service-card, .action-card').forEach(el => {
        el.style.opacity = 0;
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity .6s ease, transform .6s ease';
        observer.observe(el);
    });

    /* ----- ACTIVE NAV LINK ON SCROLL ----- */
    window.addEventListener('scroll', () => {
        let current = '';
        document.querySelectorAll('section[id]').forEach(sec => {
            if (pageYOffset >= sec.offsetTop - 200) current = sec.id;
        });
        document.querySelectorAll('.nav-link').forEach(l => {
            l.classList.toggle('active', l.getAttribute('href') === '#' + current);
        });
    });

    /* ----- NAVBAR BG ON SCROLL ----- */
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
    });

    /* ----- BOOK BUTTON ALERT (placeholder) ----- */
    document.querySelectorAll('button').forEach(btn => {
        if (/BOOK/i.test(btn.textContent)) {
            btn.addEventListener('click', () => alert('Appointment booking system will be available soon!'));
        }
    });

    /* ----- LOGIN / REGISTER ALERT (placeholder) ----- */
    const loginBtn  = document.querySelector('button:contains("LOG IN")');
    const regBtn    = document.querySelector('button:contains("REGISTER")');
    if (loginBtn)  loginBtn.onclick  = () => alert('Login functionality will be implemented');
    if (regBtn)    regBtn.onclick    = () => alert('Registration functionality will be implemented');

    /* ----- TOAST UTILITY (optional) ----- */
    window.showToast = (msg, type = 'info') => {
        const container = document.querySelector('.toast-container') || (() => {
            const c = document.createElement('div');
            c.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(c);
            return c;
        })();
        const html = `<div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                        <div class="d-flex"><div class="toast-body">${msg}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div></div>`;
        container.insertAdjacentHTML('beforeend', html);
        const toast = new bootstrap.Toast(container.lastElementChild);
        toast.show();
        container.lastElementChild.addEventListener('hidden.bs.toast', () => it.remove());
    };

    console.log('AKSyon Medical Center – All JS features loaded');
});

/* Helper for :contains selector (used above) */
HTMLElement.prototype.contains = function (text) { return this.textContent.includes(text); };