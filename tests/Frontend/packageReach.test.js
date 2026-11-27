import assert from 'node:assert/strict'
import test from 'node:test'
import { isRunningPackage, packageOnlyWarning, strandedProductions } from '../../resources/js/Pages/Settings/Tickets/packageReach.js'

const pkg = {
    id: 'd1', kind: 'package', status: 'active', period: 'running',
    allEvents: false, eventIds: [], groupIds: ['g1'],
}
const productions = [
    { id: 'p1', title: 'Workshop A', packageOnly: true },
    { id: 'p2', title: 'Workshop B', packageOnly: false },
]
const groups = [{ id: 'g1', name: 'Workshops', eventIds: ['p1', 'p2'] }]

test('only an active package inside its period counts as on sale', () => {
    assert.equal(isRunningPackage(pkg), true)
    assert.equal(isRunningPackage({ ...pkg, status: 'paused' }), false)
    assert.equal(isRunningPackage({ ...pkg, period: 'ended' }), false)
    assert.equal(isRunningPackage({ ...pkg, period: 'upcoming' }), false)
    assert.equal(isRunningPackage({ ...pkg, kind: 'percent' }), false)
})

test('deleting or pausing the last package strands the package-only productions it reached', () => {
    assert.deepEqual(strandedProductions([pkg], productions, groups, pkg, null).map((p) => p.id), ['p1'])
    assert.deepEqual(strandedProductions([pkg], productions, groups, pkg, { ...pkg, status: 'paused' }).map((p) => p.id), ['p1'])
    assert.deepEqual(strandedProductions([pkg], productions, groups, pkg, { ...pkg, groupIds: [], eventIds: ['p2'] }).map((p) => p.id), ['p1'])
})

test('nothing is stranded while another package still reaches the production', () => {
    const other = { ...pkg, id: 'd2', groupIds: [], eventIds: ['p1'] }
    assert.deepEqual(strandedProductions([pkg, other], productions, groups, pkg, null), [])
    assert.deepEqual(strandedProductions([pkg], productions, groups, pkg, { ...pkg, name: 'Renamed' }), [])
})

test('a production without a running package before the change is not reported', () => {
    const paused = { ...pkg, status: 'paused' }
    assert.deepEqual(strandedProductions([paused], productions, groups, paused, null), [])
})

test('the warning names one project in the singular and several in the plural', () => {
    const t = (key, values) => `${key}|${JSON.stringify(values)}`
    assert.match(packageOnlyWarning([{ title: 'Jazz' }], t), /^\{project\} is sold.*"project":"Jazz"/)
    assert.match(packageOnlyWarning([{ title: 'Jazz' }, { title: 'Minna' }], t), /^\{projects\} are sold.*"projects":"Jazz, Minna"/)
})
