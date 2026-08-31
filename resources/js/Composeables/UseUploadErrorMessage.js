import { usePage } from '@inertiajs/vue3';

// Zentrale, verständliche Fehlermeldung für fehlgeschlagene Datei-Uploads (Abnahme RG-04).
// Wichtigster Fall: HTTP 413 — nginx/PHP lehnen die Datei ab, BEVOR die App-Validierung
// (mit ihren übersetzten Meldungen) überhaupt läuft. Ohne diese Behandlung blieb nur ein
// nichtssagendes „Upload failed".
export function uploadErrorMessage(error, t) {
    if (error?.response?.status === 413) {
        // Serverobergrenze (PHP) transparent mitgeben, wenn bekannt — nginx kann
        // zusätzlich begrenzen, daher bleibt die Meldung bewusst allgemein formuliert
        let serverLimitMb = null;
        try {
            serverLimitMb = usePage().props.server_upload_limit_mb ?? null;
        } catch {
            /* außerhalb eines Inertia-Kontexts ohne Limit weitermachen */
        }

        if (serverLimitMb) {
            return t(
                'The file is too large and was rejected by the server (server limit: {0} MB). If you need larger uploads, ask your IT to raise the server limits.',
                [serverLimitMb]
            );
        }

        return t('The file is too large and was rejected by the server. Please choose a smaller file.');
    }

    const errors = error?.response?.data?.errors;
    if (errors) {
        const first = Object.values(errors).flat().find(Boolean);
        if (first) {
            return first;
        }
    }

    return t('Upload failed');
}
