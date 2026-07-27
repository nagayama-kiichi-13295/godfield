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
        <div class="bar-block">
            <div class="bar-label">あなた</div>
            <div class="bar"><div class="bar-fill me" id="hp-me"></div></div>
        </div>
        <div class="bar-block">
            <div class="bar-label">相手</div>
            <div class="bar"><div class="bar-fill you" id="hp-you"></div></div>
        </div>
    </div>

    <div class="stage">
        <div class="display" id="display">準備中</div>
        <div class="roma" id="roma"></div>
    </div>

    <div class="footer">
        <button class="start-btn" id="start">スタート</button>
        <span class="status" id="status">接続中...</span>
    </div>

    <div class="overlay" id="overlay">
        <div class="overlay-text" id="overlay-text">スタートを押してください</div>
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
        const playerKey = makePlayerKey();

        const MAX_HP = 100;
        const DAMAGE = 10;

        const display = document.getElementById('display');
        const roma = document.getElementById('roma');
        const status = document.getElementById('status');
        const overlay = document.getElementById('overlay');
        const overlayText = document.getElementById('overlay-text');
        const hpMeEl = document.getElementById('hp-me');
        const hpYouEl = document.getElementById('hp-you');

        let idx = 0;
        let pos = 0;
        let myDone = 0;
        let oppDone = 0;
        let playing = false;
        let finished = false;

        function render() {
            const w = words[idx];
            if (!w) return;
            display.textContent = w.d;
            roma.textContent = '';
            const done = document.createElement('span');
            done.className = 'done';
            done.textContent = w.r.slice(0, pos);
            const rest = document.createElement('span');
            rest.textContent = w.r.slice(pos);
            roma.append(done, rest);
        }

        function updateHp() {
            const hpMe = Math.max(0, MAX_HP - oppDone * DAMAGE);
            const hpYou = Math.max(0, MAX_HP - myDone * DAMAGE);
            hpMeEl.style.width = hpMe + '%';
            hpYouEl.style.width = hpYou + '%';
            return { hpMe, hpYou };
        }

        function checkFinish() {
            if (finished) return;
            const { hpMe, hpYou } = updateHp();
            if (hpYou <= 0 || hpMe <= 0) {
                finished = true;
                playing = false;
                overlay.style.display = 'flex';
                overlayText.textContent = hpYou <= 0 ? '勝ち' : '負け';
            }
        }

        function sendProgress() {
            fetch(`/match/${matchId}/progress`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Socket-Id': window.Echo ? (window.Echo.socketId() ?? '') : '',
                },
                body: JSON.stringify({ player_key: playerKey, word_index: myDone }),
            });
        }

        function beginAt(startAt) {
            finished = false;
            idx = 0; pos = 0; myDone = 0; oppDone = 0;
            updateHp();
            overlay.style.display = 'flex';

            const tick = () => {
                const left = startAt - Date.now();
                if (left <= 0) {
                    overlay.style.display = 'none';
                    playing = true;
                    render();
                    return;
                }
                overlayText.textContent = Math.ceil(left / 1000);
                requestAnimationFrame(tick);
            };
            tick();
        }

        document.addEventListener('keydown', (e) => {
            if (!playing || e.key.length !== 1) return;
            const w = words[idx];
            if (!w) return;

            if (e.key === w.r[pos]) {
                pos++;
                if (pos >= w.r.length) {
                    idx++;
                    pos = 0;
                    myDone++;
                    sendProgress();
                    checkFinish();
                    if (!finished) render();
                } else {
                    render();
                }
            } else {
                roma.classList.add('miss');
                setTimeout(() => roma.classList.remove('miss'), 120);
            }
        });

        document.getElementById('start').addEventListener('click', async () => {
            await fetch(`/match/${matchId}/start`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
            });
        });

        updateHp();

        if (!window.Echo) {
            status.textContent = 'Echo が読み込まれていません';
            return;
        }

        const conn = window.Echo.connector.pusher.connection;
        conn.bind('connected', () => { status.textContent = '接続済み'; });
        conn.bind('disconnected', () => { status.textContent = '切断'; });

        window.Echo.channel(`match.${matchId}`)
            .listen('.match.started', (e) => beginAt(e.startAt))
            .listen('.player.progressed', (e) => {
                oppDone = e.wordIndex;
                checkFinish();
            });
    });
</script>
</body>
</html>