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
        <div class="bars">
            <div class="bar-block" style="--c: {{ $me['color'] }}">
                <div class="bar-head">
                    @include('partials.icon', ['icon' => $me['icon'], 'color' => $me['color'], 'size' => 26])
                    <span class="bar-label">{{ $meName }}（{{ $me['name'] }} Lv.{{ $meLevel }}）</span>
                </div>
                <div class="bar">
                    <div class="bar-fill me" id="hp-me"></div>
                </div>
            </div>
            <div class="bar-block" style="--c: {{ $opp['color'] }}">
                <div class="bar-head">
                    @include('partials.icon', ['icon' => $opp['icon'], 'color' => $opp['color'], 'size' => 26])
                    <span class="bar-label">{{ $oppName }}（{{ $opp['name'] }} Lv.{{ $oppLevel }}）</span>
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
            </div>
        </div>

        <div class="footer">
            <span class="status" id="status">接続中...</span>
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
            document.getElementById('status').textContent = 'JS エラー: ' + e.message;
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
            const OPP_MAX = @json($opp['max_hp']);

            const playerKey = makePlayerKey();
            const token = document.querySelector('meta[name=csrf-token]').content;

            const roma = document.getElementById('roma');
            const comboEl = document.getElementById('combo');
            const dmgEl = document.getElementById('dmg');
            const status = document.getElementById('status');
            const overlay = document.getElementById('overlay');
            const overlayText = document.getElementById('overlay-text');
            const overlaySub = document.getElementById('overlay-sub');
            const startBtn = document.getElementById('start');
            const homeBtn = document.getElementById('home');
            const hpMeEl = document.getElementById('hp-me');
            const hpYouEl = document.getElementById('hp-you');

            let myDamage = 0,
                oppDamage = 0,
                myWords = 0;
            let finished = false;

            homeBtn.style.display = 'none';

            const typing = window.Typing.create({
                words,
                displayEl: document.getElementById('display'),
                readingEl: document.getElementById('reading'),
                romaEl: roma,
                onMiss: () => {
                    roma.classList.add('miss');
                    setTimeout(() => roma.classList.remove('miss'), 120);
                    comboEl.textContent = '';
                },
                onWord: (info) => {
                    const dmg = window.Typing.calcDamage(
                        MY_POWER, info.chars, info.seconds, info.combo
                    );

                    myDamage += dmg;
                    myWords = info.count;

                    comboEl.textContent = info.combo >= 2 ? `${info.combo} COMBO` : '';
                    dmgEl.textContent = `${dmg} ダメージ`;

                    sendProgress(info.combo);
                    checkFinish();
                },
            });

            function updateHp() {
                const hpMe = Math.max(0, MY_MAX - oppDamage);
                const hpOpp = Math.max(0, OPP_MAX - myDamage);
                hpMeEl.style.width = (hpMe / MY_MAX * 100) + '%';
                hpYouEl.style.width = (hpOpp / OPP_MAX * 100) + '%';
                return {
                    hpMe,
                    hpOpp
                };
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
                typing.stop();
                overlay.style.display = 'flex';
                overlayText.textContent = iWon ? '勝ち' : '負け';
                overlaySub.textContent = '結果を集計中...';
                startBtn.style.display = 'none';
                homeBtn.style.display = 'inline-block';
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
                const {
                    hpMe,
                    hpOpp
                } = updateHp();
                if (hpMe > 0 && hpOpp > 0) return;

                const iWon = hpOpp <= 0;
                showResult(iWon);
                iWon ? reportFinish() : loadResult();
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
                    }),
                });
            }

            function beginAt(startAt) {
                finished = false;
                myDamage = 0;
                oppDamage = 0;
                myWords = 0;
                typing.reset();
                updateHp();

                comboEl.textContent = '';
                dmgEl.textContent = '';
                overlay.style.display = 'flex';
                overlaySub.textContent = '';
                startBtn.style.display = 'none';
                homeBtn.style.display = 'none';

                window.Typing.countdown(
                    startAt,
                    (sec) => {
                        overlayText.textContent = sec;
                    },
                    () => {
                        overlay.style.display = 'none';
                        typing.start();
                    }
                );
            }

            startBtn.addEventListener('click', async () => {
                startBtn.blur();
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

            updateHp();

            if (!window.Echo) {
                status.textContent = 'Echo が読み込まれていません';
                return;
            }

            const conn = window.Echo.connector.pusher.connection;
            conn.bind('connected', () => {
                status.textContent = '接続済み';
            });
            conn.bind('disconnected', () => {
                status.textContent = '切断';
            });

            window.Echo.channel(`match.${matchId}`)
                .listen('.match.started', (e) => beginAt(e.startAt))
                .listen('.player.progressed', (e) => {
                    oppDamage = e.damage;
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