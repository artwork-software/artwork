import assert from 'node:assert/strict'
import test from 'node:test'
import {
    canBePlacedInFolder,
    componentIcon,
    folderBlockReason,
    requiresScope,
} from '../../resources/js/Pages/Projects/Tab/projectTabComponentRules.js'

test('only documents, comments and checklists ask for a tab scope', () => {
    assert.equal(requiresScope('CommentTab'), true)
    assert.equal(requiresScope('ChecklistComponent'), true)
    assert.equal(requiresScope('TextField'), false)
})

test('folders and large layout components stay out of folders', () => {
    assert.equal(canBePlacedInFolder('TextField'), true)
    assert.equal(canBePlacedInFolder('BusinessIntelligenceComponent'), true)
    assert.equal(canBePlacedInFolder('CalendarTab'), false)
    assert.equal(canBePlacedInFolder('DisclosureComponent'), false)
})

test('the block reason is a translation key per case', () => {
    assert.equal(folderBlockReason('DisclosureComponent'), 'Folders cannot be nested inside folders')
    assert.equal(folderBlockReason('ShiftTab'), 'This component cannot be placed inside a folder')
    assert.equal(folderBlockReason('Title'), null)
})

test('unknown types have no icon instead of a broken one', () => {
    assert.equal(componentIcon('Checkbox'), 'IconCheckbox')
    assert.equal(componentIcon('NoSuchComponent'), null)
})
