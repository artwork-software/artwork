<template>
    <div class="shadow-glass p-4 bg-white rounded-lg border border-gray-100 w-full" :class="classes">
        <slot></slot>
    </div>
</template>

<script setup>

const props = defineProps({
    classes: {
        type: String,
        default: '',
    },

})

</script>

<style scoped>

</style>