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
 * Aeltere Export-Namen, die @tabler/icons-vue nur noch als Alias fuehrt (z. B. "Icon123" fuer
 * "IconNumber123"). Sie stehen noch in der Datenbank (gespeicherte Icons von Tagesdiensten,
 * Gruppen ...), haben aber keine eigene SVG-Datei: ohne diese Liste lief der Abruf ins Leere
 * und das Icon blieb unsichtbar. Bewusst getrennt von ALIASES, damit die Pruefung des
 * Sync-Skripts unveraendert bleibt.
 */
export const LEGACY_ALIASES = {
    Icon123: 'number-123',
    Icon12Hours: 'hours-12',
    Icon24Hours: 'hours-24',
    Icon2fa: 'auth-2fa',
    Icon360: 'view-360-arrow',
    Icon360View: 'view-360-number',
    Icon3dCubeSphere: 'cube-3d-sphere',
    Icon3dCubeSphereOff: 'cube-3d-sphere-off',
    Icon3dRotate: 'rotate-3d',
    IconBoxSeam: 'package',
    IconBrandAdobePremier: 'brand-adobe-premiere',
    IconCircle0: 'circle-number-0',
    IconCircle1: 'circle-number-1',
    IconCircle2: 'circle-number-2',
    IconCircle3: 'circle-number-3',
    IconCircle4: 'circle-number-4',
    IconCircle5: 'circle-number-5',
    IconCircle6: 'circle-number-6',
    IconCircle7: 'circle-number-7',
    IconCircle8: 'circle-number-8',
    IconCircle9: 'circle-number-9',
    IconCircleDashedLetterLetterV: 'circle-dashed-letter-v',
    IconCodeAsterix: 'code-asterisk',
    IconDiscount2: 'rosette-discount',
    IconDiscount2Off: 'rosette-discount-off',
    IconDiscountCheck: 'rosette-discount-check',
    IconDiscountCheckFilled: 'rosette-discount-check-filled',
    IconHandRock: 'hand-love-you',
    IconHexagon0: 'hexagon-number-0',
    IconHexagon1: 'hexagon-number-1',
    IconHexagon2: 'hexagon-number-2',
    IconHexagon3: 'hexagon-number-3',
    IconHexagon4: 'hexagon-number-4',
    IconHexagon5: 'hexagon-number-5',
    IconHexagon6: 'hexagon-number-6',
    IconHexagon7: 'hexagon-number-7',
    IconHexagon8: 'hexagon-number-8',
    IconHexagon9: 'hexagon-number-9',
    IconKering: 'kerning',
    IconMessageCircle2: 'message-circle',
    IconMessageCircle2Filled: 'message-circle-filled',
    IconMoodSuprised: 'mood-surprised',
    IconSeeding: 'seedling',
    IconSeedingFilled: 'seedling-filled',
    IconSeedingOff: 'seedling-off',
    IconShiJumping: 'ski-jumping',
    IconSortDeacendingSmallBig: 'sort-descending-small-big',
    IconSquare0: 'square-number-0',
    IconSquare1: 'square-number-1',
    IconSquare2: 'square-number-2',
    IconSquare3: 'square-number-3',
    IconSquare4: 'square-number-4',
    IconSquare5: 'square-number-5',
    IconSquare6: 'square-number-6',
    IconSquare7: 'square-number-7',
    IconSquare8: 'square-number-8',
    IconSquare9: 'square-number-9',
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
        return safeSlug(ALIASES[name] ?? LEGACY_ALIASES[name] ?? slugFromExportName(name))
    }

    // Bereits ein Slug, ggf. mit "icon-"-Prefix
    return safeSlug(s.replace(/^icon[-_]+/i, '').replace(/_/g, '-').toLowerCase())
}

/**
 * Der Slug wird zum Dateipfad (`/build/icons/tabler/<slug>.svg`). Alles ausser a-z, 0-9 und
 * Bindestrich faellt auf das Ersatz-Icon zurueck — sonst koennte ein gespeicherter Name wie
 * `../../storage/...` auf beliebige hochgeladene Dateien zeigen.
 */
export function safeSlug(slug) {
    return /^[a-z0-9-]{1,64}$/.test(slug) ? slug : FALLBACK_SLUG
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
