<template>
    <div class="text-xs">
        {{ project.artist_name }}
    </div>
</template>

<script setup>

const props = defineProps({
    project: {
        type: Object,
        required: true
    }
})
</script>

<style scoped>

</style>