/**
 * Hilfen für geteilte Echo-Kanäle (z.B. project.{id}): Mehrere Komponenten hören auf denselben Kanal.
 * Echo.leave() würde ALLE Handler des Kanals entfernen – hier wird nur der eigene Handler abgemeldet.
 */

function echoInstance() {
    return globalThis.Echo ?? null;
}

/**
 * Bestehender privater Kanal oder null. Bewusst nicht Echo.private(): das würde beim Abmelden
 * ein neues Abo anlegen, falls der Kanal (z.B. nach Echo.leave an anderer Stelle) schon weg ist.
 */
export function existingPrivateChannel(channelName) {
    const channels = echoInstance()?.connector?.channels ?? null;

    return channels?.['private-' + channelName] ?? null;
}

/**
 * Meldet genau diesen Handler ab (stopListening mit Callback), nicht den ganzen Kanal.
 *
 * @param {string} channelName z.B. 'project.5'
 * @param {string} eventName z.B. '.comment.add'
 * @param {Function} handler derselbe Callback wie bei listen()
 */
export function stopListeningOnPrivateChannel(channelName, eventName, handler) {
    const channel = existingPrivateChannel(channelName);
    if (channel && typeof channel.stopListening === 'function') {
        channel.stopListening(eventName, handler);
    }
}
