#!/usr/bin/env node

import { readdirSync, readFileSync, mkdirSync, copyFileSync, writeFileSync, rmSync } from 'node:fs'
import { join, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'

import { ALIASES, slugFromExportName } from '../resources/js/Composeables/tablerIconNames.js'

const root = join(dirname(fileURLToPath(import.meta.url)), '..')

const COMPONENT_DIR = join(root, 'node_modules/@tabler/icons-vue/dist/esm/icons')
const SVG_DIR = join(root, 'node_modules/@tabler/icons/icons')
// Im Vite-Ausgabeordner, damit die Icons in Docker-Builds automatisch mit dem Bundle
// ins finale Image gelangen (public/icons/... wuerde dort fehlen, weil gitignored).
const DEST = join(root, 'public/build/icons/tabler')

const SIGNATURE = /createVueComponent\("(outline|filled)",\s*"([^"]+)",\s*"([^"]+)"/

function fail(message) {
    console.error(`[sync-tabler-icons] ${message}`)
    process.exit(1)
}

function readIcons() {
    let files
    try {
        files = readdirSync(COMPONENT_DIR)
    } catch {
        fail(`${COMPONENT_DIR} nicht gefunden. "npm install" ausfuehren.`)
    }

    const icons = []
    for (const file of files) {
        if (!file.endsWith('.mjs') || file === 'index.mjs') continue

        const match = readFileSync(join(COMPONENT_DIR, file), 'utf8').match(SIGNATURE)
        if (!match) {
            fail(`${file}: keine createVueComponent-Signatur gefunden. `
                + `Ausgabeformat von @tabler/icons-vue hat sich geaendert.`)
        }

        const [, type, slug, name] = match
        icons.push({ type, slug, name: `Icon${name}` })
    }

    if (icons.length === 0) fail(`Keine Icon-Module in ${COMPONENT_DIR}.`)

    return icons
}

function assertAliases(icons) {
    const expected = new Map()
    for (const { name, slug } of icons) {
        if (slugFromExportName(name) !== slug) expected.set(name, slug)
    }

    const missing = [...expected].filter(([name, slug]) => ALIASES[name] !== slug)
    const stale = Object.keys(ALIASES).filter(name => !expected.has(name))

    if (missing.length === 0 && stale.length === 0) return

    fail([
        'ALIASES in resources/js/Composeables/tablerIconNames.js weicht von @tabler/icons-vue ab:',
        ...missing.map(([name, slug]) => `  erwartet: ${name}: '${slug}',`),
        ...stale.map(name => `  entfallen: ${name}: '${ALIASES[name]}',`),
    ].join('\n'))
}

function syncSvgs(icons) {
    rmSync(DEST, { recursive: true, force: true })
    mkdirSync(DEST, { recursive: true })

    for (const { type, slug } of icons) {
        // Der Slug der filled-Variante traegt das Suffix, die Datei im Paket nicht:
        // Slug "alert-square-filled" -> filled/alert-square.svg
        const base = type === 'filled' ? slug.replace(/-filled$/, '') : slug

        try {
            copyFileSync(join(SVG_DIR, type, `${base}.svg`), join(DEST, `${slug}.svg`))
        } catch {
            fail(`SVG fehlt: ${type}/${base}.svg (Slug "${slug}")`)
        }
    }

    const slugs = icons.map(i => i.slug).sort()
    writeFileSync(join(DEST, 'index.json'), JSON.stringify(slugs))

    return slugs.length
}

const icons = readIcons()
assertAliases(icons)
const count = syncSvgs(icons)

console.log(`[sync-tabler-icons] ${count} Icons nach public/build/icons/tabler/ synchronisiert`)
