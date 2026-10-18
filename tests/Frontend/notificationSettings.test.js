import assert from 'node:assert/strict'
import test from 'node:test'
import {
    filterNotificationGroups,
    groupChannelState,
} from '../../resources/js/Layouts/Components/NotificationComponents/notificationSettings.js'

const groups = [
    {
        key: 'TASKS',
        title: 'Tasks',
        settings: [
            { title: 'New tasks', description: 'Find out about tasks', enabled_email: true, enabled_push: false },
            { title: 'Reminders', description: 'Deadlines', enabled_email: false, enabled_push: false },
        ],
    },
    { key: 'SHIFTS', title: 'Shift plan', settings: [{ title: 'Changes', description: 'Your shifts', enabled_email: true, enabled_push: true }] },
]

test('a group channel is all, some or none', () => {
    assert.equal(groupChannelState(groups[0].settings, 'enabled_email'), 'some')
    assert.equal(groupChannelState(groups[0].settings, 'enabled_push'), 'none')
    assert.equal(groupChannelState(groups[1].settings, 'enabled_email'), 'all')
})

test('search narrows types and drops empty groups, using the translated texts', () => {
    const translate = (text) => ({ Deadlines: 'Fristen' }[text] ?? text)

    assert.deepEqual(filterNotificationGroups(groups, 'fristen', translate).map((g) => g.key), ['TASKS'])
    assert.equal(filterNotificationGroups(groups, 'fristen', translate)[0].settings.length, 1)
    assert.equal(filterNotificationGroups(groups, '  ').length, 2)
    assert.deepEqual(filterNotificationGroups(groups, 'shift plan').map((g) => g.key), ['SHIFTS'])
})
