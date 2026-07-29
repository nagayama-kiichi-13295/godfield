<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>キャラクター選択</title>
    @vite(['resources/css/character.css'])
</head>

<body>
    <div class="wrap">
        <h1 class="title">キャラクター選択</h1>

        <div class="player-info">
            <span class="pname">{{ $player->name }}</span>
            <span class="plevel">Lv.{{ $player->level }}</span>
            <span class="pexp">EXP {{ $player->exp }} / {{ $player->requiredExp() }}</span>
            <span class="precord">{{ $player->wins }}勝 {{ $player->losses }}敗</span>
        </div>

        <div class="cards">
            @foreach ($characters as $key => $c)
            <form method="POST" action="/character" class="card {{ $player->character === $key ? 'selected' : '' }}">
                @csrf
                <input type="hidden" name="character" value="{{ $key }}">
                <div class="cname">{{ $c['name'] }}</div>
                <div class="cdesc">{{ $c['desc'] }}</div>
                <div class="cstat">
                    HP {{ (int) round($c['base_hp'] + $c['hp_growth'] * ($player->level - 1)) }}
                    ／ 攻撃 {{ (int) round($c['base_power'] + $c['power_growth'] * ($player->level - 1)) }}
                </div>
                <button type="submit" class="cbtn">
                    {{ $player->character === $key ? '選択中' : 'これにする' }}
                </button>
            </form>
            @endforeach
        </div>

        <h2 class="subtitle">ひとりで練習</h2>
        <div class="modes">
            @foreach ($cpus as $key => $cpu)
            <a class="mode-btn" href="/solo/{{ $key }}">{{ $cpu['label'] }}</a>
            @endforeach
        </div>

        <h2 class="subtitle">オンライン対戦</h2>
        <div class="modes">
            <a class="mode-btn online" href="/matching">対戦相手を探す</a>
        </div>
    </div>
</body>

</html>