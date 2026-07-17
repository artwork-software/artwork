import { inject, provide, ref } from 'vue';

const BI_SAVE_FEEDBACK_KEY = Symbol('biSaveFeedback');

/**
 * Zentrales Speicher-Feedback für den BI-Projekt-Tab: alle Sektionen wickeln
 * ihre Axios-Saves über run() ab, der Tab zeigt EINEN Statusindikator
 * (BiSaveIndicator). run() liefert true/false statt zu werfen, damit Aufrufer
 * optimistische UI-Änderungen bei Fehlern zurückrollen können.
 */
export function createBiSaveFeedback() {
    const status = ref('idle'); // idle | saving | saved | error
    let resetTimer = null;
    let pending = 0;

    const run = async (fn) => {
        clearTimeout(resetTimer);
        pending++;
        status.value = 'saving';
        try {
            await fn();
            pending--;
            if (pending <= 0 && status.value === 'saving') {
                status.value = 'saved';
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
            status.value = 'error';
            return false;
        }
    };

    const feedback = { status, run };
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
