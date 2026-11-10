/**
 * Auswahlwerte von Auswahl-Komponenten (DropDown, Mehrfachauswahl/CheckboxList) — Gegenstück zu
 * Artwork\Modules\Project\Support\ComponentSelection im Backend.
 *
 * `selected` ist ein Text (DropDown ohne Mehrfachauswahl), eine Liste (CheckboxList, DropDown mit
 * "multiple") oder bei Altbestand ein Objekt `{ value }`. Lesestellen nutzen diese Helfer, damit beide
 * Formen überall funktionieren.
 */

export function allowsMultipleSelection(type, componentData) {
    if (type === 'CheckboxList') {
        return true;
    }
    if (type === 'DropDown') {
        const multiple = componentData?.multiple;
        return multiple === true || multiple === 1 || multiple === '1' || multiple === 'true';
    }
    return false;
}

export function selectedValues(selected) {
    let raw = selected;
    if (raw && typeof raw === 'object' && !Array.isArray(raw) && 'value' in raw) {
        raw = raw.value;
    }
    if (raw === null || raw === undefined || raw === '') {
        return [];
    }
    const list = Array.isArray(raw) ? raw : [raw];
    const values = [];
    for (let entry of list) {
        if (entry && typeof entry === 'object' && 'value' in entry) {
            entry = entry.value;
        }
        if (entry === null || entry === undefined || typeof entry === 'object') {
            continue;
        }
        const text = String(entry);
        if (text !== '' && !values.includes(text)) {
            values.push(text);
        }
    }
    return values;
}

export function selectedDisplayText(selected, separator = ', ') {
    return selectedValues(selected).join(separator);
}

export function selectionOptions(componentData) {
    return (componentData?.options ?? [])
        .map((option) => (option && typeof option === 'object' ? option.value : option))
        .filter((value) => value !== null && value !== undefined && String(value) !== '')
        .map((value) => String(value));
}
