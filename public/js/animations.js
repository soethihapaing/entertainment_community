/**
 * Point of View — animations.js
 * Visual effects shared by all pages:
 *   1. Scroll reveal (IntersectionObserver)   4. Counter animation
 *   2. 3D tilt + spotlight on cards           5. Auto-sliding carousel
 *   3. Typing / rotating words                6. Poster wall: slow on hover, pause off-screen
 * Every effect checks prefers-reduced-motion and touch devices.
 */
(function () {
    'use strict';

    const POV = window.POV || {};
    const reducedMotion = POV.prefersReducedMotion;
    const hasObserver = 'IntersectionObserver' in window;

    /* ------------------------------------------------------------------
     * 1. Scroll reveal — add .is-visible when [data-reveal] enters view.
     *    Delay per element: style="--reveal-delay: .2s"
     * ------------------------------------------------------------------ */
    function initReveal() {
        const reveal = (el) => el.classList.add('is-visible');

        if (!hasObserver || reducedMotion) {
            document.querySelectorAll('[data-reveal]').forEach(reveal);
            POV.observeReveal = reveal;
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    reveal(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('[data-reveal]').forEach((el) => observer.observe(el));

        // Lets other scripts reveal elements they add later (e.g. feed posts)
        POV.observeReveal = (el) => observer.observe(el);
    }

    /* ------------------------------------------------------------------
     * 2. Tilt — [data-tilt] cards rotate toward the mouse.
     *    [data-tilt="flat"] only moves the spotlight (no rotation).
     * ------------------------------------------------------------------ */
    const MAX_TILT_DEG = 8;

    function bindTilt(card) {
        if (card.dataset.tiltBound) {
            return;
        }
        card.dataset.tiltBound = 'true';

        const flat = card.dataset.tilt === 'flat';
        let frame = null;

        card.addEventListener('pointermove', (event) => {
            if (event.pointerType !== 'mouse') {
                return;
            }
            const rect = card.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width;
            const y = (event.clientY - rect.top) / rect.height;

            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => {
                card.style.setProperty('--mx', `${(x * 100).toFixed(1)}%`);
                card.style.setProperty('--my', `${(y * 100).toFixed(1)}%`);
                if (!flat) {
                    card.classList.add('is-tilting');
                    card.style.setProperty('--rx', `${((0.5 - y) * MAX_TILT_DEG).toFixed(2)}deg`);
                    card.style.setProperty('--ry', `${((x - 0.5) * MAX_TILT_DEG).toFixed(2)}deg`);
                }
            });
        });

        card.addEventListener('pointerleave', () => {
            cancelAnimationFrame(frame);
            card.classList.remove('is-tilting');
            card.style.setProperty('--rx', '0deg');
            card.style.setProperty('--ry', '0deg');
        });
    }

    function initTilt() {
        if (POV.isTouch || reducedMotion) {
            POV.bindTilt = () => {};
            return;
        }
        document.querySelectorAll('[data-tilt]').forEach(bindTilt);
        POV.bindTilt = bindTilt;
    }

    /* ------------------------------------------------------------------
     * 3. Typing text — <span data-rotate-words='["Movies","Anime"]'>
     *    Types a word, waits, deletes it, moves to the next one.
     * ------------------------------------------------------------------ */
    function initTypingText() {
        document.querySelectorAll('[data-rotate-words]').forEach((el) => {
            const words = JSON.parse(el.dataset.rotateWords);
            const output = el.querySelector('[data-typing-text]') || el;
            let wordIndex = 0;

            if (reducedMotion) {
                // Simple swap every 2.5s, no typing animation
                setInterval(() => {
                    wordIndex = (wordIndex + 1) % words.length;
                    output.textContent = words[wordIndex];
                }, 2500);
                return;
            }

            let charIndex = words[0].length;
            let deleting = true;

            const tick = () => {
                const word = words[wordIndex];

                if (deleting) {
                    charIndex--;
                    output.textContent = word.slice(0, charIndex);
                    if (charIndex === 0) {
                        deleting = false;
                        wordIndex = (wordIndex + 1) % words.length;
                        return setTimeout(tick, 350);
                    }
                    return setTimeout(tick, 55);
                }

                charIndex++;
                output.textContent = words[wordIndex].slice(0, charIndex);
                if (charIndex === words[wordIndex].length) {
                    deleting = true;
                    return setTimeout(tick, 1800);
                }
                return setTimeout(tick, 110);
            };

            output.textContent = words[0];
            POV.onLoaded(() => setTimeout(tick, 2200));
        });
    }

    /* ------------------------------------------------------------------
     * 4. Counters — <span data-counter="12500" data-suffix="+">12,500+</span>
     *    Counts up with an ease-out curve when scrolled into view.
     * ------------------------------------------------------------------ */
    function initCounters() {
        const counters = document.querySelectorAll('[data-counter]');
        const format = (el, value) => {
            el.textContent = `${Math.round(value).toLocaleString('en-US')}${el.dataset.suffix || ''}`;
        };

        if (!hasObserver || reducedMotion) {
            counters.forEach((el) => format(el, Number(el.dataset.counter)));
            return;
        }

        const DURATION = 2200;
        const animate = (el) => {
            const target = Number(el.dataset.counter);
            const start = performance.now();
            const step = (now) => {
                const progress = Math.min((now - start) / DURATION, 1);
                const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress); // easeOutExpo
                format(el, target * eased);
                if (progress < 1) {
                    requestAnimationFrame(step);
                }
            };
            requestAnimationFrame(step);
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.6 });

        counters.forEach((el) => {
            format(el, 0);
            observer.observe(el);
        });
    }

    /* ------------------------------------------------------------------
     * 5. Carousel — [data-carousel] with [data-carousel-track],
     *    [data-carousel-prev], [data-carousel-next], [data-carousel-dots].
     *    Shows 1 / 2 / 3 slides depending on screen width, auto-slides,
     *    pauses on hover/focus, supports swipe.
     * ------------------------------------------------------------------ */
    function initCarousels() {
        document.querySelectorAll('[data-carousel]').forEach((carousel) => {
            const track = carousel.querySelector('[data-carousel-track]');
            const slides = Array.from(track.children);
            const dotsWrap = carousel.querySelector('[data-carousel-dots]');
            const interval = Number(carousel.dataset.interval) || 5000;
            let index = 0;
            let perView = 1;
            let timer = null;

            const maxIndex = () => Math.max(0, slides.length - perView);

            const renderDots = () => {
                if (!dotsWrap) {
                    return;
                }
                dotsWrap.innerHTML = '';
                for (let i = 0; i <= maxIndex(); i++) {
                    const dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = 'carousel-dot';
                    dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
                    dot.addEventListener('click', () => {
                        goTo(i);
                        restart();
                    });
                    dotsWrap.appendChild(dot);
                }
            };

            const goTo = (next) => {
                index = next > maxIndex() ? 0 : next < 0 ? maxIndex() : next;
                track.style.transform = `translate3d(${-(index * 100) / perView}%, 0, 0)`;
                slides.forEach((slide, i) => {
                    const visible = i >= index && i < index + perView;
                    slide.setAttribute('aria-hidden', String(!visible));
                    slide.classList.toggle('is-current', visible);
                });
                if (dotsWrap) {
                    Array.from(dotsWrap.children).forEach((dot, i) => {
                        dot.classList.toggle('is-active', i === index);
                        dot.setAttribute('aria-current', i === index ? 'true' : 'false');
                    });
                }
            };

            const measure = () => {
                const width = window.innerWidth;
                const next = width >= 992 ? 3 : width >= 768 ? 2 : 1;
                if (next !== perView || !dotsWrap.children.length) {
                    perView = next;
                    carousel.style.setProperty('--per-view', perView);
                    renderDots();
                }
                goTo(Math.min(index, maxIndex()));
            };

            const stop = () => clearInterval(timer);
            const start = () => {
                stop();
                if (!reducedMotion) {
                    timer = setInterval(() => goTo(index + 1), interval);
                }
            };
            const restart = () => start();

            carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => {
                goTo(index - 1);
                restart();
            });
            carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => {
                goTo(index + 1);
                restart();
            });

            carousel.addEventListener('mouseenter', stop);
            carousel.addEventListener('mouseleave', start);
            carousel.addEventListener('focusin', stop);
            carousel.addEventListener('focusout', start);

            // Swipe support
            let startX = null;
            track.addEventListener('pointerdown', (event) => {
                startX = event.clientX;
            });
            track.addEventListener('pointerup', (event) => {
                if (startX === null) {
                    return;
                }
                const delta = event.clientX - startX;
                if (Math.abs(delta) > 50) {
                    goTo(index + (delta < 0 ? 1 : -1));
                    restart();
                }
                startX = null;
            });

            window.addEventListener('resize', measure);
            measure();
            start();
        });
    }

    /* ------------------------------------------------------------------
     * 6. Poster wall — slow a row down while hovered (smoothly, by tweening
     *    the CSS animation's playbackRate) and pause rows that are off-screen.
     * ------------------------------------------------------------------ */
    function initPosterWall() {
        if (reducedMotion) {
            return;
        }

        document.querySelectorAll('[data-poster-row]').forEach((row) => {
            let frame = null;

            const tweenRate = (target) => {
                const animations = row.getAnimations ? row.getAnimations() : [];
                if (!animations.length) {
                    return;
                }
                cancelAnimationFrame(frame);

                const from = animations[0].playbackRate;
                const startTime = performance.now();
                const DURATION = 700;

                const step = (now) => {
                    const t = Math.min((now - startTime) / DURATION, 1);
                    const eased = 1 - Math.pow(1 - t, 3);
                    const rate = from + (target - from) * eased;
                    animations.forEach((animation) => {
                        if (animation.updatePlaybackRate) {
                            animation.updatePlaybackRate(rate);
                        } else {
                            animation.playbackRate = rate;
                        }
                    });
                    if (t < 1) {
                        frame = requestAnimationFrame(step);
                    }
                };
                frame = requestAnimationFrame(step);
            };

            row.addEventListener('pointerenter', () => tweenRate(0.12));
            row.addEventListener('pointerleave', () => tweenRate(1));
        });

        // Pause the whole wall when it is scrolled out of view (saves battery / GPU)
        if (hasObserver) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => entry.target.classList.toggle('is-offscreen', !entry.isIntersecting));
            });
            document.querySelectorAll('.poster-bg').forEach((wall) => observer.observe(wall));
        }
    }

    /* ------------------------------------------------------------------ */
    initTilt();
    initTypingText();
    initCounters();
    initCarousels();
    initPosterWall();

    // Reveal after the preloader so the first animations are actually seen
    if (POV.onLoaded) {
        POV.onLoaded(initReveal);
    } else {
        initReveal();
    }
})();
