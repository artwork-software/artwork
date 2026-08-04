<template>
    <div class="text-xs/[18px] text-text-subtle">
        <div v-if="component.data?.label" class="text-sm font-bold text-text mb-0.5">{{ component.data.label }}</div>
        <p class="line-clamp-2 print:line-clamp-none" v-html="project['TextField'][component.id]?.data?.text"></p>
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
