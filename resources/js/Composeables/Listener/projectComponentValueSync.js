/**
 * Lokaler Abgleich gespeicherter Komponentenwerte innerhalb einer Seite (z.B. dieselbe Komponente in
 * Seitenleiste und Haupt-Tab). Der Broadcast data.updated geht toOthers – die eigene Seite bekommt ihn
 * nicht und übernimmt den gespeicherten Wert aus der PATCH-Antwort; andere Instanzen erfahren es hier.
 */
const subscribers = new Map();

function keyFor(projectId, componentId) {
    return `${Number(projectId)}:${Number(componentId)}`;
}

/**
 * @param {number|string} projectId
 * @param {number|string} componentId
 * @param {(value: object) => void} handler
 * @returns {() => void} Abmelden
 */
export function subscribeComponentValue(projectId, componentId, handler) {
    const key = keyFor(projectId, componentId);
    if (!subscribers.has(key)) {
        subscribers.set(key, new Set());
    }
    subscribers.get(key).add(handler);

    return () => {
        const handlers = subscribers.get(key);
        if (!handlers) {
            return;
        }
        handlers.delete(handler);
        if (handlers.size === 0) {
            subscribers.delete(key);
        }
    };
}

/**
 * Verteilt einen gespeicherten Wert an alle anderen Instanzen derselben Komponente.
 *
 * @param {Function|null} source Handler der speichernden Instanz (wird übersprungen)
 */
export function publishComponentValue(projectId, componentId, value, source = null) {
    for (const handler of subscribers.get(keyFor(projectId, componentId)) ?? []) {
        if (handler !== source) {
            handler(value);
        }
    }
}
