const KANA = {
    'あ': ['a'], 'い': ['i'], 'う': ['u', 'wu'], 'え': ['e'], 'お': ['o'],
    'か': ['ka', 'ca'], 'き': ['ki'], 'く': ['ku', 'cu', 'qu'], 'け': ['ke'], 'こ': ['ko', 'co'],
    'が': ['ga'], 'ぎ': ['gi'], 'ぐ': ['gu'], 'げ': ['ge'], 'ご': ['go'],
    'さ': ['sa'], 'し': ['si', 'shi', 'ci'], 'す': ['su'], 'せ': ['se', 'ce'], 'そ': ['so'],
    'ざ': ['za'], 'じ': ['zi', 'ji'], 'ず': ['zu'], 'ぜ': ['ze'], 'ぞ': ['zo'],
    'た': ['ta'], 'ち': ['ti', 'chi'], 'つ': ['tu', 'tsu'], 'て': ['te'], 'と': ['to'],
    'だ': ['da'], 'ぢ': ['di'], 'づ': ['du'], 'で': ['de'], 'ど': ['do'],
    'な': ['na'], 'に': ['ni'], 'ぬ': ['nu'], 'ね': ['ne'], 'の': ['no'],
    'は': ['ha'], 'ひ': ['hi'], 'ふ': ['fu', 'hu'], 'へ': ['he'], 'ほ': ['ho'],
    'ば': ['ba'], 'び': ['bi'], 'ぶ': ['bu'], 'べ': ['be'], 'ぼ': ['bo'],
    'ぱ': ['pa'], 'ぴ': ['pi'], 'ぷ': ['pu'], 'ぺ': ['pe'], 'ぽ': ['po'],
    'ま': ['ma'], 'み': ['mi'], 'む': ['mu'], 'め': ['me'], 'も': ['mo'],
    'や': ['ya'], 'ゆ': ['yu'], 'よ': ['yo'],
    'ら': ['ra'], 'り': ['ri'], 'る': ['ru'], 'れ': ['re'], 'ろ': ['ro'],
    'わ': ['wa'], 'を': ['wo'], 'ゔ': ['vu'], 'ー': ['-'],
    'ぁ': ['xa', 'la'], 'ぃ': ['xi', 'li'], 'ぅ': ['xu', 'lu'], 'ぇ': ['xe', 'le'], 'ぉ': ['xo', 'lo'],
    'ゃ': ['xya', 'lya'], 'ゅ': ['xyu', 'lyu'], 'ょ': ['xyo', 'lyo'],
};

const COMBO = {
    'きゃ': ['kya'], 'きゅ': ['kyu'], 'きょ': ['kyo'],
    'ぎゃ': ['gya'], 'ぎゅ': ['gyu'], 'ぎょ': ['gyo'],
    'しゃ': ['sya', 'sha'], 'しゅ': ['syu', 'shu'], 'しょ': ['syo', 'sho'],
    'じゃ': ['zya', 'ja', 'jya'], 'じゅ': ['zyu', 'ju', 'jyu'], 'じょ': ['zyo', 'jo', 'jyo'],
    'ちゃ': ['tya', 'cha', 'cya'], 'ちゅ': ['tyu', 'chu', 'cyu'], 'ちょ': ['tyo', 'cho', 'cyo'],
    'にゃ': ['nya'], 'にゅ': ['nyu'], 'にょ': ['nyo'],
    'ひゃ': ['hya'], 'ひゅ': ['hyu'], 'ひょ': ['hyo'],
    'びゃ': ['bya'], 'びゅ': ['byu'], 'びょ': ['byo'],
    'ぴゃ': ['pya'], 'ぴゅ': ['pyu'], 'ぴょ': ['pyo'],
    'みゃ': ['mya'], 'みゅ': ['myu'], 'みょ': ['myo'],
    'りゃ': ['rya'], 'りゅ': ['ryu'], 'りょ': ['ryo'],
    'ふぁ': ['fa'], 'ふぃ': ['fi'], 'ふぇ': ['fe'], 'ふぉ': ['fo'],
    'てぃ': ['thi'], 'でぃ': ['dhi'], 'うぃ': ['wi'], 'うぇ': ['we'],
    'しぇ': ['sye', 'she'], 'じぇ': ['zye', 'je', 'jye'], 'ちぇ': ['tye', 'che', 'cye'],
    'つぁ': ['tsa'], 'つぃ': ['tsi'], 'つぇ': ['tse'], 'つぉ': ['tso'],
    'てゅ': ['thu'], 'でゅ': ['dhu'], 'うぉ': ['who'],
};

