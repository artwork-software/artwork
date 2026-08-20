// Namensaufloesung fuer Tabler-Icons: Export-Name <-> Slug.
//
// Bewusst frei von vue-Imports, damit scripts/sync-tabler-icons.mjs dieses Modul unter node
// laden und die ALIASES-Liste gegen das installierte @tabler/icons-vue pruefen kann. Wuerde
// das Skript die Regeln duplizieren, koennten beide Seiten gemeinsam driften und der Check
// waere wertlos.

export const FALLBACK_SLUG = 'tag'

/**
 * Export-Namen, deren Slug sich nicht mechanisch aus dem Namen ableiten laesst.
 * Generiert aus @tabler/icons-vue; scripts/sync-tabler-icons.mjs verifiziert bei jedem
 * Sync, dass diese Liste noch exakt der Menge der Sonderfaelle entspricht, und bricht
 * sonst ab. Ein Tabler-Update kann die Aufloesung damit nicht still kaputtmachen.
 */
export const ALIASES = {
    IconAB: 'a-b',
    IconAB2: 'a-b-2',
    IconABOff: 'a-b-off',
    IconBrandAo3: 'brand-ao3',
    IconBrandAuth0: 'brand-auth0',
    IconBrandCss3: 'brand-css3',
    IconBrandD3: 'brand-d3',
    IconBrandFlightradar24: 'brand-flightradar24',
    IconBrandHtml5: 'brand-html5',
    IconCrop11: 'crop-1-1',
    IconCrop11Filled: 'crop-1-1-filled',
    IconCrop169: 'crop-16-9',
    IconCrop169Filled: 'crop-16-9-filled',
    IconCrop32: 'crop-3-2',
    IconCrop32Filled: 'crop-3-2-filled',
    IconCrop54: 'crop-5-4',
    IconCrop54Filled: 'crop-5-4-filled',
    IconCrop75: 'crop-7-5',
    IconCrop75Filled: 'crop-7-5-filled',
    IconGrid3x3: 'grid-3x3',
    IconGrid4x4: 'grid-4x4',
    IconMultiplier05x: 'multiplier-0-5x',
    IconMultiplier15x: 'multiplier-1-5x',
    IconSort09: 'sort-0-9',
    IconSort90: 'sort-9-0',
    IconSortAZ: 'sort-a-z',
    IconSortZA: 'sort-z-a',
    IconSquareF0: 'square-f0',
    IconSquareF0Filled: 'square-f0-filled',
    IconSquareF1: 'square-f1',
    IconSquareF1Filled: 'square-f1-filled',
    IconSquareF2: 'square-f2',
    IconSquareF2Filled: 'square-f2-filled',
    IconSquareF3: 'square-f3',
    IconSquareF3Filled: 'square-f3-filled',
    IconSquareF4: 'square-f4',
    IconSquareF4Filled: 'square-f4-filled',
    IconSquareF5: 'square-f5',
    IconSquareF5Filled: 'square-f5-filled',
    IconSquareF6: 'square-f6',
    IconSquareF6Filled: 'square-f6-filled',
    IconSquareF7: 'square-f7',
    IconSquareF7Filled: 'square-f7-filled',
    IconSquareF8: 'square-f8',
    IconSquareF8Filled: 'square-f8-filled',
    IconSquareF9: 'square-f9',
    IconSquareF9Filled: 'square-f9-filled',
}

/**
 * Der mechanische Teil der Namensaufloesung. Getrennt exportiert, damit das Sync-Skript
 * exakt dieselbe Regel anwenden kann, wenn es die ALIASES-Liste gegenprueft.
 */
export function slugFromExportName(name) {
    return name
        .replace(/^Icon/, '')
        .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
        .replace(/([A-Z])([A-Z][a-z])/g, '$1-$2')
        .replace(/([a-zA-Z])(\d)/g, '$1-$2')
        .toLowerCase()
}

/**
 * Normalisiert alles, was in der DB oder im Template stehen kann, auf einen Slug:
 * "IconHome2" | "home-2" | "icon-home-2" | "icon_home_2" -> "home-2"
 */
export function toSlug(input) {
    if (input === null || input === undefined) return FALLBACK_SLUG

    const s = String(input).trim()
    if (!s) return FALLBACK_SLUG

    // Export-Name aus DB / PHP-Seedern ("IconHome2"), oder derselbe Name ohne Prefix
    // ("Home2") — letzteres hat die frueher hier verwendete Aufloesung ebenfalls akzeptiert.
    if (/^[A-Z]/.test(s)) {
        const name = /^Icon[A-Z0-9]/.test(s) ? s : `Icon${s}`
        return ALIASES[name] ?? slugFromExportName(name)
    }

    // Bereits ein Slug, ggf. mit "icon-"-Prefix
    return s.replace(/^icon[-_]+/i, '').replace(/_/g, '-').toLowerCase()
}

/**
 * Gegenrichtung: "home-2" -> "IconHome2". Jedes Slug-Segment wird kapitalisiert; diese Regel
 * stimmt fuer alle 6092 Icons ohne Ausnahme. Wird gebraucht, damit der IconSelector weiterhin
 * Export-Namen emittiert und das gespeicherte Datenformat unveraendert bleibt.
 */
export function toExportName(slug) {
    return 'Icon' + String(slug)
        .split('-')
        .filter(Boolean)
        .map(w => w.charAt(0).toUpperCase() + w.slice(1))
        .join('')
}

/** "home-2" -> "Home 2" */
export function toDisplayName(slug) {
    return String(slug)
        .split('-')
        .filter(Boolean)
        .map(w => w.charAt(0).toUpperCase() + w.slice(1))
        .join(' ')
}
