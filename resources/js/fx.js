let ctx = null;
let enabled = true;

export function initAudio() {
    if (!ctx) {
        const AC = window.AudioContext || window.webkitAudioContext;
        if (AC) ctx = new AC();
    }
    if (ctx && ctx.state === 'suspended') ctx.resume();
}

export function setSound(on) {
    enabled = on;
    try { localStorage.setItem('sound', on ? '1' : '0'); } catch (e) { /* 無視 */ }
}

export function soundEnabled() {
    try { return localStorage.getItem('sound') !== '0'; } catch (e) { return true; }
}

function tone({ freq = 440, dur = 0.08, type = 'square', gain = 0.06, to = null, delay = 0 }) {
    if (!ctx || !enabled) return;

    const t0 = ctx.currentTime + delay;
    const osc = ctx.createOscillator();
    const g = ctx.createGain();

    osc.type = type;
    osc.frequency.setValueAtTime(freq, t0);

    if (to) osc.frequency.exponentialRampToValueAtTime(Math.max(30, to), t0 + dur);

    g.gain.setValueAtTime(gain, t0);
    g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);

    osc.connect(g);
    g.connect(ctx.destination);
    osc.start(t0);
    osc.stop(t0 + dur);
}

export const SFX = {
    key: () => tone({ freq: 1200, dur: 0.025, type: 'square', gain: 0.022 }),
    word: () => tone({ freq: 880, to: 1400, dur: 0.09, type: 'triangle', gain: 0.06 }),
    miss: () => tone({ freq: 180, to: 90, dur: 0.13, type: 'sawtooth', gain: 0.05 }),
    hit: () => tone({ freq: 140, to: 60, dur: 0.18, type: 'sawtooth', gain: 0.08 }),
    bigHit: () => {
        tone({ freq: 90, to: 40, dur: 0.4, type: 'sawtooth', gain: 0.11 });
        tone({ freq: 150, to: 60, dur: 0.3, type: 'square', gain: 0.06, delay: 0.03 });
    },
    heal: () => {
        tone({ freq: 520, dur: 0.1, type: 'sine', gain: 0.06 });
        tone({ freq: 780, dur: 0.14, type: 'sine', gain: 0.05, delay: 0.07 });
    },
    guard: () => {
        tone({ freq: 300, to: 900, dur: 0.16, type: 'triangle', gain: 0.08 });
    },
    charge: () => {
        tone({ freq: 200, to: 420, dur: 0.5, type: 'sawtooth', gain: 0.05 });
    },
    defeat: () => {
        [523, 659, 784].forEach((f, i) =>
            tone({ freq: f, dur: 0.16, type: 'triangle', gain: 0.07, delay: i * 0.07 }));
    },
    levelup: () => {
        [523, 659, 784, 1046].forEach((f, i) =>
            tone({ freq: f, dur: 0.22, type: 'square', gain: 0.06, delay: i * 0.09 }));
    },
    lose: () => {
        [392, 330, 262].forEach((f, i) =>
            tone({ freq: f, dur: 0.3, type: 'triangle', gain: 0.07, delay: i * 0.14 }));
    },
    countdown: () => tone({ freq: 660, dur: 0.09, type: 'square', gain: 0.05 }),
    go: () => tone({ freq: 990, to: 1400, dur: 0.25, type: 'square', gain: 0.07 }),
};

export function popup(anchor, text, kind = 'dmg') {
    const host = document.querySelector('.arena');
    if (!host || !anchor) return;

    const r = anchor.getBoundingClientRect();
    const h = host.getBoundingClientRect();

    const el = document.createElement('div');
    el.className = `fx-pop fx-${kind}`;
    el.textContent = text;
    el.style.left = (r.left - h.left + r.width / 2) + 'px';
    el.style.top = (r.top - h.top) + 'px';

    host.appendChild(el);
    setTimeout(() => el.remove(), 900);
}

export function shake(el, strong = false) {
    if (!el) return;
    const cls = strong ? 'fx-shake-strong' : 'fx-shake';
    el.classList.remove(cls);
    void el.offsetWidth;
    el.classList.add(cls);
    setTimeout(() => el.classList.remove(cls), strong ? 500 : 260);
}

export function flash(kind = 'red') {
    const host = document.querySelector('.arena');
    if (!host) return;

    const el = document.createElement('div');
    el.className = `fx-flash fx-flash-${kind}`;
    host.appendChild(el);
    setTimeout(() => el.remove(), 300);
}