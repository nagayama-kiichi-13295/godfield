<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>match {{ $matchId }}</title>
    @vite(['resources/js/app.js'])
</head>

<body>
    <p>自分の進捗: <span id="mine">0</span></p>
    <p>相手の進捗: <span id="theirs">0</span></p>
    <p>自分の接続: <span id="status">未接続</span></p>
    <p>相手: <span id="peer">未確認</span></p>
    <p style="color:red" id="error"></p>
    <button id="advance">1語打ち終わった（仮）</button>

    <script>
        window.addEventListener('error', (e) => {
            document.getElementById('error').textContent = 'JS エラー: ' + e.message;
        });

        function makePlayerKey() {
            if (window.crypto && typeof crypto.randomUUID === 'function') {
                return crypto.randomUUID();
            }
            return 'p-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
        }

        window.addEventListener('DOMContentLoaded', () => {
            const status = document.getElementById('status');

            if (!window.Echo) {
                status.textContent = 'Echo が読み込まれていません';
                return;
            }

            const matchId = @json($matchId);
            const playerKey = makePlayerKey();
            let wordIndex = 0;

            const conn = window.Echo.connector.pusher.connection;
            conn.bind('connected', () => {
                status.textContent = '接続済み';
            });
            conn.bind('disconnected', () => {
                status.textContent = '切断';
            });
            conn.bind('error', (err) => {
                document.getElementById('error').textContent = '接続エラー: ' + JSON.stringify(err);
            });

            window.Echo.channel(`match.${matchId}`)
                .listen('.player.progressed', (e) => {
                    document.getElementById('peer').textContent = '受信あり';
                    document.getElementById('theirs').textContent = e.wordIndex;
                });

            document.getElementById('advance').addEventListener('click', async () => {
                wordIndex++;
                document.getElementById('mine').textContent = wordIndex;

                await fetch(`/match/${matchId}/progress`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'X-Socket-Id': window.Echo.socketId() ?? '',
                    },
                    body: JSON.stringify({
                        player_key: playerKey,
                        word_index: wordIndex
                    }),
                });
            });
        });
    </script>
</body>

</html>