<template>
    <div class="rounded-2xl border border-zinc-200 bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-zinc-400">{{ component.name }}</p>
        <p class="text-base font-semibold text-zinc-900 mt-1">{{ displayValue }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    component: { type: Object, required: true },
    projectId: { type: Number, required: true },
    tabId: { type: Number, required: true },
    scope: { type: Object, required: true },
})

const displayValue = computed(() => {
    const value = props.component.value || {}
    return value.name || value.text || '—'
})
</script>
