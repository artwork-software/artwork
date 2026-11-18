import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { mergeOccupancyRange, monthKeyInRange, useOccupancyUpdates } from '../../resources/js/Composeables/Listener/useOccupancyUpdates.js'

test('ranges of several signals are merged, an unknown range means everything', () => {
    let range = mergeOccupancyRange(undefined, { start: '2026-11-10', end: '2026-11-12' })
    assert.deepEqual(range, { start: '2026-11-10', end: '2026-11-12' })
    range = mergeOccupancyRange(range, { start: '2026-10-30', end: '2026-11-01' })
    assert.deepEqual(range, { start: '2026-10-30', end: '2026-11-12' })
    range = mergeOccupancyRange(range, { start: '2026-12-24' })
    assert.deepEqual(range, { start: '2026-10-30', end: '2026-12-24' })
    assert.equal(mergeOccupancyRange(range, {}), null)
    assert.equal(mergeOccupancyRange(null, { start: '2026-01-01', end: '2026-01-02' }), null)
})

test('only months inside the range are affected', () => {
    const range = { start: '2026-10-30', end: '2026-12-02' }
    assert.equal(monthKeyInRange('2026-09', range), false)
    assert.equal(monthKeyInRange('2026-10', range), true)
    assert.equal(monthKeyInRange('2026-11', range), true)
    assert.equal(monthKeyInRange('2026-12', range), true)
    assert.equal(monthKeyInRange('2027-01', range), false)
    assert.equal(monthKeyInRange('2027-01', null), true)
})

test('signals in quick succession trigger one reload with the merged range', async () => {
    const listeners = {}
    const channel = {
        listen(name, handler) { listeners[name] = handler; return channel },
        stopListening(name) { delete listeners[name]; return channel },
    }
    globalThis.Echo = { private: () => channel, connector: { channels: { 'private-events': channel } } }
    const { setTimeout: realSetTimeout } = globalThis
    const timers = []
    globalThis.setTimeout = (fn) => { timers.push(fn); return timers.length }
    try {
        const calls = []
        const updates = useOccupancyUpdates((range) => calls.push(range))
        updates.start()
        listeners['.occupancy.updated']({ start: '2026-11-03', end: '2026-11-03' })
        listeners['.occupancy.updated']({ start: '2026-11-20', end: '2026-11-21' })
        assert.equal(timers.length, 1)
        timers[0]()
        assert.deepEqual(calls, [{ start: '2026-11-03', end: '2026-11-21' }])
        updates.stop()
        assert.equal(listeners['.occupancy.updated'], undefined)
    } finally {
        globalThis.setTimeout = realSetTimeout
        delete globalThis.Echo
    }
})

test('calendar and shift plan listen to the occupancy signal and clean up', () => {
    const read = (path) => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8')
    for (const file of ['Components/Calendar/BaseCalendar.vue', 'Pages/Shifts/ShiftPlan.vue']) {
        const source = read(file)
        assert.match(source, /useOccupancyUpdates\(/, file)
        assert.match(source, /occupancyUpdates\.start\(\)/, file)
        assert.match(source, /occupancyUpdates\.stop\(\)/, file)
    }
})
