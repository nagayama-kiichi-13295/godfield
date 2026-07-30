<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>タイピングコロシアム</title>
    @vite(['resources/css/match.css', 'resources/js/app.js'])
</head>

<body>
    <div class="arena">
        <div class="run-head">
            <span class="run-stage" style="color: #4CAF50">オンライン対戦</span>
            <span class="run-progress" id="progress"></span>
            <span class="run-timer" id="status">接続中...</span>
        </div>

        <div class="bars">
            <div class="bar-block" style="--c: {{ $me['color'] }}">
                <div class="bar-head">
                    @include('partials.icon', ['icon' => $me['icon'], 'color' => $me['color'], 'size' => 26])
                    <span class="bar-label">{{ $meName }}（{{ $me['name'] }} Lv.{{ $meLevel }}）</span>
                    <span class="hp-num" id="hp-me-num"></span>
                </div>
                <div class="bar">
                    <div class="bar-fill me" id="hp-me"></div>
                </div>
            </div>
            <div class="bar-block" style="--c: {{ $opp['color'] }}">
                <div class="bar-head">
                    @include('partials.icon', ['icon' => $opp['icon'], 'color' => $opp['color'], 'size' => 26])
                    <span class="bar-label">{{ $oppName }}（{{ $opp['name'] }} Lv.{{ $oppLevel }}）</span>
                    <span class="hp-num" id="hp-you-num"></span>
                </div>
                <div class="bar">
                    <div class="bar-fill you" id="hp-you"></div>
                </div>
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
                    <a class="home-btn" id="home" href="/character">キャラ選択へ</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('error', (e) => {
            const el = document.getElementById('overlay-sub');
            if (el) el.textContent = 'JS エラー: ' + e.message;
        });

        function makePlayerKey() {
            if (window.crypto && typeof crypto.randomUUID === 'function') {
                return crypto.randomUUID();
            }
            return 'p-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
        }

        window.addEventListener('DOMContentLoaded', () => {
            const matchId = @json($matchId);
            const words = @json($words);
            const MY_MAX = @json($me['max_hp']);
            const MY_POWER = @json($me['power']);
            const MY_HEAL = @json($me['heal']);
            const OPP_MAX = @json($opp['max_hp']);

            const playerKey = makePlayerKey();
            const token = document.querySelector('meta[name=csrf-token]').content;

            const $ = (id) => document.getElementById(id);
            const roma = $('roma'),
                comboEl = $('combo'),
                dmgEl = $('dmg'),
                healEl = $('heal');
            const progressEl = $('progress'),
                statusEl = $('status');
            const overlay = $('overlay'),
                overlayText = $('overlay-text'),
                overlaySub = $('overlay-sub');
            const startBtn = $('start'),
                homeBtn = $('home'),
                soundBtn = $('sound');
            const hpMeEl = $('hp-me'),
                hpYouEl = $('hp-you');
            const hpMeNum = $('hp-me-num'),
                hpYouNum = $('hp-you-num');

            const HEAL_CAP_RATE = 0.30;

            let myHp = MY_MAX,
                oppHp = OPP_MAX;
            let myWords = 0,
                myDamage = 0,
                healedTotal = 0;
            let playing = false,
                finished = false;

            homeBtn.style.display = 'none';

            const typing = window.Typing.create({
                words,
                shuffle: false,
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
                    if (!playing) return;

                    const dmg = window.Typing.calcDamage(MY_POWER, info.chars, info.seconds, info.combo);

                    myWords = info.count;
                    myDamage += dmg;
                    oppHp = Math.max(0, oppHp - dmg);

                    const crit = dmg >= MY_POWER * 1.6;
                    comboEl.textContent = info.combo >= 2 ? `${info.combo} COMBO` : '';
                    dmgEl.textContent = `${dmg} ダメージ`;
                    progressEl.textContent = `${myWords} 語`;

                    window.FX.SFX.word();
                    window.FX.popup(hpYouEl, `-${dmg}`, crit ? 'crit' : 'dmg');
                    window.FX.shake(hpYouEl, crit);

                    const heal = window.Typing.calcHeal(MY_HEAL, info.combo);
                    const cap = Math.round(MY_MAX * HEAL_CAP_RATE);

                    if (heal > 0 && healedTotal < cap) {
                        const actual = Math.min(heal, cap - healedTotal, MY_MAX - myHp);

                        if (actual > 0) {
                            myHp += actual;
                            healedTotal += actual;
                            healEl.textContent = `+${actual} 回復`;
                            setTimeout(() => {
                                healEl.textContent = '';
                            }, 900);
                            window.FX.SFX.heal();
                            window.FX.popup(hpMeEl, `+${actual}`, 'heal');
                        }
                    }

                    updateHp();
                    sendProgress(info.combo);
                    checkFinish();
                },
            });

            function updateHp() {
                myHp = Math.max(0, Math.min(MY_MAX, myHp));
                oppHp = Math.max(0, oppHp);
                hpMeEl.style.width = (myHp / MY_MAX * 100) + '%';
                hpYouEl.style.width = (oppHp / OPP_MAX * 100) + '%';
                hpMeNum.textContent = `${myHp} / ${MY_MAX}`;
                hpYouNum.textContent = `${oppHp} / ${OPP_MAX}`;
            }

            function sendProgress(combo) {
                fetch(`/match/${matchId}/progress`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Socket-Id': window.Echo ? (window.Echo.socketId() ?? '') : '',
                    },
                    body: JSON.stringify({
                        player_key: playerKey,
                        word_index: myWords,
                        damage: myDamage,
                        combo: combo,
                        hp: myHp,
                        healed: healedTotal,
                    }),
                });
            }

            async function loadResult(tries = 0) {
                try {
                    const res = await fetch(`/match/${matchId}/result`);
                    const d = await res.json();

                    if (!d.ready) {
                        if (tries < 5) setTimeout(() => loadResult(tries + 1), 600);
                        return;
                    }

                    overlayText.textContent = d.won ? '勝ち' : '負け';
                    overlaySub.textContent =
                        `${d.character} EXP +${d.gain}　（Lv.${d.level}　${d.exp}/${d.required}）`;
                } catch (e) {
                    overlaySub.textContent = '結果の取得に失敗しました';
                }
            }

            function showResult(iWon) {
                finished = true;
                playing = false;
                typing.stop();

                overlay.style.display = 'flex';
                overlayText.textContent = iWon ? '勝ち' : '負け';
                overlaySub.textContent = '結果を集計中...';
                startBtn.style.display = 'none';
                homeBtn.style.display = 'inline-block';

                iWon ? window.FX.SFX.defeat() : window.FX.SFX.lose();
                window.FX.flash(iWon ? 'white' : 'red');
            }

            async function reportFinish() {
                try {
                    await fetch(`/match/${matchId}/finish`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                    });
                } finally {
                    loadResult();
                }
            }

            function checkFinish() {
                if (finished) return;
                if (myHp > 0 && oppHp > 0) return;

                const iWon = oppHp <= 0;
                showResult(iWon);
                iWon ? reportFinish() : loadResult();
            }

            function beginAt(startAt) {
                finished = false;
                myHp = MY_MAX;
                oppHp = OPP_MAX;
                myWords = 0;
                myDamage = 0;
                healedTotal = 0;

                typing.reset();
                updateHp();

                comboEl.textContent = '';
                dmgEl.textContent = '';
                healEl.textContent = '';
                progressEl.textContent = '0 語';
                overlay.style.display = 'flex';
                overlaySub.textContent = '';
                startBtn.style.display = 'none';
                homeBtn.style.display = 'none';

                window.Typing.countdown(
                    startAt,
                    (sec) => {
                        if (overlayText.textContent !== String(sec)) window.FX.SFX.countdown();
                        overlayText.textContent = sec;
                    },
                    () => {
                        overlay.style.display = 'none';
                        window.FX.SFX.go();
                        playing = true;
                        typing.start();
                    }
                );
            }

            startBtn.addEventListener('click', async () => {
                startBtn.blur();
                window.FX.initAudio();
                startBtn.disabled = true;

                try {
                    await fetch(`/match/${matchId}/start`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                    });
                } finally {
                    startBtn.disabled = false;
                }
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

            progressEl.textContent = '0 語';
            updateHp();

            if (!window.Echo) {
                statusEl.textContent = 'Echo なし';
                overlaySub.textContent = 'Echo が読み込まれていません';
                return;
            }

            const conn = window.Echo.connector.pusher.connection;
            conn.bind('connected', () => {
                statusEl.textContent = '接続済み';
            });
            conn.bind('disconnected', () => {
                statusEl.textContent = '切断';
            });

            window.Echo.channel(`match.${matchId}`)
                .listen('.match.started', (e) => beginAt(e.startAt))
                .listen('.player.progressed', (e) => {
                    if (finished) return;

                    const before = myHp;
                    myHp = Math.max(0, MY_MAX - e.damage + healedTotal);
                    myHp = Math.min(MY_MAX, myHp);

                    const taken = before - myHp;

                    if (taken > 0) {
                        window.FX.popup(hpMeEl, `-${taken}`, 'take');
                        window.FX.flash('red');
                        window.FX.shake(document.querySelector('.arena'));
                        window.FX.SFX.hit();
                    }

                    if (e.healed > 0) {
                        oppHp = Math.min(OPP_MAX, OPP_MAX - myDamage + e.healed);
                    }

                    updateHp();
                    checkFinish();
                })
                .listen('.match.finished', () => {
                    if (!finished) showResult(false);
                    loadResult();
                });
        });
    </script>
</body>

</html>