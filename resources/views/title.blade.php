<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タイピングコロシアム</title>

    <style>
        body{
            margin:0;
            height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:#1b1b2f;
            color:white;
            font-family:sans-serif;
        }

        .container{
            text-align:center;
        }

        h1{
            font-size:60px;
            margin-bottom:50px;
        }

        .btn{
            display:inline-block;
            padding:15px 40px;
            background:#4CAF50;
            color:white;
            text-decoration:none;
            border-radius:10px;
            font-size:24px;
        }

        .btn:hover{
            background:#45a049;
        }
    </style>
</head>

<body>

<div class="container">
<link rel="stylesheet" href="{{ asset('css/title.css') }}">
    <h1>⌨ タイピングコロシアム ⌨</h1>

    <a href="/matching" class="btn">
        ゲーム開始
    </a>

</div>

</body>
</html>