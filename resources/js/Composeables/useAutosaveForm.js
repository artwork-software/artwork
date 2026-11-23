import { ref, watch } from 'vue'
import { changedFields, outdatedErrorFields } from './urlTab.js'

/**
 * Speichert ein Inertia-Formular beim Verlassen eines Feldes (PATCH) und hält die Fehlermeldungen
 * am Feld aktuell: sie verschwinden, sobald das Feld geändert wird.
 *
 * @param {import('@inertiajs/vue3').InertiaForm<object>} form
 * @param {() => string} url
 * @returns {{save: () => void, saved: import('vue').Ref<boolean>, flashSaved: () => void}}
 */
export function useAutosaveForm(form, url) {
    const saved = ref(false)
    let savedTimer = null

    const flashSaved = () => {
        saved.value = true
        clearTimeout(savedTimer)
        savedTimer = setTimeout(() => (saved.value = false), 2500)
    }

    watch(() => form.data(), (now, before) => {
        changedFields(before, now).forEach((field) => form.clearErrors(field))
    })

    // Eigener Vergleich mit dem zuletzt gespeicherten Stand: form.isDirty pflegt Inertia zeitversetzt über
    // einen Watcher, direkt nach form.defaults() steht es noch auf dem alten Wert (führte zu doppeltem Senden).
    const snapshot = () => JSON.stringify(form.data())
    let savedSnapshot = snapshot()

    const save = () => {
        if (snapshot() === savedSnapshot) {
            // zurück auf dem gespeicherten Stand: frühere Fehlermeldungen gelten nicht mehr
            form.clearErrors()
            return
        }

        const sent = form.data()
        form.patch(url(), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Nur den gesendeten Stand als gespeichert merken. Was während der Anfrage getippt wurde,
                // bleibt ungespeichert und geht gleich hinterher (vorher galt es fälschlich als gespeichert).
                form.defaults(sent)
                savedSnapshot = JSON.stringify(sent)
                if (snapshot() !== savedSnapshot) {
                    // erst nach Abschluss dieser Anfrage: ein sofortiges Senden ließe Inertia den
                    // aktuellen, noch ungespeicherten Stand als gespeichert übernehmen
                    setTimeout(save, 0)
                    return
                }
                flashSaved()
            },
            // wurde ein Feld während der Anfrage schon korrigiert, gilt seine Meldung nicht mehr
            onError: () => outdatedErrorFields(form.errors, sent, form.data())
                .forEach((field) => form.clearErrors(field)),
        })
    }

    return { save, saved, flashSaved }
}
