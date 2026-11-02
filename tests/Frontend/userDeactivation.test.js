import test from 'node:test'
import assert from 'node:assert/strict'
import {isUserDeactivated, deactivatedAvatarClasses, DEACTIVATED_AVATAR_CLASSES, canChangeAccountActivation} from '../../resources/js/Helper/UserDeactivation.js'

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

test('Deaktivieren: Personalverwaltung oder Admin, nie sich selbst, Admins nur durch Admins', () => {
    const user = {id: 2, type: 'user'}
    const base = {user, authUserId: 1, isAdmin: false, canManageWorkers: true, isTargetAdmin: false}

    assert.equal(canChangeAccountActivation(base), true)
    assert.equal(canChangeAccountActivation({...base, canManageWorkers: false}), false)
    assert.equal(canChangeAccountActivation({...base, isTargetAdmin: true}), false)
    assert.equal(canChangeAccountActivation({...base, isAdmin: true, canManageWorkers: false, isTargetAdmin: true}), true)
    assert.equal(canChangeAccountActivation({...base, authUserId: 2, isAdmin: true}), false)
    assert.equal(canChangeAccountActivation({...base, user: {id: 2, type: 'freelancer'}}), false)
    // Profilseite: user_to_edit trägt kein type-Feld
    assert.equal(canChangeAccountActivation({...base, user: {id: 2}}), true)
    assert.equal(canChangeAccountActivation({...base, user: null}), false)
})
