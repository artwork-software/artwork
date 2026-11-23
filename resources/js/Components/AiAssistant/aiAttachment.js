/**
 * SSE-Event `attachment` (Protokoll 1.2, core-ki-bar-attachment; 1.3 mit Excel und CSV,
 * core-ai-export-formats): Datei zum Herunterladen aus artwork-ai. Die URL ist relativ zur Instanz und
 * wird mit dem Session-Login geladen — alles andere wird verworfen, genau wie absolute Links im
 * Antworttext (aiMarkdown.js).
 */
import { isRelativeHref } from './aiMarkdown.js'

/** MIME-Typ → Endung fuer den Ersatznamen und Icon der Download-Karte. */
const TYPES = {
    'application/pdf': { extension: 'pdf', icon: 'IconFileTypePdf' },
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': { extension: 'xlsx', icon: 'IconFileTypeXls' },
    'text/csv': { extension: 'csv', icon: 'IconFileTypeCsv' },
}

/**
 * @param {unknown} payload
 * @returns {{url: string, filename: string, title: string, mime: string}|null}
 */
export function attachmentFrom(payload) {
    if (payload === null || typeof payload !== 'object') {
        return null
    }

    const { url, filename, title, mime } = /** @type {Record<string, unknown>} */ (payload)

    if (!isRelativeHref(url) || typeof mime !== 'string' || !Object.hasOwn(TYPES, mime)) {
        return null
    }

    const name = typeof filename === 'string' && filename.trim() !== ''
        ? filename.trim()
        : `export.${TYPES[mime].extension}`

    return {
        url: /** @type {string} */ (url),
        filename: name,
        title: typeof title === 'string' && title.trim() !== '' ? title.trim() : name,
        mime,
    }
}

/**
 * @param {string} mime
 * @returns {string} Name des Tabler-Icons fuer die Download-Karte
 */
export function attachmentIcon(mime) {
    return Object.hasOwn(TYPES, mime) ? TYPES[mime].icon : 'IconFileDownload'
}
