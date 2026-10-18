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
    // vue-i18n wirft bei null/undefined („Invalid arguments“) – im axios-Interceptor käme der
    // Aufrufer dann nie an error.response (422-Feldfehler, 409-Rückfragen)
    if (key === null || key === undefined || key === '') {
        return ''
    }
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
 * Meldung für einen Request, dessen Fehler ein Dialog selbst anzeigt (skipErrorToast):
 * erste Validierungsmeldung, sonst Servermeldung, sonst dieselbe Meldung wie der globale
 * Toast (Netzwerk, 403, 404, 5xx), sonst der übersetzte Fallback.
 */
export function failedRequestMessage(error, fallback = 'Failed to save') {
    const fieldErrors = error?.response?.data?.errors
    const firstFieldError = fieldErrors && typeof fieldErrors === 'object'
        ? Object.values(fieldErrors).flat().find(Boolean)
        : null
    if (firstFieldError) {
        return firstFieldError
    }
    const serverMessage = error?.response?.data?.message
    if (serverMessage) {
        return serverMessage
    }
    const requestMessage = error?.isAxiosError ? messageForFailedRequest(error?.response?.status) : null
    return t(requestMessage ?? fallback)
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
    if (isInertiaRequest(error)) {
        return false
    }
    return MUTATING_METHODS.includes(String(config.method ?? '').toLowerCase())
}

/** Inertia-Requests (Header X-Inertia) behandelt das 'invalid'-Event in app.js */
export function isInertiaRequest(error) {
    const headers = error?.config?.headers
    if (!headers) {
        return false
    }

    return Boolean(headers['X-Inertia'] ?? headers.get?.('X-Inertia'))
}

/**
 * Für den axios-Interceptor: abgelaufene Sitzung (401/419) melden. Inertia-Requests nicht –
 * deren 'invalid'-Handler in app.js meldet sich selbst, sonst kämen zwei Alerts.
 */
export function shouldHandleSessionExpiry(error) {
    const status = error?.response?.status
    if (status !== 401 && status !== 419) {
        return false
    }

    return !isInertiaRequest(error)
}
