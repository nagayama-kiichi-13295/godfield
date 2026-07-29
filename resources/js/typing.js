export function createTyping({ words, displayEl, romaEl, onWord, onMiss }) {
    let idx = 0;
    let pos = 0;
    let completed = 0;
    let active = false;

    function render() {
        const w = words[idx];
        if (!w) return;

        displayEl.textContent = w.d;
        romaEl.textContent = '';

        const done = document.createElement('span');
        done.className = 'done';
        done.textContent = w.r.slice(0, pos);

        const rest = document.createElement('span');
        rest.textContent = w.r.slice(pos);

        romaEl.append(done, rest);
    }

    function onKey(e) {
        if (!active) return;
        if (e.key.length !== 1 || e.ctrlKey || e.metaKey || e.altKey) return;

        const w = words[idx];
        if (!w) return;

        if (e.key !== w.r[pos]) {
            if (onMiss) onMiss();
            return;
        }

        pos++;

        if (pos < w.r.length) {
            render();
            return;
        }

        idx = (idx + 1) % words.length;
        pos = 0;
        completed++;

        if (onWord) onWord(completed);
        if (active) render();
    }

    document.addEventListener('keydown', onKey);

    return {
        start() { active = true; render(); },
        stop() { active = false; },
        reset() { idx = 0; pos = 0; completed = 0; },
        render,
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