<template>
    <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-xs text-zinc-400">
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
