<!-- StatusInlineList.vue -->
<template>
    <div class="overflow-x-auto">
        <div class="flex items-center whitespace-nowrap gap-4 text-sm">
            <template v-for="(item, idx) in items" :key="item.id">
                <div
                    class="inline-flex items-center gap-2 cursor-pointer rounded-md px-2 py-1 transition-colors"
                    :class="[
                        isActive(item.id) ? 'bg-gray-100 ring-1 ring-gray-300' : '',
                        isReady(item.name) ? 'font-semibold ring-1 ring-artwork-buttons-create/40 bg-artwork-buttons-create/5' : ''
                    ]"
                    @click="toggleStatus(item.id)"
                >
                    <span
                        class="inline-block rounded-full border"
                        :class="isReady(item.name) ? 'size-4' : 'size-3.5'"
                        :style="{ backgroundColor: item.color + '55' || palette[item.index % palette.length], borderColor: item.color }"
                    />
                    <span :class="isReady(item.name) ? 'text-[0.95rem]' : ''">{{ item.name }}</span>
                    <span class="tabular-nums text-gray-600">
                        ({{ item.count.toLocaleString('de-DE') }})
                    </span>
                </div>
                <span v-if="idx < items.length - 1" class="text-gray-300">•</span>
            </template>
        </div>

        <!-- Filter Bubble -->
        <div v-if="activeStatusName" class="mt-2 inline-flex items-center gap-2 rounded-full bg-artwork-buttons-create/10 border border-artwork-buttons-create/30 px-3 py-1 text-sm text-artwork-buttons-create">
            <span>{{ $t('Only articles with at least 1x') }} "{{ activeStatusName }}"</span>
            <button
                class="ml-1 inline-flex items-center justify-center size-4 rounded-full hover:bg-artwork-buttons-create/20 transition-colors"
                @click="toggleStatus(null)"
            >
                ✕
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

const isActive = (id: string) => String(props.activeStatusId) === id

const activeStatusName = computed(() => {
    if (!props.activeStatusId) return null
    const row = props.countsByStatus?.[String(props.activeStatusId)]
    return row?.name ?? null
})

const toggleStatus = (id: string | null) => {
    const newId = (id !== null && String(props.activeStatusId) === id) ? null : id
    router.reload({
        data: { status_id: newId },
        preserveScroll: true,
        only: ['articles', 'activeStatusId'],
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
