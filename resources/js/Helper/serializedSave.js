/**
 * Speichert nacheinander statt parallel: Während eine Speicherung läuft, wird nur der jeweils neueste
 * Wert vorgemerkt und danach gesendet. So endet der Server sicher beim zuletzt gewählten Stand, und eine
 * ältere, später eintreffende Antwort kann keinen neueren Stand überschreiben (z.B. Doppelklick auf
 * eine Checkbox).
 *
 * - onSaved(result): die letzte Speicherung einer Folge war erfolgreich.
 * - onIntermediateSaved(result): eine Zwischenspeicherung war erfolgreich, ein neuerer Wert folgt noch.
 *   Ihr Ergebnis steht bereits in der Datenbank und muss übernommen werden (ohne die Eingabe zu ändern).
 * - onFailed(error, lastSuccess): die letzte Speicherung ist gescheitert; lastSuccess ist das Ergebnis
 *   der letzten ERFOLGREICHEN Speicherung dieser Folge (oder undefined) – darauf zurücksetzen, nicht auf
 *   den Stand vor der Folge.
 *
 * Gescheiterte Zwischenspeicherungen werden nicht gemeldet: der neuere Wert wird ohnehin gesendet.
 *
 * @template T, R
 * @param {{
 *   send: (value: T) => Promise<R>,
 *   onSaved?: (result: R) => void,
 *   onIntermediateSaved?: (result: R) => void,
 *   onFailed?: (error: unknown, lastSuccess: R|undefined) => void,
 * }} handlers
 */
export function createSerializedSaver({
    send,
    onSaved = () => {},
    onIntermediateSaved = () => {},
    onFailed = () => {},
}) {
    let inFlight = false;
    let hasQueued = false;
    let queuedValue;

    async function save(value) {
        if (inFlight) {
            hasQueued = true;
            queuedValue = value;
            return;
        }

        inFlight = true;
        let current = value;
        let lastSuccess;
        try {
            for (;;) {
                try {
                    const result = await send(current);
                    lastSuccess = result;
                    if (hasQueued) {
                        onIntermediateSaved(result);
                    } else {
                        onSaved(result);
                    }
                } catch (error) {
                    if (!hasQueued) {
                        onFailed(error, lastSuccess);
                    }
                }
                if (!hasQueued) {
                    break;
                }
                hasQueued = false;
                current = queuedValue;
            }
        } finally {
            inFlight = false;
        }
    }

    return {
        save,
        isSaving: () => inFlight,
    };
}
