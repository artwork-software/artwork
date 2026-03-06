<template>
    <div>
        <div class="text-sm" v-if="project.shift_description">
            {{ project.shift_description }}
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
