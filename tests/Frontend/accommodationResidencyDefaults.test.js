import assert from 'node:assert/strict'
import test from 'node:test'
import {
    accommodationResidencyDefaults,
} from '../../resources/js/Pages/Projects/Components/ArtistResidenciesComponents/accommodationResidencyDefaults.js'

const generalDefaults = {dailyAllowance: 28, breakfastDeduction: 5.6}

test('values of the accommodation take precedence over the general defaults', () => {
    assert.deepEqual(
        accommodationResidencyDefaults({daily_allowance: 35, breakfast_deduction_per_day: 7.5}, generalDefaults),
        {daily_allowance: 35, breakfast_deduction_per_day: 7.5},
    )
})

test('an explicit 0 at the accommodation is kept instead of falling back', () => {
    assert.deepEqual(
        accommodationResidencyDefaults({daily_allowance: 0, breakfast_deduction_per_day: 0}, generalDefaults),
        {daily_allowance: 0, breakfast_deduction_per_day: 0},
    )
})

test('falls back per field to the general defaults when the accommodation has no value', () => {
    assert.deepEqual(
        accommodationResidencyDefaults({daily_allowance: null, breakfast_deduction_per_day: 4}, generalDefaults),
        {daily_allowance: 28, breakfast_deduction_per_day: 4},
    )
})

test('uses 0 when neither the accommodation nor the general settings define a value', () => {
    assert.deepEqual(
        accommodationResidencyDefaults(null, {}),
        {daily_allowance: 0, breakfast_deduction_per_day: 0},
    )
})
