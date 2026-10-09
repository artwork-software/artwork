import { stopListeningOnPrivateChannel } from './echoChannel.js';

export { createLatestRequestTracker } from '../../Helper/latestRequest.js';

/**
 * Hört auf Dokument-Änderungen eines Projekts (Kanal project.{id}). Das Broadcast trägt nur Ids
 * ({ id, tab_id, project_id }) – Dateiname und Speicherort gehen nicht an alle Projektsichtigen.
 * Die betroffene Liste lädt die Komponente über ihren geprüften Endpunkt neu (onChange), damit
 * nur Dateien erscheinen, die die Person sehen darf (Tab-Sicht, Budget-Freigabe).
 *
 * Mehrere Events kurz hintereinander (z. B. Upload mehrerer Dateien) werden zu EINEM Aufruf
 * gebündelt; isRelevant filtert Events heraus, die die Liste nicht betreffen (z. B. andere Tabs).
 *
 * @param {number} projectId
 * @param {(documents: Array<{ id: number, tab_id: number|null, project_id: number }|null>) => void} onChange
 * @param {{ debounceMs?: number, isRelevant?: (document: { id: number, tab_id: number|null }) => boolean }} [options]
 */
export function useProjectDocumentListener(projectId, onChange, { debounceMs = 300, isRelevant = () => true } = {}) {
    const channelName = 'project.' + projectId;
    let pendingDocuments = [];
    let timer = null;

    const flush = () => {
        const documents = pendingDocuments;
        pendingDocuments = [];
        timer = null;
        onChange(documents);
    };

    const handleChange = (data) => {
        const document = data?.document ?? null;
        if (document !== null && !isRelevant(document)) {
            return;
        }

        pendingDocuments.push(document);
        clearTimeout(timer);
        timer = setTimeout(flush, debounceMs);
    };

    function init() {
        Echo.private(channelName)
            .listen('.document.add', handleChange)
            .listen('.document.delete', handleChange);
    }

    function stop() {
        clearTimeout(timer);
        timer = null;
        pendingDocuments = [];

        // Nicht Echo.private(): das würde einen bereits verlassenen Kanal neu abonnieren
        stopListeningOnPrivateChannel(channelName, '.document.add', handleChange);
        stopListeningOnPrivateChannel(channelName, '.document.delete', handleChange);
    }

    return { init, stop };
}

/**
 * Für Dokumente-Komponenten mit fester Tab-Auswahl: nur Events aus diesen Tabs sind relevant.
 * Ohne bekannte Auswahl (kein Array) bleibt jedes Event relevant.
 *
 * @param {unknown} scope
 * @returns {(document: { tab_id: number|null }) => boolean}
 */
export function isDocumentInScope(scope) {
    if (!Array.isArray(scope)) {
        return () => true;
    }

    const tabIds = scope.map(Number);

    return (document) => document.tab_id !== null && document.tab_id !== undefined
        && tabIds.includes(Number(document.tab_id));
}
