import assert from 'node:assert/strict'
import test from 'node:test'
import {
    projectPeriodLabel,
    shouldCheckSimilarNames,
    shouldHoldCreation,
    similarNameKey,
} from '../../resources/js/Helper/projectSimilarNames.js'

test('normalizes whitespace and case for the check key', () => {
    assert.equal(similarNameKey('  Faust   II '), 'faust ii')
    assert.equal(similarNameKey(null), '')
})

test('checks only names with at least three characters', () => {
    assert.equal(shouldCheckSimilarNames(' ab '), false)
    assert.equal(shouldCheckSimilarNames('abc'), true)
    assert.equal(shouldCheckSimilarNames(undefined), false)
})

test('holds creation only when matches appeared through the click', () => {
    const matches = [{ id: 1 }]
    assert.equal(shouldHoldCreation({ matches, visibleKey: null, currentKey: 'faust' }), true)
    assert.equal(shouldHoldCreation({ matches, visibleKey: 'faust', currentKey: 'faust' }), false)
    assert.equal(shouldHoldCreation({ matches: [], visibleKey: null, currentKey: 'faust' }), false)
    assert.equal(shouldHoldCreation({ matches, visibleKey: 'faus', currentKey: 'faust' }), true)
})

test('formats the project period without times', () => {
    assert.equal(projectPeriodLabel('01.03.2026 19:00', '15.04.2026 22:00'), '01.03.2026 – 15.04.2026')
    assert.equal(projectPeriodLabel('01.03.2026 19:00', '01.03.2026 22:00'), '01.03.2026')
    assert.equal(projectPeriodLabel(null, null), '')
    assert.equal(projectPeriodLabel('01.03.2026 19:00', null), '01.03.2026')
})
