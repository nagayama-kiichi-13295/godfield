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
    <button id="advance">1語打ち終わった（仮）</button>

    <script>
        const matchId = @json($matchId);
        const playerKey = crypto.randomUUID();
        let wordIndex = 0;

        document.getElementById('advance').addEventListener('click', async () => {
            wordIndex++;
            document.getElementById('mine').textContent = wordIndex;

            await fetch(`/match/${matchId}/progress`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Socket-Id': window.Echo.socketId(),
                },
                body: JSON.stringify({ player_key: playerKey, word_index: wordIndex }),
            });
        });

        window.Echo.channel(`match.${matchId}`)
            .listen('.player.progressed', (e) => {
                document.getElementById('theirs').textContent = e.wordIndex;
            });
    </script>
</body>
</html>