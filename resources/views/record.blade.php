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
            <div class="sum-num">{{ $totalRuns + $trainCount + $endlessCount }}</div>
            <div class="sum-label">総プレイ回数</div>
        </div>
        <div class="sum-box">
            <div class="sum-num">{{ number_format($totalExp) }}</div>
            <div class="sum-label">累計 EXP</div>
        </div>
        <div class="sum-box">
            <div class="sum-num">{{ collect($charStats)->sum('level') }}</div>
            <div class="sum-label">合計レベル</div>
        </div>
        <div class="sum-box">
            <div class="sum-num">{{ $playMinutes }}<span class="unit">分</span></div>
            <div class="sum-label">総プレイ時間</div>
        </div>
    </div>

    <div class="best-line">
        <span>最高速度 {{ $bestTyping['kps'] }} /秒</span>
        <span>最高コンボ {{ $bestTyping['combo'] }}</span>
        <span>最高正確率 {{ $bestTyping['accuracy'] }}%</span>
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

    <h2 class="subtitle">果てなき挑戦</h2>
    <div class="endless-list">
        @foreach ($endless as $eKey => $e)
            <div class="endless-card" style="--c: {{ $e['color'] }}">
                <div class="ec-head">
                    <span class="ec-label">{{ $e['label'] }}</span>
                    <a class="ec-go" href="/endless/{{ $eKey }}">挑戦する</a>
                </div>

                <div class="ec-best">
                    <span class="ec-num">{{ $e['best'] }}</span>
                    <span class="ec-unit">{{ $e['unit'] }}</span>
                    <span class="ec-caption">自己ベスト</span>
                </div>

                <div class="ec-meta">
                    <span>{{ $e['plays'] }} 回挑戦</span>
                    <span>累計 {{ number_format($e['total_kills']) }} {{ $e['unit'] }}</span>
                    <span>EXP {{ number_format($e['exp']) }}</span>
                </div>

                @if (!empty($e['recent']))
                    <div class="ec-recent">
                        @foreach ($e['recent'] as $r)
                            <a class="ec-row" href="/endless/result/{{ $r['id'] }}">
                                <span class="ec-date">{{ $r['date'] }}</span>
                                <span class="ec-result {{ $r['defeated'] >= $e['best'] && $e['best'] > 0 ? 'top' : '' }}">
                                    {{ $r['defeated'] }} {{ $e['unit'] }}
                                </span>
                                <span class="ec-acc">{{ $r['accuracy'] }}%</span>
                                <span class="ec-exp">+{{ number_format($r['exp']) }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="ec-empty">まだ記録がありません</p>
                @endif
            </div>
        @endforeach
    </div>

    <h2 class="subtitle">弱点特訓</h2>
    @if ($trainCount === 0)
        <p class="empty">まだ特訓の記録がありません</p>
    @else
        <div class="train-sum">
            <div class="ts-box">
                <div class="ts-num">{{ $trainCount }}</div>
                <div class="ts-label">特訓回数</div>
            </div>
            <div class="ts-box">
                <div class="ts-num">{{ number_format($trainWords) }}</div>
                <div class="ts-label">打った単語</div>
            </div>
            <div class="ts-box">
                <div class="ts-num">{{ number_format($trainWeakWords) }}</div>
                <div class="ts-label">うち弱点</div>
            </div>
        </div>

        @if (count($trend) >= 2)
            @php
                $accs = array_column($trend, 'accuracy');
                $minA = min($accs);
                $maxA = max($accs);
                $span = max(1, $maxA - $minA);
                $first = $accs[0];
                $last = end($accs);
                $diff = round($last - $first, 1);
            @endphp
            <div class="trend">
                <div class="trend-head">
                    <span class="trend-title">正確率の推移（直近 {{ count($trend) }} 回）</span>
                    <span class="trend-diff {{ $diff >= 0 ? 'up' : 'down' }}">
                        {{ $diff >= 0 ? '+' : '' }}{{ $diff }}%
                    </span>
                </div>
                <div class="chart">
                    @foreach ($trend as $t)
                        <div class="col" title="{{ $t['date'] }}　{{ $t['accuracy'] }}%　{{ $t['kps'] }}/秒">
                            <div class="col-bar" style="height: {{ 14 + ($t['accuracy'] - $minA) / $span * 74 }}%"></div>
                            <div class="col-val">{{ round($t['accuracy']) }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="trend-foot">
                    <span>{{ $minA }}%</span>
                    <span>{{ $maxA }}%</span>
                </div>
            </div>
        @endif
    @endif

    @if (!empty($weak))
        <h2 class="subtitle">苦手な文字</h2>
        <div class="weak-list">
            @foreach ($weak as $w)
                <div class="weak-item">
                    <span class="weak-kana">{{ $w['kana'] }}</span>
                    <span class="weak-roma">{{ \App\Support\Romaji::of($w['kana']) }}</span>
                    <span class="weak-count">{{ $w['count'] }} 回</span>
                </div>
            @endforeach
        </div>
        <p class="weak-note">この文字を含む単語が弱点特訓で優先的に出題されます</p>
    @endif

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

    <h2 class="subtitle">最近の連戦</h2>
    @if ($recent->isEmpty())
        <p class="empty">まだ記録がありません</p>
    @else
        <div class="recent-list">
            @foreach ($recent as $run)
                @php
                    $st = $stages[$run->stage] ?? null;
                    $max = $st ? count($st['enemies']) + (empty($st['boss']) ? 0 : 1) : 0;
                @endphp
                @if ($run->finished)
                    <a class="recent-row link" href="/solo/result/{{ $run->id }}">
                @else
                    <div class="recent-row">
                @endif
                    <span class="recent-date">{{ $run->created_at->format('m/d H:i') }}</span>
                    <span class="recent-stage" style="color: {{ $st['color'] ?? '#888' }}">{{ $st['label'] ?? $run->stage }}</span>
                    <span class="recent-char">{{ $chars[$run->character]['name'] ?? $run->character }}</span>
                    <span class="recent-result {{ $max && $run->cleared >= $max ? 'clear' : '' }}">
                        {{ $run->cleared }} / {{ $max }}
                    </span>
                    @if ($run->drop_item)
                        <span class="recent-drop">🎁</span>
                    @endif
                    <span class="recent-exp">+{{ $run->exp_gained }}</span>
                @if ($run->finished)
                    </a>
                @else
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    @if ($trainRecent->isNotEmpty())
        <h2 class="subtitle">最近の特訓</h2>
        <div class="recent-list">
            @foreach ($trainRecent as $t)
                <a class="recent-row link" href="/training/result/{{ $t->id }}">
                    <span class="recent-date">{{ $t->created_at->format('m/d H:i') }}</span>
                    <span class="recent-stage" style="color: #e08a3d">弱点特訓</span>
                    <span class="recent-char">{{ $t->words }} 語（弱点 {{ $t->weak_words }}）</span>
                    <span class="recent-result {{ $t->accuracy() >= 95 ? 'clear' : '' }}">{{ $t->accuracy() }}%</span>
                    <span class="recent-exp">+{{ $t->exp_gained }}</span>
                </a>
            @endforeach
        </div>
    @endif

    <div class="back">
        <a class="back-btn" href="/character">キャラ選択へ戻る</a>
    </div>
</div>
</body>
</html>