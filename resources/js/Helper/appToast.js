/**
 * Globale Toast-Meldungen außerhalb von Komponenten (axios-Interceptor, Inertia-Events,
 * $toast). Angezeigt werden sie vom Flash-Toast in AppLayout, der sich hier registriert.
 * Gleiche Meldungen innerhalb kurzer Zeit erscheinen nur einmal (z. B. mehrere parallele
 * Requests, die am selben Serverfehler scheitern).
 */

const listeners = new Set()
const DEDUPE_MS = 3000
let lastToast = {key: '', at: 0}
let translate = (key) => key

export function setAppToastTranslator(translator) {
    translate = translator
}

export function t(key) {
    return translate(key)
}

export function showAppToast(type, message) {
    if (!message) {
        return
    }
    const key = `${type}|${message}`
    const now = Date.now()
    if (key === lastToast.key && now - lastToast.at < DEDUPE_MS) {
        return
    }
    lastToast = {key, at: now}
    listeners.forEach((listener) => listener(type, message))
}

export function onAppToast(listener) {
    listeners.add(listener)
    return () => listeners.delete(listener)
}

const MUTATING_METHODS = ['post', 'put', 'patch', 'delete']

/**
 * Meldung für eine fehlgeschlagene Anfrage oder null, wenn keine globale Meldung passt
 * (Validierungsfehler zeigt das Formular selbst, 401/419/409/413 haben eigene Behandlung).
 */
export function messageForFailedRequest(status) {
    if (status === undefined || status === null || status === 0) {
        return 'The connection to the server failed. Please check your network and try again.'
    }
    if (status === 403) {
        return 'You are not allowed to perform this action.'
    }
    if (status === 404) {
        return 'The entry no longer exists. Please reload the page.'
    }
    if (status >= 500) {
        return 'The action could not be completed. Please try again.'
    }
    return null
}

/**
 * Für axios: nur schreibende Requests melden – Lesezugriffe im Hintergrund (Tooltips,
 * Nachladen) haben eigene Zustände und würden sonst zu viele Meldungen erzeugen.
 * Requests mit `skipErrorToast: true` in der Config zeigen ihren Fehler selbst an;
 * Inertia-Requests laufen über das 'invalid'-Event in app.js.
 */
export function shouldToastAxiosError(error) {
    const config = error?.config ?? {}
    if (config.skipErrorToast || error?.code === 'ERR_CANCELED') {
        return false
    }
    if (config.headers?.['X-Inertia']) {
        return false
    }
    return MUTATING_METHODS.includes(String(config.method ?? '').toLowerCase())
}
