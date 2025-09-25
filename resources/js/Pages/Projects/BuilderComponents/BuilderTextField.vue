<template>
    <div class="xxsLight">
        <p class="line-clamp-2" v-html="project['TextField'][component.component_id]?.data?.text"></p>
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
