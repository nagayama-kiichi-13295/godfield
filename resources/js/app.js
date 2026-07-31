import './bootstrap';
import { createTyping, countdown, canonicalRomaji } from './typing';
import { calcDamage, calcHeal, BALANCE, HEAL_COMBO_STEP } from './damage';
import { createBattle } from './battle';
import { SFX, initAudio, setSound, soundEnabled, popup, shake, flash } from './fx';

window.Typing = {
    create: createTyping,
    countdown,
    canonicalRomaji,
    calcDamage,
    calcHeal,
    BALANCE,
    HEAL_COMBO_STEP,
};

window.Battle = { create: createBattle };
window.FX = { SFX, initAudio, setSound, soundEnabled, popup, shake, flash };