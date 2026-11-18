/**
 * Point of View — auth.js (login / register page)
 *   1. Flip between the Login and Register forms
 *   2. Client-side validation with friendly messages + shake
 *   3. Show / hide password
 *   4. Password strength meter
 *   5. Submit → "Enter Point of View" intro (intro.js) → /feed — no backend yet
 *   6. Rotating quote on the left panel
 */
(function () {
    'use strict';

    const POV = window.POV || {};
    const card = document.querySelector('[data-auth-card]');
    if (!card) {
        return;
    }

    /* ------------------------------------------------------------------
     * 1. Login ⇄ Register flip
     * ------------------------------------------------------------------ */
    const tabs = card.querySelector('.auth-tabs');
    const panels = {
        login: document.getElementById('panel-login'),
        register: document.getElementById('panel-register'),
    };
    const titles = { login: 'Log in · Point of View', register: 'Sign up · Point of View' };
    let mode = card.dataset.mode || 'login';
    let isFlipping = false;

    // Runs the callback once *this* element finishes an animation (ignores bubbling from children)
    const onOwnAnimationEnd = (element, callback) => {
        const handler = (event) => {
            if (event.target === element) {
                element.removeEventListener('animationend', handler);
                callback();
            }
        };
        element.addEventListener('animationend', handler);
    };

    const showPanel = (nextMode) => {
        Object.entries(panels).forEach(([name, panel]) => {
            panel.hidden = name !== nextMode;
        });
        tabs.dataset.active = nextMode;
        tabs.querySelectorAll('[role="tab"]').forEach((tab) => {
            tab.setAttribute('aria-selected', String(tab.dataset.authSwitch === nextMode));
        });

        // Keep the URL in sync (/login or /register) without reloading
        const url = nextMode === 'register' ? card.dataset.registerUrl : card.dataset.loginUrl;
        if (url && window.history.replaceState) {
            window.history.replaceState(null, '', url);
        }
        document.title = titles[nextMode];
    };

    const flipTo = (nextMode) => {
        if (isFlipping || nextMode === mode) {
            return;
        }
        isFlipping = true;
        mode = nextMode;
        tabs.dataset.active = nextMode; // move the pill right away

        card.classList.add('is-flip-out');
        onOwnAnimationEnd(card, () => {
            card.classList.remove('is-flip-out');
            showPanel(nextMode);
            card.classList.add('is-flip-in');

            onOwnAnimationEnd(card, () => {
                card.classList.remove('is-flip-in');
                isFlipping = false;
                const firstInput = panels[nextMode].querySelector('input');
                if (firstInput) {
                    firstInput.focus({ preventScroll: true });
                }
            });
        });
    };

    card.addEventListener('click', (event) => {
        const switcher = event.target.closest('[data-auth-switch]');
        if (switcher) {
            flipTo(switcher.dataset.authSwitch);
        }
    });

    /* ------------------------------------------------------------------
     * 2. Validation
     * ------------------------------------------------------------------ */
    const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    const USERNAME_PATTERN = /^[a-zA-Z0-9_.]{3,20}$/;

    // Each rule returns an error message, or '' when the value is fine.
    const rules = {
        identifier(value) {
            if (!value) {
                return 'Please enter your email or username.';
            }
            if (value.includes('@') && !EMAIL_PATTERN.test(value)) {
                return "That email doesn't look quite right. Try name@example.com.";
            }
            if (!value.includes('@') && value.length < 3) {
                return 'Usernames are at least 3 characters long.';
            }
            return '';
        },
        password(value) {
            if (!value) {
                return 'Please enter your password.';
            }
            return value.length < 6 ? 'Your password should be at least 6 characters.' : '';
        },
        username(value) {
            if (!value) {
                return 'Pick a username so people can find you.';
            }
            return USERNAME_PATTERN.test(value)
                ? ''
                : 'Use 3–20 letters, numbers, dots or underscores (no spaces).';
        },
        email(value) {
            if (!value) {
                return "We'll need your email to create the account.";
            }
            return EMAIL_PATTERN.test(value) ? '' : "That email doesn't look quite right. Try name@example.com.";
        },
        'new-password'(value) {
            if (!value) {
                return 'Create a password to protect your account.';
            }
            if (value.length < 8) {
                return 'Make it at least 8 characters long.';
            }
            return /[a-zA-Z]/.test(value) && /\d/.test(value) ? '' : 'Mix in at least one letter and one number.';
        },
        confirm(value, input) {
            const original = document.getElementById(input.dataset.confirmOf);
            if (!value) {
                return 'Please type your password again.';
            }
            return original && value === original.value ? '' : "Passwords don't match yet.";
        },
        terms(_value, input) {
            return input.checked ? '' : 'Please accept the community guidelines to continue.';
        },
    };

    const shake = (element) => {
        element.classList.remove('is-shaking');
        void element.offsetWidth; // restart animation
        element.classList.add('is-shaking');
        onOwnAnimationEnd(element, () => element.classList.remove('is-shaking'));
    };

    /**
     * Validate one input and update its field UI. Returns true when valid.
     */
    const validateInput = (input, { animate = false } = {}) => {
        const field = input.closest('[data-field]');
        const errorEl = field.querySelector('.pov-field__error');
        const rule = rules[input.dataset.validate];
        const message = rule ? rule(input.value.trim(), input) : '';

        field.classList.toggle('is-invalid', Boolean(message));
        field.classList.toggle('is-valid', !message && input.type !== 'checkbox');
        input.setAttribute('aria-invalid', String(Boolean(message)));
        errorEl.innerHTML = message ? '<i class="bi bi-exclamation-circle" aria-hidden="true"></i> ' : '';
        errorEl.append(message);

        if (message && animate) {
            shake(field);
        }
        return !message;
    };

    // Mock username for the account card: the register username, or the part before "@" of a login email
    const usernameFrom = (form, isLogin) => {
        const value = form.querySelector(isLogin ? '[name="identifier"]' : '[name="username"]').value.trim();
        return value.includes('@') ? value.split('@')[0] : value;
    };

    document.querySelectorAll('[data-auth-form]').forEach((form) => {
        const inputs = Array.from(form.querySelectorAll('[data-validate]'));

        inputs.forEach((input) => {
            // Validate when leaving a field that has a value…
            input.addEventListener('blur', () => {
                if (input.value || input.closest('[data-field]').classList.contains('is-invalid')) {
                    validateInput(input);
                }
            });
            // …and re-check live once it has shown an error
            const liveEvent = input.type === 'checkbox' ? 'change' : 'input';
            input.addEventListener(liveEvent, () => {
                if (input.closest('[data-field]').classList.contains('is-invalid')) {
                    validateInput(input);
                }
            });
        });

        /* ---------------- 5. Submit → intro sequence ---------------- */
        form.addEventListener('submit', (event) => {
            // TODO (Backend): remove preventDefault() and let the form POST to Laravel
            // (or send it with fetch + the CSRF token) once auth routes exist.
            event.preventDefault();

            const results = inputs.map((input) => validateInput(input, { animate: true }));
            const firstInvalid = inputs[results.indexOf(false)];

            if (firstInvalid) {
                firstInvalid.focus();
                POV.toast?.('Please fix the highlighted fields and try again.', { title: 'Almost there', type: 'error' });
                return;
            }

            // Browsers only allow audio after a user gesture, so unlock it right here in the submit handler
            POV.introSound?.unlock?.();

            const button = form.querySelector('[type="submit"]');
            button.classList.add('is-loading');
            button.setAttribute('aria-busy', 'true');

            const isLogin = form.dataset.authForm === 'login';
            const username = usernameFrom(form, isLogin);
            const goToFeed = () => {
                window.location.href = card.dataset.successUrl;
            };

            /*
             * TODO (Backend): replace the fake delay with the real Laravel auth request, e.g.
             *   const response = await fetch(form.action, {
             *       method: 'POST',
             *       headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
             *       body: new FormData(form),
             *   });
             *   if (!response.ok) { show the server errors, stop the loading state, and return; }
             * and only THEN play the sequence below (it must run after a successful auth response).
             */
            setTimeout(() => {
                if (POV.playEnterSequence) {
                    POV.playEnterSequence({ username, avatar: null, onComplete: goToFeed });
                } else {
                    goToFeed();
                }
            }, 450);
        });
    });

    /* ------------------------------------------------------------------
     * 3. Show / hide password
     * ------------------------------------------------------------------ */
    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const input = toggle.parentElement.querySelector('input');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            toggle.setAttribute('aria-pressed', String(show));
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');

            toggle.classList.remove('is-animating');
            void toggle.offsetWidth;
            toggle.classList.add('is-animating');
        });
    });

    /* ------------------------------------------------------------------
     * 4. Password strength meter (0–4)
     * ------------------------------------------------------------------ */
    const strengthLabels = [
        'Use 8+ characters with letters and numbers.',
        'Weak: try making it longer.',
        'Okay: longer is stronger.',
        'Good: add a symbol or a capital letter.',
        'Strong password. Nice!',
    ];

    document.querySelectorAll('[data-strength-input]').forEach((input) => {
        const meter = input.closest('[data-field]').querySelector('[data-strength]');
        const label = meter.querySelector('[data-strength-label]');

        input.addEventListener('input', () => {
            const value = input.value;
            let score = 0;
            if (value.length >= 6) score++;
            if (value.length >= 10) score++;
            if (/[a-z]/i.test(value) && /\d/.test(value)) score++;
            if (/[^a-zA-Z0-9]/.test(value) || (/[A-Z]/.test(value) && /[a-z]/.test(value))) score++;
            if (!value) score = 0;

            meter.dataset.level = String(score);
            label.textContent = strengthLabels[score];
        });
    });

    /* ------------------------------------------------------------------
     * 6. Rotating quote
     * ------------------------------------------------------------------ */
    const quotes = document.querySelectorAll('[data-quote]');
    if (quotes.length > 1) {
        let current = 0;
        setInterval(() => {
            quotes[current].classList.remove('is-active');
            current = (current + 1) % quotes.length;
            quotes[current].classList.add('is-active');
        }, 6000);
    }
})();
