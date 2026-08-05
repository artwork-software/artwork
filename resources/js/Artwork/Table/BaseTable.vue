<template>
    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <table class="relative min-w-full">
                    <!-- THEAD -->
                    <thead>
                    <tr>
                        <th
                            v-for="col in normalizedColumns"
                            :key="col.key"
                            scope="col"
                            :class="[
                  'sticky top-0 z-10 bg-surface-header border-b border-border',
                  'py-2 font-lexend font-semibold text-[11px] uppercase tracking-[0.08em] text-accent-600',
                  col.headerClass,
                  isNumericColumn(col) ? 'text-right' : col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                  col.key === normalizedColumns[0]?.key ? 'pl-4 pr-3' : 'px-3'
                ]"
                            :style="col.width ? { width: col.width } : undefined"
                        >
                            <button
                                v-if="col.sortable"
                                type="button"
                                class="group inline-flex items-center gap-1 uppercase tracking-[0.08em] hover:text-accent-700"
                                @click="onToggleSort(col.key)"
                            >
                                <span class="truncate">{{ $t(col.label) }}</span>
                                <PropertyIcon v-if="sort.key !== col.key" name="IconSelector" class="size-3.5 text-text-subtle" :stroke-width="1.5" />
                                <PropertyIcon v-else-if="sort.direction === 'asc'" name="IconChevronUp" class="size-3.5" :stroke-width="1.5" />
                                <PropertyIcon v-else name="IconChevronDown" class="size-3.5" :stroke-width="1.5" />
                            </button>
                            <span v-else class="truncate">{{ $t(col.label) }}</span>
                        </th>

                        <th v-if="$slots['header-extra']" scope="col" class="sticky top-0 z-10 bg-surface-header border-b border-border py-2 pr-4 pl-3 sm:pr-0">
                            <slot name="header-extra" />
                        </th>
                    </tr>
                    </thead>

                    <!-- TBODY -->
                    <tbody v-if="displayRows.length" class="divide-y divide-border-hairline bg-surface">
                    <tr v-for="row in displayRows" :key="row[rowKey]" class="group hover:bg-surface-hover" :class="rowHeightClass">
                        <td
                            v-for="(col, idx) in normalizedColumns"
                            :key="col.key"
                            :class="[
                          cellPaddingYClass, 'text-[13px] whitespace-nowrap',
                          col.cellClass,
                          isNumericColumn(col) ? 'text-right tabular-nums' : col.align === 'right' ? 'text-right tabular-nums' : col.align === 'center' ? 'text-center' : 'text-left',
                          idx === 0 ? 'pl-4 pr-3' : 'px-3'
                        ]"
                        >
                            <!-- Zell-Slot: cell-<key> -->
                            <slot :name="`cell-${col.key}`" :row="row" :value="getValue(row,col)" :col="col">
                                <!-- Default-Renderer -->
                                <a
                                    v-if="col.type === 'link' && getValue(row, col) && getValue(row, col) !== '-'"
                                    :href="ensureProtocol(getValue(row, col))"
                                    target="_blank"
                                    class="text-accent-600 hover:text-accent-700 hover:underline"
                                    @click.stop
                                >{{ getValue(row, col) }}</a>
                                <span v-else class="text-text">{{ getValue(row, col) }}</span>
                            </slot>
                        </td>

                        <!-- Actions: bei Hover/Fokus sichtbar, Platz bleibt reserviert (invisible statt display:none/opacity) -->
                        <td v-if="$slots['row-actions']" class="pr-4 pl-3 text-right text-[13px] font-medium whitespace-nowrap sm:pr-0" :class="cellPaddingYClass">
                            <div class="invisible group-hover:visible group-focus-within:visible">
                                <slot name="row-actions" :row="row" />
                            </div>
                        </td>
                    </tr>
                    </tbody>

                    <!-- EMPTY -->
                    <tbody v-else class="bg-surface">
                    <tr>
                        <td :colspan="normalizedColumns.length + ($slots['row-actions'] ? 1 : 0)" class="px-4 py-4">
                            <div class="border border-dashed border-border rounded-lg px-4 py-10 text-center">
                                <div class="mx-auto max-w-md">
                                    <div class="font-lexend font-semibold text-[15px] text-text">{{ $t(emptyTitle) }}</div>
                                    <p class="mt-1 text-xs text-text-muted">{{ $t(emptyMessage) }}</p>
                                    <div v-if="showResetButton" class="mt-4 flex justify-center">
                                        <BaseUIButton
                                            variant="secondary"
                                            size="sm"
                                            hide-icon
                                            @click="emit('reset')"
                                        >
                                            {{ $t(resetLabel) }}
                                        </BaseUIButton>
                                    </div>
                                    <div class="mt-4">
                                        <slot name="empty-cta" />
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    </tbody>

                </table>

                <!-- Pagination (optional) -->
                <div v-if="showPagination" class="flex items-center justify-between gap-3 pt-4">
                    <div class="text-[13px] text-text-muted tabular-nums">
                        {{ pageFrom }}–{{ pageTo }} {{ $t(ofLabel) }} {{ total }}
                    </div>
                    <div class="flex items-center gap-2">
                        <BaseUIButton
                            variant="secondary"
                            size="sm"
                            hide-icon
                            :disabled="page <= 1"
                            @click="setPage(page - 1)"
                        >
                            {{ $t(prevLabel) }}
                        </BaseUIButton>
                        <BaseUIButton
                            variant="secondary"
                            size="sm"
                            hide-icon
                            :disabled="page >= totalPages"
                            @click="setPage(page + 1)"
                        >
                            {{ $t(nextLabel) }}
                        </BaseUIButton>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script lang="ts">
