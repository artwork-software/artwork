<template>
    <div>
        <div class="text-sm" v-if="project.project_period">
            <span class="font-bold">
                {{ project.project_period.first_event_date }} – {{ project.project_period.last_event_date }}
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
