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
            const MY_MAX = @json($me['max_hp']);
            const MY_POWER = @json($me['power']);
            const MY_HEAL = @json($me['heal']);
            const token = document.querySelector('meta[name=csrf-token]').content;

            const $ = (id) => document.getElementById(id);
            const roma = $('roma'),
                comboEl = $('combo'),
                dmgEl = $('dmg'),
                healEl = $('heal');
            const progressEl = $('progress'),
                timerEl = $('timer'),
                enemyNameEl = $('enemy-name');
            const enemyBlock = $('enemy-block');
            const overlay = $('overlay'),
                overlayText = $('overlay-text'),
                overlaySub = $('overlay-sub');
            const startBtn = $('start'),
                soundBtn = $('sound');
            const hpMeEl = $('hp-me'),
                hpYouEl = $('hp-you');
            const hpMeNum = $('hp-me-num'),
                hpYouNum = $('hp-you-num');
            const telegraph = $('telegraph'),
                telegraphText = $('telegraph-text'),
                telegraphFill = $('telegraph-fill');

            const HEAL_CAP_RATE = 0.25;
            const total = enemies.length + (boss ? 1 : 0);

            let ei = 0,
                myHp = MY_MAX,
                enemyHp = 0,
                enemyMax = 1;
            let healedThisEnemy = 0,
                healCap = 0;
            let deadline = 0,
                attackTimer = null,
                chargeTimer = null,
                rafId = null,
                chargeRaf = null;
            let running = false,
                over = false,
                isBoss = false;
            let charging = false,
                defNeed = 0,
                defDone = 0,
                chargeEnd = 0,
                chargeSpan = 1;

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

                    if (charging) {
                        defDone += 1;
                        telegraphText.textContent = `防御 ${defDone} / ${defNeed}`;
                        window.FX.SFX.guard();
                        if (defDone >= defNeed) resolveCharge(true);
                        return;
                    }

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
                            setTimeout(() => {
                                healEl.textContent = '';
                            }, 900);
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

            function hitMe(power, big = false) {
                myHp -= power;
                updateHp();
                hpMeEl.classList.add('hit');
                setTimeout(() => hpMeEl.classList.remove('hit'), 150);

                window.FX.popup(hpMeEl, `-${power}`, 'take');
                window.FX.flash('red');
                window.FX.shake(document.querySelector('.arena'), big);
                big ? window.FX.SFX.bigHit() : window.FX.SFX.hit();

                return myHp <= 0;
            }

            function clearTimers() {
                clearTimeout(attackTimer);
                clearTimeout(chargeTimer);
                cancelAnimationFrame(rafId);
                cancelAnimationFrame(chargeRaf);
            }

            function phase() {
                if (!isBoss) return null;
                const ratio = enemyHp / enemyMax;
                for (const p of boss.phases) {
                    if (ratio > p.until) return p;
                }
                return boss.phases[boss.phases.length - 1];
            }

            function scheduleAttack() {
                const interval = isBoss ? phase().interval : enemies[ei].interval;
                const power = isBoss ? phase().power : enemies[ei].power;

                attackTimer = setTimeout(() => {
                    if (!running) return;
                    if (!charging && hitMe(power)) {
                        finish();
                        return;
                    }
                    scheduleAttack();
                }, interval * 1000);
            }

            function scheduleCharge() {
                if (!isBoss) return;
                chargeTimer = setTimeout(() => {
                    if (!running || over) return;
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

                telegraph.classList.add('on');
                window.FX.SFX.charge();
                window.FX.flash('red');
                telegraphText.textContent = `${boss.name} が力を溜めている！ 防御 0 / ${defNeed}`;
                typing.setWords(defenseWords);

                const tick = () => {
                    if (!running || !charging) return;
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
                typing.setWords(words);

                if (defended) {
                    healEl.textContent = '防御成功！';
                    setTimeout(() => {
                        healEl.textContent = '';
                    }, 1000);
                    window.FX.flash('blue');
                    window.FX.SFX.guard();
                } else if (hitMe(phase().charge_power, true)) {
                    finish();
                    return;
                }

                scheduleCharge();
            }

            function watchTimer() {
                const tick = () => {
                    if (!running) return;
                    const left = (deadline - Date.now()) / 1000;
                    if (left <= 0) {
                        timerEl.textContent = '0.0';
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

                enemyMax = e.hp;
                enemyHp = e.hp;
                healedThisEnemy = 0;
                healCap = Math.round(MY_MAX * HEAL_CAP_RATE);
                deadline = Date.now() + e.limit * 1000;

                enemyNameEl.textContent = e.name;
                progressEl.textContent = isBoss ? 'BOSS' : `${i + 1} / ${total}`;
                enemyBlock.classList.toggle('boss', isBoss);
                if (isBoss) enemyBlock.style.setProperty('--c', boss.color);

                updateHp();
                running = true;
                typing.setWords(words);
                typing.start();
                scheduleAttack();
                scheduleCharge();
                watchTimer();
            }

            async function defeat() {
                running = false;
                charging = false;
                telegraph.classList.remove('on');
                typing.stop();
                clearTimers();

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

                const recover = Math.round(MY_MAX * healBetween);
                myHp = Math.min(MY_MAX, myHp + recover);
                updateHp();

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
                running = false;
                charging = false;
                telegraph.classList.remove('on');
                typing.stop();
                clearTimers();

                const failed = myHp <= 0 || Date.now() >= deadline;
                if (failed) window.FX.SFX.lose();

                overlay.style.display = 'flex';
                startBtn.style.display = 'none';
                overlaySub.textContent = '結果を集計中...';

                if (d.drop) {
                    overlaySub.textContent += `　【${d.drop} を入手！】`;
                    window.FX.SFX.levelup();
                }

                try {
                    const res = await fetch('/solo/finish', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            run_id: runId
                        }),
                    });
                    const d = await res.json();

                    overlayText.textContent = d.cleared ? 'クリア！' : `${ei + 1} 体目で力尽きた`;
                    overlaySub.textContent =
                        `${d.character}　合計 EXP +${d.total}` +
                        (d.bonus ? `（クリアボーナス +${d.bonus}）` : '') +
                        `　Lv.${d.level}　${d.exp}/${d.required}`;
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
            enemyMax = enemies[0].hp;
            enemyHp = enemies[0].hp;
            updateHp();
        });
    </script>
</body>

</html>