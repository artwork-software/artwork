/**
 * Remembers the last saved state of an auto-saving form, so a focus change without edits
 * sends no request.
 *
 * The snapshot must be the state that was SENT, not the form state when the response
 * arrives: anything typed into the next field while the request was running would otherwise
 * count as saved, and leaving that field would send nothing.
 *
 * Usage: `const sent = snapshot.capture()` before the request, `snapshot.markSaved(sent)` on success.
 *
 * @param {() => unknown} readFormData
 */
export function createSaveSnapshot(readFormData) {
    const capture = () => JSON.stringify(readFormData());
    let saved = capture();

    return {
        capture,
        hasChanges: () => capture() !== saved,
        /** @param {string} sentSnapshot */
        markSaved: (sentSnapshot) => {
            saved = sentSnapshot;
        },
    };
}

/**
 * Runs auto-saves one after another. A save requested while one is running is queued once
 * and started when the running one has finished – two parallel requests could otherwise
 * reach the server in the wrong order and leave the older state saved.
 *
 * @param {(done: () => void) => void} startSave must call done() when the request has finished
 * @returns {() => void}
 */
export function createQueuedSave(startSave) {
    let running = false;
    let queued = false;

    const request = () => {
        if (running) {
            queued = true;
            return;
        }
        running = true;
        startSave(() => {
            running = false;
            if (queued) {
                queued = false;
                request();
            }
        });
    };

    return request;
}
