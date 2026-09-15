/**
 * Markdown-Rendering der KI-Antworten (core-ki-bar-ui, Konzept C4 §4, 0.3 §10).
 *
 * Alles, was aus artwork-ai kommt, ist Modellinhalt. Deshalb:
 * - `html: false` — rohes HTML wird escaped, nicht gerendert.
 * - `linkify: false` — nackte URLs werden keine Links.
 * - Link-Filter: nur relative Ziele (`/…`, aber nicht `//…`) werden zu `<a>`; alles andere
 *   erscheint als Text. artwork-ai kann damit nur auf artwork selbst verlinken.
 *
 * Reine Funktionen ohne Vue-Abhaengigkeit, damit sie in tests/Frontend geprueft werden koennen.
 */
import MarkdownIt from 'markdown-it'

/** Relativ heisst: beginnt mit genau einem Schraegstrich. `//host` waere protokollrelativ = extern. */
export function isRelativeHref(href) {
    return typeof href === 'string' && href.startsWith('/') && !href.startsWith('//')
}

/** Ersetzt ein Link-Token-Paar durch Text: Beschriftung, dahinter das Ziel in Klammern. */
function unlink(children, openIndex) {
    const open = children[openIndex]
    const href = open.attrGet('href') ?? ''

    open.type = 'text'
    open.tag = ''
    open.nesting = 0
    open.attrs = null
    open.content = ''

    for (let i = openIndex + 1; i < children.length; i++) {
        if (children[i].type !== 'link_close') {
            continue
        }

        const close = children[i]
        const label = children
            .slice(openIndex + 1, i)
            .map((token) => token.content)
            .join('')

        close.type = 'text'
        close.tag = ''
        close.nesting = 0
        close.attrs = null
        // Das Ziel bleibt sichtbar, wird aber nicht klickbar — sonst ginge die Information
        // stillschweigend verloren.
        close.content = href !== '' && href !== label ? ` (${href})` : ''

        return
    }
}

function aiLinkFilter(state) {
    for (const blockToken of state.tokens) {
        if (blockToken.type !== 'inline' || !blockToken.children) {
            continue
        }

        const children = blockToken.children

        for (let i = 0; i < children.length; i++) {
            if (children[i].type === 'link_open' && !isRelativeHref(children[i].attrGet('href'))) {
                unlink(children, i)
            }
        }
    }
}

const md = new MarkdownIt({ html: false, linkify: false, breaks: true })

md.core.ruler.push('ai_link_filter', aiLinkFilter)

/**
 * Rendert den bisher gestreamten Antworttext. Bewusst ueber den kumulierten Text statt pro Delta —
 * Markdown ist nicht stueckweise renderbar. Die Bar drosselt den Aufruf per requestAnimationFrame.
 */
export function renderAiMarkdown(text) {
    if (typeof text !== 'string' || text === '') {
        return ''
    }

    return md.render(text)
}
