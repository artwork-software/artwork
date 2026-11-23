/**
 * Tastatur in einer Trefferliste unter einem Suchfeld: Pfeiltasten wandern durch die Treffer (am Ende geht es
 * vorn weiter), Enter wählt, Escape schließt. Reine Logik ohne DOM, damit sie sich einzeln prüfen lässt.
 *
 * @param {string} key Wert von KeyboardEvent.key
 * @param {number} activeIndex markierter Treffer, -1 = keiner
 * @param {number} count Zahl der Treffer
 * @returns {{ action: 'move'|'select'|'close', index: number }|null} null = Taste nicht behandeln
 */
export function resultListKey(key, activeIndex, count) {
    if (key === 'Escape') {
        return { action: 'close', index: -1 };
    }

    if (count <= 0) {
        return null;
    }

    const current = activeIndex >= 0 && activeIndex < count ? activeIndex : -1;

    if (key === 'ArrowDown') {
        return { action: 'move', index: (current + 1) % count };
    }

    if (key === 'ArrowUp') {
        return { action: 'move', index: current <= 0 ? count - 1 : current - 1 };
    }

    if (key === 'Enter') {
        // ohne Markierung nimmt Enter den ersten Treffer
        return { action: 'select', index: current === -1 ? 0 : current };
    }

    return null;
}
