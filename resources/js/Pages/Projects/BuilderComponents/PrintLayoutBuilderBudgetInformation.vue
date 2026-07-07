<template>
    <div class="text-sm">
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-secondary mb-2">{{ $t('Budget Informations') }}</h3>
        <div class="space-y-1">
            <div>{{ $t('Cost unit:') }} {{ project?.cost_center?.name || '-' }}</div>
            <div>{{ $t('GEMA') }}: {{ project.gema ? $t('Yes') : $t('No') }}</div>
            <div v-if="project.cost_center_description">
                {{ project.cost_center_description }}
            </div>
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
