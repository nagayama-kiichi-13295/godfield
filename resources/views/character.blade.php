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
    <p class="player-info">{{ $player->name }} さん</p>

    <div class="cards">
        @foreach ($characters as $key => $c)
            @php $s = $stats[$key]; @endphp
            <form method="POST" action="/character"
                  class="card {{ $player->character === $key ? 'selected' : '' }}"
                  style="--c: {{ $c['color'] }}">
                @csrf
                <input type="hidden" name="character" value="{{ $key }}">

                <div class="emblem-box">
                    @include('partials.icon', ['icon' => $c['icon'], 'color' => $c['color'], 'size' => 56])
                </div>

                <div class="cname">{{ $c['name'] }}</div>
                <div class="clevel">Lv.{{ $s->level }}</div>

                <div class="cexp">
                    <div class="cexp-fill" style="width: {{ $s->expRatio() }}%"></div>
                </div>
                <div class="cexp-text">{{ $s->exp }} / {{ $s->requiredExp() }}</div>

                <div class="cdesc">{{ $c['desc'] }}</div>
                <div class="cstat">HP {{ $s->maxHp() }} ／ 攻撃 {{ $s->power() }}</div>
                <div class="crecord">{{ $s->wins }}勝 {{ $s->losses }}敗</div>

                <button type="submit" class="cbtn">
                    {{ $player->character === $key ? '選択中' : 'これにする' }}
                </button>
            </form>
        @endforeach
    </div>

    <h2 class="subtitle">ひとりで練習</h2>
    <div class="modes">
        @foreach ($cpus as $key => $cpu)
            <a class="mode-btn" href="/solo/{{ $key }}" style="--c: {{ $cpu['color'] }}">{{ $cpu['label'] }}</a>
        @endforeach
    </div>

    <h2 class="subtitle">オンライン対戦</h2>
    <div class="modes">
        <a class="mode-btn online" href="/matching">対戦相手を探す</a>
    </div>
</div>
</body>
</html>