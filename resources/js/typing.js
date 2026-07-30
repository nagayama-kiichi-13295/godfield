import { createMatcher, canonicalRomaji } from './romaji';

export { canonicalRomaji };

function shuffled(src) {
    const a = src.slice();

    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }

    return a;
}

export function createTyping({
    words,
    altWords = null,
    shuffle = true,
    displayEl,
    readingEl,
    romaEl,
    onWord,
    onMiss,
}) {
    const prep = (src) => (shuffle ? shuffled(src) : src.slice());

    let main = prep(words);
    let alt = altWords && altWords.length ? prep(altWords) : null;

    let usingAlt = false;
    let mainIdx = 0;
    let altIdx = 0;

    let completed = 0;
    let combo = 0;
    let maxCombo = 0;
    let missCount = 0;
    let typedChars = 0;
    let missMap = {};

    let active = false;
    let missedThisWord = false;
    let wordStart = 0;
    let chars = 0;
    let matcher = null;

    const list = () => (usingAlt ? alt : main);
    const cursor = () => (usingAlt ? altIdx : mainIdx);
    const current = () => list()[cursor()];

    function prepareMatcher() {
        const w = current();
        matcher = createMatcher(w ? w.k : 'あ');
        missedThisWord = false;
        chars = 0;
        wordStart = performance.now();
    }

    function advance() {
        if (usingAlt) {
            altIdx += 1;
            if (altIdx >= alt.length) { alt = prep(alt); altIdx = 0; }
        } else {
            mainIdx += 1;
            if (mainIdx >= main.length) { main = prep(main); mainIdx = 0; }
        }

        prepareMatcher();
    }

    function render() {
        const w = current();
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

    function onKey(e) {
        if (!active) return;
        if (e.ctrlKey || e.metaKey || e.altKey) return;

        const key = e.key.toLowerCase();
        if (!/^[a-z0-9-]$/.test(key)) return;

        if (!matcher.input(key)) {
            const spot = matcher.current();

            combo = 0;
            missCount += 1;
            missedThisWord = true;

            if (spot) missMap[spot.kana] = (missMap[spot.kana] || 0) + 1;
            if (onMiss) onMiss({ combo, key, at: spot ? spot.kana : null });

            return;
        }

        chars += 1;
        typedChars += 1;

        if (!matcher.done) {
            render();
            return;
        }

        const seconds = (performance.now() - wordStart) / 1000;
        const finishedWord = current();

        completed += 1;
        if (!missedThisWord) combo += 1;
        if (combo > maxCombo) maxCombo = combo;

        const info = { count: completed, combo, chars, seconds, word: finishedWord };

        advance();

        if (onWord) onWord(info);
        if (active) render();
    }

    document.addEventListener('keydown', onKey);
    prepareMatcher();

    return {
        start() { active = true; wordStart = performance.now(); render(); },
        stop() { active = false; },

        reset() {
            main = prep(words);
            if (altWords && altWords.length) alt = prep(altWords);
            mainIdx = 0;
            altIdx = 0;
            completed = 0;
            combo = 0;
            maxCombo = 0;
            missCount = 0;
            typedChars = 0;
            missMap = {};
            prepareMatcher();
            render();
        },

        useAlt() {
            if (!alt) return;
            usingAlt = true;
            prepareMatcher();
            render();
        },

        useMain() {
            usingAlt = false;
            prepareMatcher();
            render();
        },

        stats() { return { maxCombo, missCount, typedChars, missMap }; },
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