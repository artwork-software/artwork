import { ref, computed, onBeforeUnmount, getCurrentInstance } from 'vue';
import { extractSaveErrorMessage } from '@/Composeables/BiSaveFeedback.js';

/**
 * Gemeinsamer BI-Export-Ablauf: Konfiguration cachen, Job-Status pollen,
 * fertige Datei herunterladen. Genutzt vom Export-Dialog (Projekt-Tab +
 * Dashboard) und vom Budget-Export.
 *
 * Sichtbarer Wartezustand: elapsedSeconds, phase ('pending' = wartet auf einen
 * Worker, 'running' = Datei wird erstellt), cancel() bricht das Polling ab.
 */
export function useBiExport(routes = {}) {
    const routeNames = {
        cache: routes.cache ?? 'bi.export.cache',
        status: routes.status ?? 'bi.export.status',
        download: routes.download ?? 'bi.export.download',
    };

    const isExporting = ref(false);
    const exportError = ref(false);
    // Konkreter Grund (Validierung, Job-Fehler, abgelaufen) — null = generischer Text
    const exportErrorMessage = ref(null);
    const elapsedSeconds = ref(0);
    const phase = ref('idle'); // idle | pending | running | done
    const downloadStarted = ref(false);

    // Nach 30 s ohne Worker-Reaktion ist die Warteschlange das wahrscheinlichste Problem
    const queueSuspect = computed(() => phase.value === 'pending' && elapsedSeconds.value >= 30);

    let cancelled = false;
    let pollTimer = null;
    let clockTimer = null;

    const stopTimers = () => {
        if (pollTimer) clearTimeout(pollTimer);
        if (clockTimer) clearInterval(clockTimer);
        pollTimer = null;
        clockTimer = null;
    };

    // Poll stoppen, wenn die nutzende Komponente (Dialog) verschwindet.
    if (getCurrentInstance()) {
        onBeforeUnmount(() => {
            cancelled = true;
            stopTimers();
        });
    }

    const fail = (message = null) => {
        exportError.value = true;
        exportErrorMessage.value = message;
    };

    const pollAndDownload = (token) => new Promise((resolve) => {
        let attempts = 0;
        const maxAttempts = 400; // 400 × 1,5 s = 10 Minuten
        const check = async () => {
            if (cancelled) {
                return resolve(false);
            }
            attempts++;
            try {
                const { data } = await axios.get(route(routeNames.status, token));
                if (data.status === 'ready') {
                    phase.value = 'done';
                    downloadStarted.value = true;
                    window.location.href = route(routeNames.download, token);
                    return resolve(true);
                }
                if (data.status === 'running') {
                    phase.value = 'running';
                }
                if (data.status === 'failed') {
                    fail(data.message ?? null);
                    return resolve(false);
                }
                if (data.status === 'expired' || data.status === 'unknown') {
                    fail(null);
                    return resolve(false);
                }
            } catch (error) {
                // 4xx/5xx vom Status-Endpoint sind nicht transient (403, Token weg) – sofort abbrechen.
                if (error?.response?.status) {
                    fail(extractSaveErrorMessage(error));
                    return resolve(false);
                }
                // Netzwerkfehler: weiter pollen, bis das Versuchsbudget erschöpft ist.
            }
            if (attempts >= maxAttempts) {
                fail(null);
                return resolve(false);
            }
            pollTimer = setTimeout(check, 1500);
        };
        check();
    });

    /**
     * @param {{project_ids: number[], columns: string[], date_from: ?string, date_to: ?string,
     *          granularity: ?('projects'|'events'|'both'), event_tag_filter: ?Array<number|'untagged'>}} config
     * @returns {Promise<boolean>} true, wenn der Download gestartet wurde
     */
    const runExport = async (config) => {
        cancelled = false;
        isExporting.value = true;
        exportError.value = false;
        exportErrorMessage.value = null;
        downloadStarted.value = false;
        elapsedSeconds.value = 0;
        phase.value = 'pending';
        clockTimer = setInterval(() => { elapsedSeconds.value++; }, 1000);
        try {
            const response = await axios.post(route(routeNames.cache), config);
            return await pollAndDownload(response.data.token);
        } catch (error) {
            console.error('BI export error', error);
            fail(extractSaveErrorMessage(error));
            return false;
        } finally {
            stopTimers();
            isExporting.value = false;
            if (phase.value !== 'done') {
                phase.value = 'idle';
            }
        }
    };

    /** Polling abbrechen — der Job läuft ggf. zu Ende, die Datei räumt der Cleanup-Job weg. */
    const cancel = () => {
        cancelled = true;
        stopTimers();
        isExporting.value = false;
        phase.value = 'idle';
    };

    return {
        isExporting,
        exportError,
        exportErrorMessage,
        elapsedSeconds,
        phase,
        queueSuspect,
        downloadStarted,
        runExport,
        cancel,
    };
}