/**
 * v2 »Bühnenlicht«: Klassen für eine ausgewählte Zeile (BaseTable hat selbst
 * keine Auswahl-Logik — Konsumenten mit eigenem selected-Konzept wenden diese
 * Klassen auf ihre aktive <tr> an, z. B. via cellClass/eigene Row-Slots):
 *   aktive Zeile = bg-accent-50 + Inset-Ring in accent-600.
 */
export const ROW_SELECTED_CLASSES = 'bg-accent-50 shadow-[inset_0_0_0_2px_#276293]'
</script>

<script setup lang="ts">
import { computed, reactive, watch, toRefs } from 'vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

type Align = 'left' | 'center' | 'right'
type SortDirection = 'asc' | 'desc' | null

export interface TableColumn {
    key: string                       // Feldname/Slot-Key
    label: string                     // Spaltenüberschrift
    align?: Align                     // Ausrichtung
    sortable?: boolean                // sortierbar?
    width?: string                    // optional (z.B. '220px')
    type?: string                     // optionaler Spaltentyp (z.B. 'link' oder 'number' → rechtsbündig + tabular-nums)
    accessor?: (row: any) => any      // eigener Wertleser
    headerClass?: string              // optionale Klassen Head
    cellClass?: string                // optionale Klassen Cell
}

const props = withDefaults(defineProps<{
    rows: any[]
    columns: TableColumn[]
    rowKey?: string
    // Sortierung: kontrolliert oder intern
    sortKey?: string | null
    sortDirection?: SortDirection
    // Paginierung (optional)
    pageSize?: number | null
    page?: number
    total?: number | null            // bei serverseitiger Pagination
    // Texte (i18n-Keys, werden per $t() übersetzt; Overrides bleiben möglich)
    emptyTitle?: string
    emptyMessage?: string
    prevLabel?: string
    nextLabel?: string
    ofLabel?: string
    // Empty-State: optionaler Reset-Button (emittiert 'reset')
    showResetButton?: boolean
    resetLabel?: string
    // v2: Zeilenhöhe — 'default' 40px, 'project' 44px, 'compact' 34px
    density?: 'default' | 'project' | 'compact'
}>(), {
    rowKey: 'id',
    sortKey: null,
    sortDirection: null,
    pageSize: null,
    page: 1,
    total: null,
    emptyTitle: 'No entries',
    emptyMessage: 'There is currently no data available.',
    prevLabel: 'Previous page',
    nextLabel: 'Next page',
    ofLabel: 'of',
    showResetButton: false,
    resetLabel: 'Reset filters',
    density: 'default'
})

// v2-Dichte: tr-Höhe wirkt in Tabellen als Mindesthöhe (Inhalt zentriert via
// default vertical-align:middle); compact reduziert zusätzlich das Zellpadding,
// damit 34px erreichbar sind.
const rowHeightClass = computed(() =>
    props.density === 'project' ? 'h-11' : props.density === 'compact' ? 'h-[34px]' : 'h-10'
)
const cellPaddingYClass = computed(() =>
    props.density === 'compact' ? 'py-1' : 'py-2'
)

