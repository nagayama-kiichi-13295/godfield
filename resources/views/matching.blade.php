<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マッチング中</title>

    @vite(['resources/css/matching.css'])
</head>

<body>

<div class="container">

    <h1 class="match-title">
        マッチング中...
    </h1>

    <p class="match-text">
        {{ $name }} さん
    </p>

    <p class="match-text">
        対戦相手を探しています
    </p>

    <div class="loader"></div>

    <button class="cancel-btn">
        キャンセル
    </button>

</div>

</body>
</html>