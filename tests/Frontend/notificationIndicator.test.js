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
