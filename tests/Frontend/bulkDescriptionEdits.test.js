import assert from 'node:assert/strict'
import {beforeEach, test} from 'node:test'
import {
    focusedDescriptionKey,
    openDescriptionEdits,
    rekeyDescriptionEdit,
    resetDescriptionEdits,
} from '../../resources/js/Pages/Projects/Components/BulkComponents/bulkDescriptionEdits.js'

beforeEach(() => resetDescriptionEdits())

test('an open edit moves from the local uid to the server id', () => {
    openDescriptionEdits.set('local-1', {draft: 'Probe'})
    focusedDescriptionKey.value = 'local-1'

    rekeyDescriptionEdit('local-1', 42)

    assert.equal(openDescriptionEdits.has('local-1'), false)
    assert.deepEqual(openDescriptionEdits.get(42), {draft: 'Probe'})
    assert.equal(focusedDescriptionKey.value, 42)
})

test('rows without an open edit are ignored', () => {
    rekeyDescriptionEdit('local-2', 43)

    assert.equal(openDescriptionEdits.size, 0)
    assert.equal(focusedDescriptionKey.value, null)
})

test('leaving the list forgets all drafts', () => {
    openDescriptionEdits.set(7, {draft: 'alt'})
    focusedDescriptionKey.value = 7

    resetDescriptionEdits()

    assert.equal(openDescriptionEdits.size, 0)
    assert.equal(focusedDescriptionKey.value, null)
})
