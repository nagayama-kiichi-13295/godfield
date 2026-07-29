import { createMatcher, canonicalRomaji } from './romaji';

export { canonicalRomaji };

export function createTyping({ words, displayEl, readingEl, romaEl, onWord, onMiss }) {
    let list = words;
    let idx = 0;
    let completed = 0;
    let combo = 0;
    let maxCombo = 0;
    let missCount = 0;
    let typedChars = 0;
    let active = false;
    let matcher = createMatcher(list[0].k);
    let missedThisWord = false;
    let wordStart = 0;
    let chars = 0;

    function render() {
        const w = list[idx];
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
        idx = i % list.length;
        matcher = createMatcher(list[idx].k);
        missedThisWord = false;
        chars = 0;
        wordStart = performance.now();
    }

    function onKey(e) {
        if (!active) return;
        if (e.ctrlKey || e.metaKey || e.altKey) return;

        const key = e.key.toLowerCase();
        if (!/^[a-z0-9-]$/.test(key)) return;

        if (!matcher.input(key)) {
            combo = 0;
            missCount += 1;
            missedThisWord = true;
            if (onMiss) onMiss({ combo, key });
            return;
        }

        chars += 1;
        typedChars += 1;

        if (!matcher.done) {
            render();
            return;
        }

        const seconds = (performance.now() - wordStart) / 1000;

        completed += 1;
        if (!missedThisWord) combo += 1;
        if (combo > maxCombo) maxCombo = combo;

        const info = { count: completed, combo, chars, seconds };

        loadWord(idx + 1);

        if (onWord) onWord(info);
        if (active) render();
    }

    document.addEventListener('keydown', onKey);

    return {
        start() { active = true; wordStart = performance.now(); render(); },
        stop() { active = false; },
        reset() {
            completed = 0; combo = 0; maxCombo = 0;
            missCount = 0; typedChars = 0;
            loadWord(0); render();
        },
        setWords(next) { list = next; loadWord(0); render(); },
        stats() { return { maxCombo, missCount, typedChars }; },
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