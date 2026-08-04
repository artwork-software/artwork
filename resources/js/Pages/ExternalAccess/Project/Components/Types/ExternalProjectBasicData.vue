<template>
    <div class="rounded-2xl border border-border-subtle bg-white p-5">
        <p class="text-sm font-medium text-text-muted mb-2">{{ component.name }}</p>

        <dl v-if="entries.length" class="divide-y divide-border-subtle">
            <div v-for="[key, val] in entries" :key="key" class="flex justify-between py-2 text-sm">
                <dt class="text-text-subtle">{{ key }}</dt>
                <dd class="text-text text-right">{{ val }}</dd>
            </div>
        </dl>
        <p v-else class="text-xs text-text-subtle">{{ $t('No content') }}</p>
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
