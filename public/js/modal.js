// public/js/modal.js for index and the header of index

document.addEventListener('DOMContentLoaded', function () {

    /* ----- CHECK USER LOGIN STATUS AND UPDATE HEADER ----- */
    function updateAuthSection() {
        const authSection = document.getElementById('authSection');
        if (!authSection) {
            console.log('authSection not found!');
            return;
        }

        // Try to get user data from localStorage
        const userDataStr = localStorage.getItem('aksyon_user_data');
        
        console.log('Checking login status...'); 
        console.log('User data from localStorage:', userDataStr);

        if (userDataStr) {
            try {
                const userData = JSON.parse(userDataStr);
                console.log('Parsed user data:', userData);
                
                if (userData.is_logged_in === 'true' && userData.user_name) {
                    // User is logged in - show dropdown
                    console.log('User is logged in, showing dropdown');
                    
                    const dashboardLink = userData.dashboard_link || '#';
                    
                    authSection.innerHTML = `
                        <div class="dropdown">
                            <a class="btn btn-outline-primary dropdown-toggle" href="#" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false" aria-label="User menu">
                                ${userData.user_name}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="${dashboardLink}">
                                        <i class="bi bi-speedometer2 me-2"></i>View Dashboard
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="public/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Log Out
                                    </a>
                                </li>
                            </ul>
                        </div>
                    `;
                } else {
                    showLoginButtons();
                }
            } catch (e) {
                console.error('Error parsing user data:', e);
                showLoginButtons();
            }
        } else {
            // No user data - show login/register buttons
            console.log('No user data found, showing login buttons');
            showLoginButtons();
        }
    }

    function showLoginButtons() {
        const authSection = document.getElementById('authSection');
        if (authSection) {
            authSection.innerHTML = `
                <button class="btn btn-outline-primary btn-sm me-2" 
                        onclick="location.href='public/login.php'" aria-label="Log in">LOG IN</button>
                <button class="btn btn-primary btn-sm" 
                        data-bs-toggle="modal" data-bs-target="#registerModal" aria-label="Register">REGISTER</button>
            `;
        }
    }

    // Call immediately
    updateAuthSection();

    // Also call after a short delay to ensure page is fully loaded
    setTimeout(updateAuthSection, 300);

    /* ----- STICKY HEADER ON SCROLL ----- */
    const header = document.querySelector('.main-header');
    if (header) {
        window.addEventListener('scroll', () => {
            header.classList.toggle('shadow-sm', window.scrollY > 10);
        });
    }

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

    /* ----- SCROLL-BASED CARD MOVEMENT ----- */
    let ticking = false;
    
    function updateCardPositions() {
        const serviceCards = document.querySelectorAll('.service-card');
        const scrollPosition = window.pageYOffset;
        
        serviceCards.forEach((card, index) => {
            const cardTop = card.getBoundingClientRect().top + scrollPosition;
            const cardVisible = card.getBoundingClientRect().top < window.innerHeight && 
                               card.getBoundingClientRect().bottom > 0;
            
            if (cardVisible && !card.matches(':hover')) {
                const movement = (scrollPosition - cardTop + window.innerHeight) * 0.01;
                const direction = index % 2 === 0 ? 1 : -1;
                card.style.transform = `translateY(${movement * direction}px)`;
            }
        });
        
        ticking = false;
    }

    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                updateCardPositions();
            });
            ticking = true;
        }
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
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    /* ----- TOAST ----- */
    window.showToast = (msg, type = 'info') => {
        const container = document.querySelector('.toast-container') || (() => {
            const c = document.createElement('div');
            c.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(c);
            return c;
        })();
        const html = `<div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex"><div class="toast-body">${msg}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div></div>`;
        container.insertAdjacentHTML('beforeend', html);
        const toast = new bootstrap.Toast(container.lastElementChild);
        toast.show();
        container.lastElementChild.addEventListener('hidden.bs.toast', () => {
            container.lastElementChild.remove();
        });
    };

    /* ----- FORM VALIDATION ----- */
    window.validateForm = function(formElement) {
        const inputs = formElement.querySelectorAll('input[required], textarea[required], select[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    };

    /* ----- EMAIL VALIDATION ----- */
    window.validateEmail = function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    };

    /* ----- PHONE VALIDATION ----- */
    window.validatePhone = function(phone) {
        const re = /^[\d\s\-\+\(\)]+$/;
        return re.test(phone) && phone.replace(/\D/g, '').length >= 10;
    };

    /* ----- LOADING SPINNER ----- */
    window.showLoader = function() {
        const loader = document.createElement('div');
        loader.className = 'loader-overlay';
        loader.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
        loader.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;justify-content:center;align-items:center;z-index:9999;';
        document.body.appendChild(loader);
    };

    window.hideLoader = function() {
        const loader = document.querySelector('.loader-overlay');
        if (loader) {
            loader.remove();
        }
    };

    /* ----- INITIALIZE TOOLTIPS ----- */
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    console.log('AKSyon Medical Center – All JS features loaded');
});

/* Helper for :contains selector */
if (!HTMLElement.prototype.contains) {
    HTMLElement.prototype.contains = function (text) { 
        return this.textContent.includes(text); 
    };
}