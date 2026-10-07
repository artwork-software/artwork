import assert from 'node:assert/strict'
import test from 'node:test'
import {
    failedRequestMessage,
    isInertiaRequest,
    messageForFailedRequest,
    onAppToast,
    shouldHandleSessionExpiry,
    shouldToastAxiosError,
    setAppToastTranslator,
    showAppToast,
    t,
} from '../../resources/js/Helper/appToast.js'

test('maps failed request statuses to a user message', () => {
    assert.equal(messageForFailedRequest(undefined), 'The connection to the server failed. Please check your network and try again.')
    assert.equal(messageForFailedRequest(403), 'You are not allowed to perform this action.')
    assert.equal(messageForFailedRequest(404), 'The entry no longer exists. Please reload the page.')
    assert.equal(messageForFailedRequest(500), 'The action could not be completed. Please try again.')
    assert.equal(messageForFailedRequest(503), 'The action could not be completed. Please try again.')
    // Validierung zeigt das Formular selbst, 200 ist z. B. ein OAuth-Redirect
    assert.equal(messageForFailedRequest(422), null)
    assert.equal(messageForFailedRequest(200), null)
})

test('dialogs that skip the global toast still name the reason', () => {
    // Validierung vor Servermeldung vor allgemeiner Toast-Meldung vor Fallback
    assert.equal(failedRequestMessage({isAxiosError: true, response: {status: 422, data: {errors: {name: ['Name fehlt']}, message: 'x'}}}), 'Name fehlt')
    assert.equal(failedRequestMessage({isAxiosError: true, response: {status: 409, data: {message: 'Schon vergeben'}}}), 'Schon vergeben')
    assert.equal(failedRequestMessage({isAxiosError: true}), 'The connection to the server failed. Please check your network and try again.')
    assert.equal(failedRequestMessage({isAxiosError: true, response: {status: 403, data: {}}}), 'You are not allowed to perform this action.')
    assert.equal(failedRequestMessage({isAxiosError: true, response: {status: 422, data: {}}}), 'Failed to save')
    // Kein axios-Fehler (z. B. Inertia-Feldmap ohne Einträge): nie „Verbindung fehlgeschlagen“
    assert.equal(failedRequestMessage({}, 'Saving failed'), 'Saving failed')
    assert.equal(failedRequestMessage(new Error('boom')), 'Failed to save')
})

test('only failed mutations outside Inertia raise a global toast', () => {
    assert.equal(shouldToastAxiosError({config: {method: 'post'}}), true)
    assert.equal(shouldToastAxiosError({config: {method: 'DELETE'}}), true)
    assert.equal(shouldToastAxiosError({config: {method: 'get'}}), false)
    assert.equal(shouldToastAxiosError({config: {method: 'patch', skipErrorToast: true}}), false)
    assert.equal(shouldToastAxiosError({config: {method: 'put', headers: {'X-Inertia': true}}}), false)
    assert.equal(shouldToastAxiosError({code: 'ERR_CANCELED', config: {method: 'post'}}), false)
})

test('identical toasts in quick succession are shown once', () => {
    const received = []
    const off = onAppToast((type, message) => received.push(`${type}:${message}`))

    showAppToast('error', 'Speichern fehlgeschlagen')
    showAppToast('error', 'Speichern fehlgeschlagen')
    showAppToast('success', 'Gespeichert')
    showAppToast('error', '')
    off()
    showAppToast('error', 'nach dem Abmelden')

    assert.deepEqual(received, ['error:Speichern fehlgeschlagen', 'success:Gespeichert'])
})

test('statuses without a global message never reach the translator', () => {
    // vue-i18n wirft bei t(null) – ein Wurf im axios-Interceptor verschluckt 422/409-Antworten
    setAppToastTranslator((key) => {
        if (typeof key !== 'string') {
            throw new SyntaxError('Invalid arguments')
        }
        return key
    })
    try {
        for (const status of [400, 409, 413, 422, 423]) {
            assert.equal(t(messageForFailedRequest(status)), '')
        }
        assert.equal(t('You are not allowed to perform this action.'), 'You are not allowed to perform this action.')
    } finally {
        setAppToastTranslator((key) => key)
    }
})

test('session expiry is alerted by the interceptor only for non-Inertia requests', () => {
    assert.equal(shouldHandleSessionExpiry({response: {status: 401}, config: {method: 'get'}}), true)
    assert.equal(shouldHandleSessionExpiry({response: {status: 419}, config: {method: 'post', headers: {}}}), true)
    // Inertia meldet sich per 'invalid'-Event selbst
    assert.equal(shouldHandleSessionExpiry({response: {status: 401}, config: {headers: {'X-Inertia': true}}}), false)
    assert.equal(shouldHandleSessionExpiry({response: {status: 419}, config: {headers: {'X-Inertia': 'true'}}}), false)
    // AxiosHeaders-Instanz mit get()
    const axiosHeaders = {get: (name) => (name === 'X-Inertia' ? 'true' : undefined)}
    assert.equal(shouldHandleSessionExpiry({response: {status: 419}, config: {headers: axiosHeaders}}), false)
    assert.equal(isInertiaRequest({config: {headers: axiosHeaders}}), true)
    assert.equal(isInertiaRequest({config: {}}), false)
    // andere Status gehören nicht zur Sitzungsbehandlung
    assert.equal(shouldHandleSessionExpiry({response: {status: 500}, config: {method: 'post'}}), false)
    assert.equal(shouldHandleSessionExpiry({config: {method: 'post'}}), false)
})

