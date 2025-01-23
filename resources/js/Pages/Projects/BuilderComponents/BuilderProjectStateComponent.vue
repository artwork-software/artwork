<template>
    <div v-if="project.state">
        <span class="rounded-full items-center font-medium px-3 py-1 my-2 text-xs ml-2 mb-1 inline-flex border text-white"
            :style="{ backgroundColor: project.state.color, borderColor: project.state.color}">
            {{ project?.state?.name }}
        </span>
    </div>
</template>

<script setup>

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
})

</script>

<style scoped>

</style>