import test from 'node:test'
import assert from 'node:assert/strict'
import {changeNeedsApproval, requestSubmittedKind} from '../../resources/js/Helper/roomChangeApproval.js'

const approvedBooking = {room_id: 3, start: '2026-11-20T00:00:00Z', end: '2026-11-20T22:59:00Z'}
const base = {approvedBooking, roomId: 3, canBookDirectly: false}

test('verkürzt innerhalb der bestätigten Belegung: keine neue Anfrage', () => {
    assert.equal(changeNeedsApproval({...base, start: '2026-11-20T09:00:00Z', end: '2026-11-20T13:00:00Z'}), false)
})

test('wieder auf den bestätigten Zeitraum verlängert: keine neue Anfrage', () => {
    assert.equal(changeNeedsApproval({...base, start: approvedBooking.start, end: approvedBooking.end}), false)
})

test('über die Belegung hinaus oder auf einen anderen Tag: neue Anfrage', () => {
    assert.equal(changeNeedsApproval({...base, start: '2026-11-20T00:00:00Z', end: '2026-11-21T02:00:00Z'}), true)
    assert.equal(changeNeedsApproval({...base, start: '2026-11-22T00:00:00Z', end: '2026-11-22T22:59:00Z'}), true)
})

test('anderer Raum: neue Anfrage, auch wenn die ID als String kommt', () => {
    assert.equal(changeNeedsApproval({...base, roomId: '4', start: approvedBooking.start, end: approvedBooking.end}), true)
    assert.equal(changeNeedsApproval({...base, roomId: '3', start: approvedBooking.start, end: approvedBooking.end}), false)
})

test('mit Direktbuchrecht oder ohne bekannte Belegung nie', () => {
    const outside = {start: '2026-11-22T00:00:00Z', end: '2026-11-22T22:59:00Z'}
    assert.equal(changeNeedsApproval({...base, ...outside, canBookDirectly: true}), false)
    assert.equal(changeNeedsApproval({...base, ...outside, approvedBooking: null}), false)
})

test('neuer Termin: Server-Antwort vor Vorhersage', () => {
    assert.equal(requestSubmittedKind({isNewEvent: true, createdAsRequest: true, predictedRequest: false}), 'new')
    assert.equal(requestSubmittedKind({isNewEvent: true, createdAsRequest: false, predictedRequest: true}), null)
    assert.equal(requestSubmittedKind({isNewEvent: true, createdAsRequest: undefined, predictedRequest: true}), 'new')
})

test('bestehender Termin: Hinweis nur, wenn wirklich neu angefragt wurde', () => {
    assert.equal(requestSubmittedKind({isNewEvent: false, serverState: 'none', predictedRequest: true}), null)
    assert.equal(requestSubmittedKind({isNewEvent: false, serverState: 'pending', predictedRequest: false}), null)
    assert.equal(requestSubmittedKind({isNewEvent: false, serverState: 'created', predictedRequest: false}), 'change')
    assert.equal(requestSubmittedKind({isNewEvent: false, serverState: null, predictedRequest: true}), 'change')
})
