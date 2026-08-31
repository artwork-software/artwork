<template>
    <div>
        <div class="flex items-center gap-x-1 text-sm" v-if="project.budget_deadline">
            <span class="font-bold">{{ project.budget_deadline }}</span>
        </div>
        <div v-else class="text-sm text-text-subtle">
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
