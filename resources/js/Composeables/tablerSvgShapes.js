// Zerlegt ein Tabler-SVG in eine Liste einfacher Formen, die per h() gerendert werden.
//
// Frueher landete der SVG-Inhalt per innerHTML im DOM. Zeigte der Pfad (ueber einen
// manipulierten Icon-Namen) auf eine andere Datei, liefen darin eingebettete Skripte mit.
// Jetzt kommen nur noch bekannte Formelemente mit Geometrie- und Stil-Attributen durch;
// Event-Handler, <script>, <foreignObject>, Links usw. fallen weg.

const ALLOWED_TAGS = new Set(['path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse'])

const ALLOWED_ATTRIBUTES = new Set([
    'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'width', 'height', 'points',
    'fill', 'fill-rule', 'clip-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
    'opacity', 'fill-opacity', 'stroke-opacity', 'transform',
])

const SHAPE_PATTERN = /<([a-zA-Z]+)\b([^<>]*?)\/?>/g
const ATTRIBUTE_PATTERN = /([a-zA-Z-]+)\s*=\s*"([^"]*)"/g

/**
 * @param {string} text Rohes SVG
 * @returns {{viewBox: string, shapes: Array<{tag: string, attrs: Record<string, string>}>}}
 */
export function parseSvgShapes(text) {
    const source = String(text ?? '')
    const viewBoxMatch = source.match(/viewBox="([0-9.\s-]+)"/)
    const body = source.replace(/^[\s\S]*?<svg\b[^>]*>/, '')

    const shapes = []
    for (const [, rawTag, rawAttributes] of body.matchAll(SHAPE_PATTERN)) {
        const tag = rawTag.toLowerCase()
        if (!ALLOWED_TAGS.has(tag)) continue

        const attrs = {}
        for (const [, rawName, value] of rawAttributes.matchAll(ATTRIBUTE_PATTERN)) {
            const name = rawName.toLowerCase()
            if (ALLOWED_ATTRIBUTES.has(name) && !/url\s*\(|javascript:/i.test(value)) {
                attrs[name] = value
            }
        }
        shapes.push({ tag, attrs })
    }

    return { viewBox: viewBoxMatch?.[1] ?? '0 0 24 24', shapes }
}
