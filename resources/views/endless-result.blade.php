<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>リザルト</title>
    @vite(['resources/css/result.css', 'resources/js/app.js'])
</head>
<body>
<div class="wrap {{ $isNewBest ? 'win' : 'lose' }}">

    <div class="verdict">{{ $isNewBest ? 'NEW RECORD' : 'RUN END' }}</div>

    <div class="head">
        <span class="head-stage" style="color: {{ $conf['color'] }}">{{ $conf['label'] }}</span>
        <span class="head-char">{{ $charConf['name'] ?? $run->character }}</span>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ $conf['mode'] === 'depth' ? '到達階層' : '撃破数' }}</h2>
        <div class="record-line">
            <div class="record-main">
                {{ $run->defeated }}<span class="unit">{{ $conf['mode'] === 'depth' ? '階' : '体' }}</span>
            </div>
            <div class="record-prev">
                自己ベスト {{ max($prevBest, $run->defeated) }}
                @if ($isNewBest)
                    <span class="new">更新</span>
                @endif
            </div>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">獲得 EXP</h2>
        <div class="exp-rows">
            <div class="exp-row total">
                <span>合計</span>
                <span class="num">{{ number_format($run->exp_gained) }}</span>
            </div>
        </div>
    </div>

    @if ($run->level_after > $run->level_before)
        <div class="panel levelup" id="levelup">
            <div class="lv-badge">LEVEL UP</div>
            <div class="lv-line">
                <span class="lv-from">Lv.{{ $run->level_before }}</span>
                <span class="lv-arrow">→</span>
                <span class="lv-to">Lv.{{ $run->level_after }}</span>
            </div>
            <div class="lv-note">ステータスポイントを {{ ($run->level_after - $run->level_before) * config('growth.points_per_level') }} 獲得</div>
        </div>
    @else
        <div class="panel">
            <div class="lv-line small">
                <span class="lv-to">Lv.{{ $pc->level }}</span>
                <span class="lv-note">{{ $pc->exp }} / {{ $pc->requiredExp() }}</span>
            </div>
            <div class="exp-bar"><div class="exp-fill" style="width: {{ $pc->expRatio() }}%"></div></div>
        </div>
    @endif

    <div class="panel">
        <h2 class="panel-title">タイピング成績</h2>
        <div class="grid">
            <div class="cell">
                <div class="cell-num">{{ number_format($run->typed_chars) }}</div>
                <div class="cell-label">打鍵数</div>
            </div>
            <div class="cell">
                <div class="cell-num">{{ $run->miss_count }}</div>
                <div class="cell-label">ミス</div>
            </div>
            <div class="cell">
                <div class="cell-num">{{ $run->accuracy() }}<span class="unit">%</span></div>
                <div class="cell-label">正確率</div>
            </div>
            <div class="cell">
                <div class="cell-num">{{ $run->kps() }}<span class="unit">/秒</span></div>
                <div class="cell-label">打鍵速度</div>
            </div>
            <div class="cell">
                <div class="cell-num">{{ $run->max_combo }}</div>
                <div class="cell-label">最大コンボ</div>
            </div>
            <div class="cell">
                <div class="cell-num">{{ round($run->duration_ms / 1000) }}<span class="unit">秒</span></div>
                <div class="cell-label">経過時間</div>
            </div>
        </div>
    </div>

    @if (!empty($missTop))
        <div class="panel">
            <h2 class="panel-title">つまずいた文字</h2>
            <div class="weak-list">
                @foreach ($missTop as $kana => $count)
                    <div class="weak-item">
                        <span class="weak-kana">{{ $kana }}</span>
                        <span class="weak-roma">{{ \App\Support\Romaji::of($kana) }}</span>
                        <span class="weak-count">{{ $count }} 回</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="actions">
        <a class="btn primary" href="/endless/{{ $run->mode }}">もう一度</a>
        <a class="btn" href="/character">キャラ選択へ</a>
        <a class="btn" href="/record">戦績を見る</a>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    window.FX.initAudio();
    if (document.getElementById('levelup')) setTimeout(() => window.FX.SFX.levelup(), 300);
});
</script>
</body>
</html>