import { ref, watch } from 'vue'
import { changedFields, outdatedErrorFields } from '@/Composeables/urlTab.js'

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

    const save = () => {
        if (!form.isDirty) {
            // zurück auf dem gespeicherten Stand: frühere Fehlermeldungen gelten nicht mehr
            form.clearErrors()
            return
        }

        const sent = form.data()
        form.patch(url(), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // gespeicherten Stand als Ausgangswert merken, sonst speichert jedes Verlassen eines Feldes erneut
                form.defaults()
                flashSaved()
            },
            // wurde ein Feld während der Anfrage schon korrigiert, gilt seine Meldung nicht mehr
            onError: () => outdatedErrorFields(form.errors, sent, form.data())
                .forEach((field) => form.clearErrors(field)),
        })
    }

    return { save, saved, flashSaved }
}
