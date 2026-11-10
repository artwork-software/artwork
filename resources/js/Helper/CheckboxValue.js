/**
 * Gespeicherte Werte von Checkbox-Eigenschaften (Inventar).
 *
 * Das Artikel-Formular wird als FormData gesendet; Inertia macht aus true/false dabei "1"/"0",
 * und so landet der Wert als Text in der Datenbank. "0" ist in JS truthy — deshalb nie nur
 * auf Wahrheitswert prüfen, sondern immer über diese Funktion.
 */
export function isCheckboxChecked(value) {
    if (value === true || value === 1 || value === '1' || value === 'true' || value === 'on') return true
    if (typeof value === 'string') return !['', '0', 'false', 'off'].includes(value.trim().toLowerCase())
    return !!value
}
