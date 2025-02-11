<template>
    <p class="line-clamp-3 print:line-clamp-none">
        {{ project['TextArea']?.[component.component_id ?? component.id]?.data?.text }}
    </p>

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