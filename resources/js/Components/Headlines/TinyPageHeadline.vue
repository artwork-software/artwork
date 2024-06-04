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

<template>
    <div>
        <h3 class="headline2 mb-2">{{ title }}</h3>
        <p class="xsLight">
            {{ description }}
        </p>
    </div>
</template>

<style scoped>

</style>
