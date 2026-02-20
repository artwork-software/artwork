<template>
    <div class="xxsLight">
        <div v-if="component.data?.label" class="text-sm font-bold text-gray-900 mb-0.5">{{ component.data.label }}</div>
        <p class="line-clamp-2" v-html="project['TextField'][component.id]?.data?.text"></p>
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
