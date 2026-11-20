/**
 * Point of View — main.js
 * Global UI behaviour used on every page:
 *   1. Preloader          5. Ripple effect on buttons
 *   2. Navbar on scroll   6. Toast notifications  → POV.toast(message, options)
 *   3. Scroll-spy         7. Like / bookmark reactions with particle burst
 *   4. Custom cursor
 */
(function () {
    'use strict';

    const POV = (window.POV = window.POV || {});
    POV.prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    POV.isTouch = window.matchMedia('(hover: none), (pointer: coarse)').matches;

    /* ------------------------------------------------------------------
     * 1. Preloader — fades out once the page has loaded (min ~0.7s so
     *    the reel animation is visible), then fires "pov:loaded".
     * ------------------------------------------------------------------ */
    function initPreloader() {
        const preloader = document.getElementById('preloader');
        const MIN_VISIBLE_MS = 700;
        let finished = false;

        const finish = () => {
            if (finished) {
                return;
            }
            finished = true;

            const wait = Math.max(0, MIN_VISIBLE_MS - performance.now());

            setTimeout(() => {
                document.body.classList.add('is-loaded');
                POV.loaded = true;
                document.dispatchEvent(new CustomEvent('pov:loaded'));

                if (preloader) {
                    preloader.classList.add('is-hidden');
                    setTimeout(() => preloader.remove(), 800);
                }
            }, wait);
        };

        if (document.readyState === 'complete') {
            finish();
        } else {
            window.addEventListener('load', finish);
        }

        // Safety net: never keep the preloader longer than 3.5s (slow CDN, etc.)
        setTimeout(finish, 3500);
    }

    /**
     * Run a callback after the preloader is gone (or immediately if it already is).
     */
    POV.onLoaded = function (callback) {
        if (POV.loaded) {
            callback();
        } else {
            document.addEventListener('pov:loaded', callback, { once: true });
        }
    };

    /* ------------------------------------------------------------------
     * 2. Navbar — glass background after scrolling + close mobile menu
     * ------------------------------------------------------------------ */
    function initNavbar() {
        const nav = document.getElementById('mainNav');
        if (!nav) {
            return;
        }

        let ticking = false;
        const update = () => {
            nav.classList.toggle('is-scrolled', window.scrollY > 24);
            ticking = false;
        };

        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(update);
                ticking = true;
            }
        }, { passive: true });
        update();

        // Close the offcanvas menu after a link is chosen (mobile)
        const offcanvasEl = document.getElementById('navOffcanvas');
        if (offcanvasEl && window.bootstrap) {
            offcanvasEl.querySelectorAll('.pov-nav-link').forEach((link) => {
                link.addEventListener('click', () => {
                    const menu = window.bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (menu) {
                        menu.hide();
                    }
                });
            });
        }
    }

    /* ------------------------------------------------------------------
     * 3. Scroll-spy — highlight the nav link of the section in view
     *    (only links with data-section, i.e. on the homepage)
     * ------------------------------------------------------------------ */
    function initScrollSpy() {
        const links = document.querySelectorAll('.pov-nav-link[data-section]');
        if (!links.length || !('IntersectionObserver' in window)) {
            return;
        }

        const setActive = (id) => {
            links.forEach((link) => {
                const isActive = link.dataset.section === id;
                link.classList.toggle('active', isActive);
                if (isActive) {
                    link.setAttribute('aria-current', 'true');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    setActive(entry.target.id);
                }
            });
        }, { rootMargin: '-45% 0px -50% 0px' });

        links.forEach((link) => {
            const section = document.getElementById(link.dataset.section);
            if (section) {
                observer.observe(section);
            }
        });
    }

    /* ------------------------------------------------------------------
     * 4. Custom cursor — glowing dot + trailing ring (desktop only)
     * ------------------------------------------------------------------ */
    function initCursor() {
        const dot = document.querySelector('.cursor-dot');
        const ring = document.querySelector('.cursor-ring');
        if (POV.isTouch || POV.prefersReducedMotion || !dot || !ring) {
            return;
        }

        const interactive = 'a, button, input, textarea, select, label, [role="button"], .pov-card, .poster-card, .pov-chip';
        let mouseX = window.innerWidth / 2;
        let mouseY = window.innerHeight / 2;
        let ringX = mouseX;
        let ringY = mouseY;

        const place = (el, x, y) => {
            el.style.transform = `translate3d(${x}px, ${y}px, 0) translate(-50%, -50%)`;
        };

        window.addEventListener('pointermove', (event) => {
            if (event.pointerType !== 'mouse') {
                return;
            }
            mouseX = event.clientX;
            mouseY = event.clientY;
            place(dot, mouseX, mouseY);

            if (!document.body.classList.contains('has-cursor')) {
                ringX = mouseX;
                ringY = mouseY;
                document.body.classList.add('has-cursor');
            }
        }, { passive: true });

        document.documentElement.addEventListener('mouseleave', () => document.body.classList.remove('has-cursor'));

        // Grow the ring over anything clickable
        document.addEventListener('pointerover', (event) => {
            document.body.classList.toggle('cursor-hover', Boolean(event.target.closest(interactive)));
        });
        document.addEventListener('pointerdown', () => document.body.classList.add('cursor-down'));
        document.addEventListener('pointerup', () => document.body.classList.remove('cursor-down'));

        // The ring eases toward the mouse every frame (lerp)
        const follow = () => {
            ringX += (mouseX - ringX) * 0.18;
            ringY += (mouseY - ringY) * 0.18;
            place(ring, ringX, ringY);
            requestAnimationFrame(follow);
        };
        requestAnimationFrame(follow);
    }

    /* ------------------------------------------------------------------
     * 5. Ripple — expanding circle from the click point
     * ------------------------------------------------------------------ */
    function initRipple() {
        document.addEventListener('pointerdown', (event) => {
            const target = event.target.closest('.btn-pov, [data-ripple]');
            if (!target || POV.prefersReducedMotion) {
                return;
            }

            const rect = target.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height) * 2.2;
            const ripple = document.createElement('span');

            ripple.className = 'ripple';
            ripple.style.width = `${size}px`;
            ripple.style.height = `${size}px`;
            ripple.style.left = `${event.clientX - rect.left}px`;
            ripple.style.top = `${event.clientY - rect.top}px`;

            target.appendChild(ripple);
            ripple.addEventListener('animationend', () => ripple.remove(), { once: true });
        });
    }

    /* ------------------------------------------------------------------
     * 6. Toasts — POV.toast('Saved!', { title, type: 'info'|'success'|'error', duration })
     * ------------------------------------------------------------------ */
    const TOAST_ICONS = {
        info: 'bi-info-circle',
        success: 'bi-check2-circle',
        error: 'bi-exclamation-triangle',
    };

    POV.toast = function (message, options = {}) {
        const stack = document.getElementById('toastStack');
        if (!stack) {
            return null;
        }

        const { title = 'Point of View', type = 'info', duration = 4000 } = options;
        const toast = document.createElement('div');

        toast.className = `pov-toast pov-toast--${type}`;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.style.setProperty('--toast-duration', `${duration}ms`);
        toast.innerHTML = `
            <div class="pov-toast__icon" aria-hidden="true"><i class="bi ${TOAST_ICONS[type] || TOAST_ICONS.info}"></i></div>
            <div class="pov-toast__content">
                <p class="pov-toast__title"></p>
                <p class="pov-toast__text"></p>
            </div>
            <button type="button" class="pov-toast__close" aria-label="Dismiss notification"><i class="bi bi-x-lg"></i></button>
            <span class="pov-toast__timer" aria-hidden="true"></span>`;

        // textContent (not innerHTML) so messages can never inject HTML
        toast.querySelector('.pov-toast__title').textContent = title;
        toast.querySelector('.pov-toast__text').textContent = message;

        const dismiss = () => {
            if (toast.classList.contains('is-leaving')) {
                return;
            }
            toast.classList.add('is-leaving');
            setTimeout(() => toast.remove(), 450);
        };

        toast.querySelector('.pov-toast__close').addEventListener('click', dismiss);
        setTimeout(dismiss, duration);

        stack.appendChild(toast);

        // Keep at most 4 toasts on screen
        while (stack.children.length > 4) {
            stack.firstElementChild.remove();
        }

        return toast;
    };

    // Any element with data-toast="message" shows a toast on click (handy for UI-only buttons)
    function initToastTriggers() {
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-toast]');
            if (!trigger) {
                return;
            }
            if (trigger.tagName === 'A' && trigger.getAttribute('href') === '#') {
                event.preventDefault();
            }
            POV.toast(trigger.dataset.toast, {
                title: trigger.dataset.toastTitle || 'Point of View',
                type: trigger.dataset.toastType || 'info',
            });
        });
    }

    /* ------------------------------------------------------------------
     * 7. Reactions — like / bookmark toggles with pop + particle burst
     *    Markup: <button class="action-btn" data-action="like" aria-pressed="false">
     *              <i class="bi bi-heart"></i><span data-count="12">12</span></button>
     * ------------------------------------------------------------------ */
    POV.formatCount = function (value) {
        return value >= 1000 ? `${(value / 1000).toFixed(1).replace(/\.0$/, '')}K` : String(value);
    };

    function burst(button, colors) {
        if (POV.prefersReducedMotion) {
            return;
        }
        const PIECES = 10;
        for (let i = 0; i < PIECES; i++) {
            const particle = document.createElement('span');
            particle.className = 'burst-particle';
            particle.style.setProperty('--angle', `${(360 / PIECES) * i + Math.random() * 18}deg`);
            particle.style.setProperty('--distance', `${-16 - Math.random() * 14}px`);
            particle.style.background = colors[i % colors.length];
            button.appendChild(particle);
            particle.addEventListener('animationend', () => particle.remove(), { once: true });
        }
    }

    function initReactions() {
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-action="like"], [data-action="bookmark"]');
            if (!button) {
                return;
            }

            const isLike = button.dataset.action === 'like';
            const isActive = !button.classList.contains('is-active');
            const icon = button.querySelector('i');

            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', String(isActive));

            if (isLike) {
                icon.className = isActive ? 'bi bi-heart-fill' : 'bi bi-heart';
                const counter = button.querySelector('[data-count]');
                if (counter) {
                    const next = Number(counter.dataset.count) + (isActive ? 1 : -1);
                    counter.dataset.count = String(next);
                    counter.textContent = POV.formatCount(next);
                }
            } else {
                icon.className = isActive ? 'bi bi-bookmark-fill' : 'bi bi-bookmark';
                POV.toast(isActive ? 'Saved to your bookmarks.' : 'Removed from your bookmarks.', {
                    title: 'Bookmarks',
                    type: isActive ? 'success' : 'info',
                    duration: 2500,
                });
            }

            // Restart the pop animation
            button.classList.remove('is-popping');
            void button.offsetWidth;
            button.classList.add('is-popping');

            if (isActive) {
                burst(button, isLike
                    ? ['#FF5E62', '#FF7A2F', '#FF9E5E', '#FFD1A8']
                    : ['#FF7A2F', '#FF9E5E', '#EAF0FF']);
            }

            // TODO (Backend): persist the reaction, e.g.
            // fetch(`/posts/${button.dataset.postId}/${button.dataset.action}`, {
            //     method: 'POST',
            //     headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            // });
        });
    }

    /* ------------------------------------------------------------------
     * 8. Category filter — chips inside [data-filter-group="#targetId"]
     *    show/hide [data-filter-item="anime"] elements inside the target.
     *    An optional [data-filter-empty] element shows when nothing matches.
     * ------------------------------------------------------------------ */
    POV.applyFilter = function (group, value) {
        const target = document.querySelector(group.dataset.filterGroup);
        if (!target) {
            return;
        }

        group.querySelectorAll('[data-filter]').forEach((chip) => {
            const isActive = chip.dataset.filter === value;
            chip.classList.toggle('is-active', isActive);
            chip.setAttribute('aria-pressed', String(isActive));
        });

        let visibleCount = 0;
        target.querySelectorAll('[data-filter-item]').forEach((item) => {
            const matches = value === 'all' || item.dataset.filterItem === value;
            item.hidden = !matches;
            if (matches) {
                visibleCount++;
                item.classList.remove('filter-in');
                void item.offsetWidth; // restart the animation
                item.classList.add('filter-in');
            }
        });

        const empty = target.querySelector('[data-filter-empty]');
        if (empty) {
            empty.hidden = visibleCount > 0;
        }
    };

    function initFilters() {
        document.querySelectorAll('[data-filter-group]').forEach((group) => {
            group.addEventListener('click', (event) => {
                const chip = event.target.closest('[data-filter]');
                if (chip) {
                    POV.applyFilter(group, chip.dataset.filter);
                }
            });
        });
    }

    // Logging out resets the "intro already played" flag so the next login plays it again.
    // TODO (Backend): the real logout is a POST form; keep this click handler on it.
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-logout]')) {
            try {
                sessionStorage.removeItem('pov:intro-played');
            } catch (e) { /* ignore */ }
        }
    });

    /* ------------------------------------------------------------------ */
    initPreloader();
    initFilters();
    initNavbar();
    initScrollSpy();
    initCursor();
    initRipple();
    initToastTriggers();
    initReactions();
})();
