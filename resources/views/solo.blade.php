<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $stageConf['label'] }}</title>
    @vite(['resources/css/match.css', 'resources/js/app.js'])
</head>

<body>
    <div class="arena">
        <div class="run-head">
            <span class="run-stage" style="color: {{ $stageConf['color'] }}">{{ $stageConf['label'] }}</span>
            <span class="run-progress" id="progress"></span>
            <span class="run-timer" id="timer"></span>
        </div>

        <div class="bars">
            <div class="bar-block" style="--c: {{ $me['color'] }}">
                <div class="bar-head">
                    @include('partials.icon', ['icon' => $me['icon'], 'color' => $me['color'], 'size' => 26])
                    <span class="bar-label">{{ $player->name }}（{{ $me['name'] }} Lv.{{ $level }}）</span>
                    <span class="hp-num" id="hp-me-num"></span>
                </div>
                <div class="bar">
                    <div class="bar-fill me" id="hp-me"></div>
                </div>
            </div>
            <div class="bar-block" id="enemy-block" style="--c: {{ $stageConf['color'] }}">
                <div class="bar-head">
                    <span id="enemy-icon">@include('partials.icon', ['icon' => 'cpu', 'color' => $stageConf['color'], 'size' => 26])</span>
                    <span class="bar-label" id="enemy-name">-</span>
                    <span class="hp-num" id="hp-you-num"></span>
                </div>
                <div class="bar">
                    <div class="bar-fill you" id="hp-you"></div>
                </div>
            </div>
        </div>

        <div class="telegraph" id="telegraph">
            <div class="telegraph-text" id="telegraph-text"></div>
            <div class="telegraph-bar">
                <div class="telegraph-fill" id="telegraph-fill"></div>
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
        window.addEventListener('error', (e) => {
            const el = document.getElementById('overlay-sub');
            if (el) el.textContent = 'JS エラー: ' + e.message;
        });

        window.addEventListener('DOMContentLoaded', () => {
            const words = @json($words);
            const defenseWords = @json($defenseWords);
            const enemies = @json($stageConf['enemies']);
            const boss = @json($boss);
            const healBetween = @json($stageConf['heal_between']);
            const runId = @json($runId);
            const me = @json($me);
            const token = document.querySelector('meta[name=csrf-token]').content;

            const $ = (id) => document.getElementById(id);
            const roma = $('roma');
            const progressEl = $('progress'),
                timerEl = $('timer');
            const enemyNameEl = $('enemy-name'),
                enemyBlock = $('enemy-block');
            const overlay = $('overlay'),
                overlayText = $('overlay-text'),
                overlaySub = $('overlay-sub');
            const startBtn = $('start'),
                soundBtn = $('sound');
            const telegraph = $('telegraph'),
                telegraphText = $('telegraph-text'),
                telegraphFill = $('telegraph-fill');

            const total = enemies.length + (boss ? 1 : 0);

            let ei = 0,
                isBoss = false;
            let deadline = 0,
                rafId = null,
                chargeRaf = null,
                chargeTimer = null;
            let runStart = 0,
                over = false;
            let charging = false,
                defNeed = 0,
                defDone = 0,
                chargeEnd = 0,
                chargeSpan = 1;

            const typing = window.Typing.create({
                words,
                altWords: defenseWords,
                displayEl: $('display'),
                readingEl: $('reading'),
                romaEl: roma,
                onMiss: () => {
                    roma.classList.add('miss');
                    setTimeout(() => roma.classList.remove('miss'), 120);
                    $('combo').textContent = '';
                    window.FX.SFX.miss();
                    window.FX.shake(roma);
                },
                onWord: (info) => battle.handleWord(info),
            });

            const battle = window.Battle.create({
                typing,
                me,
                healCapRate: 0.25,
                ui: {
                    hpMeBar: $('hp-me'),
                    hpYouBar: $('hp-you'),
                    hpMeNum: $('hp-me-num'),
                    hpYouNum: $('hp-you-num'),
                    combo: $('combo'),
                    dmg: $('dmg'),
                    heal: $('heal'),
                },
                onWord: (info, ctx) => {
                    if (!ctx.suspended) return;

                    defDone += 1;
                    telegraphText.textContent = `防御 ${defDone} / ${defNeed}`;
                    window.FX.SFX.guard();

                    if (defDone >= defNeed) resolveCharge(true);
                },
                onEnemyDown: () => defeat(),
                onPlayerDown: () => finish(),
            });

            function stopTimers() {
                cancelAnimationFrame(rafId);
                cancelAnimationFrame(chargeRaf);
                clearTimeout(chargeTimer);
            }

            function phase() {
                if (!isBoss) return null;
                const ratio = battle.enemyHp / boss.hp;
                for (const p of boss.phases) {
                    if (ratio > p.until) return p;
                }
                return boss.phases[boss.phases.length - 1];
            }

            function scheduleCharge() {
                if (!isBoss) return;

                chargeTimer = setTimeout(() => {
                    if (over || !battle.running) return;
                    beginCharge();
                }, phase().charge_every * 1000);
            }

            function beginCharge() {
                const p = phase();

                charging = true;
                defNeed = p.charge_words;
                defDone = 0;
                chargeSpan = p.charge_time * 1000;
                chargeEnd = Date.now() + chargeSpan;

                battle.suspend();
                typing.useAlt();

                telegraph.classList.add('on');
                telegraphText.textContent = `${boss.name} が力を溜めている！ 防御 0 / ${defNeed}`;
                window.FX.SFX.charge();
                window.FX.flash('red');

                const tick = () => {
                    if (over || !charging) return;
                    const left = chargeEnd - Date.now();
                    if (left <= 0) {
                        resolveCharge(false);
                        return;
                    }
                    telegraphFill.style.width = (left / chargeSpan * 100) + '%';
                    chargeRaf = requestAnimationFrame(tick);
                };
                tick();
            }

            function resolveCharge(defended) {
                charging = false;
                cancelAnimationFrame(chargeRaf);
                telegraph.classList.remove('on');

                typing.useMain();
                battle.resume();

                if (defended) {
                    $('heal').textContent = '防御成功！';
                    setTimeout(() => {
                        $('heal').textContent = '';
                    }, 1000);
                    window.FX.flash('blue');
                    window.FX.SFX.guard();
                } else if (battle.takeDamage(phase().charge_power, true)) {
                    battle.stop();
                    finish();
                    return;
                }

                scheduleCharge();
            }

            function watchTimer() {
                const tick = () => {
                    if (over || !battle.running) return;
                    const left = (deadline - Date.now()) / 1000;
                    if (left <= 0) {
                        timerEl.textContent = '0.0';
                        battle.stop();
                        finish();
                        return;
                    }
                    timerEl.textContent = left.toFixed(1);
                    rafId = requestAnimationFrame(tick);
                };
                tick();
            }

            function startEnemy(i) {
                ei = i;
                isBoss = !!(boss && i === enemies.length);

                const e = isBoss ? boss : enemies[i];

                enemyNameEl.textContent = e.name;
                progressEl.textContent = isBoss ? 'BOSS' : `${i + 1} / ${total}`;
                enemyBlock.classList.toggle('boss', isBoss);
                if (isBoss) enemyBlock.style.setProperty('--c', boss.color);

                deadline = Date.now() + e.limit * 1000;

                battle.setEnemy(isBoss ? {
                    ...boss,
                    power: boss.phases[0].power,
                    interval: boss.phases[0].interval
                } : e);

                typing.useMain();
                battle.start();

                stopTimers();
                watchTimer();
                scheduleCharge();
            }

            async function defeat() {
                charging = false;
                telegraph.classList.remove('on');
                stopTimers();

                window.FX.SFX.defeat();
                window.FX.flash('white');

                try {
                    const res = await fetch('/solo/defeat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            run_id: runId,
                            index: ei
                        }),
                    });
                    const d = await res.json();

                    if (d.leveled) {
                        overlaySub.textContent = `レベルアップ！ Lv.${d.level}`;
                        window.FX.SFX.levelup();
                    }
                } catch (e) {
                    // 記録に失敗しても進行は止めない
                }

                const last = ei + 1 >= total;

                overlay.style.display = 'flex';
                overlayText.textContent = last ? 'クリア！' : `${isBoss ? boss.name : enemies[ei].name} 撃破`;
                startBtn.style.display = 'none';

                if (last) {
                    finish();
                    return;
                }

                const recover = Math.round(me.max_hp * healBetween);
                battle.hp = battle.hp + recover;

                const nextIsBoss = !!(boss && ei + 1 === enemies.length);
                overlaySub.textContent = nextIsBoss ? `HP +${recover}　次はボスだ` : `HP +${recover} 回復`;

                setTimeout(() => {
                    overlay.style.display = 'none';
                    overlaySub.textContent = '';
                    startEnemy(ei + 1);
                }, nextIsBoss ? 2400 : 1600);
            }

            async function finish() {
                if (over) return;
                over = true;

                charging = false;
                telegraph.classList.remove('on');
                battle.stop();
                stopTimers();

                const failed = battle.hp <= 0 || Date.now() >= deadline;
                if (failed) window.FX.SFX.lose();

                const s = typing.stats();

                overlay.style.display = 'flex';
                startBtn.style.display = 'none';
                overlayText.textContent = failed ? `${ei + 1} 体目で力尽きた` : 'クリア！';
                overlaySub.textContent = '結果を集計中...';

                try {
                    const res = await fetch('/solo/finish', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            run_id: runId,
                            max_combo: s.maxCombo,
                            typed_chars: s.typedChars,
                            miss_count: s.missCount,
                            duration_ms: runStart ? Date.now() - runStart : 0,
                            miss_map: s.missMap,
                        }),
                    });
                    const d = await res.json();

                    if (d.redirect) {
                        setTimeout(() => {
                            location.href = d.redirect;
                        }, 900);
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
                if (soundOn) {
                    window.FX.initAudio();
                    window.FX.SFX.word();
                }
            });

            progressEl.textContent = `1 / ${total}`;
            enemyNameEl.textContent = enemies[0].name;
            battle.setEnemy(enemies[0]);
            timerEl.textContent = enemies[0].limit.toFixed(1);
        });
    </script>
</body>

</html>