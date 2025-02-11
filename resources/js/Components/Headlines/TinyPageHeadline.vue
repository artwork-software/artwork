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
        <h3 class="headline2 mb-2 print:headline3">{{ title }}</h3>
        <p class="xsLight print:hidden">
            {{ description }}
        </p>
    </div>
</template>

<style scoped>

</style>
