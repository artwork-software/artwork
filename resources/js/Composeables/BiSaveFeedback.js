import { inject, provide, ref } from 'vue';
import { messageForFailedRequest, t } from '@/Helper/appToast.js';

const BI_SAVE_FEEDBACK_KEY = Symbol('biSaveFeedback');

/**
 * axios-Config für BI-Speichervorgänge: Fehler zeigt der BI-Speicherindikator bzw. das Formular selbst,
 * der globale Fehler-Toast käme sonst doppelt dazu.
 */
export const BI_REQUEST_CONFIG = Object.freeze({ skipErrorToast: true });

/**
 * Zentrales Speicher-Feedback für den BI-Projekt-Tab: alle Sektionen wickeln
 * ihre Axios-Saves über run() ab, der Tab zeigt EINEN Statusindikator
 * (BiSaveIndicator). run() liefert true/false statt zu werfen, damit Aufrufer
 * optimistische UI-Änderungen bei Fehlern zurückrollen können.
 */
/**
 * Lesbarer Grund aus einem Axios-Fehler: bei 422 die erste Validierungsmeldung, bei
 * Verbindungsfehler/403/404/5xx der übersetzte Standardgrund (wie der globale Fehler-Toast,
 * den BI_REQUEST_CONFIG unterdrückt – die Server-Texte dort sind englisch), sonst die
 * Server-Message, sonst null (der Indikator zeigt dann den Standardtext).
 */
export function extractSaveErrorMessage(error) {
    if (error?.code === 'ERR_CANCELED') return null;
    const data = error?.response?.data;
    if (data?.errors && typeof data.errors === 'object') {
        const first = Object.values(data.errors).flat().find(Boolean);
        if (first) return String(first);
    }
    // Nur echte Request-Fehler: ein Programmfehler im Speicherablauf ist kein Verbindungsfehler
    const isRequestError = Boolean(error?.isAxiosError || error?.response || error?.request);
    const reason = isRequestError ? messageForFailedRequest(error?.response?.status) : null;
    if (reason) return t(reason);
    if (typeof data?.message === 'string' && data.message.trim() !== '') {
        return data.message;
    }
    return null;
}

export function createBiSaveFeedback() {
    const status = ref('idle'); // idle | saving | saved | error
    // Grund des letzten Fehlers (z. B. Validierungstext) — null = generischer Text
    const errorMessage = ref(null);
    let resetTimer = null;
    let pending = 0;

    const dismiss = () => {
        if (status.value === 'error') {
            status.value = 'idle';
            errorMessage.value = null;
        }
    };

    const run = async (fn) => {
        clearTimeout(resetTimer);
        pending++;
        status.value = 'saving';
        try {
            await fn();
            pending--;
            if (pending <= 0 && status.value === 'saving') {
                status.value = 'saved';
                errorMessage.value = null;
                resetTimer = setTimeout(() => {
                    if (status.value === 'saved') {
                        status.value = 'idle';
                    }
                }, 2500);
            }
            return true;
        } catch (error) {
            pending--;
            // eslint-disable-next-line no-console
            console.error('BI save failed', error);
            errorMessage.value = extractSaveErrorMessage(error);
            status.value = 'error';
            return false;
        }
    };

    const feedback = { status, errorMessage, run, dismiss };
    return feedback;
}

export function provideBiSaveFeedback() {
    const feedback = createBiSaveFeedback();
    provide(BI_SAVE_FEEDBACK_KEY, feedback);
    return feedback;
}

// Fallback auf eine lokale Instanz, falls eine Sektion außerhalb des BI-Tabs
// gerendert wird (z.B. Print-Layout-Kopien) — Saves funktionieren dann weiter,
// nur ohne gemeinsamen Indikator.
export function useBiSaveFeedback() {
    return inject(BI_SAVE_FEEDBACK_KEY, createBiSaveFeedback, true);
}
