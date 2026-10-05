/**
 * Zahlen, Beträge und Daten im Format der Instanz (Einstellungen → Tool → Regionale Formate).
 * Gegenstück zu InstanceFormatter.php; die Werte kommen als Inertia-Prop "instanceFormat".
 */
export const DEFAULT_INSTANCE_FORMAT = { numberLocale: 'de-DE', currency: 'EUR', dateFormat: 'd.m.Y' }

const toNumber = (value) => {
    if (value === null || value === undefined || value === '') {
        return 0
    }
    if (typeof value === 'number') {
        return value
    }
    const text = String(value).trim()
    // deutsche Eingaben wie "1.234,5" tolerieren
    const normalized = /^-?[\d.]*,\d*$/.test(text) ? text.replace(/\./g, '').replace(',', '.') : text
    const number = Number(normalized)

    return Number.isFinite(number) ? number : 0
}

const pad = (value) => String(value).padStart(2, '0')

export function createInstanceFormatter(format = {}) {
    const { numberLocale, currency, dateFormat } = { ...DEFAULT_INSTANCE_FORMAT, ...(format ?? {}) }

    const formatNumber = (value, decimals = 2) => new Intl.NumberFormat(numberLocale, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(toNumber(value))

    const formatCurrency = (value, options = {}) => new Intl.NumberFormat(numberLocale, {
        style: 'currency',
        currency,
        ...options,
    }).format(toNumber(value))

    const formatDate = (value) => {
        if (!value) {
            return ''
        }
        // reine Datumsangaben lokal lesen (new Date('2026-12-31') wäre UTC → Vortag westlich von UTC)
        const dateOnly = typeof value === 'string' && /^(\d{4})-(\d{2})-(\d{2})$/.exec(value)
        const date = value instanceof Date
            ? value
            : (dateOnly ? new Date(Number(dateOnly[1]), Number(dateOnly[2]) - 1, Number(dateOnly[3])) : new Date(value))
        if (Number.isNaN(date.getTime())) {
            return ''
        }
        const parts = { d: pad(date.getDate()), m: pad(date.getMonth() + 1), Y: String(date.getFullYear()) }

        return dateFormat.replace(/[dmY]/g, (token) => parts[token])
    }

    return { numberLocale, currency, dateFormat, formatNumber, formatCurrency, formatDate }
}