const VOWELS = 'aiueo';

export function toChunks(kana) {
    const chunks = [];
    let i = 0;

    while (i < kana.length) {
        const two = kana.slice(i, i + 2);

        if (COMBO[two]) {
            chunks.push({ kana: two, candidates: COMBO[two].slice() });
            i += 2;
            continue;
        }

        const ch = kana[i];

        if (ch === 'っ') {
            chunks.push({ kana: ch, sokuon: true, candidates: [] });
        } else if (ch === 'ん') {
            chunks.push({ kana: ch, hatsuon: true, candidates: [] });
        } else {
            chunks.push({ kana: ch, candidates: (KANA[ch] || [ch]).slice() });
        }

        i += 1;
    }

    for (let j = chunks.length - 1; j >= 0; j--) {
        const c = chunks[j];
        const next = chunks[j + 1];

        if (c.sokuon) {
            const set = new Set(['xtu', 'ltu', 'xtsu', 'ltsu']);

            if (next) {
                next.candidates.forEach((r) => {
                    const head = r[0];
                    if (head && !VOWELS.includes(head)) set.add(head);
                });
            }

            c.candidates = [...set];
        }

        if (c.hatsuon) {
            const set = new Set(['nn', 'xn']);

            if (next) {
                const safe = next.candidates.every((r) => {
                    const head = r[0];
                    return head && !VOWELS.includes(head) && head !== 'n' && head !== 'y';
                });

                if (safe) set.add('n');
            }

            c.candidates = [...set];
        }
    }

    return chunks;
}

function shortest(candidates, prefix = '') {
    return candidates
        .filter((c) => c.startsWith(prefix))
        .sort((a, b) => a.length - b.length)[0] || '';
}

export function canonicalRomaji(kana) {
    return toChunks(kana).map((c) => shortest(c.candidates)).join('');
}

export function createMatcher(kana) {
    const chunks = toChunks(kana);
    let ci = 0;
    let buf = '';
    let typed = '';

    function settle() {
        while (ci < chunks.length) {
            const cands = chunks[ci].candidates.filter((c) => c.startsWith(buf));
            const exact = cands.includes(buf);
            const longer = cands.some((c) => c.length > buf.length);

            if (exact && !longer) {
                ci += 1;
                buf = '';
            } else {
                break;
            }
        }
    }

    return {
        get done() {
            return ci >= chunks.length;
        },

        input(key) {
            if (ci >= chunks.length) return false;

            const chunk = chunks[ci];
            const attempt = buf + key;

            if (chunk.candidates.some((c) => c.startsWith(attempt))) {
                buf = attempt;
                typed += key;
                settle();
                return true;
            }

            const next = chunks[ci + 1];

            if (chunk.candidates.includes(buf) && next
                && next.candidates.some((c) => c.startsWith(key))) {
                ci += 1;
                buf = key;
                typed += key;
                settle();
                return true;
            }

            return false;
        },

        hint() {
            let rest = shortest(chunks[ci] ? chunks[ci].candidates : [], buf).slice(buf.length);

            for (let j = ci + 1; j < chunks.length; j++) {
                rest += shortest(chunks[j].candidates);
            }

            return { typed, rest };
        },
        
        current() {
            const chunk = chunks[ci];

            return chunk ? { kana: chunk.kana, buf } : null;
        },
    };
}