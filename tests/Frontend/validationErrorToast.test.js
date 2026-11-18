import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { onAppToast, toastValidationErrors } from '../../resources/js/Helper/appToast.js'

test('inertia validation errors without field display become an error toast', () => {
    const toasts = []
    const stop = onAppToast((type, message) => toasts.push([type, message]))

    toastValidationErrors({ request_comment: 'Bitte einen Kommentar angeben.', other: 'zweite' })
    toastValidationErrors({ list: ['Erste Meldung aus Liste'] })
    toastValidationErrors({}, 'Speichern fehlgeschlagen (Test)')
    stop?.()

    assert.deepEqual(toasts, [
        ['error', 'Bitte einen Kommentar angeben.'],
        ['error', 'Erste Meldung aus Liste'],
        ['error', 'Speichern fehlgeschlagen (Test)'],
    ])
})

test('forms that used to swallow validation errors now show them', () => {
    for (const file of [
        'Components/Modals/GeneralCreateOrUpdateContactModal.vue',
        'Pages/Shifts/Components/RequestWorkTimeChangeModal.vue',
        'Pages/WorkTime/Components/DeclineWorkTimeRequest.vue',
        'Pages/IssueOfMaterial/Components/EnterExternalIssueReturnModal.vue',
        'Pages/Settings/Calendar/Index.vue',
    ]) {
        const source = readFileSync(new URL(`../../resources/js/${file}`, import.meta.url), 'utf8')
        assert.match(source, /onError: \(errors\) => toastValidationErrors\(errors\)/, file)
    }
})
