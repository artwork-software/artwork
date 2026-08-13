import { useTranslation } from '@/Composeables/Translation.js'

/**
 * Builds a normalized display descriptor for an inventory article property.
 *
 * Handles both regular articles (values stored on the article itself) and
 * "detailed quantity" articles (values stored per sub-item in
 * `detailedArticleQuantities[*].properties[*]`). For detailed articles the
 * values are aggregated across all sub-items: if every sub-item shares the same
 * value it is shown directly, otherwise the descriptor is flagged as `varied`
 * ("Unterschiedlich") and the distinct values are exposed for a tooltip.
 *
 * Descriptor shape:
 *   {
 *     type: string,            // the property type (room, manufacturer, file, …)
 *     varied: boolean,         // detailed article with differing values
 *     empty: boolean,          // no (uniform) value → render as "-"
 *     text: string,            // display text (or the "Different" label when varied)
 *     file: { path, name }|null,
 *     distinctValues: string[] // formatted distinct values (only when varied)
 *   }
 */
export function useInventoryPropertyDisplay() {
    const $t = useTranslation()

    const fileName = (path) => (typeof path === 'string' ? path.split('/').pop() : '')

    // Formats the value of a single owner (article or detailed sub-item) for display.
    const formatSingle = (owner, property, pivotValue) => {
        switch (property.type) {
            case 'room':
                return owner?.room?.name && owner.room.name !== 'Room not found' ? owner.room.name : ''
            case 'manufacturer':
                return owner?.manufacturer?.name && owner.manufacturer.name !== 'Manufacturer not found'
                    ? owner.manufacturer.name
                    : ''
            case 'date':
                return pivotValue ? new Date(pivotValue).toLocaleDateString() : ''
            case 'time':
                return pivotValue || ''
            case 'datetime':
                return pivotValue ? new Date(pivotValue).toLocaleString() : ''
            case 'checkbox':
                return pivotValue ? $t('Yes') : $t('No')
            case 'file':
                return pivotValue ? fileName(pivotValue) : ''
            default:
                return pivotValue == null ? '' : String(pivotValue)
        }
    }

    // Stable key used to detect whether values differ across detailed sub-items.
    const groupingKey = (owner, property, pivotValue) => {
        if (property.type === 'room') return 'room:' + String(owner?.room?.id ?? '')
        if (property.type === 'manufacturer') return 'man:' + String(owner?.manufacturer?.id ?? '')
        if (property.type === 'checkbox') return pivotValue ? '1' : '0'
        return pivotValue == null ? '' : String(pivotValue)
    }

    // Whether a sub-item has no value for this property. Empty sub-items are
    // ignored when aggregating: if the filled ones agree, that value is shown.
    // A checkbox is never "empty" — unchecked is a real value (No).
    const isEmptyValue = (owner, property, pivotValue) => {
        if (property.type === 'checkbox') return false
        if (property.type === 'room') return !owner?.room?.id
        if (property.type === 'manufacturer') return !owner?.manufacturer?.id
        return pivotValue == null || String(pivotValue).trim() === ''
    }

    const buildUniform = (property, owner, pivotValue) => {
        if (property.type === 'file') {
            return {
                type: 'file',
                varied: false,
                empty: !pivotValue,
                text: '',
                file: pivotValue ? { path: pivotValue, name: fileName(pivotValue) } : null,
                distinctValues: [],
            }
        }

        const text = formatSingle(owner, property, pivotValue)
        return {
            type: property.type,
            varied: false,
            empty: text === '',
            text,
            file: null,
            distinctValues: [],
        }
    }

    const emptyDescriptor = (property) => ({
        type: property.type,
        varied: false,
        empty: true,
        text: '',
        file: null,
        distinctValues: [],
    })

    // The relation serializes as snake_case (`detailed_article_quantities`);
    // fall back to camelCase for safety.
    const detailedQuantities = (article) =>
        article?.detailed_article_quantities ?? article?.detailedArticleQuantities ?? null

    const getPropertyDisplay = (article, property) => {
        // Detailed quantity articles: aggregate values across all sub-items.
        const subs = detailedQuantities(article)
        if (article?.is_detailed_quantity && Array.isArray(subs)) {

            if (subs.length === 0) {
                return emptyDescriptor(property)
            }

            const entries = subs.map((sub) => {
                const subProperty = (sub.properties || []).find((p) => p.id === property.id)
                const pivotValue = subProperty?.pivot?.value ?? null
                return {
                    sub,
                    pivotValue,
                    empty: isEmptyValue(sub, property, pivotValue),
                }
            })

            // Ignore empty sub-items; only compare the ones that actually have a value.
            const filled = entries.filter((e) => !e.empty)

            if (filled.length === 0) {
                return emptyDescriptor(property)
            }

            const distinctKeys = [...new Set(filled.map((e) => groupingKey(e.sub, property, e.pivotValue)))]

            if (distinctKeys.length === 1) {
                return buildUniform(property, filled[0].sub, filled[0].pivotValue)
            }

            // Filled values genuinely differ → "Unterschiedlich" + distinct values for the tooltip.
            const distinctValues = [
                ...new Set(filled.map((e) => formatSingle(e.sub, property, e.pivotValue))),
            ].filter((text) => text !== '')
            return {
                type: property.type,
                varied: true,
                empty: false,
                text: $t('Different'),
                file: null,
                distinctValues,
            }
        }

        // Regular article: look up the value on the article itself.
        const articleProperty = (article?.properties || []).find((p) => p.id === property.id)
        if (!articleProperty) {
            return emptyDescriptor(property)
        }

        return buildUniform(property, article, articleProperty.pivot?.value ?? null)
    }

    // Raw property definitions an article carries itself — for detailed-quantity
    // articles the union across all sub-items, otherwise the article's own list.
    const getArticlePropertyDefinitions = (article) => {
        const detailedSubs = detailedQuantities(article)
        if (article?.is_detailed_quantity && Array.isArray(detailedSubs)) {
            const byId = new Map()
            for (const sub of detailedSubs) {
                for (const property of sub.properties || []) {
                    if (!byId.has(property.id)) {
                        byId.set(property.id, property)
                    }
                }
            }
            return [...byId.values()]
        }
        return article?.properties || []
    }

    // Returns the list of property rows to render for an article's tile / list,
    // filtered to those flagged `show_in_list` and ordered by the property order.
    const getDisplayProperties = (article) => {
        return getArticlePropertyDefinitions(article)
            .filter((property) => property.show_in_list)
            .sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
            .map((property) => ({
                id: property.id,
                name: property.name,
                ...getPropertyDisplay(article, property),
            }))
    }

    return { getPropertyDisplay, getDisplayProperties, getArticlePropertyDefinitions, fileName }
}
