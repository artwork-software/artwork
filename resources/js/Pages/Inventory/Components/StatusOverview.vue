<!-- StatusInlineList.vue -->
<template>
    <div class="overflow-x-auto">
        <div class="flex items-center whitespace-nowrap gap-4 text-sm">
            <template v-for="(item, idx) in items" :key="item.id">
                <div class="inline-flex items-center gap-2">
          <span
              class="inline-block size-3.5 rounded-full border"
              :style="{ backgroundColor: item.color + '55' || palette[item.index % palette.length], borderColor: item.color }"
          />
                    <span>{{ item.name }}</span>
                    <span class="tabular-nums text-gray-600">
            ({{ item.count.toLocaleString('de-DE') }})
          </span>
                </div>
                <span v-if="idx < items.length - 1" class="text-gray-300">•</span>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type Row = { name: string; color?: string; count: number }
type CountsByStatus = Record<string, Row>

const props = defineProps<{ countsByStatus: CountsByStatus }>()

const palette = [
    '#1C6EA4', '#33A1E0', '#3B82F6', '#F59E0B',
    '#EF4444', '#10B981', '#6B7280', '#8915a3',
]

const items = computed(() =>
    Object.entries(props.countsByStatus || {})
        .map(([id, r], index) => ({
            id,
            index,
            name: r.name,
            color: r.color,
            count: Number(r.count) || 0,
        }))
        .sort((a, b) => Number(a.id) - Number(b.id))
)
</script>
