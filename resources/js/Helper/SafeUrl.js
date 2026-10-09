/**
 * Nur http(s)-Adressen als Link-Ziel zulassen — Werte aus CRM-Feldern können von Externen stammen,
 * javascript:- oder data:-URLs dürfen nie klickbar werden.
 */
export function isSafeHttpUrl(value) {
    return typeof value === 'string' && /^https?:\/\//i.test(value.trim())
}

/**
 * Link-Ziel für frei eingegebene Adressen (Link-/Linklisten-Komponenten) oder null = nicht klickbar:
 *  - http(s)://, mailto:, tel: bleiben unverändert
 *  - relative Pfade mit genau einem "/" ("/projects/12") bleiben auf derselben Origin
 *  - ohne Schema wird https:// ergänzt („www.beispiel.de“, auch "//host/pfad")
 *  - alle anderen Schemata (javascript:, data:, vbscript:, file:, smb:, teams: …) und UNC-Pfade ("\\server\…")
 *    sind nicht klickbar – Browser öffnen sie aus https-Seiten ohnehin nicht zuverlässig
 * Browser entfernen Tabs/Zeilenumbrüche in URLs und lesen "\" wie "/" – geprüft wird daher die kompakte Form.
 */
export function safeLinkTarget(value) {
    const url = typeof value === 'string' ? value.trim() : ''
    if (!url) return null
    if (isSafeHttpUrl(url) || /^(mailto|tel):/i.test(url)) return url

    const compact = url.replace(/[\x00-\x20]+/g, '')
    // Genau ein "/" am Anfang: Pfad auf derselben Origin ("/\" und "//" wären fremde Hosts)
    if (/^\/(?![/\\])/.test(compact)) return url
    // UNC-Pfade und "/\host"-Varianten nie zu https umbauen
    if (/^(\\|\/\\)/.test(compact)) return null
    // Schema-relative Adresse "//host/pfad" → https
    if (compact.startsWith('//')) return `https://${url.replace(/^[\s/]+/, '')}`
    // Andere Schemata nie als Ziel; "host:port" ohne Schema bleibt erlaubt
    if (/^[a-z][a-z0-9+.-]*:(?!\d)/i.test(compact)) return null
    return `https://${url}`
}

/**
 * Wie safeLinkTarget, nicht klickbare Werte werden zu "#".
 */
export function safeLinkHref(value) {
    return safeLinkTarget(value) ?? '#'
}
