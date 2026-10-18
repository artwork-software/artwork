/**
 * Nur die Antwort der zuletzt gestarteten Anfrage übernehmen: ältere, später eintreffende Antworten
 * dürfen einen neueren Stand nicht überschreiben.
 */
export function createLatestRequestTracker() {
    let latestRequestId = 0;

    return {
        begin: () => ++latestRequestId,
        isLatest: (requestId) => requestId === latestRequestId,
    };
}
