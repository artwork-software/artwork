import { ref } from 'vue'
import axios from 'axios'

/**
 * Zahl der offenen eingehenden Anfragen (Raumbelegung und Terminüberprüfung) für Seitenmenü und Reiter.
 * Ein gemeinsamer Wert für die ganze Sitzung: das Seitenmenü lädt ihn höchstens einmal je Minute nach,
 * die Seite "Eingehende Anfragen" setzt ihn direkt aus ihren Listen.
 */
export const incomingRequestCount = ref(0)

const MAX_AGE_MS = 60_000
let lastLoadedAt = 0
let requestSeq = 0

/**
 * @param {number} loadedAt Zeitpunkt des letzten Ladens (0 = nie)
 * @param {number} now
 * @param {boolean} force
 * @param {number} maxAgeMs
 * @returns {boolean}
 */
export function isCountStale(loadedAt, now, force = false, maxAgeMs = MAX_AGE_MS) {
    return force || loadedAt === 0 || now - loadedAt >= maxAgeMs
}

export function setIncomingRequestCount(count) {
    // eine noch laufende Abfrage darf den genaueren Wert der Seite nicht überschreiben
    requestSeq++
    lastLoadedAt = Date.now()
    incomingRequestCount.value = Math.max(0, Number(count) || 0)
}

export function loadIncomingRequestCount({ force = false } = {}) {
    if (!isCountStale(lastLoadedAt, Date.now(), force)) {
        return
    }

    lastLoadedAt = Date.now()
    const request = ++requestSeq
    axios.get(route('event-verifications.open-count'), { skipErrorToast: true })
        .then((response) => {
            if (request === requestSeq) {
                incomingRequestCount.value = Math.max(0, Number(response.data?.count) || 0)
            }
        })
        .catch(() => {})
}
