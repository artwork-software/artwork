<template>
    <div class="xxsLight">
        <p class="line-clamp-3" v-html="project['TextArea'][component.component_id]?.data?.text"></p>
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