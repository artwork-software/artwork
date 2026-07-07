<template>
    <div class="">
        <div v-if="component.data?.label" class="text-sm font-bold text-gray-900 mb-0.5">{{ component.data.label }}</div>
        <p class="line-clamp-2 text-xs print:line-clamp-none ">
            {{ project['TextArea']?.[component.component_id]?.data?.text }}
        </p>
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
