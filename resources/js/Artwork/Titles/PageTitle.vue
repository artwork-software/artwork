<template>
    <div class="py-4">
        <h3 class="headline h2 text-color ">{{ title }}</h3>
        <p class="text-color light sub-line light">
            {{ description }}
        </p>
    </div>
</template>

<script setup>

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        required: true
    },
})
</script>

<style scoped>

</style>