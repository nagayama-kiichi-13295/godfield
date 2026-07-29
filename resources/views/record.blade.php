<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>戦績</title>
    @vite(['resources/css/record.css'])
</head>
<body>
<div class="wrap">
    <h1 class="title">戦績</h1>
    <p class="player-info">{{ $player->name }} さん</p>

    <div class="summary">
        <div class="sum-box">
            <div class="sum-num">{{ $totalRuns }}</div>
            <div class="sum-label">挑戦回数</div>
        </div>
        <div class="sum-box">
            <div class="sum-num">{{ number_format($totalExp) }}</div>
            <div class="sum-label">累計 EXP</div>
        </div>
        <div class="sum-box">
            <div class="sum-num">{{ collect($charStats)->sum('level') }}</div>
            <div class="sum-label">合計レベル</div>
        </div>
    </div>

    <h2 class="subtitle">ステージ到達</h2>
    <div class="stage-list">
        @foreach ($rows as $r)
            <div class="stage-row" style="--c: {{ $r['color'] }}">
                <div class="stage-name">
                    {{ $r['label'] }}
                    @if ($r['cleared'])
                        <span class="badge">CLEAR</span>
                    @endif
                </div>
                <div class="stage-bar">
                    <div class="stage-fill" style="width: {{ $r['total'] ? $r['best'] / $r['total'] * 100 : 0 }}%"></div>
                </div>
                <div class="stage-num">{{ $r['best'] }} / {{ $r['total'] }}</div>
                <div class="stage-plays">{{ $r['plays'] }} 回</div>
            </div>
        @endforeach
    </div>

    <h2 class="subtitle">キャラクター</h2>
    <div class="char-list">
        @foreach ($charStats as $cs)
            <div class="char-row" style="--c: {{ $cs['color'] }}">
                @include('partials.icon', ['icon' => $cs['icon'], 'color' => $cs['color'], 'size' => 30])
                <span class="char-name">{{ $cs['name'] }}</span>
                <span class="char-level">Lv.{{ $cs['level'] }}</span>
                <span class="char-stat">HP {{ $cs['stats']['max_hp'] }}</span>
                <span class="char-stat">攻 {{ $cs['stats']['power'] }}</span>
                <span class="char-stat">知 {{ $cs['stats']['int'] }}</span>
                <span class="char-record">{{ $cs['wins'] }}勝 {{ $cs['losses'] }}敗</span>
            </div>
        @endforeach
    </div>

    <h2 class="subtitle">最近の挑戦</h2>
    @if ($recent->isEmpty())
        <p class="empty">まだ記録がありません</p>
    @else
        <div class="recent-list">
            @foreach ($recent as $run)
                @php
                    $st = $stages[$run->stage] ?? null;
                    $max = $st ? count($st['enemies']) + (empty($st['boss']) ? 0 : 1) : 0;
                @endphp
                <div class="recent-row">
                    <span class="recent-date">{{ $run->created_at->format('m/d H:i') }}</span>
                    <span class="recent-stage" style="color: {{ $st['color'] ?? '#888' }}">{{ $st['label'] ?? $run->stage }}</span>
                    <span class="recent-char">{{ $chars[$run->character]['name'] ?? $run->character }}</span>
                    <span class="recent-result {{ $max && $run->cleared >= $max ? 'clear' : '' }}">
                        {{ $run->cleared }} / {{ $max }}
                    </span>
                    <span class="recent-exp">+{{ $run->exp_gained }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="back">
        <a class="back-btn" href="/character">キャラ選択へ戻る</a>
    </div>
</div>
</body>
</html>