<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>マッチング中</title>
    @vite(['resources/css/matching.css', 'resources/js/app.js'])
</head>
<body>

<div class="container">
    <h1 class="match-title">マッチング中...</h1>
    <p class="match-text">{{ $name }} さん</p>
    <p class="match-text" id="message">対戦相手を探しています</p>
    <div class="loader"></div>
    <button class="cancel-btn" id="cancel">キャンセル</button>
</div>

<script>
window.addEventListener('DOMContentLoaded', async () => {
    const token = document.querySelector('meta[name=csrf-token]').content;
    const message = document.getElementById('message');
    let done = false;

    function go(matchId) {
        if (done) return;
        done = true;
        location.href = '/match/' + matchId;
    }

    document.getElementById('cancel').addEventListener('click', async () => {
        done = true;
        await fetch('/matching/cancel', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token },
        });
        location.href = '/';
    });

    const res = await fetch('/matching/join', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token },
    });

    if (!res.ok) {
        location.href = '/player';
        return;
    }

    const data = await res.json();

    if (data.status === 'playing') {
        go(data.match_id);
        return;
    }

    message.textContent = '対戦相手を待っています';

    if (window.Echo) {
        window.Echo.channel('match.' + data.match_id)
            .listen('.match.found', () => go(data.match_id));
    }

    setInterval(async () => {
        if (done) return;
        const r = await fetch('/matching/status');
        const s = await r.json();
        if (s.status === 'playing') go(s.match_id);
    }, 2000);
});
</script>

</body>
</html>