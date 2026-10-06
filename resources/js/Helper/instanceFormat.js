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

/** Formatter je Format-Schlüssel – Budget & Co. formatieren tausende Zellen pro Render */
const formatterCache = new Map()

/**
 * Liefert den (gecachten) Formatter für ein Instanzformat. Intl.NumberFormat-Instanzen werden
 * je Nachkommastellen/Optionen wiederverwendet, das Währungssymbol erst beim ersten Zugriff ermittelt.
 */
export function createInstanceFormatter(format = {}) {
    const { numberLocale, currency, dateFormat } = { ...DEFAULT_INSTANCE_FORMAT, ...(format ?? {}) }
    const cacheKey = `${numberLocale}|${currency}|${dateFormat}`
    const cached = formatterCache.get(cacheKey)
    if (cached) {
        return cached
    }

    const numberFormats = new Map()
    const numberFormatFor = (decimals) => {
        let numberFormat = numberFormats.get(decimals)
        if (!numberFormat) {
            numberFormat = new Intl.NumberFormat(numberLocale, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            })
            numberFormats.set(decimals, numberFormat)
        }

        return numberFormat
    }

    const currencyFormats = new Map()
    const currencyFormatFor = (options) => {
        const optionsKey = options && Object.keys(options).length > 0 ? JSON.stringify(options) : ''
        let currencyFormat = currencyFormats.get(optionsKey)
        if (!currencyFormat) {
            currencyFormat = new Intl.NumberFormat(numberLocale, {
                style: 'currency',
                currency,
                ...options,
            })
            currencyFormats.set(optionsKey, currencyFormat)
        }

        return currencyFormat
    }

    const formatNumber = (value, decimals = 2) => numberFormatFor(decimals).format(toNumber(value))

    const formatCurrency = (value, options = {}) => currencyFormatFor(options).format(toNumber(value))

    const toDate = (value) => {
        if (!value) {
            return null
        }
        // reine Datumsangaben lokal lesen (new Date('2026-12-31') wäre UTC → Vortag westlich von UTC)
        const dateOnly = typeof value === 'string' && /^(\d{4})-(\d{2})-(\d{2})$/.exec(value)
        const date = value instanceof Date
            ? value
            : (dateOnly ? new Date(Number(dateOnly[1]), Number(dateOnly[2]) - 1, Number(dateOnly[3])) : new Date(value))

        return Number.isNaN(date.getTime()) ? null : date
    }

    const formatDate = (value) => {
        const date = toDate(value)
        if (!date) {
            return ''
        }
        const parts = { d: pad(date.getDate()), m: pad(date.getMonth() + 1), Y: String(date.getFullYear()) }

        return dateFormat.replace(/[dmY]/g, (token) => parts[token])
    }

    /** Datum im Instanzformat plus Uhrzeit (H:i) – wie InstanceFormatter::dateTime */
    const formatDateTime = (value) => {
        const date = toDate(value)
        if (!date) {
            return ''
        }

        return `${formatDate(date)} ${pad(date.getHours())}:${pad(date.getMinutes())}`
    }

    let currencySymbol = null
    const formatter = {
        numberLocale,
        currency,
        dateFormat,
        /** Währungssymbol im Format der Instanz (€, CHF, £, $) – erst bei Bedarf ermittelt */
        get currencySymbol() {
            if (currencySymbol === null) {
                currencySymbol = currencyFormatFor({})
                    .formatToParts(0)
                    .find((part) => part.type === 'currency')?.value ?? currency
            }

            return currencySymbol
        },
        formatNumber,
        formatCurrency,
        formatDate,
        formatDateTime,
    }
    formatterCache.set(cacheKey, formatter)

    return formatter
}
