import { inject, provide, ref } from 'vue';

const BI_SAVE_FEEDBACK_KEY = Symbol('biSaveFeedback');

/**
 * Zentrales Speicher-Feedback für den BI-Projekt-Tab: alle Sektionen wickeln
 * ihre Axios-Saves über run() ab, der Tab zeigt EINEN Statusindikator
 * (BiSaveIndicator). run() liefert true/false statt zu werfen, damit Aufrufer
 * optimistische UI-Änderungen bei Fehlern zurückrollen können.
 */
/**
 * Lesbarer Grund aus einem Axios-Fehler: bei 422 die erste Validierungsmeldung,
 * sonst die Server-Message, sonst null (der Indikator zeigt dann den Standardtext).
 */
export function extractSaveErrorMessage(error) {
    const data = error?.response?.data;
    if (!data) return null;
    if (data.errors && typeof data.errors === 'object') {
        const first = Object.values(data.errors).flat().find(Boolean);
        if (first) return String(first);
    }
    if (typeof data.message === 'string' && data.message.trim() !== '') {
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
