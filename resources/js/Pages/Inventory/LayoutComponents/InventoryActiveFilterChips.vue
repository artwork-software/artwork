<!--
    Immer sichtbare "Aktive Filter"-Chips direkt unter der Funktionsleiste.
    Jedes Kriterium (Suche, Status, Vorlage, Eigenschaften inkl. Operator,
    Tags) ist einzeln entfernbar; "Alle Filter zurücksetzen" leert zusätzlich
    Suche und Status. (Die Status-Pills selbst rendert Index in der Leiste.)
-->
<template>
    <div>
        <div v-if="activeChips.length" class="flex flex-wrap items-center gap-2">
            <span class="text-[11px] uppercase tracking-wide text-text-subtle">{{ $t('Active filters') }}</span>

            <div
                v-for="chip in activeChips"
                :key="chip.key"
                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                :class="chip.class"
                :style="chip.style"
            >
                <component v-if="chip.icon" :is="chip.icon" class="size-3.5 shrink-0" />
                <span class="max-w-[260px] truncate">{{ chip.label }}</span>
                <button type="button" class="hover:opacity-70" :title="$t('Clear')" @click="chip.onRemove">
                    <component :is="IconX" class="size-3.5" />
                </button>
            </div>

            <button
                type="button"
                class="text-xs text-text-subtle underline underline-offset-2 hover:text-danger transition-colors"
                @click="onResetAll"
            >
                {{ $t('Reset all filters') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { IconBookmark, IconSearch, IconX } from '@tabler/icons-vue'
import { useTranslation } from '@/Composeables/Translation.js'
import { useInventoryFilters } from '@/Pages/Inventory/Composables/useInventoryFilters.js'

const $t = useTranslation()

const props = defineProps({
    countsByStatus: {
        type: Object,
        required: false,
        default: () => ({}),
    },
    activeStatusId: {
        type: Number,
        required: false,
        default: null,
    },
    // Lokaler Suchzustand der Funktionsleiste (urlParameters kommen bei
    // partiellen Reloads nicht mit, daher via Props von Index).
    search: {
        type: String,
        required: false,
        default: '',
    },
    searchPropertyId: {
        type: Number,
        required: false,
        default: null,
    },
})

const emit = defineEmits(['clear-search', 'reset'])

const {
    appliedFilters,
    activePresetId,
    activePresetName,
    allProperties,
    allTags,
    allStatuses,
    appliedTagIds,
    propertyById,
    operatorLabel,
    removePropertyFilter,
    removeTag,
    clearPreset,
    clearStatus,
    resetAllFilters,
    tagChipStyle,
} = useInventoryFilters()

const searchChip = computed(() => {
    const term = String(props.search || '').trim()
    if (!term) return null
    const scope = props.searchPropertyId
        ? (allProperties.value.find((p) => p.id === props.searchPropertyId)?.name ?? null)
        : null
    return {
        key: 'search',
        label: scope ? $t('Search in {0}: "{1}"', [scope, term]) : $t('Search: "{0}"', [term]),
        class: 'bg-surface-sunken text-text-muted border-border-subtle',
        style: null,
        icon: IconSearch,
        onRemove: () => emit('clear-search'),
    }
})

const statusChip = computed(() => {
    if (!props.activeStatusId) return null
    // Fallback auf die vollständige Status-Liste: bei 0 Treffern kommt
    // countsByStatus leer zurück, der Status filtert aber weiterhin —
    // der Chip muss sichtbar bleiben, sonst ist der Filter unsichtbar aktiv.
    const row = props.countsByStatus?.[String(props.activeStatusId)]
        ?? allStatuses.value.find((s) => s.id === props.activeStatusId)
    if (!row) return null
    const base = row.color || '#6b7280'
    return {
        key: 'status',
        label: `${$t('Status')}: ${row.name}`,
        class: '',
        style: {
            backgroundColor: base + '14',
            borderColor: base,
            color: '#1f2937',
        },
        icon: null,
        onRemove: clearStatus,
    }
})

const presetChip = computed(() => {
    if (!activePresetId.value || !activePresetName.value) return null
    return {
        key: 'preset',
        label: `${$t('Preset')}: ${activePresetName.value}`,
        class: 'bg-white text-text-muted border-border',
        style: null,
        icon: IconBookmark,
        onRemove: clearPreset,
    }
})

const propertyChips = computed(() =>
    appliedFilters.value.map((f) => {
        const property = propertyById(f.property_id)
        const name = property?.name ?? `#${f.property_id}`
        const isCheckbox = property?.type === 'checkbox'
        const op = (isCheckbox || property?.type === 'selection') ? null : operatorLabel(f.operator)
        const value = typeof f.value === 'boolean' ? '' : String(f.value ?? '')

        let label = name
        if (op) label += ` ${op.toLowerCase()}`
        if (value && !isCheckbox) label += `: ${value}`

        return {
            key: `p-${f.property_id}`,
            label,
            class: 'bg-accent-50 text-accent-700 border-accent-200',
            style: null,
            icon: null,
            onRemove: () => removePropertyFilter(f.property_id),
        }
    })
)

const tagChips = computed(() =>
    allTags.value
        .filter((t) => appliedTagIds.value.includes(t.id))
        .map((t) => ({
            key: `t-${t.id}`,
            label: t.name,
            class: '',
            style: tagChipStyle(t),
            icon: null,
            onRemove: () => removeTag(t.id),
        }))
)

const activeChips = computed(() => [
    ...(searchChip.value ? [searchChip.value] : []),
    ...(statusChip.value ? [statusChip.value] : []),
    ...(presetChip.value ? [presetChip.value] : []),
    ...propertyChips.value,
    ...tagChips.value,
])

const onResetAll = () => {
    // Index leert die lokalen Suchfelder, der Reload läuft hier zentral.
    emit('reset')
    resetAllFilters()
}
</script>

<style scoped></style>
