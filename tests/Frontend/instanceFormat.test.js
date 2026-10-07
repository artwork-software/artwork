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

test('the currency symbol follows the instance currency', () => {
    assert.equal(createInstanceFormatter().currencySymbol, '€')
    assert.equal(createInstanceFormatter({ numberLocale: 'de-CH', currency: 'CHF' }).currencySymbol, 'CHF')
    assert.equal(createInstanceFormatter({ numberLocale: 'en-GB', currency: 'GBP' }).currencySymbol, '£')
})

test('formatters are cached per format and reuse their Intl instances', () => {
    const OriginalNumberFormat = Intl.NumberFormat
    let constructed = 0
    Intl.NumberFormat = function (...args) {
        constructed++
        return new OriginalNumberFormat(...args)
    }
    try {
        const format = { numberLocale: 'fr-FR', currency: 'EUR', dateFormat: 'd/m/Y' }
        const formatter = createInstanceFormatter(format)
        assert.equal(constructed, 0, 'creating the formatter must not build Intl instances (symbol is lazy)')

        assert.equal(createInstanceFormatter({ ...format }), formatter)

        formatter.formatNumber(1)
        formatter.formatNumber(2)
        formatter.formatCurrency(1)
        formatter.formatCurrency(2)
        assert.equal(constructed, 2)

        formatter.formatNumber(3, 0)
        formatter.formatCurrency(3, { maximumFractionDigits: 0 })
        formatter.formatCurrency(4, { maximumFractionDigits: 0 })
        assert.equal(constructed, 4)

        assert.equal(formatter.currencySymbol, '€')
        assert.equal(formatter.currencySymbol, '€')
        assert.equal(constructed, 4, 'the currency symbol reuses the default currency format')

        assert.notEqual(createInstanceFormatter({ ...format, dateFormat: 'Y-m-d' }), formatter)
    } finally {
        Intl.NumberFormat = OriginalNumberFormat
    }
})

test('date-times use the instance date format plus 24h time', () => {
    const us = createInstanceFormatter({ numberLocale: 'en-US', currency: 'USD', dateFormat: 'm/d/Y' })

    assert.equal(us.formatDateTime(new Date(2026, 2, 4, 9, 5)), '03/04/2026 09:05')
    assert.equal(createInstanceFormatter().formatDateTime(new Date(2026, 11, 31, 23, 59)), '31.12.2026 23:59')
    assert.equal(us.formatDateTime(null), '')
    assert.equal(us.formatDateTime('not a date'), '')
    assert.equal(us.formatDate('not a date'), '')
})

test('budget, funding sources, document requests and BI snapshots use the instance date format', async () => {
    const { readFileSync } = await import('node:fs')
    const files = {
        'resources/js/Layouts/Components/CellDetailModal.vue': /\.formatDateTime\(date\)/,
        'resources/js/Layouts/Components/MoneySourceSidenav.vue': /instanceFormat\.formatDateTime\(date\)/,
        'resources/js/Pages/DocumentRequests/Components/DocumentRequestDetailModal.vue': /\.formatDate\(value\)/,
        'resources/js/Pages/Projects/Components/BiComponents/BiSnapshotSection.vue': /instanceFormat\.formatDate\(value\)/,
    }
    for (const [file, usage] of Object.entries(files)) {
        const source = readFileSync(new URL(`../../${file}`, import.meta.url), 'utf8')
        assert.match(source, usage, file)
        assert.doesNotMatch(source, /toLocaleString\('de-DE'|`\$\{day\}\.\$\{month\}\.\$\{year\}`/, file)
    }
})

test('budget sums, booking amounts, residency costs and BI percentages use the instance number format', async () => {
    const { readFileSync } = await import('node:fs')
    const files = {
        'resources/js/Pages/Projects/Components/Budget/RelevantBudgetDataSumModal.vue': /formatNumber\(value \?\? 0, 2\)/,
        'resources/js/Pages/Projects/Components/ArtistResidenciesComponents/AddEditArtistResidenciesModal.vue': /:locale="numberLocale"/,
        'resources/js/Pages/Projects/Components/BiComponents/BiKpiHeader.vue': /instanceFormat\.formatNumber\(v, 1\)/,
        'resources/js/Layouts/Components/Budget/BookingModalContents.vue': /toCurrencyString\(booking\.buchungsbetrag\)/,
        'resources/js/Layouts/Components/SageAssignedDataModal.vue': /this\.toCurrencyString\(value\)/,
        'resources/js/Pages/Projects/Tab/Components/BudgetInformations.vue': /formatCurrency\(amount \|\| 0\)/,
    }
    for (const [file, usage] of Object.entries(files)) {
        const source = readFileSync(new URL(`../../${file}`, import.meta.url), 'utf8')
        assert.match(source, usage, file)
        assert.doesNotMatch(source, /['"]de-DE['"]|replace\('\.', ','\)/, file)
    }
})
