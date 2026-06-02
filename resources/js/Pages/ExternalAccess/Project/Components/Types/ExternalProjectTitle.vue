<template>
    <div class="rounded-2xl border border-zinc-200 bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-zinc-400">{{ $t('Project') }}</p>
        <h2 class="text-xl font-bold text-zinc-900 mt-1">{{ displayValue }}</h2>
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
    return value.title || value.text || props.component.name
})
</script>
