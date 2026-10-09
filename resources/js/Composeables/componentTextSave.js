import { createSerializedSaver } from '@/Helper/serializedSave.js';

const normalize = (value) => value ?? '';

/**
 * Speichern eines Text-Komponentenwerts (TextField, TextArea, Link) beim Verlassen des Felds:
 * - nur bei echter Änderung,
 * - nacheinander (neuester Wert gewinnt, keine parallelen PATCHes),
 * - eigene Antwort über dataListener.saved() übernehmen; ist inzwischen ein neuerer fremder Stand
 *   da (updated_at), wird dieser angezeigt,
 * - Live-Updates überschreiben keine laufende Eingabe; wer auf den alten Text zurücktippt, sieht danach
 *   den inzwischen gespeicherten (ggf. fremden) Stand.
 *
 * @param {{
 *   text: { value: string|null },
 *   isEditing: { value: boolean },
 *   getStoredText: () => string|null,
 *   dataListener: { saved: (value: object|undefined) => boolean },
 *   send: (text: string|null) => Promise<{ data?: { project_value?: object } }>,
 *   afterSaved?: () => void,
 * }} options
 */
export function createComponentTextSave({ text, isEditing, getStoredText, dataListener, send, afterSaved = () => {} }) {
    // zuletzt bestätigt gespeichert / zuletzt zum Speichern übergeben
    let lastSavedText = text.value;
    let lastSubmittedText = text.value;
    // Speichern gescheitert: Eingabe nicht durch Live-Updates ersetzen, bis sie gespeichert ist
    let hasFailedInput = false;

    const isDirty = () => normalize(text.value) !== normalize(lastSubmittedText);

    function showStoredText() {
        text.value = getStoredText();
        lastSavedText = text.value;
        lastSubmittedText = text.value;
    }

    const saver = createSerializedSaver({
        send: async (value) => ({ response: await send(value), value }),
        onIntermediateSaved: ({ response, value }) => {
            if (dataListener.saved(response?.data?.project_value)) {
                lastSavedText = value;
            }
        },
        onSaved: ({ response, value }) => {
            hasFailedInput = false;
            if (dataListener.saved(response?.data?.project_value)) {
                lastSavedText = value;
            }
            // Während des Speicherns weitergetippt: Eingabe behalten, sie wird beim Verlassen gespeichert
            if (!(isEditing.value && normalize(text.value) !== normalize(value))) {
                showStoredText();
            }
            afterSaved();
        },
        onFailed: (error) => {
            console.error('Fehler beim Aktualisieren:', error);
            // Eingabe bleiben lassen (auch gegen Live-Updates); beim nächsten Verlassen erneut versuchen
            lastSubmittedText = lastSavedText;
            hasFailedInput = true;
        },
    });

    /**
     * Beim Verlassen des Felds.
     *
     * @returns {boolean} true, wenn gespeichert wird
     */
    function submit() {
        if (!isDirty()) {
            // Keine Änderung: ein während der Eingabe übersprungenes Live-Update jetzt anzeigen
            if (!saver.isSaving()) {
                showStoredText();
            }
            return false;
        }
        lastSubmittedText = text.value;
        saver.save(text.value);

        return true;
    }

    /**
     * Für den Watcher auf props.data.
     */
    function syncFromStored() {
        // Laufende eigene Speicherung: onSaved gleicht danach ab
        if (saver.isSaving()) {
            return;
        }
        if ((isEditing.value || hasFailedInput) && isDirty()) {
            return;
        }
        showStoredText();
    }

    return { submit, syncFromStored, isDirty, isSaving: saver.isSaving };
}
