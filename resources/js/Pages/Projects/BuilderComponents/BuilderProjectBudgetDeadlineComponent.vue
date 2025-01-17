<template>
    <div class="flex items-center gap-x-1 xxsLight" v-if="project.budget_deadline">
        <component is="IconClock" class="h-4 w-4" stroke-width="2"/>
        <span class="font-bold">{{ project.budget_deadline }}</span>
    </div>
</template>

<script setup>

const props = defineProps({
    project: {
        type: Object,
        required: true,
    }
})

</script>

<style scoped>

</style>