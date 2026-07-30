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
        @foreach ($characters as $charKey => $c)
            @php
                $s = $stats[$charKey];
                $st = $s->stats();
            @endphp
            <div class="card {{ $player->character === $charKey ? 'selected' : '' }}" style="--c: {{ $c['color'] }}">

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

                <div class="alloc">
                    @foreach ($growth['labels'] as $stat => $label)
                        <div class="alloc-row">
                            <span class="alloc-label">{{ $label }}</span>
                            <span class="alloc-value">{{ $st[$stat === 'hp' ? 'max_hp' : $stat] }}</span>
                            <span class="alloc-pt">+{{ $s->{'pt_' . $stat} }}</span>

                            <form method="POST" action="/character/allocate">
                                @csrf
                                <input type="hidden" name="character" value="{{ $charKey }}">
                                <input type="hidden" name="stat" value="{{ $stat }}">
                                <input type="hidden" name="amount" value="-1">
                                <button class="pt-btn" @disabled($s->{'pt_' . $stat} < 1)>−</button>
                            </form>

                            <form method="POST" action="/character/allocate">
                                @csrf
                                <input type="hidden" name="character" value="{{ $charKey }}">
                                <input type="hidden" name="stat" value="{{ $stat }}">
                                <input type="hidden" name="amount" value="1">
                                <button class="pt-btn" @disabled($s->points < 1)>＋</button>
                            </form>
                        </div>
                    @endforeach

                    <div class="alloc-foot">
                        <span class="remain {{ $s->points > 0 ? 'has' : '' }}">残 {{ $s->points }} pt</span>

                        <form method="POST" action="/character/reset">
                            @csrf
                            <input type="hidden" name="character" value="{{ $charKey }}">
                            <button class="reset-btn" @disabled($s->pt_hp + $s->pt_power + $s->pt_int < 1)>振り直し</button>
                        </form>
                    </div>
                </div>

                <div class="equip">
                    @foreach ($equipment['slots'] as $slot => $slotLabel)
                        <form method="POST" action="/character/equip" class="equip-row">
                            @csrf
                            <input type="hidden" name="character" value="{{ $charKey }}">
                            <input type="hidden" name="slot" value="{{ $slot }}">
                            <span class="equip-label">{{ $slotLabel }}</span>
                            <select name="item" class="equip-select" onchange="this.form.submit()">
                                <option value="">なし</option>
                                @foreach ($equipment['items'] as $itemKey => $it)
                                    @if ($it['slot'] === $slot && in_array($itemKey, $owned, true))
                                        <option value="{{ $itemKey }}" @selected($s->{'eq_' . $slot} === $itemKey)>
                                            {{ $it['name'] }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </form>
                    @endforeach
                </div>

                <div class="crecord">{{ $s->wins }}勝 {{ $s->losses }}敗</div>

                <form method="POST" action="/character">
                    @csrf
                    <input type="hidden" name="character" value="{{ $charKey }}">
                    <button type="submit" class="cbtn">
                        {{ $player->character === $charKey ? '選択中' : 'これにする' }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    <h2 class="subtitle">弱点特訓</h2>
    <div class="modes">
        <a class="mode-btn" href="/training" style="--c: #e08a3d">
            苦手な文字を練習<span class="mode-sub">90 秒</span>
        </a>
    </div>

    <h2 class="subtitle">連戦に挑む</h2>
    <div class="modes">
        @foreach ($stages as $stageKey => $stage)
            <a class="mode-btn" href="/solo/{{ $stageKey }}" style="--c: {{ $stage['color'] }}">
                {{ $stage['label'] }}<span class="mode-sub">{{ count($stage['enemies']) }} 体</span>
            </a>
        @endforeach
    </div>

    <h2 class="subtitle">果てなき挑戦</h2>
    <div class="modes">
        @foreach (config('endless') as $mKey => $m)
            <a class="mode-btn" href="/endless/{{ $mKey }}" style="--c: {{ $m['color'] }}">
                {{ $m['label'] }}<span class="mode-sub">{{ $m['mode'] === 'depth' ? '無限' : $m['total_time'] . ' 秒' }}</span>
            </a>
        @endforeach
    </div>

    <h2 class="subtitle">オンライン対戦</h2>
    <div class="modes">
        <a class="mode-btn online" href="/matching">対戦相手を探す</a>
    </div>
    
    <div class="modes" style="margin-top: 30px">
        <a class="mode-btn" href="/record">戦績を見る</a>
    </div>
</div>
</body>
</html>