import axios from 'axios';
import { stopListeningOnPrivateChannel } from './echoChannel.js';
import { publishComponentValue, subscribeComponentValue } from './projectComponentValueSync.js';

const EVENT_NAME = '.data.updated';

async function fetchComponentValue(projectId, componentId) {
    const { data } = await axios.get(
        route('project.tab.component.value', { project: projectId, component: componentId })
    );

    return data?.project_value ?? null;
}

/**
 * Vergleich über updated_at: 'older' | 'same' | 'newer', oder null, wenn ein Zeitstempel fehlt.
 */
export function compareUpdatedAt(displayed, offered) {
    const displayedAt = Date.parse(displayed?.updated_at ?? '');
    const offeredAt = Date.parse(offered?.updated_at ?? '');
    if (Number.isNaN(displayedAt) || Number.isNaN(offeredAt)) {
        return null;
    }
    if (offeredAt < displayedAt) {
        return 'older';
    }

    return offeredAt === displayedAt ? 'same' : 'newer';
}

/**
 * true, wenn der angezeigte Wert nachweislich neuer ist als der angebotene (beide mit updated_at).
 */
export function isOlderThanDisplayed(displayed, offered) {
    return compareUpdatedAt(displayed, offered) === 'older';
}

/**
 * Übernimmt einen gespeicherten Wert (Format von project_value) in die Komponentendaten.
 */
export function applyComponentValue(componentData, value) {
    if (!value) {
        return;
    }
    // Eigene Kopie je Instanz: sonst teilen sich Seitenleiste und Haupt-Tab dieselben Objekte
    const copy = JSON.parse(JSON.stringify(value));
    if (componentData.project_value) {
        Object.assign(componentData.project_value, copy);
    } else {
        componentData.project_value = copy;
    }
}

/**
 * data.updated auf project.{id} trägt nur Kennungen ({ id, project_id, component_id }) — der Kanal prüft
 * nur das Projekt-Sichtrecht, nicht die Sichtbarkeit der Komponente. Den Wert lädt der Listener über
 * project.tab.component.value nach (prüft Komponenten- und Tab-Sichtbarkeit); nur die jeweils letzte
 * Antwort wird übernommen. Der Broadcast geht toOthers: nach eigenem Speichern ruft die Komponente
 * saved(value) mit der PATCH-Antwort auf – das übernimmt den Wert und gleicht andere Instanzen derselben
 * Komponente auf der Seite ab (Seitenleiste + Haupt-Tab).
 *
 * @param {object|(() => object)} source Komponente (props.data) oder besser ein Getter (() => props.data):
 *   Inertia-Besuche mit preserveState ersetzen die Komponenten-Objekte, die Komponente bleibt gemountet –
 *   ein beim Setup festgehaltenes Objekt wäre dann veraltet.
 * @param {number|string} projectId
 * @param {{ fetchValue?: (projectId: number|string, componentId: number) => Promise<object|null> }} options
 */
export function useProjectDataListener(source, projectId, options = {}) {
    const getData = typeof source === 'function' ? source : () => source;
    const channelName = 'project.' + projectId;
    const fetchValue = options.fetchValue ?? fetchComponentValue;
    const componentId = () => {
        const data = getData() ?? {};

        return data.project_value?.component_id ?? data.id;
    };

    let requestSequence = 0;
    let inFlight = 0;
    // saved() hat eine laufende Antwort verworfen, die einen neueren fremden Stand bringen könnte
    let refetchAfterSave = false;
    let active = false;
    let unsubscribeLocal = null;

    const applyLocal = (value) => {
        const data = getData();
        if (data) {
            applyComponentValue(data, value);
        }
    };

    /**
     * Gespeicherter Wert (eigene PATCH-Antwort oder lokaler Abgleich einer anderen Instanz): eine noch
     * laufende Nachlade-Antwort ist älter → verwerfen und danach einmal nachholen. Nicht übernehmen,
     * wenn bereits ein neuerer Stand angezeigt wird (updated_at), z.B. ein fremder Wert, der während
     * des eigenen Speicherns per Broadcast nachgeladen wurde.
     *
     * @returns {boolean} false, wenn der angezeigte Stand neuer ist
     */
    const acceptSavedValue = (value) => {
        if (!value) {
            return true;
        }
        const displayed = getData()?.project_value;
        const order = compareUpdatedAt(displayed, value);
        if (order === 'older') {
            return false;
        }
        // Gleiche Sekunde, andere Daten: Reihenfolge unklar → übernehmen und den Serverstand nachladen
        const ambiguous = order === 'same'
            && JSON.stringify(displayed?.data ?? null) !== JSON.stringify(value?.data ?? null);
        if (inFlight > 0) {
            refetchAfterSave = true;
        }
        requestSequence++;
        applyLocal(value);
        if (ambiguous && active) {
            reload();
        }

        return true;
    };

    function afterDiscardedResponse() {
        if (refetchAfterSave && inFlight === 0) {
            refetchAfterSave = false;

            return reload();
        }

        return undefined;
    }

    async function reload(isRetry = false) {
        const sequence = ++requestSequence;
        inFlight++;
        let value = null;
        try {
            value = await fetchValue(projectId, componentId());
        } catch (error) {
            inFlight--;
            if (!active) {
                return undefined;
            }
            if (sequence !== requestSequence) {
                return afterDiscardedResponse();
            }
            // Netzfehler (keine Antwort): einmal wiederholen. 403/404: Komponente für diese Person
            // nicht sichtbar – nichts übernehmen.
            if (!isRetry && !error?.response) {
                return reload(true);
            }

            return undefined;
        }
        inFlight--;

        if (!active) {
            return undefined;
        }
        if (sequence !== requestSequence) {
            return afterDiscardedResponse();
        }
        refetchAfterSave = false;
        applyLocal(value);

        return undefined;
    }

    const handleBroadcast = (event) => {
        const updated = event?.data ?? {};
        if (
            Number(updated.project_id) !== Number(projectId)
            || Number(updated.component_id) !== Number(componentId())
        ) {
            return undefined;
        }

        return reload();
    };

    function init() {
        if (active) {
            return;
        }
        active = true;
        Echo.private(channelName).listen(EVENT_NAME, handleBroadcast);
        unsubscribeLocal = subscribeComponentValue(projectId, componentId(), acceptSavedValue);
    }

    function stop() {
        if (!active) {
            return;
        }
        active = false;
        stopListeningOnPrivateChannel(channelName, EVENT_NAME, handleBroadcast);
        unsubscribeLocal?.();
        unsubscribeLocal = null;
    }

    /**
     * Nach erfolgreichem PATCH: gespeicherten Wert übernehmen und an andere Instanzen weitergeben
     * (jede Instanz prüft selbst, ob ihr Stand neuer ist).
     *
     * @returns {boolean} false, wenn bereits ein neuerer Stand angezeigt wird – die eigene Speicherung
     *   ist dann überholt und die Komponente soll ihren Eingabestand nicht als gespeichert markieren
     */
    function saved(value) {
        if (!value) {
            return true;
        }
        const accepted = acceptSavedValue(value);
        publishComponentValue(projectId, componentId(), value, acceptSavedValue);

        return accepted;
    }

    return { init, stop, saved };
}
