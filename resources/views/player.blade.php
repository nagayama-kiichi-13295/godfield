<!DOCTYPE html>
<html lang="ja">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プレイヤー名入力</title>
@vite(['resources/css/player.css'])
</head>
<body>

<div class="container">
    <h1 class="player-title">プレイヤー名入力</h1>

    <form class="player-form" action="/player" method="POST">
        @csrf

        <input
            class="name-box"
            type="text"
            name="name"
            maxlength="20"
            placeholder="プレイヤー名を入力"
            required
        >

        <button class="player-btn" type="submit">
            マッチング開始
        </button>
    </form>
</div>

</body>
</html>