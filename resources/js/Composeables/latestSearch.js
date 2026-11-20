/**
 * Suche beim Tippen: wartet eine kurze Pause ab (nicht ein Request je Tastendruck) und übernimmt nur die
 * Antwort auf die letzte Eingabe. Eine langsame ältere Antwort überschreibt so nie eine neuere.
 *
 * @param {object} options
 * @param {(query: string) => Promise<any>} options.fetch
 * @param {(results: any, query: string) => void} options.onResults
 * @param {() => void} [options.onClear] leere Eingabe
 * @param {number} [options.delay]
 * @param {typeof setTimeout} [options.setTimer]
 * @param {typeof clearTimeout} [options.clearTimer]
 * @returns {(value: unknown) => void}
 */
export function createLatestSearch({
    fetch,
    onResults,
    onClear = () => {},
    delay = 250,
    setTimer = setTimeout,
    clearTimer = clearTimeout,
}) {
    let sequence = 0;
    let timer = null;

    return (value) => {
        clearTimer(timer);
        const query = String(value ?? '').trim();
        const current = ++sequence;
        if (query === '') {
            onClear();
            return;
        }

        timer = setTimer(async () => {
            try {
                const results = await fetch(query);
                if (current === sequence) {
                    onResults(results, query);
                }
            } catch (error) {
                // Fehler der Anfrage meldet der Aufrufer bzw. der globale Hinweis; die Liste bleibt, wie sie ist
            }
        }, delay);
    };
}