const emit = defineEmits<{
    (e: 'update:sortKey', value: string | null): void
    (e: 'update:sortDirection', value: SortDirection): void
    (e: 'update:page', value: number): void
    (e: 'sort-change', payload: { key: string | null, direction: SortDirection }): void
    (e: 'page-change', payload: { page: number, pageSize: number | null }): void
    (e: 'reset'): void
}>()

// Spalten normalisieren
const normalizedColumns = computed<TableColumn[]>(() =>
    (props.columns || []).map(c => ({
        align: 'left',
        sortable: false,
        ...c
    }))
)

// numerische Spalten: rechtsbündig + tabular-nums
function isNumericColumn(col?: TableColumn) {
    return col?.type === 'number'
}

// Sort-Model (uncontrolled fallback)
const internalSort = reactive<{ key: string | null, direction: SortDirection }>({
    key: props.sortKey ?? null,
    direction: props.sortDirection ?? null
})

watch(() => [props.sortKey, props.sortDirection], ([k, d]) => {
    internalSort.key = k ?? null
    internalSort.direction = (d ?? null) as SortDirection
})

// Derzeit aktiver Sort-Zustand (kontrolliert oder intern)
const sort = computed(() => ({
    key: props.sortKey ?? internalSort.key,
    direction: (props.sortDirection ?? internalSort.direction) as SortDirection
}))

function onToggleSort(key: string) {
    let next: SortDirection = 'asc'
    if (sort.value.key === key) {
        next = sort.value.direction === 'asc' ? 'desc' : sort.value.direction === 'desc' ? null : 'asc'
    }
    // update local if uncontrolled
    internalSort.key = key
    internalSort.direction = next

    emit('update:sortKey', key)
    emit('update:sortDirection', next)
    emit('sort-change', { key, direction: next })
}

// Daten (clientseitig) sortieren, wenn Sort aktiv und kein Serverside total gesetzt
const isServerSide = computed(() => props.total !== null)
const sortedRows = computed(() => {
    if (!sort.value.key || !sort.value.direction) return props.rows ?? []
    if (isServerSide.value) return props.rows ?? [] // Server macht Sortierung
    const col = normalizedColumns.value.find(c => c.key === sort.value.key)
    const arr = [...(props.rows ?? [])]
    const dir = sort.value.direction === 'asc' ? 1 : -1
    return arr.sort((a, b) => {
        const va = getValue(a, col)
        const vb = getValue(b, col)
        if (va == null && vb == null) return 0
        if (va == null) return -1 * dir
        if (vb == null) return 1 * dir
        if (typeof va === 'number' && typeof vb === 'number') return (va - vb) * dir
        return String(va).localeCompare(String(vb)) * dir
    })
})

// Pagination
const showPagination = computed(() => !!props.pageSize)
const totalItems = computed(() => (props.total ?? sortedRows.value.length))
const totalPages = computed(() => props.pageSize ? Math.max(1, Math.ceil(totalItems.value / props.pageSize)) : 1)
const page = computed(() => props.page ?? 1)
const pageFrom = computed(() => {
    if (!props.pageSize) return 1
    return (page.value - 1) * props.pageSize + 1
})
const pageTo = computed(() => {
    if (!props.pageSize) return sortedRows.value.length
    return Math.min(page.value * props.pageSize, totalItems.value)
})
const displayRows = computed(() => {
    if (!props.pageSize) return sortedRows.value
    // Serverseitig: wir erwarten bereits die aktuelle Seite in rows
    if (isServerSide.value) return props.rows ?? []
    const start = (page.value - 1) * props.pageSize
    return sortedRows.value.slice(start, start + props.pageSize)
})

function setPage(p: number) {
    const next = Math.min(Math.max(1, p), totalPages.value)
    emit('update:page', next)
    emit('page-change', { page: next, pageSize: props.pageSize })
}

// Wert-Resolver
function getValue(row: any, col?: TableColumn) {
    if (!col) return null
    if (col.accessor) return col.accessor(row)
    return row[col.key]
}

function ensureProtocol(url: string): string {
    if (!url) return url
    return /^https?:\/\//i.test(url) ? url : `https://${url}`
}

const { rows, columns, rowKey } = toRefs(props)
</script>
