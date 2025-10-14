<template>
    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <table class="relative min-w-full divide-y divide-gray-300">
                    <!-- THEAD -->
                    <thead>
                    <tr>
                        <th
                            v-for="col in normalizedColumns"
                            :key="col.key"
                            scope="col"
                            :class="[
                  'py-3.5 text-sm font-semibold text-gray-900',
                  col.headerClass,
                  col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                  col.key === normalizedColumns[0]?.key ? 'pl-4 pr-3' : 'px-3'
                ]"
                            :style="col.width ? { width: col.width } : undefined"
                        >
                            <button
                                v-if="col.sortable"
                                type="button"
                                class="group inline-flex items-center gap-1 hover:text-gray-700"
                                @click="onToggleSort(col.key)"
                            >
                                <span class="truncate">{{ $t(col.label) }}</span>
                                <svg v-if="sort.key !== col.key" class="h-4 w-4 opacity-40" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M7 7h6l-3-4-3 4Zm6 6H7l3 4 3-4Z"/>
                                </svg>
                                <svg v-else-if="sort.direction === 'asc'" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M7 12h6l-3 4-3-4Z"/>
                                </svg>
                                <svg v-else class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M7 8h6l-3-4-3 4Z"/>
                                </svg>
                            </button>
                            <span v-else class="truncate">{{ $t(col.label) }}</span>
                        </th>

                        <th v-if="$slots['header-extra']" scope="col" class="py-3.5 pr-4 pl-3 sm:pr-0">
                            <slot name="header-extra" />
                        </th>
                    </tr>
                    </thead>

                    <!-- TBODY -->
                    <tbody v-if="displayRows.length" class="divide-y divide-gray-200 bg-white">
                    <tr v-for="row in displayRows" :key="row[rowKey]" class="hover:bg-gray-50">
                        <td
                            v-for="(col, idx) in normalizedColumns"
                            :key="col.key"
                            :class="[
                          'py-5 text-sm whitespace-nowrap',
                          col.cellClass,
                          col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                          idx === 0 ? 'pl-4 pr-3' : 'px-3'
                        ]"
                        >
                            <!-- Zell-Slot: cell-<key> -->
                            <slot :name="`cell-${col.key}`" :row="row" :value="getValue(row,col)" :col="col">
                                <!-- Default-Renderer -->
                                <span class="text-gray-700">{{ getValue(row, col) }}</span>
                            </slot>
                        </td>

                        <!-- Actions -->
                        <td v-if="$slots['row-actions']" class="py-5 pr-4 pl-3 text-right text-sm font-medium whitespace-nowrap sm:pr-0">
                            <slot name="row-actions" :row="row" />
                        </td>
                    </tr>
                    </tbody>

                    <!-- EMPTY -->
                    <tbody v-else class="bg-white">
                    <tr>
                        <td :colspan="normalizedColumns.length + ($slots['row-actions'] ? 1 : 0)" class="px-4 py-10 text-center">
                            <div class="mx-auto max-w-md">
                                <div class="text-lg font-semibold text-gray-900">{{ emptyTitle }}</div>
                                <p class="mt-1 text-sm text-gray-500">{{ emptyMessage }}</p>
                                <div class="mt-4">
                                    <slot name="empty-cta" />
                                </div>
                            </div>
                        </td>
                    </tr>
                    </tbody>

                </table>

                <!-- Pagination (optional) -->
                <div v-if="showPagination" class="flex items-center justify-between gap-3 pt-4">
                    <div class="text-sm text-gray-600">
                        {{ pageFrom }}–{{ pageTo }} {{ ofLabel }} {{ total }}
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 disabled:opacity-50"
                            :disabled="page <= 1"
                            @click="setPage(page - 1)"
                        >
                            {{ prevLabel }}
                        </button>
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 disabled:opacity-50"
                            :disabled="page >= totalPages"
                            @click="setPage(page + 1)"
                        >
                            {{ nextLabel }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, reactive, watch, toRefs } from 'vue'

type Align = 'left' | 'center' | 'right'
type SortDirection = 'asc' | 'desc' | null

export interface TableColumn {
    key: string                       // Feldname/Slot-Key
    label: string                     // Spaltenüberschrift
    align?: Align                     // Ausrichtung
    sortable?: boolean                // sortierbar?
    width?: string                    // optional (z.B. '220px')
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
    // Texte
    emptyTitle?: string
    emptyMessage?: string
    prevLabel?: string
    nextLabel?: string
    ofLabel?: string
}>(), {
    rowKey: 'id',
    sortKey: null,
    sortDirection: null,
    pageSize: null,
    page: 1,
    total: null,
    emptyTitle: 'Keine Einträge',
    emptyMessage: 'Es sind aktuell keine Daten vorhanden.',
    prevLabel: 'Zurück',
    nextLabel: 'Weiter',
    ofLabel: 'von'
})

const emit = defineEmits<{
    (e: 'update:sortKey', value: string | null): void
    (e: 'update:sortDirection', value: SortDirection): void
    (e: 'update:page', value: number): void
    (e: 'sort-change', payload: { key: string | null, direction: SortDirection }): void
    (e: 'page-change', payload: { page: number, pageSize: number | null }): void
}>()

// Spalten normalisieren
const normalizedColumns = computed<TableColumn[]>(() =>
    (props.columns || []).map(c => ({
        align: 'left',
        sortable: false,
        ...c
    }))
)

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

const { rows, columns, rowKey } = toRefs(props)
</script>
