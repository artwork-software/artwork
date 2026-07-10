<!--
    Status-Filter der Inventarübersicht als Pill-Leiste.
    Klick wirkt sofort (Toggle); der aktive Status erscheint zusätzlich als
    entfernbarer Chip in der Aktive-Filter-Zeile der InventoryFilterComponent.
-->
<template>
    <div class="overflow-x-auto">
        <div class="flex items-center whitespace-nowrap gap-1.5 text-sm">
            <span class="text-[11px] uppercase tracking-wide text-gray-400 mr-1">{{ $t('Status') }}</span>
            <button
                v-for="item in items"
                :key="item.id"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition-colors"
                :class="[
                    isActive(item.id) ? '' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50',
                    isReady(item.name) && !isActive(item.id) ? 'font-semibold ring-1 ring-artwork-buttons-create/40 bg-artwork-buttons-create/5' : ''
                ]"
                :style="isActive(item.id) ? activeStyle(item) : {}"
                @click="toggleStatus(item.id)"
            >
                <span
                    class="inline-block size-2.5 rounded-full border"
                    :style="{ backgroundColor: (item.color || fallbackColor(item.index)) + '55', borderColor: item.color || fallbackColor(item.index) }"
                />
                <span>{{ item.name }}</span>
                <span class="tabular-nums text-gray-500">{{ item.count.toLocaleString('de-DE') }}</span>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()

type Row = { name: string; color?: string; count: number; order?: number }
type CountsByStatus = Record<string, Row>

const props = defineProps<{
    countsByStatus: CountsByStatus,
    activeStatusId?: number | null
}>()

const palette = [
    '#1C6EA4', '#33A1E0', '#3B82F6', '#F59E0B',
    '#EF4444', '#10B981', '#6B7280', '#8915a3',
]

const fallbackColor = (index: number) => palette[index % palette.length]

const isActive = (id: string) => String(props.activeStatusId) === id

const activeStyle = (item: { color?: string, index: number }) => {
    const base = item.color || fallbackColor(item.index)
    return {
        backgroundColor: base + '14',
        borderColor: base,
        color: '#1f2937',
    }
}

const toggleStatus = (id: string | null) => {
    const newId = (id !== null && String(props.activeStatusId) === id) ? null : id
    router.reload({
        data: { status_id: newId },
        preserveScroll: true,
        // Sidebar & Chips hängen an hasActiveFilter/categories -> mitladen,
        // sonst zeigen sie nach einem Status-Toggle veraltete Zustände.
        only: ['articles', 'activeStatusId', 'countsByStatus', 'categories', 'currentCategory', 'hasActiveFilter'],
    })
}

// Ref 1.29: "Einsatzbereit" wird visuell hervorgehoben.
const isReady = (name: string) =>
    (name || '').trim().toLowerCase() === 'einsatzbereit'

const items = computed(() =>
    Object.entries(props.countsByStatus || {})
        .map(([id, r], index) => ({
            id,
            index,
            name: r.name,
            color: r.color,
            order: Number(r.order) || 0,
            count: Number(r.count) || 0,
        }))
        // nach konfigurierbarer Reihenfolge (order), bei Gleichstand nach id
        .sort((a, b) => (a.order - b.order) || (Number(a.id) - Number(b.id)))
)
</script>
