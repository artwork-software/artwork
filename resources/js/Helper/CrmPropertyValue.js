/**
 * Werte von CRM-Eigenschaften — Gegenstück zu Artwork\Modules\Crm\Support\CrmPropertyValueFormat.
 *
 * Mehrfachauswahl (Typ "multiselect") wird als JSON-Text einer Liste übertragen und gespeichert,
 * z. B. '["Tanz","Theater"]'. Ein einzelner Text ohne JSON (z. B. aus einer umgestellten Einfachauswahl)
 * zählt als Liste mit einem Eintrag.
 */

export const isMultiselectType = (type) => type === 'multiselect';

export function decodeMultiselect(value) {
    if (value === null || value === undefined || value === '') {
        return [];
    }
    let list = value;
    if (typeof value === 'string') {
        try {
            const parsed = JSON.parse(value);
            list = Array.isArray(parsed) ? parsed : [value];
        } catch {
            list = [value];
        }
    }
    if (!Array.isArray(list)) {
        list = [list];
    }
    const result = [];
    for (const entry of list) {
        if (entry === null || entry === undefined || typeof entry === 'object') continue;
        const text = String(entry);
        if (text !== '' && !result.includes(text)) result.push(text);
    }
    return result;
}

/** Liste → JSON-Text in Reihenfolge der Optionen; leer = null */
export function encodeMultiselect(values, options = []) {
    const selected = decodeMultiselect(values);
    const ordered = options.length
        ? [...options.map(String).filter((option) => selected.includes(option)), ...selected.filter((value) => !options.map(String).includes(value))]
        : selected;
    return ordered.length ? JSON.stringify(ordered) : null;
}

/** Anzeige-Text: Mehrfachauswahl kommagetrennt, sonst der Wert selbst */
export function crmPropertyDisplayText(value, type) {
    if (isMultiselectType(type)) {
        return decodeMultiselect(value).join(', ');
    }
    return value ?? '';
}

/** Option an-/abwählen und als JSON-Text zurückgeben ('' = nichts gewählt) */
export function toggledMultiselect(value, option, checked, options = []) {
    const current = decodeMultiselect(value);
    const next = checked ? [...current, String(option)] : current.filter((entry) => entry !== String(option));
    return encodeMultiselect(next, options) ?? '';
}
