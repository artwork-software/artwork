import test from 'node:test'
import assert from 'node:assert/strict'
import {isUserDeactivated, deactivatedAvatarClasses, DEACTIVATED_AVATAR_CLASSES} from '../../resources/js/Helper/UserDeactivation.js'

test('aktiv ohne oder mit leerem deactivated_at', () => {
    assert.equal(isUserDeactivated(null), false)
    assert.equal(isUserDeactivated({id: 1}), false)
    assert.equal(isUserDeactivated({id: 1, deactivated_at: null}), false)
    assert.equal(isUserDeactivated({id: 1, deactivated_at: ''}), false)
})

test('deaktiviert mit Zeitstempel', () => {
    assert.equal(isUserDeactivated({id: 1, deactivated_at: '2026-11-02T10:00:00+01:00'}), true)
})

test('Avatar-Klassen nur für deaktivierte Accounts', () => {
    assert.equal(deactivatedAvatarClasses({deactivated_at: null}), '')
    assert.equal(deactivatedAvatarClasses({deactivated_at: '2026-11-02T10:00:00+01:00'}), DEACTIVATED_AVATAR_CLASSES)
})
