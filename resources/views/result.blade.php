<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>リザルト</title>
    @vite(['resources/css/result.css', 'resources/js/app.js'])
</head>
<body>
<div class="wrap {{ $run->is_cleared ? 'win' : 'lose' }}">

    <div class="verdict">{{ $run->is_cleared ? 'STAGE CLEAR' : 'GAME OVER' }}</div>

    <div class="head">
        <span class="head-stage" style="color: {{ $stageConf['color'] }}">{{ $stageConf['label'] }}</span>
        <span class="head-char">{{ $charConf['name'] ?? $run->character }}</span>
        <span class="head-reach">
            到達 {{ $run->cleared }} / {{ $run->totalEnemies() }}
            @if (!empty($records['reach']))<span class="new">自己ベスト</span>@endif
        </span>
    </div>

    <div class="panel">
        <h2 class="panel-title">獲得 EXP</h2>
        <div class="exp-rows">
            <div class="exp-row">
                <span>撃破ぶん</span>
                <span class="num">{{ number_format($run->exp_gained - $run->bonus_exp) }}</span>
            </div>
            @if ($run->bonus_exp > 0)
                <div class="exp-row bonus">
                    <span>クリアボーナス</span>
                    <span class="num">+{{ number_format($run->bonus_exp) }}</span>
                </div>
            @endif
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

    @if ($dropName)
        <div class="panel drop" id="drop" style="--c: {{ $dropColor }}">
            <div class="drop-badge">ITEM GET</div>
            <div class="drop-name">{{ $dropName }}</div>
            <div class="drop-note">キャラ選択画面から装備できます</div>
        </div>
    @endif

    <div class="panel">
        <h2 class="panel-title">タイピング成績</h2>
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
                <div class="cell-label">打鍵速度 @if (!empty($records['kps']))<span class="new">NEW</span>@endif</div>
            </div>
            <div class="cell">
                <div class="cell-num">{{ $run->max_combo }}</div>
                <div class="cell-label">最大コンボ @if (!empty($records['combo']))<span class="new">NEW</span>@endif</div>
            </div>
            <div class="cell">
                <div class="cell-num">{{ round($run->duration_ms / 1000) }}<span class="unit">秒</span></div>
                <div class="cell-label">経過時間</div>
            </div>
        </div>
    </div>

    <div class="actions">
        <a class="btn primary" href="/solo/{{ $run->stage }}">もう一度挑戦</a>
        <a class="btn" href="/character">キャラ選択へ</a>
        <a class="btn" href="/record">戦績を見る</a>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    window.FX.initAudio();

    const levelup = document.getElementById('levelup');
    const drop = document.getElementById('drop');

    if (levelup) setTimeout(() => window.FX.SFX.levelup(), 300);
    if (drop) setTimeout(() => window.FX.SFX.defeat(), levelup ? 1100 : 300);
});
</script>
</body>
</html>