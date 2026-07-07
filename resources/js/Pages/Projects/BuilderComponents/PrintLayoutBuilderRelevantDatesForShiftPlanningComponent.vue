<template>
    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-secondary mb-2">{{ $t('Relevant dates for shift planning') }}</h3>
        <div v-if="project.shift_relevant_event_types?.length > 0" class="flex flex-wrap gap-1">
            <span v-for="eventType in project.shift_relevant_event_types" :key="eventType.id"
                class="inline-flex items-center rounded-full px-3 py-1 text-xs text-white font-medium"
                :style="{ backgroundColor: eventType.hex_code }">
                {{ eventType.name }}
            </span>
        </div>
        <div v-else class="text-sm text-secondary">
            {{ $t('No entries') }}
        </div>
    </div>
</template>
<script setup>
const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: false,
    },
})
</script>
<style scoped>
</style>
