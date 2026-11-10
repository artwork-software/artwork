<template>
    <!-- Zeitraum-Filter der Materialausgabe-Bücher: Schnellwahl als Segment + Von/Bis in einer Zeile -->
    <div class="flex flex-wrap items-center gap-2">
        <div class="inline-flex h-8 shrink-0 overflow-hidden rounded-md border border-border bg-surface" role="group" :aria-label="$t('Quick range')">
            <button
                v-for="range in quickRanges"
                :key="range.key"
                type="button"
                class="px-2.5 text-xs font-medium transition-[background-color,color] duration-150 ease-out not-first:border-l not-first:border-border"
                :class="activeQuickRange === range.key ? 'bg-accent-50 text-accent-700' : 'text-text-muted hover:bg-surface-sunken'"
                :aria-pressed="activeQuickRange === range.key"
                @click="toggleQuickRange(range)"
            >
                {{ $t(range.label) }}
            </button>
        </div>
        <div class="flex items-center gap-1.5">
            <BaseInput
                :id="`${idPrefix}-date-from`"
                v-model="dateFrom"
                type="date"
                label="From"
                :show-label="false"
                :max="dateTo || null"
                class="w-36"
            />
            <span class="text-xs text-text-subtle" aria-hidden="true">–</span>
            <BaseInput
                :id="`${idPrefix}-date-to`"
                v-model="dateTo"
                type="date"
                label="To"
                :show-label="false"
                :min="dateFrom || null"
                class="w-36"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import { ISSUE_QUICK_RANGES, activeQuickRangeKey, resolveQuickRange } from '@/Pages/IssueOfMaterial/Components/issueDateRanges.js'

defineProps({
    /** Präfix für die Input-IDs, damit Label/aria eindeutig bleiben */
    idPrefix: { type: String, default: 'issue-filter' },
})

const dateFrom = defineModel('from', { type: String, default: '' })
const dateTo = defineModel('to', { type: String, default: '' })

const quickRanges = ISSUE_QUICK_RANGES

/** Schnellwahl gilt als aktiv, solange Von/Bis exakt ihrem Zeitraum entsprechen */
const activeQuickRange = computed(() => activeQuickRangeKey(dateFrom.value, dateTo.value))

/** Erneuter Klick auf die aktive Schnellwahl hebt den Zeitraum wieder auf */
const toggleQuickRange = (range) => {
    if (activeQuickRange.value === range.key) {
        dateFrom.value = ''
        dateTo.value = ''
        return
    }
    const resolved = resolveQuickRange(range.key)
    dateFrom.value = resolved.dateFrom
    dateTo.value = resolved.dateTo
}
</script>
