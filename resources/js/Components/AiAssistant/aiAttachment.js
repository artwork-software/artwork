/**
 * SSE-Event `attachment` (Protokoll 1.2, core-ki-bar-attachment): Datei zum Herunterladen, derzeit
 * PDF-Exporte aus artwork-ai. Die URL ist relativ zur Instanz und wird mit dem Session-Login geladen —
 * alles andere wird verworfen, genau wie absolute Links im Antworttext (aiMarkdown.js).
 */
import { isRelativeHref } from './aiMarkdown.js'

const MIME_TYPES = ['application/pdf']

/**
 * @param {unknown} payload
 * @returns {{url: string, filename: string, title: string, mime: string}|null}
 */
export function attachmentFrom(payload) {
    if (payload === null || typeof payload !== 'object') {
        return null
    }

    const { url, filename, title, mime } = /** @type {Record<string, unknown>} */ (payload)

    if (!isRelativeHref(url) || !MIME_TYPES.includes(/** @type {string} */ (mime))) {
        return null
    }

    const name = typeof filename === 'string' && filename.trim() !== '' ? filename.trim() : 'export.pdf'

    return {
        url: /** @type {string} */ (url),
        filename: name,
        title: typeof title === 'string' && title.trim() !== '' ? title.trim() : name,
        mime: /** @type {string} */ (mime),
    }
}
