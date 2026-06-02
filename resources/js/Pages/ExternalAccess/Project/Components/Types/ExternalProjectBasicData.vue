<template>
    <div class="rounded-2xl border border-zinc-200 bg-white p-5">
        <p class="text-sm font-medium text-zinc-700 mb-2">{{ component.name }}</p>

        <dl v-if="entries.length" class="divide-y divide-zinc-100">
            <div v-for="[key, val] in entries" :key="key" class="flex justify-between py-2 text-sm">
                <dt class="text-zinc-500">{{ key }}</dt>
                <dd class="text-zinc-900 text-right">{{ val }}</dd>
            </div>
        </dl>
        <p v-else class="text-xs text-zinc-400">{{ $t('No content') }}</p>
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

const entries = computed(() => {
    const value = props.component.value
    if (!value || typeof value !== 'object') return []
    return Object.entries(value).filter(([, v]) => v !== null && v !== '' && typeof v !== 'object')
})
</script>
