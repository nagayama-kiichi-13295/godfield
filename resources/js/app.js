import './bootstrap';
import { createTyping, countdown, canonicalRomaji } from './typing';
import { calcDamage, BALANCE } from './damage';

window.Typing = { create: createTyping, countdown, canonicalRomaji, calcDamage, BALANCE };