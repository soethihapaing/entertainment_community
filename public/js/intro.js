/**
 * Point of View — intro.js
 * "Enter Point of View": a cinematic transition from login/register to the feed.
 *
 *   POV.playEnterSequence({ username, avatar, onComplete, force })
 *
 * Sequence (see INTRO_CONFIG): poster cards lift off the login wall → spiral
 * card tunnel dive → POV card reveal → tunnel burst with depth of field →
 * the user's account card flies to the camera → flash → /feed.
 *
 * Sections:
 *   1. Config                 6. WebGL scene (Three.js)
 *   2. Helpers                7. CSS-3D fallback
 *   3. Card artwork (canvas)  8. Quick fade (reduced motion / already played)
 *   4. Overlay + lift-off     9. Public API + Three.js preloading
 *   5. Sequence controller   10. Feed arrival (runs on /feed after the hand-off)
 */
(function () {
    'use strict';

    const POV = (window.POV = window.POV || {});

    /* =====================================================================
     * 1. CONFIG — tweak timings (seconds), counts and quality here
     * ===================================================================== */
    const INTRO_CONFIG = {
        duration: 8.5,

        // Main phases
        phases: {
            liftOff: [0.0, 0.8],   // login card shrinks, posters lift off the wall
            dive: [0.8, 3.5],      // camera dives into the spiral card tunnel
            reveal: [3.5, 5.0],    // POV card flips in the centre (slow motion)
            burst: [5.0, 6.0],     // tunnel explodes, cards float with depth of field
            pick: [6.0, 7.5],      // account card is picked and flies to the camera
            zoom: [7.5, 8.5],      // zoom into the card, flash, go to /feed
        },

        // Fine cue points inside the phases
        cues: {
            wallToTunnel: [0.1, 1.6],  // poster wall morphs into the spiral
            povArrive: [3.5, 4.1],     // POV card travels out of the depth
            povFlip: [3.8, 4.45],      // soft flip reveal
            heartbeats: [4.1, 4.55],   // glow pulses (synced with the heartbeat sound)
            povVanish: [5.0, 5.35],    // POV card collapses into the burst
            shockwave: [5.0, 5.9],
            accountFly: [6.15, 7.4],
            accountFlip: [6.4, 7.15],
            othersFade: [6.05, 7.2],
            ghostFeed: [7.6, 8.25],    // feed layout fades in behind the card
            flash: [7.95, 8.35],
        },

        // Sound cues (seconds) — read by intro-sound.js
        sound: {
            flutter: [0.0, 0.8],
            whoosh: [0.8, 3.6],
            drone: [0.8, 5.2],
            heartbeats: [4.1, 4.55],
            chime: 4.2,
            burst: 5.0,
            riser: [6.0, 7.98],
            impact: 7.98,
        },

        // Tunnel speed (world units / second) and spin (radians / second)
        motion: {
            travelStart: 2.5,
            travelMax: 26,
            travelReveal: 0.5,
            spinStart: 0.25,
            spinMax: 2.1,
            spinReveal: 0.12,
            diveRampEnd: 3.3,   // speed peaks here
            slowDownEnd: 4.2,   // slow-motion reached here
        },

        tunnel: { radius: 2.7, length: 30, cardsPerTurn: 9.4, pitch: 0.35, twist: 0.05 },

        camera: { fovLandscape: 60, fovPortrait: 78, diveFovBoost: 14 },

        // Depth of field strength (mip-map blur per unit of distance from focus)
        dof: { dive: 0.05, burst: 0.3, pick: 0.65 },

        // Quality presets (mobile = narrow screen or ≤ 4 CPU cores)
        quality: {
            desktop: { cards: 140, embers: 420, pixelRatio: 1.75, atlasCell: [256, 384], heroCard: [900, 1350], liftOffClones: 16 },
            mobile: { cards: 72, embers: 160, pixelRatio: 1.25, atlasCell: [160, 240], heroCard: [600, 900], liftOffClones: 8 },
        },

        threeUrl: 'https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js',
        loadTimeoutMs: 5000,
        reducedMotionFadeMs: 300,
        storage: { played: 'pov:intro-played', handoff: 'pov:intro-handoff' },
    };

    POV.INTRO_CONFIG = INTRO_CONFIG;

    /* =====================================================================
     * 2. HELPERS
     * ===================================================================== */
    const clamp01 = (value) => Math.min(1, Math.max(0, value));
    const lerp = (a, b, t) => a + (b - a) * t;
    const span = (t, [start, end]) => clamp01((t - start) / (end - start));

    const EASE = {
        inQuad: (x) => x * x,
        inCubic: (x) => x * x * x,
        outCubic: (x) => 1 - Math.pow(1 - x, 3),
        inOutCubic: (x) => (x < 0.5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2),
        outExpo: (x) => (x >= 1 ? 1 : 1 - Math.pow(2, -10 * x)),
        outBack: (x) => 1 + 2.70158 * Math.pow(x - 1, 3) + 1.70158 * Math.pow(x - 1, 2),
    };

    // Small seeded random generator so the layout is the same every time
    const seededRandom = (seed) => () => {
        seed = (seed + 0x6D2B79F5) | 0;
        let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };

    const storage = {
        get: (store, key) => { try { return window[store].getItem(key); } catch (e) { return null; } },
        set: (store, key, value) => { try { window[store].setItem(key, value); } catch (e) { /* private mode */ } },
        remove: (store, key) => { try { window[store].removeItem(key); } catch (e) { /* ignore */ } },
    };

    const hexToRgba = (hex, alpha) => {
        const clean = hex.replace('#', '');
        const value = parseInt(clean.length === 3 ? clean.replace(/./g, '$&$&') : clean, 16);
        return `rgba(${(value >> 16) & 255}, ${(value >> 8) & 255}, ${value & 255}, ${alpha})`;
    };

    const withTimeout = (promise, ms) => Promise.race([
        promise,
        new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), ms)),
    ]);

    const loadImage = (url, timeoutMs = 2000) => new Promise((resolve) => {
        if (!url) {
            resolve(null);
            return;
        }
        const img = new Image();
        const timer = setTimeout(() => resolve(null), timeoutMs);
        img.onload = () => { clearTimeout(timer); resolve(img); };
        img.onerror = () => { clearTimeout(timer); resolve(null); };
        img.src = url;
    });

    // Theme colors come from variables.css so the intro follows the site theme
    function readTheme() {
        const css = getComputedStyle(document.documentElement);
        const value = (name, fallback) => css.getPropertyValue(name).trim() || fallback;
        return {
            midnight: value('--pov-midnight', '#070B1F'),
            darkBlue: value('--pov-dark-blue', '#0B1530'),
            card: value('--pov-card', '#101A3A'),
            orange: value('--pov-orange', '#FF7A2F'),
            glow: value('--pov-glow', '#FF9E5E'),
            pink: value('--pov-pink', '#FF5E62'),
            text: value('--pov-text', '#EAF0FF'),
            muted: value('--pov-muted', '#9AA6C7'),
            fontDisplay: '"Bebas Neue", Impact, sans-serif',
            fontBody: '"Poppins", system-ui, sans-serif',
            fontJp: '"Hiragino Mincho ProN", "Yu Mincho", "YuMincho", "Noto Serif JP", serif',
        };
    }

    function readIntroData() {
        const element = document.getElementById('introData');
        try {
            return element ? JSON.parse(element.textContent) : {};
        } catch (e) {
            return {};
        }
    }

    // Used when no #introData is on the page
    const FALLBACK_POSTERS = [
        { title: 'Your Name', jp: '君の名は。', year: 2016, type: 'Film', from: '#1D3B8B', to: '#FF8A5C', accent: '#FFD7A8', motif: 'sun' },
        { title: 'Spirited Away', jp: '千と千尋の神隠し', year: 2001, type: 'Film', from: '#7A1F2B', to: '#F2B45A', accent: '#FFE3A3', motif: 'moon' },
        { title: 'Akira', jp: 'アキラ', year: 1988, type: 'Film', from: '#3A0008', to: '#FF3D3D', accent: '#FFB4A8', motif: 'stripes' },
        { title: 'One Piece', jp: 'ワンピース', year: 1999, type: 'Anime', from: '#0B4F8A', to: '#FFCC4D', accent: '#FFF2C4', motif: 'wave' },
    ];

    function detectLowPower() {
        const cores = navigator.hardwareConcurrency || 4;
        return window.innerWidth < 768 || cores <= 4;
    }

    function hasWebGL() {
        try {
            const canvas = document.createElement('canvas');
            return Boolean(canvas.getContext('webgl2') || canvas.getContext('webgl'));
        } catch (e) {
            return false;
        }
    }

    /**
     * Normalize the user passed to playEnterSequence().
     * avatar may be an image URL; otherwise initials + a hue are generated.
     */
    function makeUser(username, avatar) {
        const name = String(username || 'storyteller').replace(/^@/, '').trim().slice(0, 20) || 'storyteller';
        let hash = 0;
        for (const char of name) {
            hash = (hash * 31 + char.charCodeAt(0)) | 0;
        }
        return {
            username: name,
            initials: (name.replace(/[^a-zA-Z0-9]/g, '').slice(0, 2) || 'PV').toUpperCase(),
            hue: Math.abs(hash) % 360,
            avatar: typeof avatar === 'string' && avatar ? avatar : null,
        };
    }

    /* =====================================================================
     * 3. CARD ARTWORK — everything is drawn on <canvas>, no assets required
     * ===================================================================== */
    const Draw = {
        roundRect(ctx, x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r);
            ctx.arcTo(x + w, y + h, x, y + h, r);
            ctx.arcTo(x, y + h, x, y, r);
            ctx.arcTo(x, y, x + w, y, r);
            ctx.closePath();
        },

        cover(ctx, img, x, y, w, h) {
            const scale = Math.max(w / img.width, h / img.height);
            const iw = img.width * scale;
            const ih = img.height * scale;
            ctx.drawImage(img, x + (w - iw) / 2, y + (h - ih) / 2, iw, ih);
        },

        spaced(ctx, px) {
            if ('letterSpacing' in ctx) {
                ctx.letterSpacing = `${px}px`;
            }
        },

        wrapLines(ctx, text, maxWidth, maxLines) {
            const words = text.split(' ');
            const lines = [];
            let line = '';
            words.forEach((word) => {
                const test = line ? `${line} ${word}` : word;
                if (ctx.measureText(test).width > maxWidth && line) {
                    lines.push(line);
                    line = word;
                } else {
                    line = test;
                }
            });
            lines.push(line);
            return lines.slice(0, maxLines);
        },

        fitFont(ctx, text, maxWidth, size, family, weight = '') {
            let fontSize = size;
            do {
                ctx.font = `${weight} ${fontSize}px ${family}`;
                if (ctx.measureText(text).width <= maxWidth) {
                    break;
                }
                fontSize -= 2;
            } while (fontSize > 10);
            return fontSize;
        },

        /** Stylized eye: almond outline, iris ring, pupil, highlight. */
        eye(ctx, cx, cy, s, color, lineWidth, glow = 0) {
            ctx.save();
            ctx.strokeStyle = color;
            ctx.fillStyle = color;
            ctx.lineWidth = lineWidth;
            ctx.lineJoin = 'round';
            if (glow) {
                ctx.shadowColor = color;
                ctx.shadowBlur = glow;
            }
            ctx.beginPath();
            ctx.moveTo(cx - s, cy);
            ctx.quadraticCurveTo(cx, cy - s * 0.95, cx + s, cy);
            ctx.quadraticCurveTo(cx, cy + s * 0.95, cx - s, cy);
            ctx.closePath();
            ctx.stroke();
            ctx.beginPath();
            ctx.arc(cx, cy, s * 0.34, 0, Math.PI * 2);
            ctx.stroke();
            ctx.beginPath();
            ctx.arc(cx, cy, s * 0.15, 0, Math.PI * 2);
            ctx.fill();
            ctx.shadowBlur = 0;
            ctx.fillStyle = 'rgba(255,255,255,0.9)';
            ctx.beginPath();
            ctx.arc(cx + s * 0.1, cy - s * 0.1, s * 0.05, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        },

        motif(ctx, x, y, w, h, poster) {
            const accent = poster.accent || '#FFFFFF';
            ctx.save();
            if (poster.motif === 'sun') {
                const cx = x + w * 0.5;
                const cy = y + h * 0.36;
                const radius = w * 0.34;
                const gradient = ctx.createRadialGradient(cx - radius * 0.2, cy - radius * 0.2, 0, cx, cy, radius);
                gradient.addColorStop(0, accent);
                gradient.addColorStop(0.5, hexToRgba(accent, 0.5));
                gradient.addColorStop(1, hexToRgba(accent, 0));
                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(cx, cy, radius, 0, Math.PI * 2);
                ctx.fill();
            } else if (poster.motif === 'moon') {
                const cx = x + w * 0.52;
                const cy = y + h * 0.33;
                const radius = w * 0.26;
                ctx.fillStyle = hexToRgba(accent, 0.9);
                ctx.beginPath();
                ctx.arc(cx, cy, radius, 0, Math.PI * 2);
                ctx.arc(cx - radius * 0.38, cy - radius * 0.22, radius * 0.92, 0, Math.PI * 2);
                ctx.fill('evenodd');
            } else if (poster.motif === 'stripes') {
                ctx.strokeStyle = hexToRgba(accent, 0.22);
                ctx.lineWidth = Math.max(1, w * 0.012);
                for (let i = -h; i < w + h; i += w * 0.09) {
                    ctx.beginPath();
                    ctx.moveTo(x + i, y);
                    ctx.lineTo(x + i - h * 0.7, y + h);
                    ctx.stroke();
                }
            } else {
                const gradient = ctx.createLinearGradient(0, y + h * 0.45, 0, y + h * 0.8);
                gradient.addColorStop(0, hexToRgba(accent, 0.55));
                gradient.addColorStop(1, hexToRgba(accent, 0));
                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.ellipse(x + w * 0.5, y + h * 0.62, w * 0.75, h * 0.16, 0, 0, Math.PI * 2);
                ctx.fill();
            }
            ctx.restore();
        },

        /** Poster face: real image when available, otherwise gradient + title (same look as the CSS wall). */
        posterFace(ctx, x, y, w, h, poster, img, theme) {
            const radius = w * 0.06;
            ctx.save();
            this.roundRect(ctx, x, y, w, h, radius);
            ctx.clip();

            if (img) {
                this.cover(ctx, img, x, y, w, h);
            } else {
                const gradient = ctx.createLinearGradient(x, y, x + w * 0.4, y + h);
                gradient.addColorStop(0, poster.from);
                gradient.addColorStop(1, poster.to);
                ctx.fillStyle = gradient;
                ctx.fillRect(x, y, w, h);
                this.motif(ctx, x, y, w, h, poster);

                const jpSize = w * 0.085;
                ctx.font = `${jpSize}px ${theme.fontJp}`;
                ctx.fillStyle = 'rgba(255,255,255,0.85)';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                Array.from(poster.jp || '').slice(0, 9).forEach((char, i) => {
                    ctx.fillText(char, x + w * 0.9, y + h * 0.05 + i * jpSize * 1.12);
                });
            }

            const shade = ctx.createLinearGradient(0, y + h * 0.5, 0, y + h);
            shade.addColorStop(0, 'rgba(0,0,0,0)');
            shade.addColorStop(1, 'rgba(0,0,0,0.78)');
            ctx.fillStyle = shade;
            ctx.fillRect(x, y + h * 0.5, w, h * 0.5);

            const titleSize = w * 0.14;
            const subSize = w * 0.05;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'alphabetic';
            ctx.fillStyle = '#FFFFFF';
            ctx.font = `${titleSize}px ${theme.fontDisplay}`;
            const lines = this.wrapLines(ctx, String(poster.title).toUpperCase(), w * 0.84, 2);
            let lineY = y + h - h * 0.06 - subSize * 1.7 - (lines.length - 1) * titleSize * 0.95;
            lines.forEach((line) => {
                ctx.fillText(line, x + w * 0.08, lineY);
                lineY += titleSize * 0.95;
            });
            ctx.font = `600 ${subSize}px ${theme.fontBody}`;
            ctx.fillStyle = 'rgba(255,255,255,0.65)';
            ctx.fillText(`${poster.year} · ${poster.type}`.toUpperCase(), x + w * 0.08, y + h - h * 0.06);
            ctx.restore();

            // Thin sunset-orange edge
            const line = Math.max(1.5, w * 0.012);
            ctx.save();
            ctx.lineWidth = line;
            ctx.strokeStyle = hexToRgba(theme.orange, 0.9);
            this.roundRect(ctx, x + line / 2, y + line / 2, w - line, h - line, radius);
            ctx.stroke();
            ctx.restore();
        },

        /** Card back: navy gradient, lattice, orange frame and eye emblem. */
        cardBack(ctx, x, y, w, h, theme) {
            const radius = w * 0.06;
            ctx.save();
            this.roundRect(ctx, x, y, w, h, radius);
            ctx.clip();

            const gradient = ctx.createLinearGradient(x, y, x + w, y + h);
            gradient.addColorStop(0, theme.darkBlue);
            gradient.addColorStop(1, theme.midnight);
            ctx.fillStyle = gradient;
            ctx.fillRect(x, y, w, h);

            ctx.strokeStyle = hexToRgba(theme.orange, 0.08);
            ctx.lineWidth = Math.max(1, w * 0.006);
            for (let i = -h; i < w + h; i += w * 0.08) {
                ctx.beginPath();
                ctx.moveTo(x + i, y);
                ctx.lineTo(x + i + h, y + h);
                ctx.moveTo(x + i, y + h);
                ctx.lineTo(x + i + h, y);
                ctx.stroke();
            }

            const inset = w * 0.07;
            ctx.strokeStyle = hexToRgba(theme.orange, 0.55);
            ctx.lineWidth = Math.max(1, w * 0.008);
            this.roundRect(ctx, x + inset, y + inset, w - inset * 2, h - inset * 2, radius * 0.6);
            ctx.stroke();

            const glow = ctx.createRadialGradient(x + w / 2, y + h / 2, 0, x + w / 2, y + h / 2, w * 0.45);
            glow.addColorStop(0, hexToRgba(theme.orange, 0.22));
            glow.addColorStop(1, hexToRgba(theme.orange, 0));
            ctx.fillStyle = glow;
            ctx.fillRect(x, y, w, h);

            this.eye(ctx, x + w / 2, y + h / 2, w * 0.2, theme.orange, Math.max(1.5, w * 0.014));
            ctx.font = `${w * 0.075}px ${theme.fontDisplay}`;
            this.spaced(ctx, w * 0.012);
            ctx.fillStyle = hexToRgba(theme.orange, 0.85);
            ctx.textAlign = 'center';
            ctx.fillText('POINT OF VIEW', x + w / 2, y + h / 2 + w * 0.3);
            this.spaced(ctx, 0);
            ctx.restore();

            const line = Math.max(1.5, w * 0.012);
            ctx.lineWidth = line;
            ctx.strokeStyle = theme.orange;
            this.roundRect(ctx, x + line / 2, y + line / 2, w - line, h - line, radius);
            ctx.stroke();
        },

        /** Gradient frame used by the hero cards (POV + account). */
        heroFrame(ctx, w, h, theme, thickness) {
            const gradient = ctx.createLinearGradient(0, 0, w, h);
            gradient.addColorStop(0, theme.glow);
            gradient.addColorStop(0.5, theme.orange);
            gradient.addColorStop(1, theme.pink);
            ctx.save();
            // Clip to the card shape so the glow never leaves square corners
            this.roundRect(ctx, 0, 0, w, h, w * 0.06);
            ctx.clip();
            ctx.lineWidth = thickness;
            ctx.strokeStyle = gradient;
            ctx.shadowColor = theme.orange;
            ctx.shadowBlur = thickness * 1.5;
            this.roundRect(ctx, thickness / 2, thickness / 2, w - thickness, h - thickness, w * 0.06);
            ctx.stroke();
            ctx.restore();
            const inset = w * 0.05;
            ctx.save();
            ctx.lineWidth = Math.max(1, w * 0.004);
            ctx.strokeStyle = hexToRgba(theme.orange, 0.45);
            this.roundRect(ctx, inset, inset, w - inset * 2, h - inset * 2, w * 0.035);
            ctx.stroke();
            ctx.restore();
        },

        /** The POV card (replaces the "eye card" of the reference). */
        povFace(canvas, theme) {
            const ctx = canvas.getContext('2d');
            const w = canvas.width;
            const h = canvas.height;
            ctx.clearRect(0, 0, w, h);
            ctx.save();
            this.roundRect(ctx, 0, 0, w, h, w * 0.06);
            ctx.clip();

            const bg = ctx.createRadialGradient(w / 2, h * 0.42, 0, w / 2, h * 0.42, h * 0.75);
            bg.addColorStop(0, theme.darkBlue);
            bg.addColorStop(1, theme.midnight);
            ctx.fillStyle = bg;
            ctx.fillRect(0, 0, w, h);

            // Sunburst rays
            ctx.save();
            ctx.translate(w / 2, h * 0.42);
            for (let i = 0; i < 24; i++) {
                ctx.rotate((Math.PI * 2) / 24);
                ctx.fillStyle = hexToRgba(theme.orange, i % 2 ? 0.03 : 0.06);
                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.lineTo(-w * 0.07, -h);
                ctx.lineTo(w * 0.07, -h);
                ctx.fill();
            }
            ctx.restore();

            const halo = ctx.createRadialGradient(w / 2, h * 0.42, 0, w / 2, h * 0.42, w * 0.5);
            halo.addColorStop(0, hexToRgba(theme.orange, 0.4));
            halo.addColorStop(1, hexToRgba(theme.orange, 0));
            ctx.fillStyle = halo;
            ctx.fillRect(0, 0, w, h);

            this.eye(ctx, w / 2, h * 0.42, w * 0.3, theme.glow, w * 0.018, w * 0.06);

            const text = ctx.createLinearGradient(w * 0.25, 0, w * 0.75, 0);
            text.addColorStop(0, theme.glow);
            text.addColorStop(0.5, theme.orange);
            text.addColorStop(1, theme.pink);
            ctx.fillStyle = text;
            ctx.textAlign = 'center';
            ctx.font = `${w * 0.34}px ${theme.fontDisplay}`;
            ctx.shadowColor = theme.orange;
            ctx.shadowBlur = w * 0.04;
            ctx.fillText('POV', w / 2, h * 0.79);
            ctx.shadowBlur = 0;

            ctx.font = `600 ${w * 0.038}px ${theme.fontBody}`;
            this.spaced(ctx, w * 0.012);
            ctx.fillStyle = theme.muted;
            ctx.fillText('POINT OF VIEW', w / 2, h * 0.86);
            this.spaced(ctx, 0);

            // Playing-card corner indices
            ctx.fillStyle = theme.orange;
            ctx.font = `${w * 0.09}px ${theme.fontDisplay}`;
            ctx.fillText('P', w * 0.12, h * 0.11);
            this.eye(ctx, w * 0.12, h * 0.15, w * 0.035, theme.orange, Math.max(1, w * 0.005));
            ctx.save();
            ctx.translate(w * 0.88, h * 0.89);
            ctx.rotate(Math.PI);
            ctx.fillText('P', 0, 0);
            ctx.restore();
            ctx.restore();

            this.heroFrame(ctx, w, h, theme, w * 0.022);
        },

        /** The user's account card (replaces the Joker of the reference). */
        accountFace(canvas, user, theme, images) {
            const ctx = canvas.getContext('2d');
            const w = canvas.width;
            const h = canvas.height;
            ctx.clearRect(0, 0, w, h);
            ctx.save();
            this.roundRect(ctx, 0, 0, w, h, w * 0.06);
            ctx.clip();

            const bg = ctx.createLinearGradient(0, 0, 0, h);
            bg.addColorStop(0, theme.darkBlue);
            bg.addColorStop(1, theme.midnight);
            ctx.fillStyle = bg;
            ctx.fillRect(0, 0, w, h);
            const halo = ctx.createRadialGradient(w / 2, h * 0.42, 0, w / 2, h * 0.42, w * 0.7);
            halo.addColorStop(0, hexToRgba(theme.orange, 0.28));
            halo.addColorStop(1, hexToRgba(theme.orange, 0));
            ctx.fillStyle = halo;
            ctx.fillRect(0, 0, w, h);

            ctx.textAlign = 'center';
            const handle = `@${user.username}`;

            if (images.joker) {
                // Optional custom artwork (public/images/intro/joker.png)
                this.cover(ctx, images.joker, 0, 0, w, h);
                const shade = ctx.createLinearGradient(0, h * 0.55, 0, h);
                shade.addColorStop(0, 'rgba(0,0,0,0)');
                shade.addColorStop(1, 'rgba(0,0,0,0.85)');
                ctx.fillStyle = shade;
                ctx.fillRect(0, h * 0.55, w, h * 0.45);
                ctx.fillStyle = theme.text;
                this.fitFont(ctx, handle, w * 0.8, w * 0.14, theme.fontDisplay);
                ctx.fillText(handle, w / 2, h * 0.9);
            } else {
                // Crest
                this.eye(ctx, w / 2, h * 0.1, w * 0.07, theme.orange, Math.max(1.5, w * 0.008), w * 0.02);
                ctx.font = `${w * 0.06}px ${theme.fontDisplay}`;
                this.spaced(ctx, w * 0.012);
                ctx.fillStyle = theme.orange;
                ctx.fillText('POINT OF VIEW', w / 2, h * 0.175);
                this.spaced(ctx, 0);
                ctx.strokeStyle = hexToRgba(theme.orange, 0.35);
                ctx.lineWidth = Math.max(1, w * 0.003);
                ctx.beginPath();
                ctx.moveTo(w * 0.3, h * 0.2);
                ctx.lineTo(w * 0.7, h * 0.2);
                ctx.stroke();

                // Avatar
                const ax = w / 2;
                const ay = h * 0.43;
                const ar = w * 0.21;
                ctx.save();
                ctx.beginPath();
                ctx.arc(ax, ay, ar, 0, Math.PI * 2);
                ctx.clip();
                if (images.avatar) {
                    this.cover(ctx, images.avatar, ax - ar, ay - ar, ar * 2, ar * 2);
                } else {
                    const avatarBg = ctx.createLinearGradient(ax - ar, ay - ar, ax + ar, ay + ar);
                    avatarBg.addColorStop(0, `hsl(${user.hue} 85% 62%)`);
                    avatarBg.addColorStop(1, `hsl(${user.hue + 45} 75% 42%)`);
                    ctx.fillStyle = avatarBg;
                    ctx.fillRect(ax - ar, ay - ar, ar * 2, ar * 2);
                    ctx.fillStyle = '#FFFFFF';
                    ctx.font = `600 ${ar * 0.8}px ${theme.fontBody}`;
                    ctx.textBaseline = 'middle';
                    ctx.fillText(user.initials, ax, ay + ar * 0.04);
                    ctx.textBaseline = 'alphabetic';
                }
                ctx.restore();
                const ring = ctx.createLinearGradient(ax - ar, ay - ar, ax + ar, ay + ar);
                ring.addColorStop(0, theme.glow);
                ring.addColorStop(1, theme.pink);
                ctx.save();
                ctx.strokeStyle = ring;
                ctx.lineWidth = w * 0.014;
                ctx.shadowColor = theme.orange;
                ctx.shadowBlur = w * 0.04;
                ctx.beginPath();
                ctx.arc(ax, ay, ar + w * 0.025, 0, Math.PI * 2);
                ctx.stroke();
                ctx.restore();

                // Name + subtitle
                ctx.fillStyle = theme.text;
                this.fitFont(ctx, handle, w * 0.8, w * 0.15, theme.fontDisplay);
                ctx.fillText(handle, w / 2, h * 0.72);
                ctx.font = `500 ${w * 0.034}px ${theme.fontBody}`;
                this.spaced(ctx, w * 0.006);
                ctx.fillStyle = theme.muted;
                ctx.fillText(`STORYTELLER · MEMBER SINCE ${new Date().getFullYear()}`, w / 2, h * 0.78);
                this.spaced(ctx, 0);
                ctx.font = `italic 500 ${w * 0.036}px ${theme.fontBody}`;
                ctx.fillStyle = theme.glow;
                ctx.fillText('Ready to share your point of view', w / 2, h * 0.88);
            }

            // Corner stars
            ctx.fillStyle = theme.orange;
            ctx.font = `${w * 0.06}px ${theme.fontBody}`;
            ctx.fillText('★', w * 0.1, h * 0.08);
            ctx.fillText('★', w * 0.9, h * 0.96);
            ctx.restore();

            this.heroFrame(ctx, w, h, theme, w * 0.03);
        },

        /** Back of the hero cards. */
        backCanvas(width, height, theme) {
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            this.cardBack(canvas.getContext('2d'), 0, 0, width, height, theme);
            return canvas;
        },

        radial(size, stops) {
            const canvas = document.createElement('canvas');
            canvas.width = canvas.height = size;
            const ctx = canvas.getContext('2d');
            const gradient = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
            stops.forEach(([offset, color]) => gradient.addColorStop(offset, color));
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, size, size);
            return canvas;
        },
    };

    /* =====================================================================
     * 4. OVERLAY + LIFT-OFF of the real poster cards on the login page
     * ===================================================================== */
    function createOverlay() {
        const root = document.createElement('div');
        root.className = 'pov-intro';
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-modal', 'true');
        root.setAttribute('aria-label', 'Entering Point of View');
        root.innerHTML = `
            <div class="pov-intro__ghost" aria-hidden="true">
                <div class="ghost-nav"></div>
                <div class="ghost-cols">
                    <div class="ghost-col ghost-col--side"><span style="height: 46%"></span><span style="height: 30%"></span></div>
                    <div class="ghost-col ghost-col--main"><span style="height: 22%"></span><span style="height: 34%"></span><span style="height: 34%"></span></div>
                    <div class="ghost-col ghost-col--side"><span style="height: 38%"></span><span style="height: 30%"></span></div>
                </div>
            </div>
            <canvas class="pov-intro__canvas" aria-hidden="true"></canvas>
            <div class="pov-intro__stage" aria-hidden="true"></div>
            <div class="pov-intro__clones" aria-hidden="true"></div>
            <div class="pov-intro__vignette" aria-hidden="true"></div>
            <div class="pov-intro__grain" aria-hidden="true"></div>
            <div class="pov-intro__flash" aria-hidden="true"></div>
            <p class="visually-hidden" aria-live="polite">Entering Point of View…</p>
            <div class="pov-intro__controls">
                <button type="button" class="pov-intro__btn sound-toggle" data-sound-toggle aria-pressed="false" aria-label="Mute intro sound">
                    <i class="bi bi-volume-up"></i>
                </button>
                <button type="button" class="pov-intro__btn pov-intro__skip" data-intro-skip>
                    Skip <i class="bi bi-skip-forward-fill"></i>
                </button>
            </div>`;
        document.body.appendChild(root);

        return {
            root,
            canvas: root.querySelector('.pov-intro__canvas'),
            stage: root.querySelector('.pov-intro__stage'),
            clones: root.querySelector('.pov-intro__clones'),
            ghost: root.querySelector('.pov-intro__ghost'),
            flash: root.querySelector('.pov-intro__flash'),
            skip: root.querySelector('[data-intro-skip]'),
        };
    }

    /** Clone the visible poster cards of the login wall and swirl them toward the centre. */
    function liftOffPosters(layer, maxClones) {
        const inView = Array.from(document.querySelectorAll('.poster-bg .poster-card')).filter((card) => {
            const rect = card.getBoundingClientRect();
            return rect.width > 0 && rect.bottom > 0 && rect.right > 0 && rect.top < window.innerHeight && rect.left < window.innerWidth;
        });
        const random = seededRandom(7);
        const picked = inView.sort(() => random() - 0.5).slice(0, maxClones);
        const centerX = window.innerWidth / 2;
        const centerY = window.innerHeight / 2;

        picked.forEach((card, index) => {
            const rect = card.getBoundingClientRect();
            const clone = card.cloneNode(true);
            clone.classList.add('intro-clone');
            Object.assign(clone.style, {
                left: `${rect.left}px`,
                top: `${rect.top}px`,
                width: `${rect.width}px`,
                height: `${rect.height}px`,
            });
            layer.appendChild(clone);

            const dx = centerX - (rect.left + rect.width / 2);
            const dy = centerY - (rect.top + rect.height / 2);
            const turn = (index / picked.length) * 360;
            clone.animate([
                { transform: 'translate3d(0, 0, 0) rotate(-9deg) scale(1)', opacity: 1, filter: 'brightness(1)' },
                { transform: `translate3d(${dx * 0.25}px, ${dy * 0.25 - 50}px, 0) rotate(${turn * 0.2 - 9}deg) scale(1.12)`, opacity: 1, filter: 'brightness(1.35)', offset: 0.35 },
                { transform: `translate3d(${dx}px, ${dy}px, 0) rotate(${turn + 200}deg) scale(0.2)`, opacity: 0, filter: 'brightness(2)' },
            ], {
                duration: 1000,
                delay: index * 28,
                easing: 'cubic-bezier(0.55, 0, 0.35, 1)',
                fill: 'forwards',
            });
        });
    }

    /* =====================================================================
     * 5. SEQUENCE CONTROLLER
     * ===================================================================== */
    let isRunning = false;

    async function runSequence(user, finish) {
        const theme = readTheme();
        const data = readIntroData();
        const lowPower = detectLowPower();
        const quality = lowPower ? INTRO_CONFIG.quality.mobile : INTRO_CONFIG.quality.desktop;

        const overlay = createOverlay();
        document.body.classList.add('intro-active');
        POV.introSound?.syncButtons?.();

        const startTime = performance.now();
        liftOffPosters(overlay.clones, quality.liftOffClones);

        // Sound starts together with the visual clock; `elapsed` = seconds already on that clock
        const startSound = (elapsed) => POV.introSound?.playSequence?.(INTRO_CONFIG.sound, elapsed);

        const controller = { skipped: false, onSkip: null };
        let finished = false;

        const done = () => {
            if (finished) {
                return;
            }
            finished = true;
            document.removeEventListener('keydown', onKey);
            finish();
        };

        const skip = () => {
            if (controller.skipped || finished) {
                return;
            }
            controller.skipped = true;
            POV.introSound?.stop?.(0.25);
            overlay.flash.style.transition = 'opacity 0.25s ease';
            overlay.flash.style.opacity = '1';
            setTimeout(() => {
                controller.onSkip?.();
                done();
            }, 300);
        };

        function onKey(event) {
            if (event.key === 'Escape') {
                skip();
            }
        }

        overlay.skip.addEventListener('click', skip);
        document.addEventListener('keydown', onKey);
        setTimeout(() => overlay.skip.focus({ preventScroll: true }), 150);

        const context = { overlay, user, theme, data, quality, lowPower, startTime, startSound, controller, onDone: done };

        try {
            // Debug: /login?intro=replay&introFallback=css previews the no-WebGL version
            if (!hasWebGL() || new URLSearchParams(window.location.search).get('introFallback') === 'css') {
                throw new Error('WebGL is not available');
            }
            const THREE = await withTimeout(loadThree(), INTRO_CONFIG.loadTimeoutMs);
            if (controller.skipped) {
                return;
            }
            await runWebGLScene(THREE, context);
        } catch (error) {
            console.warn('[intro] Using the CSS fallback:', error && error.message);
            if (!controller.skipped) {
                runCssScene(context);
            }
        }
    }

    /* =====================================================================
     * 6. WEBGL SCENE (Three.js)
     * ===================================================================== */
    const CARD_VERTEX = /* glsl */ `
        attribute float aSlot;
        uniform float uFocus;
        uniform float uDof;
        varying vec2 vUv;
        varying float vSlot;
        varying float vDepth;
        varying float vBlur;
        void main() {
            vUv = uv;
            vSlot = aSlot;
            vec4 mvPosition = modelViewMatrix * instanceMatrix * vec4(position, 1.0);
            vDepth = -mvPosition.z;
            // Fake depth of field: blur grows with the distance from the focus plane
            vBlur = clamp(abs(vDepth - uFocus) * uDof, 0.0, 4.5);
            gl_Position = projectionMatrix * mvPosition;
        }`;

    const CARD_FRAGMENT = /* glsl */ `
        uniform sampler2D uAtlas;
        uniform vec2 uGrid;
        uniform float uBackSlot;
        uniform vec3 uBg;
        uniform float uFogNear;
        uniform float uFogFar;
        uniform float uFade;
        uniform float uBrightness;
        varying vec2 vUv;
        varying float vSlot;
        varying float vDepth;
        varying float vBlur;

        // Signed distance to a rounded rectangle the size of the card (1 x 1.5)
        float roundedDist(vec2 uv) {
            vec2 p = (uv - 0.5) * vec2(1.0, 1.5);
            vec2 q = abs(p) - (vec2(0.5, 0.75) - 0.06);
            return length(max(q, 0.0)) + min(max(q.x, q.y), 0.0) - 0.06;
        }

        void main() {
            float d = roundedDist(vUv);
            // Clamp the edge softness so cards seen edge-on don't become semi-transparent
            float aa = min(fwidth(d) * 1.2, 0.012);
            float alpha = 1.0 - smoothstep(-aa, aa, d);
            if (alpha < 0.02) discard;

            // Round the interpolated slot (a varying can arrive as 4.9999 instead of 5.0)
            float slot = floor((gl_FrontFacing ? vSlot : uBackSlot) + 0.5);
            vec2 uv = gl_FrontFacing ? vUv : vec2(1.0 - vUv.x, vUv.y);
            uv = clamp(uv, 0.004, 0.996);
            vec2 cell = 1.0 / uGrid;
            float row = floor((slot + 0.5) / uGrid.x);
            float col = slot - row * uGrid.x;
            vec2 atlasUv = vec2((col + uv.x) * cell.x, 1.0 - (row + 1.0 - uv.y) * cell.y);

            vec3 color = texture2D(uAtlas, atlasUv, vBlur).rgb * uBrightness;
            float fog = smoothstep(uFogNear, uFogFar, vDepth);
            color = mix(color, uBg, clamp(fog + uFade, 0.0, 1.0));
            gl_FragColor = vec4(color, alpha);
        }`;

    const EMBER_VERTEX = /* glsl */ `
        attribute float aSize;
        attribute vec3 aColor;
        uniform float uScale;
        uniform float uOpacity;
        varying vec3 vColor;
        varying float vAlpha;
        void main() {
            vColor = aColor;
            vec4 mvPosition = modelViewMatrix * vec4(position, 1.0);
            float depth = max(-mvPosition.z, 0.1);
            gl_PointSize = aSize * uScale / depth;
            vAlpha = uOpacity * smoothstep(42.0, 8.0, depth);
            gl_Position = projectionMatrix * mvPosition;
        }`;

    const EMBER_FRAGMENT = /* glsl */ `
        varying vec3 vColor;
        varying float vAlpha;
        void main() {
            float d = length(gl_PointCoord - 0.5);
            float a = smoothstep(0.5, 0.0, d);
            gl_FragColor = vec4(vColor, a * a * vAlpha);
        }`;

    async function runWebGLScene(THREE, context) {
        const { overlay, user, theme, data, quality, lowPower, startSound, controller, onDone } = context;
        let { startTime } = context;
        const cfg = INTRO_CONFIG;
        const { phases, cues } = cfg;

        // Plain sRGB pipeline: colors drawn on the canvases are shown exactly as drawn
        THREE.ColorManagement.enabled = false;

        const renderer = new THREE.WebGLRenderer({
            canvas: overlay.canvas,
            antialias: !lowPower,
            alpha: true,
            powerPreference: 'high-performance',
        });
        renderer.outputColorSpace = THREE.LinearSRGBColorSpace;
        let pixelRatio = Math.min(window.devicePixelRatio || 1, quality.pixelRatio);
        renderer.setPixelRatio(pixelRatio);
        renderer.setSize(window.innerWidth, window.innerHeight, false);
        const bgColor = new THREE.Color(theme.midnight);
        renderer.setClearColor(bgColor, 1);

        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.05, 140);
        const baseFov = () => (camera.aspect < 1 ? cfg.camera.fovPortrait : cfg.camera.fovLandscape);
        camera.fov = baseFov();
        camera.updateProjectionMatrix();

        /* ---------- Artwork ---------- */
        try {
            await withTimeout(Promise.all([
                document.fonts?.load(`64px ${theme.fontDisplay}`),
                document.fonts?.load(`600 32px ${theme.fontBody}`),
            ]), 1000);
        } catch (e) { /* draw with fallback fonts */ }

        const posters = data.posters && data.posters.length ? data.posters : FALLBACK_POSTERS;
        const [posterImages, jokerImage, avatarImage] = await Promise.all([
            Promise.all(posters.map((poster) => loadImage(poster.image, 1500))),
            loadImage(data.joker, 1500),
            loadImage(user.avatar, 1500),
        ]);

        const [cellW, cellH] = quality.atlasCell;
        const gridCols = 4;
        const slotCount = posters.length + 1;
        const gridRows = Math.ceil(slotCount / gridCols);
        const atlasCanvas = document.createElement('canvas');
        atlasCanvas.width = gridCols * cellW;
        atlasCanvas.height = gridRows * cellH;
        const atlasCtx = atlasCanvas.getContext('2d');
        posters.forEach((poster, i) => {
            Draw.posterFace(atlasCtx, (i % gridCols) * cellW, Math.floor(i / gridCols) * cellH, cellW, cellH, poster, posterImages[i], theme);
        });
        const backSlot = posters.length;
        Draw.cardBack(atlasCtx, (backSlot % gridCols) * cellW, Math.floor(backSlot / gridCols) * cellH, cellW, cellH, theme);

        const atlas = new THREE.CanvasTexture(atlasCanvas);
        atlas.minFilter = THREE.LinearMipmapLinearFilter;
        atlas.anisotropy = Math.min(4, renderer.capabilities.getMaxAnisotropy());

        const [heroW, heroH] = quality.heroCard;
        const povCanvas = document.createElement('canvas');
        povCanvas.width = Math.round(heroW * 0.6);
        povCanvas.height = Math.round(heroH * 0.6);
        Draw.povFace(povCanvas, theme);
        const accountCanvas = document.createElement('canvas');
        accountCanvas.width = heroW;
        accountCanvas.height = heroH;
        Draw.accountFace(accountCanvas, user, theme, { joker: jokerImage, avatar: avatarImage });
        const heroBackCanvas = Draw.backCanvas(Math.round(heroW * 0.6), Math.round(heroH * 0.6), theme);

        const disposables = [atlas];
        const track = (item) => {
            disposables.push(item);
            return item;
        };

        /* ---------- Poster cards (one InstancedMesh) ---------- */
        const count = quality.cards;
        const cardGeometry = track(new THREE.PlaneGeometry(1, 1.5));
        const random = seededRandom(42);
        const slots = new Float32Array(count);
        for (let i = 0; i < count; i++) {
            slots[i] = Math.floor(random() * posters.length);
        }
        cardGeometry.setAttribute('aSlot', new THREE.InstancedBufferAttribute(slots, 1));

        const cardUniforms = {
            uAtlas: { value: atlas },
            uGrid: { value: new THREE.Vector2(gridCols, gridRows) },
            uBackSlot: { value: backSlot },
            uBg: { value: bgColor.clone() },
            uFogNear: { value: 10 },
            uFogFar: { value: cfg.tunnel.length * 0.95 },
            uFade: { value: 0 },
            uBrightness: { value: 0.92 },
            uFocus: { value: 8 },
            uDof: { value: 0 },
        };
        const cardMaterial = track(new THREE.ShaderMaterial({
            uniforms: cardUniforms,
            vertexShader: CARD_VERTEX,
            fragmentShader: CARD_FRAGMENT,
            side: THREE.DoubleSide,
            alphaToCoverage: !lowPower,
            extensions: { derivatives: true },
        }));
        const cards = new THREE.InstancedMesh(cardGeometry, cardMaterial, count);
        cards.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
        cards.frustumCulled = false;
        scene.add(cards);

        /* ---------- Per-card layout ---------- */
        const { radius, length: tunnelLength, cardsPerTurn, pitch, twist } = cfg.tunnel;
        const theta0 = new Float32Array(count);
        const z0 = new Float32Array(count);
        const stagger = new Float32Array(count);
        const phase = new Float32Array(count);
        const tumble = new Float32Array(count);
        const tumbleAxis = [];
        const burstTarget = [];
        const burstQuat = [];
        const snapPos = [];
        const snapQuat = [];

        for (let i = 0; i < count; i++) {
            theta0[i] = (i / cardsPerTurn) * Math.PI * 2;
            z0[i] = -(i / count) * tunnelLength;
            stagger[i] = random() * 0.35;
            phase[i] = random() * Math.PI * 2;
            tumble[i] = (random() - 0.5) * 0.5;
            tumbleAxis.push(new THREE.Vector3(random() - 0.5, random() - 0.5, random() - 0.5).normalize());
            snapPos.push(new THREE.Vector3());
            snapQuat.push(new THREE.Quaternion());
            burstQuat.push(new THREE.Quaternion().setFromEuler(new THREE.Euler(
                (random() - 0.5) * 1.2,
                (random() - 0.5) * 1.6,
                (random() - 0.5) * Math.PI * 2,
            )));
            burstTarget.push(new THREE.Vector3());
        }

        // Poster wall in front of the camera (what the login background looks like)
        const wallDistance = 7;
        const wallQuat = new THREE.Quaternion().setFromEuler(new THREE.Euler(-0.32, 0, -0.14));
        const wallPos = [];
        const buildWall = () => {
            wallPos.length = 0;
            const viewH = 2 * wallDistance * Math.tan(THREE.MathUtils.degToRad(camera.fov / 2));
            const viewW = viewH * camera.aspect;
            const cellX = 1.18;
            const cellY = 1.72;
            const cols = Math.ceil((viewW * 1.4) / cellX) + 1;
            const rows = Math.ceil((viewH * 1.4) / cellY) + 1;
            for (let i = 0; i < Math.min(count, cols * rows); i++) {
                const row = Math.floor(i / cols);
                const col = i % cols;
                const local = new THREE.Vector3(
                    (col - (cols - 1) / 2) * cellX + (row % 2 ? cellX / 2 : 0),
                    ((rows - 1) / 2 - row) * cellY,
                    0,
                );
                wallPos.push(local.applyQuaternion(wallQuat).add(new THREE.Vector3(0, 0, -wallDistance)));
            }
        };
        buildWall();

        const buildBurstTargets = () => {
            const spreadY = camera.aspect < 1 ? 1.6 : 1;
            for (let i = 0; i < count; i++) {
                const angle = theta0[i] + (random() - 0.5) * 0.8;
                const distance = 3.5 + random() * 12;
                burstTarget[i].set(Math.cos(angle) * distance, Math.sin(angle) * distance * spreadY, -3 - random() * 26);
            }
        };
        buildBurstTargets();

        /* ---------- Hero cards ---------- */
        const makeHeroCard = (frontCanvas, scale) => {
            const geometry = track(new THREE.PlaneGeometry(1, 1.5));
            const frontTexture = track(new THREE.CanvasTexture(frontCanvas));
            const backTexture = track(new THREE.CanvasTexture(heroBackCanvas));
            [frontTexture, backTexture].forEach((texture) => {
                texture.anisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy());
            });
            const front = new THREE.Mesh(geometry, track(new THREE.MeshBasicMaterial({ map: frontTexture, transparent: true })));
            const back = new THREE.Mesh(geometry, track(new THREE.MeshBasicMaterial({ map: backTexture, transparent: true })));
            back.rotation.y = Math.PI;
            const group = new THREE.Group();
            group.add(front, back);
            group.scale.setScalar(scale);
            group.visible = false;
            scene.add(group);
            return group;
        };

        const povCard = makeHeroCard(povCanvas, 1.25);
        const accountCard = makeHeroCard(accountCanvas, 1);

        const glowTexture = track(new THREE.CanvasTexture(Draw.radial(128, [
            [0, hexToRgba(theme.glow, 1)],
            [0.25, hexToRgba(theme.orange, 0.6)],
            [1, hexToRgba(theme.orange, 0)],
        ])));
        const ringTexture = track(new THREE.CanvasTexture(Draw.radial(256, [
            [0, 'rgba(0,0,0,0)'],
            [0.62, 'rgba(0,0,0,0)'],
            [0.72, hexToRgba(theme.glow, 0.9)],
            [0.8, hexToRgba(theme.pink, 0.4)],
            [1, 'rgba(0,0,0,0)'],
        ])));
        const makeSprite = (texture) => {
            const sprite = new THREE.Sprite(track(new THREE.SpriteMaterial({
                map: texture,
                transparent: true,
                opacity: 0,
                blending: THREE.AdditiveBlending,
                depthWrite: false,
            })));
            sprite.visible = false;
            scene.add(sprite);
            return sprite;
        };
        const povGlow = makeSprite(glowTexture);
        const shockwave = makeSprite(ringTexture);
        const accountGlow = makeSprite(glowTexture);

        /* ---------- Embers + speed streaks ---------- */
        const emberCount = quality.embers;
        const emberPositions = new Float32Array(emberCount * 3);
        const emberColors = new Float32Array(emberCount * 3);
        const emberSizes = new Float32Array(emberCount);
        const emberVelocity = new Float32Array(emberCount * 3);
        const streakPositions = new Float32Array(emberCount * 6);
        const streakColors = new Float32Array(emberCount * 6);
        const palette = [theme.glow, theme.pink, theme.orange].map((hex) => new THREE.Color(hex));

        const spawnEmber = (i, zMin, zMax) => {
            const angle = random() * Math.PI * 2;
            const r = 0.3 + random() * 4.6;
            emberPositions[i * 3] = Math.cos(angle) * r;
            emberPositions[i * 3 + 1] = Math.sin(angle) * r;
            emberPositions[i * 3 + 2] = zMin + random() * (zMax - zMin);
        };
        for (let i = 0; i < emberCount; i++) {
            spawnEmber(i, -45, 1);
            const color = palette[i % palette.length];
            emberColors.set([color.r, color.g, color.b], i * 3);
            streakColors.set([color.r, color.g, color.b, 0, 0, 0], i * 6); // head bright, tail dark
            emberSizes[i] = 0.05 + random() * 0.1;
        }

        const emberGeometry = track(new THREE.BufferGeometry());
        emberGeometry.setAttribute('position', new THREE.BufferAttribute(emberPositions, 3).setUsage(THREE.DynamicDrawUsage));
        emberGeometry.setAttribute('aColor', new THREE.BufferAttribute(emberColors, 3));
        emberGeometry.setAttribute('aSize', new THREE.BufferAttribute(emberSizes, 1));
        const emberUniforms = { uScale: { value: 1 }, uOpacity: { value: 0 } };
        const embers = new THREE.Points(emberGeometry, track(new THREE.ShaderMaterial({
            uniforms: emberUniforms,
            vertexShader: EMBER_VERTEX,
            fragmentShader: EMBER_FRAGMENT,
            transparent: true,
            depthWrite: false,
            blending: THREE.AdditiveBlending,
        })));
        embers.frustumCulled = false;
        scene.add(embers);

        const streakGeometry = track(new THREE.BufferGeometry());
        streakGeometry.setAttribute('position', new THREE.BufferAttribute(streakPositions, 3).setUsage(THREE.DynamicDrawUsage));
        streakGeometry.setAttribute('color', new THREE.BufferAttribute(streakColors, 3));
        const streakMaterial = track(new THREE.LineBasicMaterial({
            vertexColors: true,
            transparent: true,
            opacity: 0,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
        }));
        const streaks = new THREE.LineSegments(streakGeometry, streakMaterial);
        streaks.frustumCulled = false;
        scene.add(streaks);

        const updatePointScale = () => {
            const heightPx = renderer.domElement.height;
            emberUniforms.uScale.value = heightPx / (2 * Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)));
        };
        updatePointScale();

        /* ---------- Resize ---------- */
        const onResize = () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.fov = baseFov();
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight, false);
            updatePointScale();
        };
        window.addEventListener('resize', onResize);

        /* ---------- Motion helpers ---------- */
        const m = cfg.motion;
        const travelSpeedAt = (t) => {
            if (t < phases.dive[0] - 0.2) {
                return m.travelStart * span(t, [0, phases.dive[0] - 0.2]);
            }
            if (t < m.diveRampEnd) {
                return lerp(m.travelStart, m.travelMax, EASE.inQuad(span(t, [phases.dive[0] - 0.2, m.diveRampEnd])));
            }
            return lerp(m.travelMax, m.travelReveal, EASE.outCubic(span(t, [m.diveRampEnd, m.slowDownEnd])));
        };
        const spinSpeedAt = (t) => {
            if (t < m.diveRampEnd) {
                return lerp(m.spinStart, m.spinMax, EASE.inQuad(span(t, [0, m.diveRampEnd])));
            }
            return lerp(m.spinMax, m.spinReveal, EASE.outCubic(span(t, [m.diveRampEnd, m.slowDownEnd])));
        };

        const wrapZ = (z) => {
            const range = tunnelLength + 2;
            return ((((z + tunnelLength) % range) + range) % range) - tunnelLength;
        };

        // Reusable objects (no allocations inside the render loop)
        const tmpPos = new THREE.Vector3();
        const tmpQuat = new THREE.Quaternion();
        const tunnelPos = new THREE.Vector3();
        const tunnelQuat = new THREE.Quaternion();
        const extraQuat = new THREE.Quaternion();
        const basis = new THREE.Matrix4();
        const axisX = new THREE.Vector3();
        const axisY = new THREE.Vector3();
        const axisZ = new THREE.Vector3();
        const unitScale = new THREE.Vector3(1, 1, 1);
        const zeroScale = new THREE.Vector3(0, 0, 0);
        const matrix = new THREE.Matrix4();
        const identity = new THREE.Quaternion();
        const flipY = new THREE.Quaternion().setFromAxisAngle(new THREE.Vector3(0, 1, 0), Math.PI);

        /** Card i on the spiral tunnel wall, facing the axis. */
        const tunnelPose = (i, travel, spin, outPos, outQuat) => {
            const z = wrapZ(z0[i] + travel);
            const theta = theta0[i] + spin + z * twist;
            const cos = Math.cos(theta);
            const sin = Math.sin(theta);
            outPos.set(radius * cos, radius * sin, z);
            axisZ.set(-cos, -sin, 0);                                                 // normal → axis
            axisY.set(-sin * Math.cos(pitch), cos * Math.cos(pitch), Math.sin(pitch)); // spiral up
            axisX.crossVectors(axisY, axisZ);
            basis.makeBasis(axisX, axisY, axisZ);
            outQuat.setFromRotationMatrix(basis);
        };

        /* ---------- State ---------- */
        let travel = 0;
        let spin = 0;
        let burstCaptured = false;
        let heroIndex = -1;
        const currentPos = new Float32Array(count * 3);
        const projected = new THREE.Vector3();
        const heroStartPos = new THREE.Vector3();
        const heroStartQuat = new THREE.Quaternion();
        const flyControl1 = new THREE.Vector3();
        const flyControl2 = new THREE.Vector3();
        const flyEnd = new THREE.Vector3();
        let lastNow = startTime;
        let frameId = 0;
        let disposed = false;
        let slowFrames = 0;
        let frameCounter = 0;

        const cubicBezier = (out, p0, p1, p2, p3, t) => {
            const u = 1 - t;
            out.set(0, 0, 0)
                .addScaledVector(p0, u * u * u)
                .addScaledVector(p1, 3 * u * u * t)
                .addScaledVector(p2, 3 * u * t * t)
                .addScaledVector(p3, t * t * t);
            return out;
        };

        const viewFit = (fraction) => {
            const tan = Math.tan(THREE.MathUtils.degToRad(camera.fov / 2));
            return Math.max(1.5 / (2 * tan * fraction), 1 / (2 * tan * camera.aspect * fraction));
        };
        const fillDistance = () => {
            const tan = Math.tan(THREE.MathUtils.degToRad(camera.fov / 2));
            return Math.min(1.5 / (2 * tan), 1 / (2 * tan * camera.aspect));
        };

        /* ---------- Per-frame update ---------- */
        const update = (t, dt) => {
            const speed = travelSpeedAt(t);
            const spinSpeed = spinSpeedAt(t);
            const speedNorm = speed / m.travelMax;
            if (t < phases.burst[0]) {
                travel += speed * dt;
                spin += spinSpeed * dt;
            }

            // Camera: FOV kick with speed, a little sway and shake, push-in on reveal, pull-back on burst
            camera.fov = baseFov() + cfg.camera.diveFovBoost * speedNorm * (t < phases.burst[0] ? 1 : 0);
            camera.updateProjectionMatrix();
            const shake = 0.025 * speedNorm;
            camera.position.set(
                (Math.random() - 0.5) * shake,
                (Math.random() - 0.5) * shake,
                -0.8 * EASE.inOutCubic(span(t, phases.reveal)) + 2 * EASE.outExpo(span(t, phases.burst)),
            );
            camera.rotation.z = Math.sin(t * 1.3) * 0.15 * speedNorm;

            // Overlay canvas fades in during the lift-off; afterwards the overlay itself becomes
            // opaque so the login page never shows through when the canvas turns transparent
            const canvasOpacity = EASE.outCubic(span(t, [0.05, phases.liftOff[1]]));
            overlay.canvas.style.opacity = String(canvasOpacity);
            overlay.root.style.backgroundColor = canvasOpacity >= 1 ? theme.midnight : 'transparent';

            /* --- Poster cards --- */
            const inBurst = t >= phases.burst[0];
            if (inBurst && !burstCaptured) {
                burstCaptured = true;
                for (let i = 0; i < count; i++) {
                    tunnelPose(i, travel, spin, snapPos[i], snapQuat[i]);
                }
            }

            // Pick the account card: a floating card that is on screen, upper-right, mid-depth
            if (inBurst && heroIndex === -1 && t >= phases.pick[0]) {
                let best = Infinity;
                for (let j = 0; j < count; j++) {
                    projected.set(currentPos[j * 3], currentPos[j * 3 + 1], currentPos[j * 3 + 2]);
                    const depth = camera.position.z - projected.z;
                    projected.project(camera);
                    if (depth < 6 || depth > 16 || Math.abs(projected.x) > 0.85 || Math.abs(projected.y) > 0.8) {
                        continue;
                    }
                    const score = Math.hypot(projected.x - 0.4, projected.y - 0.25) + Math.abs(depth - 10) * 0.05;
                    if (score < best) {
                        best = score;
                        heroIndex = j;
                    }
                }
                if (heroIndex === -1) {
                    heroIndex = 0;
                }
            }

            const fadeOthers = EASE.inOutCubic(span(t, cues.othersFade));
            const burstProgress = EASE.outExpo(span(t, phases.burst));
            const floatTime = t - phases.burst[0];
            const wallBlendEnd = cues.wallToTunnel[1];

            for (let i = 0; i < count; i++) {
                if (!inBurst) {
                    tunnelPose(i, travel, spin, tunnelPos, tunnelQuat);
                    if (i < wallPos.length && t < wallBlendEnd + 0.4) {
                        const blend = EASE.inOutCubic(span(t, [cues.wallToTunnel[0] + stagger[i], wallBlendEnd + stagger[i]]));
                        tmpPos.lerpVectors(wallPos[i], tunnelPos, blend);
                        tmpPos.z += Math.sin(blend * Math.PI) * 1.6; // lift toward the camera
                        tmpQuat.slerpQuaternions(wallQuat, tunnelQuat, blend);
                    } else {
                        tmpPos.copy(tunnelPos);
                        tmpPos.z -= (1 - EASE.outCubic(span(t, [0.3, 1.5]))) * 30; // late cards rise from the depth
                        tmpQuat.copy(tunnelQuat);
                    }
                } else {
                    tmpPos.lerpVectors(snapPos[i], burstTarget[i], burstProgress);
                    tmpPos.x += Math.sin(floatTime * 0.7 + phase[i]) * 0.3 * burstProgress;
                    tmpPos.y += Math.cos(floatTime * 0.6 + phase[i] * 1.3) * 0.3 * burstProgress;
                    tmpPos.x *= 1 + fadeOthers * 0.6;
                    tmpPos.y *= 1 + fadeOthers * 0.6;
                    tmpPos.z -= fadeOthers * 6;
                    tmpQuat.slerpQuaternions(snapQuat[i], burstQuat[i], EASE.outCubic(span(t, phases.burst)));
                    extraQuat.setFromAxisAngle(tumbleAxis[i], floatTime * tumble[i]);
                    tmpQuat.multiply(extraQuat);
                }

                currentPos[i * 3] = tmpPos.x;
                currentPos[i * 3 + 1] = tmpPos.y;
                currentPos[i * 3 + 2] = tmpPos.z;
                if (i === heroIndex && !accountCard.visible) {
                    heroStartPos.copy(tmpPos);
                    heroStartQuat.copy(tmpQuat);
                }
                matrix.compose(tmpPos, tmpQuat, i === heroIndex ? zeroScale : unitScale);
                cards.setMatrixAt(i, matrix);
            }
            cards.instanceMatrix.needsUpdate = true;
            cards.visible = t < cues.othersFade[1] + 0.15;

            // Depth of field + fades
            cardUniforms.uFade.value = fadeOthers * 0.95;
            cardUniforms.uDof.value = t < phases.burst[0]
                ? cfg.dof.dive * speedNorm
                : lerp(cfg.dof.burst, cfg.dof.pick, fadeOthers) * EASE.outCubic(span(t, [phases.burst[0], phases.burst[0] + 0.3]));
            cardUniforms.uFocus.value = accountCard.visible
                ? Math.max(1, camera.position.z - accountCard.position.z)
                : t >= phases.burst[0] ? 9 : 7;

            /* --- POV card --- */
            const arrive = span(t, cues.povArrive);
            const vanish = EASE.inCubic(span(t, cues.povVanish));
            povCard.visible = t >= cues.povArrive[0] && vanish < 1;
            if (povCard.visible) {
                const flip = EASE.outBack(span(t, cues.povFlip));
                povCard.position.set(0, Math.sin(t * 2) * 0.05, lerp(-34, -5.4, EASE.outCubic(arrive)));
                povCard.rotation.set(0, Math.PI * (1 - flip), vanish * 2.4);
                povCard.scale.setScalar(1.25 * (1 - vanish));
            }
            const pulse = cues.heartbeats.reduce((sum, beat) => sum + Math.exp(-Math.pow((t - beat) / 0.09, 2)), 0);
            povGlow.visible = povCard.visible;
            povGlow.position.copy(povCard.position).add(tmpPos.set(0, 0, -0.05));
            povGlow.scale.setScalar((2.6 + pulse * 1.6) * (1 - vanish * 0.5));
            povGlow.material.opacity = (0.35 * arrive + pulse * 0.6) * (1 - vanish);

            const wave = span(t, cues.shockwave);
            shockwave.visible = wave > 0 && wave < 1;
            shockwave.position.set(0, 0, -5.4);
            shockwave.scale.setScalar(0.5 + EASE.outCubic(wave) * 16);
            shockwave.material.opacity = (1 - wave) * 0.9;

            /* --- Account card --- */
            if (t >= phases.pick[0] && heroIndex !== -1) {
                if (!accountCard.visible) {
                    accountCard.visible = true;
                    accountCard.position.copy(heroStartPos);
                    accountCard.quaternion.copy(heroStartQuat).multiply(flipY); // back faces us first
                    heroStartQuat.copy(accountCard.quaternion);
                    flyEnd.set(0, 0, camera.position.z - viewFit(0.72));
                    flyControl1.copy(heroStartPos).add(tmpPos.set(-0.3, 0.4, 1.5));
                    flyControl2.copy(flyEnd).add(tmpPos.set(0.8, -0.3, -2.5));
                }
                const fly = EASE.inOutCubic(span(t, cues.accountFly));
                cubicBezier(accountCard.position, heroStartPos, flyControl1, flyControl2, flyEnd, fly);
                accountCard.quaternion.slerpQuaternions(heroStartQuat, identity, EASE.outCubic(span(t, cues.accountFlip)));
                accountCard.rotateZ(Math.sin(t * 3) * 0.06 * (1 - fly));

                // Zoom until the card is bigger than the screen
                const zoom = EASE.inCubic(span(t, [phases.zoom[0], cues.flash[1]]));
                if (zoom > 0) {
                    const distance = lerp(camera.position.z - flyEnd.z, fillDistance() * 0.45, zoom);
                    accountCard.position.set(0, 0, camera.position.z - distance);
                }

                const glint = Math.exp(-Math.pow((t - phases.pick[0] - 0.12) / 0.12, 2));
                accountGlow.visible = true;
                accountGlow.position.copy(accountCard.position).add(tmpPos.set(0, 0, -0.08));
                accountGlow.scale.setScalar(2.8 + glint * 2);
                accountGlow.material.opacity = 0.25 + glint * 0.75;
            }

            /* --- Ghost feed + transparent background during the zoom --- */
            const ghost = EASE.inOutCubic(span(t, cues.ghostFeed));
            renderer.setClearColor(bgColor, 1 - ghost);
            overlay.ghost.style.opacity = String(ghost);
            overlay.flash.style.opacity = String(EASE.inQuad(span(t, cues.flash)));

            /* --- Embers --- */
            const burstKick = t >= phases.burst[0];
            const streakLength = burstKick ? 0 : speed * 0.035;
            emberUniforms.uOpacity.value = EASE.outCubic(span(t, [0.2, 1.2])) * (1 - ghost);
            streakMaterial.opacity = Math.min(1, speedNorm * 1.4) * (burstKick ? 0 : 0.8);
            const swirl = spinSpeed * dt * 0.5;
            const swirlCos = Math.cos(swirl);
            const swirlSin = Math.sin(swirl);
            for (let i = 0; i < emberCount; i++) {
                const ix = i * 3;
                let x = emberPositions[ix];
                let y = emberPositions[ix + 1];
                let z = emberPositions[ix + 2];
                if (!burstKick) {
                    z += (speed * 0.9 + 0.6) * dt;
                    const rx = x * swirlCos - y * swirlSin;
                    y = x * swirlSin + y * swirlCos;
                    x = rx;
                } else {
                    if (emberVelocity[ix] === 0 && emberVelocity[ix + 1] === 0) {
                        emberVelocity[ix] = x * (0.8 + random());
                        emberVelocity[ix + 1] = y * (0.8 + random());
                        emberVelocity[ix + 2] = (random() - 0.5) * 2;
                    }
                    x += emberVelocity[ix] * dt;
                    y += emberVelocity[ix + 1] * dt + 0.15 * dt;
                    z += emberVelocity[ix + 2] * dt;
                    emberVelocity[ix] *= 0.97;
                    emberVelocity[ix + 1] *= 0.97;
                }
                if (z > 1.5) {
                    spawnEmber(i, -45, -38);
                    x = emberPositions[ix];
                    y = emberPositions[ix + 1];
                    z = emberPositions[ix + 2];
                }
                emberPositions[ix] = x;
                emberPositions[ix + 1] = y;
                emberPositions[ix + 2] = z;
                const sx = i * 6;
                streakPositions[sx] = x;
                streakPositions[sx + 1] = y;
                streakPositions[sx + 2] = z;
                streakPositions[sx + 3] = x;
                streakPositions[sx + 4] = y;
                streakPositions[sx + 5] = z - streakLength;
            }
            emberGeometry.attributes.position.needsUpdate = true;
            streakGeometry.attributes.position.needsUpdate = true;
        };

        /* ---------- Cleanup ---------- */
        const cleanup = () => {
            if (disposed) {
                return;
            }
            disposed = true;
            cancelAnimationFrame(frameId);
            window.removeEventListener('resize', onResize);
            disposables.forEach((item) => item.dispose && item.dispose());
            cards.dispose?.();
            renderer.dispose();
            renderer.forceContextLoss?.();
        };

        controller.onSkip = cleanup;

        /* ---------- Render loop ---------- */
        const frame = (now) => {
            if (disposed) {
                return;
            }
            const t = (now - startTime) / 1000;
            const dt = Math.min((now - lastNow) / 1000, 0.05);
            lastNow = now;

            // Adaptive quality: drop the pixel ratio if the first frames are slow
            frameCounter++;
            if (frameCounter > 10 && frameCounter < 70 && dt > 0.024) {
                slowFrames++;
                if (slowFrames > 20 && pixelRatio > 1) {
                    pixelRatio = 1;
                    renderer.setPixelRatio(1);
                    renderer.setSize(window.innerWidth, window.innerHeight, false);
                    updatePointScale();
                }
            }

            update(t, dt);
            renderer.render(scene, camera);

            if (t >= cfg.duration) {
                cleanup();
                onDone();
                return;
            }
            frameId = requestAnimationFrame(frame);
        };

        if (controller.skipped) {
            cleanup();
            return;
        }

        // Debug helper for tweaking timings: /login?intro=replay&introFreeze=4.2
        // fast-forwards to 4.2s and holds that frame (the sequence never ends).
        const freezeAt = Number(new URLSearchParams(window.location.search).get('introFreeze'));
        if (freezeAt > 0) {
            for (let simulated = 0; simulated < freezeAt; simulated += 1 / 60) {
                update(simulated, 1 / 60);
            }
            const hold = () => {
                if (!disposed) {
                    update(freezeAt, 0);
                    renderer.render(scene, camera);
                    frameId = requestAnimationFrame(hold);
                }
            };
            frameId = requestAnimationFrame(hold);
            return;
        }

        // Slow devices may need a moment to build textures and compile shaders. Start the visual
        // clock when the scene is ready (skipping at most 0.6s, which the DOM lift-off covers).
        const readyAt = performance.now();
        startTime = Math.max(startTime, readyAt - 600);
        lastNow = readyAt;
        startSound((readyAt - startTime) / 1000);

        frameId = requestAnimationFrame(frame);
    }

    /* =====================================================================
     * 7. CSS-3D FALLBACK (no WebGL or Three.js failed to load)
     * ===================================================================== */
    function runCssScene(context) {
        const { overlay, user, theme, data, startSound, controller, onDone } = context;
        const { phases, cues, duration } = INTRO_CONFIG;
        // If Three.js failed slowly, restart the clock (skip at most 0.6s)
        const startTime = Math.max(context.startTime, performance.now() - 600);
        startSound((performance.now() - startTime) / 1000);
        const posters = data.posters && data.posters.length ? data.posters : FALLBACK_POSTERS;
        const stage = overlay.stage;
        overlay.canvas.remove();
        stage.classList.add('is-active');

        const tunnel = document.createElement('div');
        tunnel.className = 'css-tunnel';
        const total = 16;
        for (let i = 0; i < total; i++) {
            const card = document.createElement('canvas');
            card.width = 120;
            card.height = 180;
            Draw.posterFace(card.getContext('2d'), 0, 0, 120, 180, posters[i % posters.length], null, theme);
            card.className = 'css-card';
            card.style.setProperty('--a', `${(360 / total) * i}deg`);
            card.style.setProperty('--d', `${(i % 4) * -320}px`);
            card.style.setProperty('--i', String(i));
            tunnel.appendChild(card);
        }

        const pov = document.createElement('canvas');
        pov.width = 300;
        pov.height = 450;
        pov.className = 'css-hero css-pov';
        Draw.povFace(pov, theme);

        const account = document.createElement('canvas');
        account.width = 400;
        account.height = 600;
        account.className = 'css-hero css-account';
        Draw.accountFace(account, user, theme, { joker: null, avatar: null });

        stage.append(tunnel, pov, account);

        const timers = [];
        const at = (seconds, callback) => {
            const delay = Math.max(0, seconds * 1000 - (performance.now() - startTime));
            timers.push(setTimeout(() => {
                if (!controller.skipped) {
                    callback();
                }
            }, delay));
        };

        requestAnimationFrame(() => stage.classList.add('is-start'));
        at(phases.dive[0], () => stage.classList.add('is-diving'));
        at(phases.reveal[0], () => stage.classList.add('is-reveal'));
        at(phases.burst[0], () => stage.classList.add('is-burst'));
        at(phases.pick[0], () => stage.classList.add('is-pick'));
        at(phases.zoom[0], () => stage.classList.add('is-zoom'));
        at(cues.flash[0], () => {
            overlay.flash.style.transition = `opacity ${cues.flash[1] - cues.flash[0]}s ease-in`;
            overlay.flash.style.opacity = '1';
        });
        at(duration, onDone);
        controller.onSkip = () => timers.forEach(clearTimeout);
    }

    /* =====================================================================
     * 8. QUICK FADE (reduced motion, or the intro already played this session)
     * ===================================================================== */
    function quickFade(finish) {
        const veil = document.createElement('div');
        veil.className = 'pov-intro-veil';
        document.body.appendChild(veil);
        requestAnimationFrame(() => requestAnimationFrame(() => veil.classList.add('is-visible')));
        setTimeout(finish, INTRO_CONFIG.reducedMotionFadeMs);
    }

    /* =====================================================================
     * 9. PUBLIC API + THREE.JS PRELOADING
     * ===================================================================== */
    let threePromise = null;
    function loadThree() {
        if (!threePromise) {
            threePromise = import(INTRO_CONFIG.threeUrl).catch((error) => {
                threePromise = null;
                throw error;
            });
        }
        return threePromise;
    }

    /**
     * Play the "Enter Point of View" sequence.
     * @param {{ username?: string, avatar?: string|null, onComplete?: Function, force?: boolean }} options
     */
    POV.playEnterSequence = function ({ username = 'storyteller', avatar = null, onComplete = () => {}, force = false } = {}) {
        if (isRunning) {
            return;
        }
        isRunning = true;

        const user = makeUser(username, avatar);
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const replayRequested = new URLSearchParams(window.location.search).get('intro') === 'replay';
        const alreadyPlayed = storage.get('sessionStorage', INTRO_CONFIG.storage.played) === '1';
        const quick = reducedMotion || (alreadyPlayed && !force && !replayRequested);

        const finish = () => {
            // Hand-off for the feed page (it continues the transition)
            storage.set('sessionStorage', INTRO_CONFIG.storage.handoff, JSON.stringify({
                username: user.username,
                initials: user.initials,
                hue: user.hue,
                mode: quick ? 'quick' : 'full',
                at: Date.now(),
            }));
            onComplete();
        };

        // Warm up the feed page so the navigation at the end is instant
        const feedUrl = readIntroData().feedUrl;
        if (feedUrl) {
            const prefetch = document.createElement('link');
            prefetch.rel = 'prefetch';
            prefetch.href = feedUrl;
            document.head.appendChild(prefetch);
        }

        if (quick) {
            quickFade(finish);
            return;
        }

        storage.set('sessionStorage', INTRO_CONFIG.storage.played, '1');
        runSequence(user, finish);
    };

    // Start downloading Three.js early on the login page (first interaction or idle)
    if (document.querySelector('[data-auth-form]')) {
        const warmUp = () => {
            if (hasWebGL()) {
                loadThree().catch(() => {});
            }
        };
        document.addEventListener('focusin', warmUp, { once: true });
        document.addEventListener('pointerdown', warmUp, { once: true });
        window.addEventListener('load', () => setTimeout(warmUp, 2500), { once: true });
    }

    /* =====================================================================
     * 10. FEED ARRIVAL — the feed page continues the flash and fades in
     *     (markup: partials/intro-arrival; the class is set by a <head> script)
     * ===================================================================== */
    function initArrival() {
        const root = document.documentElement;
        const arrival = document.querySelector('[data-intro-arrival]');
        if (!root.classList.contains('intro-handoff') || !arrival) {
            arrival?.remove();
            return;
        }

        let handoff = null;
        try {
            handoff = JSON.parse(storage.get('sessionStorage', INTRO_CONFIG.storage.handoff) || 'null');
        } catch (e) {
            handoff = null;
        }
        storage.remove('sessionStorage', INTRO_CONFIG.storage.handoff);

        if (handoff) {
            const name = arrival.querySelector('[data-arrival-name]');
            const avatar = arrival.querySelector('[data-arrival-avatar]');
            if (name) {
                name.textContent = `@${handoff.username}`;
            }
            if (avatar) {
                avatar.textContent = handoff.initials;
                avatar.style.setProperty('--avatar-hue', handoff.hue);
            }
        }

        // The CSS animation does the work; clean up afterwards
        setTimeout(() => {
            root.classList.remove('intro-handoff');
            delete root.dataset.introHandoff;
            arrival.remove();
        }, 2600);
    }

    initArrival();
})();
