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

/**
 * Wählbare Sprachen aus dem geteilten Prop "supportedLocales" (Server: app.supported_locales).
 * Jede Sprache trägt ihren Eigennamen ("Deutsch", "Nederlands"), unabhängig von der aktiven Oberfläche.
 */
export function supportedLocaleOptions(sharedLocales, fallbackLocale = 'en') {
    const options = (Array.isArray(sharedLocales) ? sharedLocales : [])
        .filter((locale) => locale && typeof locale.code === 'string' && locale.code !== '')
        .map((locale) => ({id: locale.code, name: locale.name || locale.code}));

    return options.length > 0 ? options : [{id: fallbackLocale, name: fallbackLocale}];
}

/**
 * Rückfallsprache im Hintergrund nachladen. Die Schlüssel sind englische Ausgangstexte, ohne geladene
 * Rückfalltexte zeigte eine fehlende Übersetzung aber Platzhalter wie {count} wörtlich an.
 */
export async function loadFallbackMessages(i18n, loadMessages, fallbackLocale = 'en') {
    if (i18n.global.availableLocales.includes(fallbackLocale)) {
        return;
    }
    try {
        i18n.global.setLocaleMessage(fallbackLocale, await loadMessages(fallbackLocale));
    } catch {
        // Ohne Rückfalltexte bleibt der Schlüssel (englischer Ausgangstext) sichtbar
    }
}
