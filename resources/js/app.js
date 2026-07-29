import './bootstrap';
import { createTyping, countdown, canonicalRomaji } from './typing';
import { calcDamage, calcHeal, BALANCE, HEAL_COMBO_STEP } from './damage';

window.Typing = {
    create: createTyping,
    countdown,
    canonicalRomaji,
    calcDamage,
    calcHeal,
    BALANCE,
    HEAL_COMBO_STEP,
};