<template>
    <div class="rounded-2xl border border-dashed border-border bg-surface-sunken p-4 text-xs text-text-subtle">
        {{ $t('Unsupported component type') }}
    </div>
</template>

<script setup>
defineProps({
    component: { type: Object, required: true },
    projectId: { type: Number, required: true },
    tabId: { type: Number, required: true },
    scope: { type: Object, required: true },
})
</script>
