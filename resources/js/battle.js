export function createBattle({
    typing,
    ui,
    me,
    healCapRate = 0.25,
    onEnemyDown,
    onPlayerDown,
    onWord,
}) {
    let myHp = me.max_hp;
    let enemy = null;
    let enemyHp = 0;
    let healed = 0;
    let healCap = 0;
    let attackTimer = null;
    let running = false;
    let suspended = false;

    function clampHp() {
        myHp = Math.max(0, Math.min(me.max_hp, myHp));
        enemyHp = Math.max(0, enemyHp);
    }

    function render() {
        clampHp();

        const emax = enemy ? enemy.hp : 1;

        ui.hpMeBar.style.width = (myHp / me.max_hp * 100) + '%';
        ui.hpYouBar.style.width = (enemyHp / emax * 100) + '%';

        if (ui.hpMeNum) ui.hpMeNum.textContent = `${myHp} / ${me.max_hp}`;
        if (ui.hpYouNum) ui.hpYouNum.textContent = `${enemyHp} / ${emax}`;
    }

    function flashMeter(el, text, ms = 900) {
        if (!el) return;
        el.textContent = text;
        setTimeout(() => { el.textContent = ''; }, ms);
    }

    function dealDamage(info) {
        const dmg = window.Typing.calcDamage(me.power, info.chars, info.seconds, info.combo);
        const crit = dmg >= me.power * 1.6;

        enemyHp -= dmg;

        if (ui.combo) ui.combo.textContent = info.combo >= 2 ? `${info.combo} COMBO` : '';
        if (ui.dmg) ui.dmg.textContent = `${dmg} ダメージ`;

        window.FX.SFX.word();
        window.FX.popup(ui.hpYouBar, `-${dmg}`, crit ? 'crit' : 'dmg');
        window.FX.shake(ui.hpYouBar, crit);

        return dmg;
    }

    function tryHeal(combo) {
        const heal = window.Typing.calcHeal(me.heal, combo);

        if (heal <= 0 || healed >= healCap) return 0;

        const actual = Math.min(heal, healCap - healed, me.max_hp - myHp);

        if (actual <= 0) return 0;

        myHp += actual;
        healed += actual;

        flashMeter(ui.heal, `+${actual} 回復`);
        window.FX.SFX.heal();
        window.FX.popup(ui.hpMeBar, `+${actual}`, 'heal');

        return actual;
    }

    function takeDamage(power, big = false) {
        myHp -= power;
        render();

        ui.hpMeBar.classList.add('hit');
        setTimeout(() => ui.hpMeBar.classList.remove('hit'), 150);

        window.FX.popup(ui.hpMeBar, `-${power}`, 'take');
        window.FX.flash('red');
        window.FX.shake(document.querySelector('.arena'), big);
        big ? window.FX.SFX.bigHit() : window.FX.SFX.hit();

        return myHp <= 0;
    }

    function scheduleAttack() {
        clearTimeout(attackTimer);

        if (!enemy) return;

        attackTimer = setTimeout(() => {
            if (!running) return;

            if (!suspended && takeDamage(enemy.power)) {
                stop();
                if (onPlayerDown) onPlayerDown();
                return;
            }

            scheduleAttack();
        }, enemy.interval * 1000);
    }

    function handleWord(info) {
        if (!running) return;

        if (suspended) {
            if (onWord) onWord(info, { suspended: true });
            return;
        }

        const dmg = dealDamage(info);
        const heal = tryHeal(info.combo);

        render();

        if (onWord) onWord(info, { damage: dmg, heal, suspended: false });

        if (enemyHp <= 0) {
            stop();
            if (onEnemyDown) onEnemyDown(enemy);
        }
    }

    function setEnemy(next) {
        enemy = next;
        enemyHp = next.hp;
        healed = 0;
        healCap = Math.round(me.max_hp * healCapRate);

        render();
    }

    function start() {
        running = true;
        suspended = false;
        typing.start();
        scheduleAttack();
    }

    function stop() {
        running = false;
        clearTimeout(attackTimer);
        typing.stop();
    }

    return {
        handleWord,
        setEnemy,
        start,
        stop,
        render,
        takeDamage,

        suspend() { suspended = true; },
        resume() { suspended = false; },

        get hp() { return myHp; },
        set hp(v) { myHp = v; render(); },
        get enemyHp() { return enemyHp; },
        get enemy() { return enemy; },
        get running() { return running; },
    };
}