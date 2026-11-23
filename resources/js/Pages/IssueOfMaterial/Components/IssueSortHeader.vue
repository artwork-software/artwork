<template>
    <!-- Sortierbare Spaltenüberschrift der Materialausgabe-Listen -->
    <button
        type="button"
        class="group inline-flex items-center gap-1 uppercase tracking-wide hover:text-text"
        :class="isActive ? 'text-text' : ''"
        :aria-sort="isActive ? (direction === 'asc' ? 'ascending' : 'descending') : 'none'"
        :title="$t('Sort')"
        @click="$emit('sort', sortKey)"
    >
        <span class="truncate">{{ $t(label) }}</span>
        <component :is="icon" class="size-3.5 shrink-0" :class="isActive ? '' : 'opacity-40 group-hover:opacity-80'" stroke-width="1.5"/>
    </button>
</template>

<script setup>
import { computed } from 'vue'
import { IconChevronDown, IconChevronUp, IconSelector } from '@tabler/icons-vue'

defineOptions({ name: 'IssueSortHeader' })

const props = defineProps({
    label: { type: String, required: true },
    sortKey: { type: String, required: true },
    sort: { type: String, default: null },
    direction: { type: String, default: null },
})

defineEmits(['sort'])

const isActive = computed(() => props.sort === props.sortKey)
const icon = computed(() => !isActive.value ? IconSelector : (props.direction === 'asc' ? IconChevronUp : IconChevronDown))
</script>
