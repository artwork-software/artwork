import { ref, onBeforeUnmount, getCurrentInstance } from 'vue';

/**
 * Gemeinsamer BI-Export-Ablauf: Konfiguration cachen, Job-Status pollen,
 * fertige Datei herunterladen. Genutzt vom Projekt-Export-Modal und der
 * Export-Einstellungsseite.
 */
export function useBiExport(routes = {}) {
    const routeNames = {
        cache: routes.cache ?? 'bi.export.cache',
        status: routes.status ?? 'bi.export.status',
        download: routes.download ?? 'bi.export.download',
    };

    const isExporting = ref(false);
    const exportError = ref(false);

    let cancelled = false;
    let pollTimer = null;

    // Poll stoppen, wenn die nutzende Komponente (Modal) verschwindet.
    if (getCurrentInstance()) {
        onBeforeUnmount(() => {
            cancelled = true;
            if (pollTimer) {
                clearTimeout(pollTimer);
            }
        });
    }

    const pollAndDownload = (token) => new Promise((resolve) => {
        let attempts = 0;
        const maxAttempts = 200;
        const check = async () => {
            if (cancelled) {
                return resolve(false);
            }
            attempts++;
            try {
                const { data } = await axios.get(route(routeNames.status, token));
                if (data.status === 'ready') {
                    window.location.href = route(routeNames.download, token);
                    return resolve(true);
                }
                if (data.status === 'failed' || data.status === 'unknown') {
                    exportError.value = true;
                    return resolve(false);
                }
            } catch (error) {
                // 4xx/5xx vom Status-Endpoint sind nicht transient (403, Token weg) – sofort abbrechen.
                if (error?.response?.status) {
                    exportError.value = true;
                    return resolve(false);
                }
                // Netzwerkfehler: weiter pollen, bis das Versuchsbudget erschöpft ist.
            }
            if (attempts >= maxAttempts) {
                exportError.value = true;
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
        isExporting.value = true;
        exportError.value = false;
        try {
            const response = await axios.post(route(routeNames.cache), config);
            return await pollAndDownload(response.data.token);
        } catch (error) {
            console.error('BI export error', error);
            exportError.value = true;
            return false;
        } finally {
            isExporting.value = false;
        }
    };

    return { isExporting, exportError, runExport };
}
