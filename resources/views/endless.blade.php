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

    <div class="target-line">
        <span class="target-label">
            @if ($conf['mode'] === 'depth')
                自己ベスト {{ $best }} 階
            @else
                自己ベスト {{ $best }} 体（制限 {{ $conf['total_time'] }} 秒）
            @endif
        </span>
        <span class="target-kana" id="depth-badge" style="margin-left:auto"></span>
    </div>

    <div class="bars">
        <div class="bar-block" style="--c: {{ $me['color'] }}">
            <div class="bar-head">
                @include('partials.icon', ['icon' => $me['icon'], 'color' => $me['color'], 'size' => 26])
                <span class="bar-label">{{ $player->name }}（{{ $me['name'] }} Lv.{{ $level }}）</span>
                <span class="hp-num" id="hp-me-num"></span>
            </div>
            <div class="bar"><div class="bar-fill me" id="hp-me"></div></div>
        </div>
        <div class="bar-block" id="enemy-block" style="--c: {{ $conf['color'] }}">
            <div class="bar-head">
                @include('partials.icon', ['icon' => 'cpu', 'color' => $conf['color'], 'size' => 26])
                <span class="bar-label" id="enemy-name">-</span>
                <span class="hp-num" id="hp-you-num"></span>
            </div>
            <div class="bar"><div class="bar-fill you" id="hp-you"></div></div>
        </div>
    </div>

    <div class="stage">
        <div class="display" id="display">準備中</div>
        <div class="reading" id="reading"></div>
        <div class="roma" id="roma"></div>
        <div class="meter">
            <span class="combo" id="combo"></span>
            <span class="dmg" id="dmg"></span>
            <span class="heal" id="heal"></span>
        </div>
    </div>

    <div class="footer">
        <span class="status">攻撃 {{ $me['power'] }} ／ HP {{ $me['max_hp'] }} ／ 知力 {{ $me['int'] }}</span>
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
    const enemies = @json($enemies);
    const conf = @json($conf);
    const runId = @json($runId);
    const MY_MAX = @json($me['max_hp']);
    const MY_POWER = @json($me['power']);
    const MY_HEAL = @json($me['heal']);
    const token = document.querySelector('meta[name=csrf-token]').content;

    const $ = (id) => document.getElementById(id);
    const roma = $('roma'), comboEl = $('combo'), dmgEl = $('dmg'), healEl = $('heal');
    const progressEl = $('progress'), timerEl = $('timer'), depthBadge = $('depth-badge');
    const enemyNameEl = $('enemy-name'), enemyBlock = $('enemy-block');
    const overlay = $('overlay'), overlayText = $('overlay-text'), overlaySub = $('overlay-sub');
    const startBtn = $('start'), soundBtn = $('sound');
    const hpMeEl = $('hp-me'), hpYouEl = $('hp-you');
    const hpMeNum = $('hp-me-num'), hpYouNum = $('hp-you-num');

    const TIMED = conf.mode === 'timed';
    const HEAL_CAP_RATE = 0.25;

    let ei = 0, myHp = MY_MAX, enemyHp = 0, enemyMax = 1, cur = null;
    let healedThisEnemy = 0, healCap = 0;
    let deadline = 0, attackTimer = null, rafId = null;
    let running = false, over = false, runStart = 0, defeated = 0;

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
        },
        onWord: (info) => {
            if (!running) return;

            const dmg = window.Typing.calcDamage(MY_POWER, info.chars, info.seconds, info.combo);
            enemyHp -= dmg;

            comboEl.textContent = info.combo >= 2 ? `${info.combo} COMBO` : '';
            dmgEl.textContent = `${dmg} ダメージ`;

            const crit = dmg >= MY_POWER * 1.6;
            window.FX.SFX.word();
            window.FX.popup(hpYouEl, `-${dmg}`, crit ? 'crit' : 'dmg');
            window.FX.shake(hpYouEl, crit);

            const heal = window.Typing.calcHeal(MY_HEAL, info.combo);

            if (heal > 0 && healedThisEnemy < healCap) {
                const actual = Math.min(heal, healCap - healedThisEnemy, MY_MAX - myHp);
                if (actual > 0) {
                    myHp += actual;
                    healedThisEnemy += actual;
                    healEl.textContent = `+${actual} 回復`;
                    setTimeout(() => { healEl.textContent = ''; }, 900);
                    window.FX.SFX.heal();
                    window.FX.popup(hpMeEl, `+${actual}`, 'heal');
                }
            }

            updateHp();
            if (enemyHp <= 0) defeat();
        },
    });

    function updateHp() {
        myHp = Math.max(0, Math.min(MY_MAX, myHp));
        const eh = Math.max(0, enemyHp);
        hpMeEl.style.width = (myHp / MY_MAX * 100) + '%';
        hpYouEl.style.width = (eh / enemyMax * 100) + '%';
        hpMeNum.textContent = `${myHp} / ${MY_MAX}`;
        hpYouNum.textContent = `${eh} / ${enemyMax}`;
    }

    function hitMe(power) {
        myHp -= power;
        updateHp();
        hpMeEl.classList.add('hit');
        setTimeout(() => hpMeEl.classList.remove('hit'), 150);
        window.FX.popup(hpMeEl, `-${power}`, 'take');
        window.FX.flash('red');
        window.FX.shake(document.querySelector('.arena'));
        window.FX.SFX.hit();
        return myHp <= 0;
    }

    function clearTimers() {
        clearTimeout(attackTimer);
        cancelAnimationFrame(rafId);
    }

    function scheduleAttack() {
        attackTimer = setTimeout(() => {
            if (!running) return;
            if (hitMe(cur.power)) { finish(); return; }
            scheduleAttack();
        }, cur.interval * 1000);
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

    function startEnemy(i) {
        ei = i;
        cur = enemies[i];

        if (!cur) { finish(); return; }

        enemyMax = cur.hp;
        enemyHp = cur.hp;
        healedThisEnemy = 0;
        healCap = Math.round(MY_MAX * HEAL_CAP_RATE);

        enemyNameEl.textContent = cur.name;
        enemyBlock.classList.toggle('boss', !!cur.is_boss);

        if (TIMED) {
            progressEl.textContent = `撃破 ${defeated}`;
            depthBadge.textContent = `${i + 1} 体目`;
        } else {
            progressEl.textContent = `${i + 1} 階`;
            depthBadge.textContent = cur.is_boss ? '階層主' : '';
            deadline = Date.now() + cur.limit * 1000;
        }

        updateHp();
        typing.useMain();
        running = true;
        clearTimeout(attackTimer);
        scheduleAttack();
        if (!TIMED) { cancelAnimationFrame(rafId); watchTimer(); }
    }

    function defeat() {
        defeated += 1;
        window.FX.SFX.defeat();
        window.FX.flash('white');

        const recover = Math.round(MY_MAX * (TIMED ? conf.heal_per_kill : conf.heal_between));
        myHp = Math.min(MY_MAX, myHp + recover);
        updateHp();
        window.FX.popup(hpMeEl, `+${recover}`, 'heal');

        if (TIMED) {
            progressEl.textContent = `撃破 ${defeated}`;
            startEnemy(ei + 1);
            return;
        }

        running = false;
        clearTimers();

        overlay.style.display = 'flex';
        overlayText.textContent = `${ei + 1} 階 突破`;
        overlaySub.textContent = `HP +${recover}　次は ${ei + 2} 階`;
        startBtn.style.display = 'none';

        setTimeout(() => {
            overlay.style.display = 'none';
            overlaySub.textContent = '';
            startEnemy(ei + 1);
        }, 1400);
    }

    async function finish() {
        if (over) return;
        over = true;
        running = false;
        typing.stop();
        clearTimers();
        window.FX.SFX.lose();

        const s = typing.stats();

        overlay.style.display = 'flex';
        startBtn.style.display = 'none';
        overlayText.textContent = TIMED ? '時間切れ' : `${ei + 1} 階で力尽きた`;
        overlaySub.textContent = '結果を集計中...';

        try {
            const res = await fetch('/endless/finish', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({
                    run_id: runId,
                    defeated,
                    max_combo: s.maxCombo,
                    typed_chars: s.typedChars,
                    miss_count: s.missCount,
                    duration_ms: runStart ? Date.now() - runStart : 0,
                    miss_map: s.missMap,
                }),
            });
            const d = await res.json();

            if (d.redirect) {
                setTimeout(() => { location.href = d.redirect; }, 800);
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
                runStart = Date.now();

                if (TIMED) {
                    deadline = runStart + conf.total_time * 1000;
                    watchTimer();
                }

                typing.start();
                startEnemy(0);
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

    enemyNameEl.textContent = enemies[0].name;
    enemyMax = enemies[0].hp;
    enemyHp = enemies[0].hp;
    progressEl.textContent = TIMED ? '撃破 0' : '1 階';
    timerEl.textContent = TIMED ? conf.total_time.toFixed(1) : enemies[0].limit.toFixed(1);
    updateHp();
});
</script>
</body>
</html>