/**
 * Point of View — intro-sound.js
 * Sound design for the "Enter Point of View" sequence, generated with the
 * Web Audio API (no audio files, nothing copyrighted).
 *
 *   POV.introSound.unlock()              → create/resume the AudioContext (call inside a click/submit)
 *   POV.introSound.playSequence(cues)    → schedule every cue (times come from INTRO_CONFIG.sound)
 *   POV.introSound.stop(fadeSeconds)     → fade out and stop (used by "Skip")
 *   POV.introSound.setMuted(bool) / toggleMuted() / isMuted()
 *
 * Optional files: if public/audio/whoosh.mp3 or public/audio/hit.mp3 exist
 * (detected by the server, passed in #introData) they replace the synthesized
 * whoosh / burst and the final impact.
 */
(function () {
    'use strict';

    const POV = (window.POV = window.POV || {});

    const SOUND_CONFIG = {
        masterVolume: 0.3,      // keep it low and cinematic
        reverbSeconds: 2.8,
        reverbSend: 0.35,
        storageKey: 'pov:sound-muted',
    };

    let ctx = null;
    let master = null;
    let reverbInput = null;
    let noiseBuffer = null;
    let muted = readMuted();
    const files = {};
    let activeNodes = [];

    function readMuted() {
        try {
            return localStorage.getItem(SOUND_CONFIG.storageKey) === '1';
        } catch (e) {
            return false;
        }
    }

    /* ------------------------------------------------------------------
     * Audio graph: sources → (dry) master → compressor → speakers
     *                      ↘ reverb send → convolver → master
     * ------------------------------------------------------------------ */
    function ensureContext() {
        if (ctx) {
            return ctx;
        }
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            return null;
        }
        ctx = new AudioContextClass();

        const compressor = ctx.createDynamicsCompressor();
        compressor.threshold.value = -18;
        compressor.ratio.value = 4;
        compressor.connect(ctx.destination);

        master = ctx.createGain();
        master.gain.value = muted ? 0 : SOUND_CONFIG.masterVolume;
        master.connect(compressor);

        const convolver = ctx.createConvolver();
        convolver.buffer = makeImpulse(SOUND_CONFIG.reverbSeconds);
        reverbInput = ctx.createGain();
        reverbInput.gain.value = 1;
        reverbInput.connect(convolver);
        convolver.connect(master);

        noiseBuffer = makeNoise(2);
        loadFiles();
        return ctx;
    }

    function makeNoise(seconds) {
        const buffer = ctx.createBuffer(1, ctx.sampleRate * seconds, ctx.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < data.length; i++) {
            data[i] = Math.random() * 2 - 1;
        }
        return buffer;
    }

    // Synthetic reverb: stereo noise with an exponential decay
    function makeImpulse(seconds) {
        const length = Math.floor(ctx.sampleRate * seconds);
        const impulse = ctx.createBuffer(2, length, ctx.sampleRate);
        for (let channel = 0; channel < 2; channel++) {
            const data = impulse.getChannelData(channel);
            for (let i = 0; i < length; i++) {
                data[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / length, 3);
            }
        }
        return impulse;
    }

    function loadFiles() {
        let urls = {};
        try {
            urls = (JSON.parse(document.getElementById('introData')?.textContent || '{}').audio) || {};
        } catch (e) {
            urls = {};
        }
        Object.entries(urls).forEach(([name, url]) => {
            if (!url) {
                return;
            }
            fetch(url)
                .then((response) => response.arrayBuffer())
                .then((data) => ctx.decodeAudioData(data))
                .then((buffer) => {
                    files[name] = buffer;
                })
                .catch(() => {});
        });
    }

    /* ------------------------------------------------------------------
     * Small building blocks
     * ------------------------------------------------------------------ */
    const track = (node) => {
        activeNodes.push(node);
        return node;
    };

    // Gain envelope that ends at silence; also routes to the reverb if `wet` > 0
    function output(when, wet = 0, pan = 0) {
        const gain = ctx.createGain();
        gain.gain.setValueAtTime(0.0001, when);
        let last = gain;
        if (pan && ctx.createStereoPanner) {
            const panner = ctx.createStereoPanner();
            panner.pan.value = pan;
            gain.connect(panner);
            last = panner;
        }
        last.connect(master);
        if (wet > 0) {
            const send = ctx.createGain();
            send.gain.value = wet * SOUND_CONFIG.reverbSend;
            last.connect(send);
            send.connect(reverbInput);
        }
        return gain;
    }

    function noise(when, duration) {
        const source = track(ctx.createBufferSource());
        source.buffer = noiseBuffer;
        source.loop = true;
        source.start(when, Math.random());
        source.stop(when + duration + 0.05);
        return source;
    }

    function osc(type, frequency, when, duration) {
        const oscillator = track(ctx.createOscillator());
        oscillator.type = type;
        oscillator.frequency.setValueAtTime(frequency, when);
        oscillator.start(when);
        oscillator.stop(when + duration + 0.05);
        return oscillator;
    }

    function filter(type, frequency, q = 1) {
        const node = ctx.createBiquadFilter();
        node.type = type;
        node.frequency.value = frequency;
        node.Q.value = q;
        return node;
    }

    function playFile(name, when, volume = 1, wet = 0.3) {
        const source = track(ctx.createBufferSource());
        source.buffer = files[name];
        const gain = output(when, wet);
        gain.gain.setValueAtTime(volume, when);
        source.connect(gain);
        source.start(when);
    }

    /* ------------------------------------------------------------------
     * Cues
     * ------------------------------------------------------------------ */

    // Soft card flutter: tiny filtered noise ticks, like cards being riffled
    function flutter(when, duration) {
        for (let i = 0; i < 16; i++) {
            const at = when + Math.random() * duration;
            const length = 0.02 + Math.random() * 0.04;
            const band = filter('bandpass', 1800 + Math.random() * 2600, 2.5);
            const gain = output(at, 0.2, Math.random() * 1.4 - 0.7);
            gain.gain.exponentialRampToValueAtTime(0.35, at + 0.004);
            gain.gain.exponentialRampToValueAtTime(0.0001, at + length);
            noise(at, length).connect(band).connect(gain);
        }
    }

    // Rising filtered-noise whoosh that sweeps across the stereo field
    function whoosh(when, duration) {
        if (files.whoosh) {
            playFile('whoosh', when, 0.9);
            return;
        }
        const band = filter('bandpass', 250, 0.9);
        band.frequency.setValueAtTime(250, when);
        band.frequency.exponentialRampToValueAtTime(3600, when + duration);
        const gain = output(when, 0.3);
        gain.gain.exponentialRampToValueAtTime(0.12, when + duration * 0.4);
        gain.gain.exponentialRampToValueAtTime(0.55, when + duration * 0.95);
        gain.gain.exponentialRampToValueAtTime(0.0001, when + duration + 0.4);
        let source = noise(when, duration + 0.4).connect(band);
        if (ctx.createStereoPanner) {
            const panner = ctx.createStereoPanner();
            panner.pan.setValueAtTime(-0.6, when);
            panner.pan.linearRampToValueAtTime(0.6, when + duration);
            source = source.connect(panner);
        }
        source.connect(gain);
    }

    // Low, dark drone (detuned saws through a slowly moving low-pass)
    function drone(when, duration) {
        const low = filter('lowpass', 320, 4);
        const lfo = osc('sine', 0.25, when, duration);
        const lfoDepth = ctx.createGain();
        lfoDepth.gain.value = 120;
        lfo.connect(lfoDepth).connect(low.frequency);

        const gain = output(when, 0.25);
        gain.gain.exponentialRampToValueAtTime(0.22, when + 1);
        gain.gain.setValueAtTime(0.22, when + duration - 1.2);
        gain.gain.exponentialRampToValueAtTime(0.0001, when + duration);
        low.connect(gain);

        [[55, 0], [82.4, 7], [27.5, 0]].forEach(([frequency, detune], index) => {
            const oscillator = osc(index === 2 ? 'sine' : 'sawtooth', frequency, when, duration);
            oscillator.detune.value = detune;
            oscillator.connect(low);
        });
    }

    // Deep heartbeat / sub-bass thump (pitch-dropping sine)
    function heartbeat(when, strength) {
        const oscillator = osc('sine', 72, when, 0.45);
        oscillator.frequency.exponentialRampToValueAtTime(36, when + 0.2);
        const gain = output(when, 0.15);
        gain.gain.exponentialRampToValueAtTime(strength, when + 0.006);
        gain.gain.exponentialRampToValueAtTime(0.0001, when + 0.42);
        oscillator.connect(gain);

        const click = filter('lowpass', 220, 1);
        const clickGain = output(when);
        clickGain.gain.exponentialRampToValueAtTime(strength * 0.4, when + 0.003);
        clickGain.gain.exponentialRampToValueAtTime(0.0001, when + 0.06);
        noise(when, 0.07).connect(click).connect(clickGain);
    }

    // Shimmering chime (bell-like partials with a light tremolo, mostly reverb)
    function chime(when) {
        [1318.5, 1975.5, 2637, 3951].forEach((frequency, index) => {
            const oscillator = osc('sine', frequency, when, 2.8);
            oscillator.detune.value = (Math.random() - 0.5) * 8;
            const gain = output(when, 0.8, (index % 2 ? 0.3 : -0.3));
            gain.gain.exponentialRampToValueAtTime(0.09 / (index + 1), when + 0.012);
            gain.gain.exponentialRampToValueAtTime(0.0001, when + 2.6);
            const tremolo = osc('sine', 6 + index, when, 2.8);
            const depth = ctx.createGain();
            depth.gain.value = 0.02 / (index + 1);
            tremolo.connect(depth).connect(gain.gain);
            oscillator.connect(gain);
        });
    }

    // Airy burst when the tunnel explodes
    function airBurst(when) {
        if (files.whoosh) {
            playFile('whoosh', when, 0.7, 0.5);
            return;
        }
        const high = filter('highpass', 600, 0.7);
        const low = filter('lowpass', 9000, 0.7);
        low.frequency.setValueAtTime(9000, when);
        low.frequency.exponentialRampToValueAtTime(1200, when + 1.1);
        const gain = output(when, 0.6);
        gain.gain.exponentialRampToValueAtTime(0.5, when + 0.015);
        gain.gain.exponentialRampToValueAtTime(0.0001, when + 1.2);
        noise(when, 1.2).connect(high).connect(low).connect(gain);
        heartbeat(when, 0.45);
    }

    // Riser that builds during the final zoom
    function riser(when, duration) {
        const band = filter('bandpass', 200, 3);
        band.frequency.setValueAtTime(200, when);
        band.frequency.exponentialRampToValueAtTime(6000, when + duration);
        const noiseGain = output(when, 0.3);
        noiseGain.gain.exponentialRampToValueAtTime(0.02, when + 0.05);
        noiseGain.gain.exponentialRampToValueAtTime(0.4, when + duration);
        noiseGain.gain.exponentialRampToValueAtTime(0.0001, when + duration + 0.02);
        noise(when, duration).connect(band).connect(noiseGain);

        const tone = osc('sawtooth', 110, when, duration);
        tone.frequency.exponentialRampToValueAtTime(660, when + duration);
        const toneFilter = filter('lowpass', 500, 2);
        toneFilter.frequency.setValueAtTime(500, when);
        toneFilter.frequency.exponentialRampToValueAtTime(4000, when + duration);
        const toneGain = output(when, 0.2);
        toneGain.gain.exponentialRampToValueAtTime(0.1, when + duration);
        toneGain.gain.exponentialRampToValueAtTime(0.0001, when + duration + 0.02);
        tone.connect(toneFilter).connect(toneGain);
    }

    // Soft impact with a short reverb tail at the flash
    function impact(when) {
        if (files.hit) {
            playFile('hit', when, 1, 0.6);
            return;
        }
        const sub = osc('sine', 58, when, 1.4);
        sub.frequency.exponentialRampToValueAtTime(30, when + 0.6);
        const subGain = output(when, 0.5);
        subGain.gain.exponentialRampToValueAtTime(0.9, when + 0.008);
        subGain.gain.exponentialRampToValueAtTime(0.0001, when + 1.3);
        sub.connect(subGain);

        const crack = filter('lowpass', 1400, 0.8);
        const crackGain = output(when, 0.9);
        crackGain.gain.exponentialRampToValueAtTime(0.45, when + 0.005);
        crackGain.gain.exponentialRampToValueAtTime(0.0001, when + 0.5);
        noise(when, 0.5).connect(crack).connect(crackGain);
    }

    /* ------------------------------------------------------------------
     * Public API
     * ------------------------------------------------------------------ */
    function syncButtons() {
        document.querySelectorAll('[data-sound-toggle]').forEach((button) => {
            button.setAttribute('aria-pressed', String(muted));
            button.setAttribute('aria-label', muted ? 'Unmute intro sound' : 'Mute intro sound');
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = muted ? 'bi bi-volume-mute' : 'bi bi-volume-up';
            }
        });
    }

    function setMuted(value) {
        muted = Boolean(value);
        try {
            localStorage.setItem(SOUND_CONFIG.storageKey, muted ? '1' : '0');
        } catch (e) { /* ignore */ }
        if (ctx && master) {
            const now = ctx.currentTime;
            master.gain.cancelScheduledValues(now);
            master.gain.setTargetAtTime(muted ? 0 : SOUND_CONFIG.masterVolume, now, 0.05);
        }
        syncButtons();
    }

    POV.introSound = {
        config: SOUND_CONFIG,

        unlock() {
            if (!ensureContext()) {
                return;
            }
            if (ctx.state === 'suspended') {
                ctx.resume().catch(() => {});
            }
        },

        /**
         * Schedule every cue relative to "now". `cues` uses seconds, e.g.
         * { flutter: [0, 0.8], whoosh: [0.8, 3.6], heartbeats: [4.1, 4.55], chime: 4.2, ... }
         */
        playSequence(cues, elapsed = 0) {
            if (!ensureContext() || !cues) {
                return;
            }
            activeNodes = [];
            master.gain.cancelScheduledValues(ctx.currentTime);
            master.gain.setValueAtTime(muted ? 0 : SOUND_CONFIG.masterVolume, ctx.currentTime);

            // `elapsed` = seconds the visual timeline has already run; cues in the past start right away
            const start = ctx.currentTime + 0.05 - elapsed;
            const at = (seconds) => Math.max(ctx.currentTime + 0.02, start + seconds);
            const length = ([from, to]) => Math.max(0.1, start + to - at(from));

            if (cues.flutter) flutter(at(cues.flutter[0]), length(cues.flutter));
            if (cues.whoosh) whoosh(at(cues.whoosh[0]), length(cues.whoosh));
            if (cues.drone) drone(at(cues.drone[0]), length(cues.drone));
            (cues.heartbeats || []).forEach((beat, index) => heartbeat(at(beat), index === 0 ? 0.9 : 0.6));
            if (cues.chime !== undefined) chime(at(cues.chime));
            if (cues.burst !== undefined) airBurst(at(cues.burst));
            if (cues.riser) riser(at(cues.riser[0]), length(cues.riser));
            if (cues.impact !== undefined) impact(at(cues.impact));
        },

        stop(fadeSeconds = 0.3) {
            if (!ctx) {
                return;
            }
            const now = ctx.currentTime;
            master.gain.cancelScheduledValues(now);
            master.gain.setValueAtTime(master.gain.value, now);
            master.gain.linearRampToValueAtTime(0, now + fadeSeconds);
            const nodes = activeNodes;
            activeNodes = [];
            setTimeout(() => {
                nodes.forEach((node) => {
                    try {
                        node.stop();
                    } catch (e) { /* already stopped */ }
                });
            }, fadeSeconds * 1000 + 50);
        },

        setMuted,
        toggleMuted: () => setMuted(!muted),
        isMuted: () => muted,
        syncButtons,
    };

    // Mute buttons (also the ones added later inside the intro overlay)
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-sound-toggle]');
        if (!button) {
            return;
        }
        POV.introSound.toggleMuted();
        button.classList.remove('is-animating');
        void button.offsetWidth;
        button.classList.add('is-animating');
    });

    syncButtons();
})();
