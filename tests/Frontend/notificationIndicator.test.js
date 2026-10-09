import assert from 'node:assert/strict'
import test from 'node:test'
import {
    hasUnseenNotifications,
    liveNotificationArrived,
    markNotificationArrived,
} from '../../resources/js/Helper/notificationIndicator.js'

test('the bell shows unseen notifications from the page or a live hint, never on the centre itself', () => {
    liveNotificationArrived.value = false
    assert.equal(hasUnseenNotifications({ show_notification_indicator: true }, false), true)
    assert.equal(hasUnseenNotifications({ show_notification_indicator: false }, false), false)
    assert.equal(hasUnseenNotifications({ show_notification_indicator: true }, true), false)

    markNotificationArrived()
    assert.equal(hasUnseenNotifications({ show_notification_indicator: false }, false), true)
    assert.equal(hasUnseenNotifications(null, true), false)
})

test('only live hints that created an entry light the bell, visiting the centre clears it', async () => {
    const {createsNotificationEntry, markNotificationArrived, resetNotificationArrived, liveNotificationArrived} =
        await import('../../resources/js/Helper/notificationIndicator.js')

    assert.equal(createsNotificationEntry({delivered: false}), false)
    assert.equal(createsNotificationEntry({delivered: true}), true)
    // ältere Payloads ohne Kennzeichen zählen weiter als Eintrag
    assert.equal(createsNotificationEntry({message: 'x'}), true)

    markNotificationArrived()
    assert.equal(liveNotificationArrived.value, true)
    resetNotificationArrived()
    assert.equal(liveNotificationArrived.value, false)
})
