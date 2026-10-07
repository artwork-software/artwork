/**
 * Nur http(s)-Adressen als Link-Ziel zulassen — Werte aus CRM-Feldern können von Externen stammen,
 * javascript:- oder data:-URLs dürfen nie klickbar werden.
 */
export function isSafeHttpUrl(value) {
    return typeof value === 'string' && /^https?:\/\//i.test(value.trim())
}

/**
 * Link-Ziel für frei eingegebene Adressen (Link-/Linklisten-Komponenten): ohne Schema wird https://
 * ergänzt („www.beispiel.de“), mailto:/tel: bleiben, alle anderen Schemata werden zu "#".
 */
export function safeLinkHref(value) {
    const url = typeof value === 'string' ? value.trim() : ''
    if (!url) return '#'
    if (isSafeHttpUrl(url) || /^(mailto|tel):/i.test(url)) return url
    // Andere Schemata (javascript:, data: …) nie als Ziel; "host:port" ohne Schema bleibt erlaubt
    if (/^[a-z][a-z0-9+.-]*:(?!\d)/i.test(url.replace(/[\x00-\x20]+/g, ''))) return '#'
    return `https://${url}`
}
