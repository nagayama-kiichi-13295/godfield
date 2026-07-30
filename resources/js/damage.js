export const BALANCE = {
    baseKps: 4.0,
    minSpeed: 0.6,
    maxSpeed: 2.0,
    comboStep: 0.05,
    comboMax: 10,
};

export function calcDamage(power, chars, seconds, combo, opt = {}) {
    const c = { ...BALANCE, ...opt };

    const kps = seconds > 0 ? chars / seconds : c.baseKps;
    const speed = Math.min(c.maxSpeed, Math.max(c.minSpeed, kps / c.baseKps));
    const comboFactor = 1 + Math.min(combo, c.comboMax) * c.comboStep;

    return Math.max(1, Math.round(power * speed * comboFactor));
}

export const HEAL_COMBO_STEP = 5;

export function calcHeal(heal, combo) {
    return combo > 0 && combo % HEAL_COMBO_STEP === 0 ? Math.max(1, Math.round(heal)) : 0;
}