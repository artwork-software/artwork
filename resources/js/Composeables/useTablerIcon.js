// Tabler-Icons ohne Bundler-Beteiligung.
//
// Frueher haben PropertyIcon und IconSelector die Icons per `import('@tabler/icons-vue')`
// und String-Lookup aufgeloest. Fuer Rollup ist das nicht analysierbar, also mussten alle
// 6092 Icon-Module in den Modulgraph — der Build brauchte dadurch ~2,5 GB Heap und starb
// auf kleineren Maschinen am OOM-Killer.
//
// Stattdessen liegen die SVGs als statische Assets unter public/icons/tabler/ (erzeugt von
// scripts/sync-tabler-icons.mjs) und werden zur Laufzeit per fetch geholt. Der Bundler
// sieht davon nichts.
//
// Statische Imports (`import { IconCheck } from '@tabler/icons-vue'`) bleiben erlaubt und
// erwuenscht — das Vite-Plugin `tabler-deep-imports` schreibt sie beim Build auf Deep-Pfade
// um, so dass auch dort nur die tatsaechlich benutzten Module im Graph landen.

import { defineComponent, h, shallowRef } from 'vue'
import { FALLBACK_SLUG, toSlug } from '@/Composeables/tablerIconNames'

export { ALIASES, FALLBACK_SLUG, slugFromExportName, toSlug, toExportName, toDisplayName } from '@/Composeables/tablerIconNames'

const BASE = '/icons/tabler'

const svgCache = new Map()
const compCache = new Map()
let namesPromise = null

function parseSvg(text) {
    const viewBox = text.match(/viewBox="([^"]+)"/)?.[1] ?? '0 0 24 24'
    const inner = text
        .replace(/^[\s\S]*?<svg[^>]*>/, '')
        .replace(/<\/svg>\s*$/, '')
        .trim()

    return { viewBox, inner }
}

function fetchSvg(slug) {
    if (svgCache.has(slug)) return svgCache.get(slug)

    const promise = fetch(`${BASE}/${slug}.svg`)
        .then(r => (r.ok ? r.text() : null))
        .then(text => (text ? parseSvg(text) : null))
        .catch(() => null)

    svgCache.set(slug, promise)
    return promise
}

/**
 * Vue-Komponente fuer ein Icon, gecacht pro Slug.
 *
 * Bewusst synchrones setup() mit einem shallowRef statt async setup(): eine echte
 * Async-Komponente braeuchte ein <Suspense> im Baum, und im v-for-Grid des Pickers gibt es
 * keines. Bis das SVG da ist, rendert die Komponente ein leeres <svg> in korrekter Groesse —
 * damit gibt es keinen Layout-Shift.
 */
export function iconComponent(nameLike) {
    const slug = toSlug(nameLike)
    const cached = compCache.get(slug)
    if (cached) return cached

    const Comp = defineComponent({
        name: `TablerIcon-${slug}`,
        inheritAttrs: false,
        setup(_props, { attrs }) {
            const data = shallowRef(null)

            fetchSvg(slug).then((d) => {
                if (d) {
                    data.value = d
                    return
                }
                if (slug === FALLBACK_SLUG) return
                return fetchSvg(FALLBACK_SLUG).then((f) => { data.value = f })
            })

            return () => h('svg', {
                xmlns: 'http://www.w3.org/2000/svg',
                width: 24,
                height: 24,
                viewBox: data.value?.viewBox ?? '0 0 24 24',
                fill: 'none',
                stroke: 'currentColor',
                'stroke-width': 2,
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                // Aufrufer gewinnt: class, stroke-width, style, aria-* ueberschreiben die Defaults
                ...attrs,
                innerHTML: data.value?.inner ?? '',
            })
        },
    })

    compCache.set(slug, Comp)
    return Comp
}

/** Alle verfuegbaren Slugs — nur der Picker braucht das. */
export function loadIconNames() {
    if (!namesPromise) {
        namesPromise = fetch(`${BASE}/index.json`)
            .then(r => (r.ok ? r.json() : []))
            .catch(() => [])
    }
    return namesPromise
}
