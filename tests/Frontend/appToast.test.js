import assert from 'node:assert/strict'
import test from 'node:test'
import {
    messageForFailedRequest,
    onAppToast,
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
