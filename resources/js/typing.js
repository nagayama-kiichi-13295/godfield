import { createMatcher, canonicalRomaji } from './romaji';

export { canonicalRomaji };

export function createTyping({ words, displayEl, readingEl, romaEl, onWord, onMiss }) {
    let idx = 0;
    let completed = 0;
    let active = false;
    let matcher = createMatcher(words[0].k);

    function render() {
        const w = words[idx];
        if (!w) return;

        displayEl.textContent = w.d;
        if (readingEl) readingEl.textContent = w.k;

        const { typed, rest } = matcher.hint();

        romaEl.textContent = '';

        const done = document.createElement('span');
        done.className = 'done';
        done.textContent = typed;

        const remain = document.createElement('span');
        remain.textContent = rest;

        romaEl.append(done, remain);
    }

    function loadWord(i) {
        idx = i % words.length;
        matcher = createMatcher(words[idx].k);
    }

    function onKey(e) {
        if (!active) return;
        if (e.ctrlKey || e.metaKey || e.altKey) return;

        const key = e.key.toLowerCase();
        if (!/^[a-z0-9-]$/.test(key)) return;

        if (!matcher.input(key)) {
            if (onMiss) onMiss();
            return;
        }

        if (!matcher.done) {
            render();
            return;
        }

        completed += 1;
        loadWord(idx + 1);

        if (onWord) onWord(completed);
        if (active) render();
    }

    document.addEventListener('keydown', onKey);

    return {
        start() { active = true; render(); },
        stop() { active = false; },
        reset() { completed = 0; loadWord(0); render(); },
        render,
        destroy() { active = false; document.removeEventListener('keydown', onKey); },
    };
}

export function countdown(targetMs, onTick, onEnd) {
    const tick = () => {
        const left = targetMs - Date.now();

        if (left <= 0) {
            onEnd();
            return;
        }

        onTick(Math.ceil(left / 1000));
        requestAnimationFrame(tick);
    };

    tick();
}