test('components that show request errors themselves opt out of the global toast', async () => {
    const { readFileSync } = await import('node:fs')
    const read = (path) => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8')
    const optOuts = (source) => (source.match(/skipErrorToast: true|BI_REQUEST_CONFIG\)/g) ?? []).length

    const expected = {
        'Layouts/Components/SumDetailComponent.vue': 5,
        'Layouts/Components/SubPositionComponent.vue': 1,
        'Layouts/Components/MultiEditModal.vue': 1,
        'Layouts/Components/MultiDuplicateModal.vue': 1,
        'Layouts/Components/MultiCellMoveModal.vue': 1,
        'Layouts/Components/MultiCellDuplicateModal.vue': 1,
        'Layouts/Components/MultiCellEventCreateModal.vue': 1,
        'Layouts/Components/ShiftPlanComponents/CopyWeekModal.vue': 1,
        'Layouts/Components/CellDetailModal.vue': 4,
        'Layouts/Components/Export/Modals/SaveFilterPresetModal.vue': 1,
        'Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue': 1,
        'Pages/Settings/EventType/Components/Modals/AddEditBiTagModal.vue': 2,
        'Pages/Settings/EventType/Components/Modals/AssignBiTagEventTypesModal.vue': 1,
        'Pages/Settings/BiSettings/Components/BiAudienceCategoryManager.vue': 3,
        // Modals/Panels mit eigener Fehlermeldung bzw. Lesezugriffe per POST im Hintergrund
        'Components/Crm/CreateCrmArtistModal.vue': 1,
        'Components/SearchBars/ProjectSearch.vue': 3,
        'Components/SearchBars/RoomSearch.vue': 1,
        'Layouts/Components/EventComponent.vue': 3,
        'Layouts/Components/RoomRequestDialogComponent.vue': 1,
        'Pages/CRM/Components/InviteExternalModal.vue': 1,
        'Pages/ExternalAccess/Project/Components/Types/ExternalCrmContactList.vue': 1,
        'Pages/ExternalAccess/Project/Components/Types/ExternalCrmContactModal.vue': 2,
        'Pages/ExternalAccess/Project/Components/Types/ExternalDocuments.vue': 2,
        'Pages/ExternalUserManagement/Components/SourceModal.vue': 1,
        'Pages/ExternalUserManagement/Index.vue': 2,
        'Pages/Inventory/Components/Article/PropertyFileCell.vue': 1,
        'Pages/Inventory/LayoutComponents/InventoryFilterComponent.vue': 1,
        'Pages/Projects/Components/AddShiftModal.vue': 3,
        'Pages/Projects/Components/BulkComponents/BulkMultiEditModal.vue': 1,
        'Pages/Projects/Components/ProjectAllDocumentsComponent.vue': 1,
        'Pages/Projects/Components/ProjectDocumentsComponent.vue': 1,
        'Pages/Projects/Components/TimelineComponents/AddEditTimelineModal.vue': 1,
        'Pages/Projects/Tab/Components/CrmContactFormModal.vue': 1,
        'Pages/Projects/Tab/Components/CrmContactListComponent.vue': 2,
        'Pages/Projects/Tab/Components/ExternalTabStatus.vue': 2,
        'Pages/Shifts/Components/ProjectAssignPersonModal.vue': 1,
        'Pages/Shifts/Components/ProjectAssignmentModal.vue': 1,
        'Pages/Shifts/Components/ShiftReplacementModal.vue': 1,
        'Pages/Shifts/Components/ShowUserShiftsModal.vue': 2,
        'Pages/Shifts/Components/UserOvertimePanel.vue': 1,
        'Pages/Shifts/DailyViewComponents/SingleShiftInDailyShiftView.vue': 3,
        'Pages/Shifts/ShiftPlan.vue': 5,
        'Pages/Shifts/ShiftPlanDailyView.vue': 4,
        'Pages/Shifts/ShiftPlanListView.vue': 2,
        'Pages/Shifts/WeekStatus/WeekStatusDetailModal.vue': 1,
        'Pages/ToolSettings/Mail/Index.vue': 1,
    }
    for (const [file, count] of Object.entries(expected)) {
        assert.equal(optOuts(read(file)), count, file)
    }
})
