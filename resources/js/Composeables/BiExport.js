import { ref } from 'vue';

/**
 * Gemeinsamer BI-Export-Ablauf: Konfiguration cachen, Job-Status pollen,
 * fertige Datei herunterladen. Genutzt vom Projekt-Export-Modal und der
 * Export-Einstellungsseite.
 */
export function useBiExport() {
    const isExporting = ref(false);
    const exportError = ref(false);

    const pollAndDownload = (token) => new Promise((resolve) => {
        let attempts = 0;
        const maxAttempts = 120;
        const check = async () => {
            attempts++;
            try {
                const { data } = await axios.get(route('bi.export.status', token));
                if (data.status === 'ready') {
                    window.location.href = route('bi.export.download', token);
                    return resolve(true);
                }
                if (data.status === 'failed' || data.status === 'unknown') {
                    exportError.value = true;
                    return resolve(false);
                }
            } catch (error) {
                // transient error – keep polling until the attempt budget is exhausted
            }
            if (attempts >= maxAttempts) {
                exportError.value = true;
                return resolve(false);
            }
            setTimeout(check, 1500);
        };
        check();
    });

    /**
     * @param {{project_ids: number[], columns: string[], date_from: ?string, date_to: ?string}} config
     * @returns {Promise<boolean>} true, wenn der Download gestartet wurde
     */
    const runExport = async (config) => {
        isExporting.value = true;
        exportError.value = false;
        try {
            const response = await axios.post(route('bi.export.cache'), config);
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
