import assert from 'node:assert/strict'
import test from 'node:test'
import {
    isTooltipUserEntity,
    needsTooltipDetails,
} from '../../resources/js/Helper/userTooltipDetails.js'

const avatarOnlyUser = {
    id: 7,
    first_name: 'Anna',
    last_name: 'Berg',
    full_name: 'Anna Berg',
    profile_photo_url: '/avatar.png',
    deactivated_at: null,
}

test('loads details for avatar-only payloads even without lazy-load', () => {
    assert.equal(needsTooltipDetails(avatarOnlyUser), true)
})

test('keeps full payloads with contact fields as they are', () => {
    assert.equal(needsTooltipDetails({...avatarOnlyUser, email: 'anna@example.org', phone_number: null}), false)
    assert.equal(needsTooltipDetails({...avatarOnlyUser, email: null}), false)
    assert.equal(needsTooltipDetails({...avatarOnlyUser, phone_number: '0123'}), false)
})

test('loads details when the caller asks for lazy-load explicitly', () => {
    assert.equal(needsTooltipDetails({...avatarOnlyUser, email: 'anna@example.org'}, true), true)
})

test('never loads user details for freelancers or service providers', () => {
    assert.equal(needsTooltipDetails({id: 7, type: 'freelancer', first_name: 'Frei'}), false)
    assert.equal(needsTooltipDetails({id: 7, type: 1, first_name: 'Frei'}, true), false)
    assert.equal(needsTooltipDetails({id: 7, type: 'service_provider', provider_name: 'Licht GmbH'}), false)
    assert.equal(needsTooltipDetails({id: 7, provider_name: 'Licht GmbH', profile_photo_url: '/x.png'}), false)
    assert.equal(needsTooltipDetails({
        id: 7,
        provider_name: 'Licht GmbH',
        manager_type: 'Artwork\\Modules\\ServiceProvider\\Models\\ServiceProvider',
    }), false)
    assert.equal(needsTooltipDetails({id: 7, type: 'external', first_name: 'Gast'}), false)
})

test('treats users with an explicit user type or user manager type as users', () => {
    assert.equal(isTooltipUserEntity({id: 1, type: 'user'}), true)
    assert.equal(isTooltipUserEntity({id: 1, type: 0}), true)
    assert.equal(isTooltipUserEntity({id: 1, manager_type: 'Artwork\\Modules\\User\\Models\\User'}), true)
})

test('does nothing without a user or id', () => {
    assert.equal(needsTooltipDetails(null), false)
    assert.equal(needsTooltipDetails({first_name: 'Ohne Id'}), false)
    assert.equal(isTooltipUserEntity(null), false)
})
