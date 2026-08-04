<template>
    <div class="text-xs/[18px] text-text-subtle">
        <div v-if="component.data?.label" class="text-sm font-bold text-text mb-0.5">{{ component.data.label }}</div>
        <div>
            {{ project['Checkbox'][component.id]?.data?.checked ? $t('Yes') : $t('No') }}
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
        required: true,
    },
})
</script>
<style scoped>
</style>
