/**
 * Nur http(s)-Adressen als Link-Ziel zulassen — Werte aus CRM-Feldern können von Externen stammen,
 * javascript:- oder data:-URLs dürfen nie klickbar werden.
 */
export function isSafeHttpUrl(value) {
    return typeof value === 'string' && /^https?:\/\//i.test(value.trim())
}
