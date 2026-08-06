import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

// Props, die bei jedem Filter-Reload mitkommen müssen, damit Liste, Sidebar,
// Status-Zähler und Chips synchron bleiben.
export const INVENTORY_FILTER_RELOAD_PROPS = [
    'articles',
    'countsByStatus',
    'categories',
    'currentCategory',
    'hasActiveFilter',
    'activeStatusId',
    'appliedFilters',
    'appliedTagIds',
    'activeFilterPresetId',
]

// Kompakter Listbox-Button (42px) — die globale .menu-button-Klasse hat py-4
// (54px) und passt nicht zur Höhe von BaseInput (is-small) und .ui-button.
export const COMPACT_LISTBOX_BUTTON_CLASS =
    'bg-white w-full flex items-center justify-between text-left rounded-md border border-gray-200 ' +
    'shadow-sm px-3 py-2.5 text-sm font-normal text-gray-900 ' +
    'focus:outline-none focus:ring-1 focus:ring-accent-600'

export const FILTER_OPERATORS = [
    { name: 'Enthält', type: 'like', allowedTypes: ['string', 'room', 'manufacturer'] },
    { name: 'Beginnt mit', type: 'starts_with', allowedTypes: ['string', 'room', 'manufacturer'] },
    { name: 'Endet mit', type: 'ends_with', allowedTypes: ['string', 'room', 'manufacturer'] },
    { name: 'Genau', type: 'exact', allowedTypes: ['string', 'date'] },
    { name: 'Kleiner als', type: 'less_than', allowedTypes: ['string', 'date'] },
    { name: 'Größer als', type: 'greater_than', allowedTypes: ['string', 'date'] },
    { name: 'Bis', type: 'until', allowedTypes: ['date'] },
    { name: 'Von', type: 'from', allowedTypes: ['date'] },
    { name: 'Gleich', type: 'equals', allowedTypes: ['string', 'date', 'room', 'manufacturer'] },
    { name: 'Ungleich', type: 'not_equals', allowedTypes: ['boolean', 'date'] },
    { name: 'Ist leer', type: 'is_null', allowedTypes: ['string', 'date', 'boolean'] },
    { name: 'Enthält nicht', type: 'not_like', allowedTypes: ['string'] },
    { name: 'Datum vor', type: 'date_before', allowedTypes: ['date', 'datetime', 'time'] },
    { name: 'Datum nach', type: 'date_after', allowedTypes: ['date', 'datetime', 'time'] },
]

/**
 * Geteilter Lese- und Mutations-Zustand der Inventar-Filter.
 * Quelle der Wahrheit sind die Inertia-Page-Props (appliedFilters, appliedTagIds,
 * activeFilterPresetId) — Trigger-Modal und Chips-Zeile arbeiten beide darauf.
 */
export function useInventoryFilters() {
    const page = usePage()

    const appliedFilters = computed(() =>
        Array.isArray(page.props.appliedFilters) ? page.props.appliedFilters : []
    )
    const appliedTagIds = computed(() =>
        Array.isArray(page.props.appliedTagIds)
            ? page.props.appliedTagIds.map((id) => Number(id)).filter((n) => !Number.isNaN(n))
            : []
    )
    const activePresetId = computed(() => page.props.activeFilterPresetId ?? null)
    const filterPresets = computed(() => page.props.filterPresets ?? [])
    const allProperties = computed(() => page.props.properties ?? [])
    const allTags = computed(() => (Array.isArray(page.props.tags) ? page.props.tags : []))
    const allStatuses = computed(() => (Array.isArray(page.props.statuses) ? page.props.statuses : []))

    const propertyById = (id) => allProperties.value.find((p) => p.id === id) ?? null

    const operatorLabel = (operator) =>
        FILTER_OPERATORS.find((f) => f.type === operator)?.name ?? null

    const activePresetName = computed(() => {
        if (!activePresetId.value) return null
        return filterPresets.value.find((p) => p.id === activePresetId.value)?.name ?? null
    })

    const activeFilterCount = computed(
        () => appliedFilters.value.length + appliedTagIds.value.length
    )

    /**
     * Zentraler Apply: schreibt Filter/Tags/Preset (und optional weitere Params)
     * explizit in die Anfrage, damit die Resolver-Präzedenz deterministisch bleibt.
     */
    const apply = ({ filters, tagIds, presetId = null, extra = {} }) => {
        router.reload({
            data: {
                filters: JSON.stringify(filters),
                tag_ids: tagIds,
                filter_preset_id: presetId,
                ...extra,
            },
            preserveScroll: true,
            only: INVENTORY_FILTER_RELOAD_PROPS,
        })
    }

    const removePropertyFilter = (propertyId) => {
        apply({
            filters: appliedFilters.value.filter((f) => f.property_id !== propertyId),
            tagIds: appliedTagIds.value,
        })
    }

    const removeTag = (tagId) => {
        apply({
            filters: appliedFilters.value,
            tagIds: appliedTagIds.value.filter((id) => id !== tagId),
        })
    }

    // Preset lösen, aktuelle Werte als manuelle Filter behalten.
    const clearPreset = () => {
        apply({
            filters: appliedFilters.value,
            tagIds: appliedTagIds.value,
            presetId: null,
        })
    }

    // Setzt ALLES zurück: Eigenschaften, Tags, Preset, Suche und Status.
    const resetAllFilters = () => {
        apply({
            filters: [],
            tagIds: [],
            presetId: null,
            extra: { search: '', search_property_id: null, status_id: null },
        })
    }

    const clearStatus = () => {
        router.reload({
            data: { status_id: null },
            preserveScroll: true,
            only: INVENTORY_FILTER_RELOAD_PROPS,
        })
    }

    const tagChipStyle = (tag) => {
        const base = tag?.color || '#2563eb'
        return {
            backgroundColor: base + '10',
            borderColor: base + '40',
            color: base,
        }
    }

    return {
        appliedFilters,
        appliedTagIds,
        activePresetId,
        activePresetName,
        activeFilterCount,
        filterPresets,
        allProperties,
        allTags,
        allStatuses,
        propertyById,
        operatorLabel,
        apply,
        removePropertyFilter,
        removeTag,
        clearPreset,
        clearStatus,
        resetAllFilters,
        tagChipStyle,
    }
}
