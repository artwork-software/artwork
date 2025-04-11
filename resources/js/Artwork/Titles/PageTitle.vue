<template>
    <div class="py-4">
        <h3 class="font-lexend text-xl font-bold">{{ title }}</h3>
        <p class="text-sm font-light text-gray-700 mt-0.5">
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