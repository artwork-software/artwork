<template>
    <div class="rounded-2xl border border-border-subtle bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-text-subtle">{{ component.name }}</p>
        <p class="text-base font-semibold text-text mt-1">{{ displayValue }}</p>
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
