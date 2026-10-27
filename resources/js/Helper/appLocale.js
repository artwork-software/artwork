import { safeGetItem, safeSetItem } from './safeStorage.js';

/**
 * Startsprache: <html lang> setzt der Server aus der Sprache der angemeldeten Person (Localization-Middleware)
 * und hat Vorrang. localStorage ist nur Rückfall – sonst „erbte“ ein Browser die Sprache des zuletzt
 * bearbeiteten Kontos oder des Kontos, das vorher am Rechner angemeldet war.
 */
export function resolveInitialLocale(documentLang, fallback = 'de') {
    return documentLang || safeGetItem('locale') || fallback;
}

/**
 * Sprache wechseln und fehlende Übersetzungen vorher nachladen: Geladen wird beim Start nur die Startsprache;
 * ohne Nachladen zeigte die neue Sprache rohe Schlüssel mit wörtlichem {0}.
 */
export function createLocaleSwitcher(i18n, loadMessages, documentElement) {
    return async (newLocale) => {
        if (!newLocale) {
            return;
        }
        if (!i18n.global.availableLocales.includes(newLocale)) {
            try {
                i18n.global.setLocaleMessage(newLocale, await loadMessages(newLocale));
            } catch {
                return;
            }
        }
        i18n.global.locale.value = newLocale;
        if (documentElement) {
            documentElement.lang = newLocale;
        }
        safeSetItem('locale', newLocale);
    };
}
