/**
 * Kennzeichnet Inertia-Uploads, deren Fehler das Modal selbst anzeigt: der globale 'invalid'-Handler in app.js
 * zeigt dafür kein alert()/Toast (sonst je Datei eines – z. B. bei 413).
 */
export const INLINE_FEEDBACK_HEADER = 'X-Artwork-Inline-Feedback';

/**
 * @param {{ config?: { headers?: object } }|null|undefined} response axios-Antwort aus dem 'invalid'-Event
 * @returns {boolean}
 */
export function hasInlineFeedback(response) {
    const headers = response?.config?.headers;
    if (!headers) {
        return false;
    }
    const value = typeof headers.get === 'function'
        ? headers.get(INLINE_FEEDBACK_HEADER)
        : headers[INLINE_FEEDBACK_HEADER] ?? headers[INLINE_FEEDBACK_HEADER.toLowerCase()];

    return value !== undefined && value !== null && value !== false && value !== '';
}

/**
 * Mehrere Dateien nacheinander hochladen. Mehrere .post() derselben Inertia-useForm hintereinander brechen
 * sich gegenseitig ab (interruptInFlight) – praktisch kam nur die letzte Datei an. Hier wartet jeder Upload
 * auf den vorherigen; Fehler einzelner Dateien halten die übrigen nicht auf.
 *
 * @template T
 * @param {T[]} files
 * @param {(file: T) => Promise<unknown>} uploadOne
 * @param {{ stopOnError?: (error: unknown) => boolean }} [options] true → Kette nach diesem Fehler beenden
 * @returns {Promise<{ uploaded: T[], failed: Array<{ file: T, error: unknown }>, skipped: T[] }>}
 */
export async function uploadSequentially(files, uploadOne, { stopOnError = () => false } = {}) {
    const uploaded = [];
    const failed = [];
    const skipped = [];

    for (const [index, file] of files.entries()) {
        try {
            await uploadOne(file);
            uploaded.push(file);
        } catch (error) {
            failed.push({ file, error });
            // z. B. 413: die übrigen Dateien würden genauso abgelehnt – nicht weiter versuchen
            if (stopOnError(error)) {
                skipped.push(...files.slice(index + 1));
                break;
            }
        }
    }

    return { uploaded, failed, skipped };
}

/**
 * Inertia-Formular als Promise absenden: erfüllt bei onSuccess, abgelehnt bei onError (mit { errors })
 * oder wenn der Besuch ohne Ergebnis endet ({ cancelled: true, status }). Mit router wird der Status einer
 * ungültigen Antwort (z. B. 413) über das 'invalid'-Event erfasst; die Anfrage trägt INLINE_FEEDBACK_HEADER,
 * damit app.js dafür keine eigene Meldung zeigt – das Modal meldet den Fehler.
 *
 * @param {{ post: Function, patch?: Function, put?: Function, delete?: Function }} form useForm-Objekt
 * @param {'post'|'patch'|'put'|'delete'} method
 * @param {string} url
 * @param {object} [options] weitere Inertia-Optionen; eigene Callbacks werden zusätzlich aufgerufen
 * @param {{ router?: { on: Function } }} [context] Inertia-Router für das 'invalid'-Event
 * @returns {Promise<unknown>}
 */
export function submitInertiaForm(form, method, url, options = {}, { router = null } = {}) {
    return new Promise((resolve, reject) => {
        let settled = false;
        let invalidStatus = null;
        const stopListening = router?.on('invalid', (event) => {
            if (hasInlineFeedback(event.detail?.response)) {
                invalidStatus = event.detail.response.status ?? null;
            }
        });

        form[method](url, {
            ...options,
            headers: { ...(options.headers ?? {}), [INLINE_FEEDBACK_HEADER]: '1' },
            onSuccess: (page) => {
                settled = true;
                // 403 rendert der Exception-Handler für Inertia als Fehlerseite (kein invalid-Event) –
                // wie eine abgelehnte Anfrage behandeln, damit die Kette stoppt
                if (typeof page?.component === 'string' && page.component.startsWith('Errors/')) {
                    reject({ cancelled: true, status: 403 });
                    return;
                }
                options.onSuccess?.(page);
                resolve(page);
            },
            onError: (errors) => {
                settled = true;
                options.onError?.(errors);
                reject({ errors });
            },
            onFinish: (visit) => {
                stopListening?.();
                options.onFinish?.(visit);
                if (!settled) {
                    reject({ cancelled: true, status: invalidStatus });
                }
            },
        });
    });
}

/**
 * Meldung für eine fehlgeschlagene Datei eines Inertia-Uploads (Validierungsfehler, 413, sonst allgemein).
 *
 * @param {unknown} error Ablehnung aus submitInertiaForm
 * @param {(key: string, params?: unknown) => string} t Übersetzer
 * @returns {string}
 */
export function inertiaUploadErrorMessage(error, t) {
    const firstError = Object.values(error?.errors ?? {}).flat().find(Boolean);
    if (firstError) {
        return firstError;
    }
    if (error?.status === 413) {
        return t('The file is too large and was rejected by the server. Please choose a smaller file.');
    }

    return t('Upload failed');
}

/**
 * Ungültige Antwort (413, 403, 5xx …): die übrigen Dateien würden genauso scheitern.
 *
 * @param {unknown} error
 * @returns {boolean}
 */
export function isInvalidResponse(error) {
    return Boolean(error?.cancelled);
}
