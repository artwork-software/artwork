import assert from 'node:assert/strict'
import test from 'node:test'
import { createInstanceFormatter } from '../../resources/js/Helper/instanceFormat.js'

// Intl liefert je nach Locale geschützte Leerzeichen/Apostrophe – für den Vergleich vereinheitlichen
const plain = (text) => text.replace(/[  ]/g, ' ').replace(/’/g, "'")

test('defaults keep the former German format', () => {
    const { formatNumber, formatCurrency, formatDate } = createInstanceFormatter()

    assert.equal(formatNumber(1234.5), '1.234,50')
    assert.equal(plain(formatCurrency(1234.5)), '1.234,50 €')
    assert.equal(formatDate('2026-12-31'), '31.12.2026')
})

test('a Swiss instance formats with apostrophes and francs', () => {
    const swiss = createInstanceFormatter({ numberLocale: 'de-CH', currency: 'CHF', dateFormat: 'd.m.Y' })

    assert.equal(plain(swiss.formatNumber(1234567.891)), "1'234'567.89")
    assert.equal(plain(swiss.formatCurrency(1234.5)), "CHF 1'234.50")
})

test('date formats and German number input are honoured', () => {
    const iso = createInstanceFormatter({ dateFormat: 'Y-m-d' })
    const us = createInstanceFormatter({ numberLocale: 'en-US', currency: 'USD', dateFormat: 'm/d/Y' })

    assert.equal(iso.formatDate('2026-03-04'), '2026-03-04')
    assert.equal(us.formatDate('2026-03-04'), '03/04/2026')
    assert.equal(us.formatNumber('1.234,5'), '1,234.50')
    assert.equal(us.formatCurrency(null), '$0.00')
    assert.equal(iso.formatDate(null), '')
})
