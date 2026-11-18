import test from 'node:test'
import assert from 'node:assert/strict'
import {adminToggleConfirmation} from '../../resources/js/Helper/adminRoleToggle.js'

test('Vergeben fragt immer nach, auch beim eigenen Konto', () => {
    assert.equal(adminToggleConfirmation({checked: true, targetUserId: 2, authUserId: 1}), 'grant')
    assert.equal(adminToggleConfirmation({checked: true, targetUserId: 1, authUserId: 1}), 'grant')
})

test('Entziehen bei einer anderen Person', () => {
    assert.equal(adminToggleConfirmation({checked: false, targetUserId: 2, authUserId: 1}), 'revoke')
})

test('Entziehen beim eigenen Konto, auch wenn eine ID als String kommt', () => {
    assert.equal(adminToggleConfirmation({checked: false, targetUserId: 1, authUserId: 1}), 'revoke-self')
    assert.equal(adminToggleConfirmation({checked: false, targetUserId: '1', authUserId: 1}), 'revoke-self')
})

test('fehlende IDs gelten nie als eigenes Konto', () => {
    assert.equal(adminToggleConfirmation({checked: false, targetUserId: null, authUserId: null}), 'revoke')
    assert.equal(adminToggleConfirmation({checked: false, targetUserId: 1, authUserId: undefined}), 'revoke')
})
