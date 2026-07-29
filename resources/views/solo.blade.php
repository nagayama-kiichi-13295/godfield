<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ひとりで練習</title>
    @vite(['resources/css/match.css'])
</head>

<body>
    <div class="arena">
        <div class="bars">
            <div class="bar-block">
                <div class="bar-label">{{ $player->name }}（{{ $characterName }} Lv.{{ $level }}）</div>
                <div class="bar">
                    <div class="bar-fill me" id="hp-me"></div>
                </div>
            </div>
            <div class="bar-block">
                <div class="bar-label">CPU（{{ $cpu['label'] }}）</div>
                <div class="bar">
                    <div class="bar-fill you" id="hp-you"></div>
                </div>
            </div>
        </div>

        <div class="stage">
            <div class="display" id="display">準備中</div>
            <div class="roma" id="roma"></div>
        </div>

        <div class="footer">
            <span class="status">攻撃 {{ $power }} ／ HP {{ $maxHp }}</span>
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
            const difficulty = @json($difficulty);
            const MY_MAX = @json($maxHp);
            const MY_POWER = @json($power);
            const CPU_MAX = @json($cpu['hp']);
            const CPU_POWER = @json($cpu['power']);
            const CPU_SPEED = @json($cpu['speed']);
            const token = document.querySelector('meta[name=csrf-token]').content;

            const display = document.getElementById('display');
            const roma = document.getElementById('roma');
            const overlay = document.getElementById('overlay');
            const overlayText = document.getElementById('overlay-text');
            const overlaySub = document.getElementById('overlay-sub');
            const startBtn = document.getElementById('start');
            const hpMeEl = document.getElementById('hp-me');
            const hpYouEl = document.getElementById('hp-you');

            let idx = 0,
                pos = 0,
                myHp = MY_MAX,
                cpuHp = CPU_MAX;
            let playing = false,
                finished = false,
                cpuTimer = null,
                cpuIdx = 0;

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
                hpMeEl.style.width = Math.max(0, myHp / MY_MAX * 100) + '%';
                hpYouEl.style.width = Math.max(0, cpuHp / CPU_MAX * 100) + '%';
            }

            async function finish(won) {
                finished = true;
                playing = false;
                clearTimeout(cpuTimer);
                overlay.style.display = 'flex';
                overlayText.textContent = won ? '勝ち' : '負け';
                startBtn.style.display = 'none';
                overlaySub.textContent = '結果を記録中...';

                try {
                    const res = await fetch('/solo/result', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            won: won,
                            difficulty: difficulty
                        }),
                    });
                    const d = await res.json();
                    overlaySub.textContent = d.leveled ?
                        `${d.character} EXP +${d.gain}　レベルアップ！ Lv.${d.level}` :
                        `${d.character} EXP +${d.gain}　（Lv.${d.level}　${d.exp}/${d.required}）`;
                } catch (e) {
                    overlaySub.textContent = '結果の記録に失敗しました';
                }
            }

            function scheduleCpu() {
                const w = words[cpuIdx % words.length];
                const base = (w.r.length / CPU_SPEED) * 1000;
                const wait = base * (0.8 + Math.random() * 0.4);

                cpuTimer = setTimeout(() => {
                    if (!playing) return;
                    cpuIdx++;
                    myHp -= CPU_POWER;
                    updateHp();
                    if (myHp <= 0) {
                        finish(false);
                        return;
                    }
                    scheduleCpu();
                }, wait);
            }

            function begin() {
                idx = 0;
                pos = 0;
                myHp = MY_MAX;
                cpuHp = CPU_MAX;
                cpuIdx = 0;
                finished = false;
                updateHp();
                overlaySub.textContent = '';
                startBtn.style.display = 'none';

                let count = 3;
                overlayText.textContent = count;

                const t = setInterval(() => {
                    count--;
                    if (count <= 0) {
                        clearInterval(t);
                        overlay.style.display = 'none';
                        playing = true;
                        render();
                        scheduleCpu();
                        return;
                    }
                    overlayText.textContent = count;
                }, 1000);
            }

            document.addEventListener('keydown', (e) => {
                if (!playing || e.key.length !== 1) return;
                const w = words[idx];
                if (!w) return;

                if (e.key === w.r[pos]) {
                    pos++;
                    if (pos >= w.r.length) {
                        idx = (idx + 1) % words.length;
                        pos = 0;
                        cpuHp -= MY_POWER;
                        updateHp();
                        if (cpuHp <= 0) {
                            finish(true);
                            return;
                        }
                        render();
                    } else {
                        render();
                    }
                } else {
                    roma.classList.add('miss');
                    setTimeout(() => roma.classList.remove('miss'), 120);
                }
            });

            startBtn.addEventListener('click', () => {
                startBtn.blur();
                begin();
            });

            updateHp();
        });
    </script>
</body>

</html>