/**
 * Point of View — feed.js (community feed page)
 *   1. Greeting by time of day        6. Comments, spoilers, share (event delegation)
 *   2. Skeleton → posts on load        7. Follow buttons
 *   3. Category filter + tabs          8. Chat panel (UI only, with a fake reply)
 *   4. Composer: auto-grow, counter,   9. Load more + small keyboard shortcuts
 *      rating stars, new post
 *   5. Post builder for new posts
 * Like / bookmark buttons are handled globally in main.js.
 */
(function () {
    'use strict';

    const POV = window.POV || {};
    const feedList = document.querySelector('[data-feed-list]');
    if (!feedList) {
        return;
    }

    const toast = (message, options) => POV.toast && POV.toast(message, options);

    // Escape user text before putting it into HTML strings
    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[char]));

    const CATEGORY_ICONS = {
        movies: 'bi-film',
        series: 'bi-tv',
        anime: 'bi-stars',
        manga: 'bi-journal-richtext',
        books: 'bi-book',
    };

    /* ------------------------------------------------------------------
     * 1. Greeting
     * ------------------------------------------------------------------ */
    const greeting = document.querySelector('[data-greeting]');
    if (greeting) {
        const hour = new Date().getHours();
        greeting.textContent = hour < 5 ? 'Still up' : hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
    }

    /* ------------------------------------------------------------------
     * 2. Skeleton loading → show posts with a stagger
     *    TODO (Backend): fetch posts here (or render them server-side and
     *    drop the fake delay).
     * ------------------------------------------------------------------ */
    const skeletons = document.querySelector('[data-feed-skeletons]');

    const animateIn = (items) => {
        items.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.08}s`;
            item.classList.remove('filter-in');
            void item.offsetWidth;
            item.classList.add('filter-in');
        });
    };

    const visibleItems = () => Array.from(feedList.querySelectorAll('[data-feed-item]:not([hidden])'));

    const showSkeletons = (duration, callback) => {
        skeletons.hidden = false;
        feedList.setAttribute('aria-busy', 'true');
        setTimeout(() => {
            skeletons.hidden = true;
            feedList.setAttribute('aria-busy', 'false');
            callback();
        }, duration);
    };

    POV.onLoaded(() => {
        showSkeletons(1100, () => animateIn(visibleItems()));
    });

    /* ------------------------------------------------------------------
     * 3. Category filter + Latest / Trending / Following tabs
     * ------------------------------------------------------------------ */
    const state = { category: 'all', tab: 'latest' };
    const emptyState = feedList.querySelector('[data-feed-empty]');
    const tabsWrap = document.querySelector('[data-feed-tabs]');
    const indicator = tabsWrap.querySelector('.feed-tabs__indicator');

    const applyFeedView = () => {
        const items = Array.from(feedList.querySelectorAll('[data-feed-item]'));

        // Sort: trending = most likes, otherwise original order (newest first)
        const sorted = items.slice().sort((a, b) => (state.tab === 'trending'
            ? Number(b.dataset.likes) - Number(a.dataset.likes)
            : Number(a.dataset.order) - Number(b.dataset.order)));
        sorted.forEach((item) => feedList.insertBefore(item, emptyState));

        let visible = 0;
        sorted.forEach((item) => {
            const categoryMatch = state.category === 'all' || item.dataset.category === state.category;
            const tabMatch = state.tab !== 'following' || item.dataset.following === 'true';
            item.hidden = !(categoryMatch && tabMatch);
            if (!item.hidden) {
                visible++;
            }
        });
        emptyState.hidden = visible > 0;
        animateIn(visibleItems());
    };

    const setCategory = (category) => {
        state.category = category;
        document.querySelectorAll('[data-feed-category]').forEach((chip) => {
            const isActive = chip.dataset.feedCategory === category;
            chip.classList.toggle('is-active', isActive);
            chip.setAttribute('aria-pressed', String(isActive));
        });
        applyFeedView();
    };

    document.querySelectorAll('[data-feed-category]').forEach((chip) => {
        chip.addEventListener('click', () => setCategory(chip.dataset.feedCategory));
    });

    const moveIndicator = (tab) => {
        indicator.style.setProperty('--x', `${tab.offsetLeft}px`);
        indicator.style.setProperty('--w', `${tab.offsetWidth}px`);
    };

    tabsWrap.querySelectorAll('[data-feed-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            if (state.tab === tab.dataset.feedTab) {
                return;
            }
            state.tab = tab.dataset.feedTab;
            tabsWrap.querySelectorAll('[data-feed-tab]').forEach((other) => {
                other.classList.toggle('is-active', other === tab);
                other.setAttribute('aria-selected', String(other === tab));
            });
            moveIndicator(tab);
            // Short skeleton flash to fake a request
            showSkeletons(450, applyFeedView);
        });
    });

    const placeIndicator = () => moveIndicator(tabsWrap.querySelector('.feed-tabs__btn.is-active'));
    placeIndicator();
    window.addEventListener('resize', placeIndicator);
    document.fonts?.ready.then(placeIndicator);

    // Pre-select a category from the URL, e.g. /feed?category=anime (links from the homepage)
    const urlCategory = new URLSearchParams(window.location.search).get('category');
    if (urlCategory && CATEGORY_ICONS[urlCategory]) {
        state.category = urlCategory;
        document.querySelectorAll('[data-feed-category]').forEach((chip) => {
            const isActive = chip.dataset.feedCategory === urlCategory;
            chip.classList.toggle('is-active', isActive);
            chip.setAttribute('aria-pressed', String(isActive));
        });
        feedList.querySelectorAll('[data-feed-item]').forEach((item) => {
            item.hidden = item.dataset.category !== urlCategory;
        });
        emptyState.hidden = visibleItems().length > 0;
    }

    /* ------------------------------------------------------------------
     * 4. Composer
     * ------------------------------------------------------------------ */
    const composer = document.querySelector('[data-composer]');
    const textarea = composer.querySelector('[data-composer-text]');
    const counter = composer.querySelector('[data-composer-count]');
    const rating = composer.querySelector('[data-rating]');
    const stars = Array.from(rating.querySelectorAll('[data-star]'));
    const MAX_LENGTH = Number(textarea.getAttribute('maxlength')) || 500;

    // Auto-grow textarea + live character counter
    textarea.addEventListener('input', () => {
        textarea.style.height = 'auto';
        textarea.style.height = `${textarea.scrollHeight}px`;
        counter.textContent = `${textarea.value.length} / ${MAX_LENGTH}`;
        counter.classList.toggle('is-near-limit', textarea.value.length > MAX_LENGTH * 0.9);
        textarea.closest('[data-field]').classList.remove('is-invalid');
    });

    // Rating stars: hover preview, click to set, pop animation
    const paintStars = (value, className) => {
        stars.forEach((star) => star.classList.toggle(className, Number(star.dataset.star) <= value));
    };

    stars.forEach((star) => {
        star.addEventListener('mouseenter', () => {
            rating.classList.add('is-hovering');
            paintStars(Number(star.dataset.star), 'is-hover');
        });
        star.addEventListener('click', () => {
            const value = Number(star.dataset.star);
            rating.dataset.value = String(value);
            paintStars(value, 'is-on');
            stars.forEach((other) => other.setAttribute('aria-pressed', String(Number(other.dataset.star) <= value)));
            star.classList.remove('is-pop');
            void star.offsetWidth;
            star.classList.add('is-pop');
        });
    });

    rating.addEventListener('mouseleave', () => {
        rating.classList.remove('is-hovering');
        paintStars(0, 'is-hover');
    });

    composer.addEventListener('submit', (event) => {
        // TODO (Backend): send to Laravel instead, e.g.
        // fetch('/posts', { method: 'POST', headers: { 'X-CSRF-TOKEN': token }, body: new FormData(composer) })
        event.preventDefault();

        const text = textarea.value.trim();
        const field = textarea.closest('[data-field]');

        if (text.length < 10) {
            field.classList.add('is-invalid');
            const card = composer.closest('.composer');
            card.classList.remove('is-shaking');
            void card.offsetWidth;
            card.classList.add('is-shaking');
            textarea.focus();
            toast(text ? 'Write at least 10 characters so people know what you think.' : 'Your post is empty. Share a thought first!', {
                title: 'Almost there',
                type: 'error',
            });
            return;
        }

        const category = composer.querySelector('[data-composer-category]').value;
        const work = composer.querySelector('[data-composer-work]').value.trim() || 'Untitled story';
        const post = {
            id: Date.now(),
            category,
            work,
            text,
            rating: Number(rating.dataset.value) || 0,
            spoiler: composer.querySelector('[data-composer-spoiler]').checked,
            user: { name: 'Hana Kyaw', handle: 'hanapov', initials: 'HK', hue: 22 },
        };

        const wrapper = document.createElement('div');
        wrapper.className = 'feed-item';
        wrapper.dataset.feedItem = '';
        wrapper.dataset.category = post.category;
        wrapper.dataset.likes = '0';
        wrapper.dataset.order = '-1';
        wrapper.dataset.following = 'true';
        wrapper.innerHTML = buildPostHtml(post);
        feedList.prepend(wrapper);
        POV.bindTilt?.(wrapper.querySelector('[data-tilt]'));

        // Make sure the new post is visible with the current filters
        if (state.category !== 'all' && state.category !== post.category) {
            setCategory('all');
        } else {
            emptyState.hidden = true;
            animateIn([wrapper]);
        }

        // Reset the composer
        composer.reset();
        textarea.style.height = '';
        counter.textContent = `0 / ${MAX_LENGTH}`;
        rating.dataset.value = '0';
        paintStars(0, 'is-on');

        wrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
        toast('Your point of view is live! (Only on this page until the backend is connected.)', { title: 'Posted', type: 'success' });
    });

    /* ------------------------------------------------------------------
     * 5. Build the HTML for a new post (mirrors components/post-card)
     * ------------------------------------------------------------------ */
    function buildPostHtml(post) {
        const starsHtml = [1, 2, 3, 4, 5]
            .map((n) => `<i class="bi ${n <= post.rating ? 'bi-star-fill' : 'bi-star'}"></i>`)
            .join('');
        const label = post.category.charAt(0).toUpperCase() + post.category.slice(1);
        const body = post.spoiler
            ? `<div class="post-card__body spoiler" data-spoiler>
                    <p class="spoiler__text mb-0">${escapeHtml(post.text)}</p>
                    <button type="button" class="spoiler__reveal" data-spoiler-reveal><i class="bi bi-eye-slash"></i> Contains spoilers · click to reveal</button>
               </div>`
            : `<p class="post-card__body mb-0">${escapeHtml(post.text)}</p>`;
        const title = post.work !== 'Untitled story'
            ? `My point of view on “${post.work}”`
            : post.text.length > 70 ? `${post.text.slice(0, 70).trim()}…` : post.text;

        return `
        <article class="pov-card post-card post-card--full" data-category="${post.category}" data-tilt="flat" aria-labelledby="post-${post.id}-title">
            <header class="post-card__head">
                <span class="pov-avatar" style="--avatar-hue: ${post.user.hue}" aria-hidden="true">${post.user.initials}</span>
                <div class="post-card__author">
                    <span class="post-card__name">${escapeHtml(post.user.name)}</span>
                    <span class="post-card__meta">@${escapeHtml(post.user.handle)} · <time>just now</time></span>
                </div>
                <span class="pov-badge ms-auto" data-category="${post.category}">${label}</span>
            </header>
            <div class="post-card__work">
                <i class="bi ${CATEGORY_ICONS[post.category] || 'bi-collection-play'}" aria-hidden="true"></i>
                <strong>${escapeHtml(post.work)}</strong>
                <span class="stars-display" role="img" aria-label="Rated ${post.rating} out of 5">${starsHtml}</span>
            </div>
            <h3 class="post-card__title" id="post-${post.id}-title">${escapeHtml(title)}</h3>
            ${body}
            <footer class="post-card__actions">
                <button type="button" class="action-btn" data-action="like" data-post-id="${post.id}" aria-pressed="false" aria-label="Like">
                    <i class="bi bi-heart"></i> <span data-count="0">0</span>
                </button>
                <button type="button" class="action-btn" data-comment-toggle aria-expanded="false" aria-controls="comments-${post.id}" aria-label="Show comments">
                    <i class="bi bi-chat"></i> <span>0</span>
                </button>
                <button type="button" class="action-btn" data-share data-post-title="${escapeHtml(title)}" aria-label="Share">
                    <i class="bi bi-send"></i> <span class="d-none d-sm-inline">Share</span>
                </button>
                <button type="button" class="action-btn ms-auto" data-action="bookmark" data-post-id="${post.id}" aria-pressed="false" aria-label="Bookmark">
                    <i class="bi bi-bookmark"></i>
                </button>
            </footer>
            <div class="post-comments" id="comments-${post.id}" data-comments inert>
                <div class="post-comments__inner">
                    <ul class="post-comments__list" data-comment-list></ul>
                    <form class="comment-form" data-comment-form novalidate>
                        <span class="pov-avatar pov-avatar--sm" style="--avatar-hue: ${post.user.hue}" aria-hidden="true">${post.user.initials}</span>
                        <label class="visually-hidden" for="comment-input-${post.id}">Write a comment</label>
                        <input type="text" id="comment-input-${post.id}" class="comment-form__input" placeholder="Write a comment…" maxlength="300" autocomplete="off">
                        <button type="submit" class="comment-form__send" aria-label="Send comment"><i class="bi bi-send-fill"></i></button>
                    </form>
                </div>
            </div>
        </article>`;
    }

    /* ------------------------------------------------------------------
     * 6. Comments, spoilers, share (delegated so new posts work too)
     * ------------------------------------------------------------------ */
    document.addEventListener('click', (event) => {
        // Expand / collapse comments
        const commentToggle = event.target.closest('[data-comment-toggle]');
        if (commentToggle) {
            const panel = document.getElementById(commentToggle.getAttribute('aria-controls'));
            const open = !panel.classList.contains('is-open');
            panel.classList.toggle('is-open', open);
            panel.inert = !open;
            commentToggle.setAttribute('aria-expanded', String(open));
            commentToggle.setAttribute('aria-label', open ? 'Hide comments' : 'Show comments');
            commentToggle.querySelector('i').className = open ? 'bi bi-chat-fill' : 'bi bi-chat';
            commentToggle.classList.toggle('is-active', open);
            if (open) {
                setTimeout(() => panel.querySelector('input')?.focus({ preventScroll: true }), 350);
            }
            return;
        }

        // Reveal spoiler
        const spoilerButton = event.target.closest('[data-spoiler-reveal]');
        if (spoilerButton) {
            spoilerButton.closest('[data-spoiler]').classList.add('is-revealed');
            spoilerButton.setAttribute('tabindex', '-1');
            return;
        }

        // Share → copy a link
        const shareButton = event.target.closest('[data-share]');
        if (shareButton) {
            const link = `${window.location.origin}${window.location.pathname}`;
            const done = () => toast(`Link to “${shareButton.dataset.postTitle}” copied to your clipboard.`, { title: 'Share', type: 'success', duration: 3000 });
            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(link).then(done, done);
            } else {
                done();
            }
        }
    });

    // Add a comment (client-side only)
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-comment-form]');
        if (!form) {
            return;
        }
        event.preventDefault();
        // TODO (Backend): POST /posts/{id}/comments

        const input = form.querySelector('input');
        const text = input.value.trim();
        if (!text) {
            form.classList.remove('is-shaking');
            void form.offsetWidth;
            form.classList.add('is-shaking');
            return;
        }

        const list = form.parentElement.querySelector('[data-comment-list]');
        const item = document.createElement('li');
        item.className = 'comment is-new';
        item.innerHTML = `
            <span class="pov-avatar pov-avatar--sm" style="--avatar-hue: 22" aria-hidden="true">HK</span>
            <div class="comment__bubble">
                <span class="comment__name">Hana Kyaw</span>
                <p class="comment__text">${escapeHtml(text)}</p>
                <span class="comment__meta">just now · <button type="button" class="comment__link">Like</button> · <button type="button" class="comment__link">Reply</button></span>
            </div>`;
        list.appendChild(item);
        input.value = '';

        // Bump the visible comment count
        const card = form.closest('.post-card');
        const countEl = card.querySelector('[data-comment-toggle] span');
        if (countEl && /^\d+$/.test(countEl.textContent.trim())) {
            countEl.textContent = String(Number(countEl.textContent) + 1);
        }
    });

    /* ------------------------------------------------------------------
     * 7. Follow buttons
     * ------------------------------------------------------------------ */
    document.querySelectorAll('[data-follow]').forEach((button) => {
        button.addEventListener('click', () => {
            const following = !button.classList.contains('is-following');
            const name = button.getAttribute('aria-label').replace(/^(Follow|Unfollow) /, '');
            button.classList.toggle('is-following', following);
            button.setAttribute('aria-pressed', String(following));
            button.setAttribute('aria-label', `${following ? 'Unfollow' : 'Follow'} ${name}`);
            button.querySelector('i').className = following ? 'bi bi-check-lg' : 'bi bi-plus-lg';
            button.querySelector('span').textContent = following ? 'Following' : 'Follow';
            button.classList.remove('is-popping');
            void button.offsetWidth;
            button.classList.add('is-popping');
            if (following) {
                toast(`You're now following ${name}.`, { title: 'Following', type: 'success', duration: 2500 });
            }
        });
    });

    /* ------------------------------------------------------------------
     * 8. Chat panel (UI only)
     * ------------------------------------------------------------------ */
    const chatPanel = document.getElementById('chatPanel');
    const chatToggles = document.querySelectorAll('[data-chat-toggle]');
    const chatMessages = chatPanel.querySelector('[data-chat-messages]');
    const chatForm = chatPanel.querySelector('[data-chat-form]');
    const typing = chatPanel.querySelector('[data-chat-typing]');
    const badge = document.querySelector('[data-chat-badge]');
    const fakeReplies = [
        { name: 'Mika Tanaka', initials: 'MT', hue: 18, text: 'Haha, totally agree! 🔥' },
        { name: 'Jun Park', initials: 'JP', hue: 265, text: 'That scene had me screaming.' },
        { name: 'Nay Lin', initials: 'NL', hue: 40, text: 'Okay but the soundtrack though 🎶' },
    ];
    let replyIndex = 0;

    const setChatOpen = (open) => {
        chatPanel.classList.toggle('is-open', open);
        chatPanel.inert = !open;
        chatToggles.forEach((toggle) => toggle.setAttribute('aria-expanded', String(open)));
        if (open) {
            if (badge) {
                badge.hidden = true;
            }
            chatMessages.scrollTop = chatMessages.scrollHeight;
            setTimeout(() => chatForm.querySelector('input').focus({ preventScroll: true }), 300);
        }
    };

    chatToggles.forEach((toggle) => {
        toggle.addEventListener('click', () => setChatOpen(!chatPanel.classList.contains('is-open')));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && chatPanel.classList.contains('is-open')) {
            setChatOpen(false);
        }
    });

    const addChatMessage = ({ text, mine, name = '', initials = '', hue = 0 }) => {
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
        const message = document.createElement('div');
        message.className = `chat-msg is-new${mine ? ' chat-msg--mine' : ''}`;
        message.innerHTML = `
            ${mine ? '' : `<span class="pov-avatar pov-avatar--sm" style="--avatar-hue: ${hue}; --size: 30px" aria-hidden="true">${initials}</span>`}
            <div class="chat-msg__bubble">
                ${mine ? '' : `<span class="chat-msg__name">${escapeHtml(name)}</span>`}
                <p class="mb-0">${escapeHtml(text)}</p>
                <time class="chat-msg__time">${time}</time>
            </div>`;
        chatMessages.appendChild(message);
        chatMessages.scrollTo({ top: chatMessages.scrollHeight, behavior: 'smooth' });
    };

    chatForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const input = chatForm.querySelector('input');
        const text = input.value.trim();
        if (!text) {
            return;
        }
        addChatMessage({ text, mine: true });
        input.value = '';

        // Fake someone typing back
        const reply = fakeReplies[replyIndex++ % fakeReplies.length];
        typing.querySelector('span:last-child').textContent = `${reply.name.split(' ')[0]} is typing…`;
        setTimeout(() => {
            typing.hidden = false;
        }, 400);
        setTimeout(() => {
            typing.hidden = true;
            addChatMessage({ ...reply, mine: false });
        }, 1900);
    });

    /* ------------------------------------------------------------------
     * 9. Load more + shortcuts
     * ------------------------------------------------------------------ */
    const loadMore = document.querySelector('[data-load-more]');
    loadMore?.addEventListener('click', () => {
        // TODO (Backend): request the next page (?page=2) and append the posts
        loadMore.classList.add('is-loading');
        loadMore.disabled = true;
        setTimeout(() => {
            loadMore.classList.remove('is-loading');
            loadMore.disabled = false;
            toast("You're all caught up! Check back later for new takes.", { title: 'Feed', type: 'info' });
        }, 900);
    });

    // "/" focuses search, the mobile "+" button focuses the composer
    document.addEventListener('keydown', (event) => {
        const typingInField = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
        if (event.key === '/' && !typingInField) {
            const search = document.getElementById('feedSearch');
            if (search && search.offsetParent !== null) {
                event.preventDefault();
                search.focus();
            }
        }
    });

    document.querySelector('[data-focus-composer]')?.addEventListener('click', (event) => {
        event.preventDefault();
        document.getElementById('composer').scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => textarea.focus({ preventScroll: true }), 500);
    });
})();
