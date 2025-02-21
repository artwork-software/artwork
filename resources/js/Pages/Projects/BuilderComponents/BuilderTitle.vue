<template>
    <div class="xxsLight print:xsDark">
        <p class="line-clamp-3" v-html="project['Title'][component.id]?.title"></p>
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