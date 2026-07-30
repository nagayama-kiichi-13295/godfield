<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $conf['label'] }}</title>
    @vite(['resources/css/match.css', 'resources/js/app.js'])
</head>
<body>
<div class="arena">
    <div class="run-head">
        <span class="run-stage" style="color: {{ $conf['color'] }}">{{ $conf['label'] }}</span>
        <span class="run-progress" id="progress"></span>
        <span class="run-timer" id="timer"></span>
    </div>

    @if (!empty($weak))
        <div class="target-line">
            <span class="target-label">重点</span>
            @foreach ($weak as $k)
                <span class="target-kana">{{ $k }}</span>
            @endforeach
        </div>
    @else
        <div class="target-line">
            <span class="target-label">まだ苦手データがありません。通常の単語で練習します</span>
        </div>
    @endif

    <div class="stage">
        <div class="display" id="display">準備中</div>
        <div class="reading" id="reading"></div>
        <div class="roma" id="roma"></div>
        <div class="meter">
            <span class="combo" id="combo"></span>
            <span class="dmg" id="acc"></span>
        </div>
    </div>

    <div class="footer">
        <span class="status">正確率 {{ $conf['accuracy_line'] }}% 以上でボーナス EXP</span>
        <button class="sound-btn" id="sound">♪ ON</button>
    </div>

    <div class="overlay" id="overlay">
        <div class="overlay-inner">
            <div class="overlay-text" id="overlay-text">スタートを押してください</div>
            <div class="overlay-sub" id="overlay-sub"></div>
            <div class="overlay-actions">
                <button class="start-btn" id="start">スタート</button>
                <a class="home-btn" href="/character">キャラ選択へ</a>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    const words = @json($words);
    const weak = @json($weak);
    const runId = @json($runId);
    const LIMIT = @json($conf['time_limit']);
    const token = document.querySelector('meta[name=csrf-token]').content;

    const $ = (id) => document.getElementById(id);
    const roma = $('roma'), comboEl = $('combo'), accEl = $('acc');
    const progressEl = $('progress'), timerEl = $('timer');
    const overlay = $('overlay'), overlayText = $('overlay-text'), overlaySub = $('overlay-sub');
    const startBtn = $('start'), soundBtn = $('sound');

    let running = false, over = false, runStart = 0, rafId = null, deadline = 0;
    let doneWords = 0, weakWords = 0;

    function isWeakWord(kana) {
        return weak.some((k) => k && kana.includes(k));
    }

    const typing = window.Typing.create({
        words,
        displayEl: $('display'),
        readingEl: $('reading'),
        romaEl: roma,
        onMiss: () => {
            roma.classList.add('miss');
            setTimeout(() => roma.classList.remove('miss'), 120);
            comboEl.textContent = '';
            window.FX.SFX.miss();
            window.FX.shake(roma);
            updateAcc();
        },
        onWord: (info) => {
            if (!running) return;

            doneWords += 1;

            if (info.word && isWeakWord(info.word.k)) {
                weakWords += 1;
                window.FX.popup(roma, '弱点 +1', 'heal');
            }

            comboEl.textContent = info.combo >= 2 ? `${info.combo} COMBO` : '';
            progressEl.textContent = `${doneWords} 語`;
            window.FX.SFX.word();
            updateAcc();

            if (doneWords >= words.length) finish();
        },
    });

    function updateAcc() {
        const s = typing.stats();
        const all = s.typedChars + s.missCount;
        accEl.textContent = all > 0 ? `正確率 ${(s.typedChars / all * 100).toFixed(1)}%` : '';
    }

    function watchTimer() {
        const tick = () => {
            if (!running) return;
            const left = (deadline - Date.now()) / 1000;
            if (left <= 0) { timerEl.textContent = '0.0'; finish(); return; }
            timerEl.textContent = left.toFixed(1);
            rafId = requestAnimationFrame(tick);
        };
        tick();
    }

    async function finish() {
        if (over) return;
        over = true;
        running = false;
        typing.stop();
        cancelAnimationFrame(rafId);

        const s = typing.stats();

        overlay.style.display = 'flex';
        startBtn.style.display = 'none';
        overlayText.textContent = '終了';
        overlaySub.textContent = '結果を集計中...';

        try {
            const res = await fetch('/training/finish', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({
                    run_id: runId,
                    words: doneWords,
                    weak_words: weakWords,
                    typed_chars: s.typedChars,
                    miss_count: s.missCount,
                    max_combo: s.maxCombo,
                    duration_ms: runStart ? Date.now() - runStart : 0,
                    miss_map: s.missMap,
                }),
            });
            const d = await res.json();

            if (d.redirect) {
                setTimeout(() => { location.href = d.redirect; }, 700);
                return;
            }

            overlaySub.textContent = '結果の記録に失敗しました';
        } catch (e) {
            overlaySub.textContent = '結果の記録に失敗しました';
        }
    }

    startBtn.addEventListener('click', () => {
        startBtn.blur();
        startBtn.style.display = 'none';
        overlaySub.textContent = '';
        window.FX.initAudio();

        window.Typing.countdown(
            Date.now() + 3000,
            (sec) => {
                if (overlayText.textContent !== String(sec)) window.FX.SFX.countdown();
                overlayText.textContent = sec;
            },
            () => {
                overlay.style.display = 'none';
                window.FX.SFX.go();
                running = true;
                runStart = Date.now();
                deadline = runStart + LIMIT * 1000;
                typing.start();
                watchTimer();
            }
        );
    });

    let soundOn = window.FX.soundEnabled();
    window.FX.setSound(soundOn);
    soundBtn.textContent = soundOn ? '♪ ON' : '♪ OFF';
    soundBtn.classList.toggle('off', !soundOn);

    soundBtn.addEventListener('click', () => {
        soundBtn.blur();
        soundOn = !soundOn;
        window.FX.setSound(soundOn);
        soundBtn.textContent = soundOn ? '♪ ON' : '♪ OFF';
        soundBtn.classList.toggle('off', !soundOn);
        if (soundOn) { window.FX.initAudio(); window.FX.SFX.word(); }
    });

    progressEl.textContent = `0 語 / ${words.length}`;
    timerEl.textContent = LIMIT.toFixed(1);
});
</script>
</body>
</html>