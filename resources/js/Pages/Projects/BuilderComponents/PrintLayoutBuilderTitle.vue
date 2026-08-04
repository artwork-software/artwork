<template>
    <div class="text-xs/[18px] text-text-subtle print:text-sm/5 font-semibold text-text">
        <p class="line-clamp-3 print:line-clamp-none" v-html="project['Title'][component.id]?.title"></p>
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