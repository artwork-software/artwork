<template>
    <div class="shadow-[0_2px_5px_rgb(0,0,0,0.12)] p-4 bg-white rounded-lg border border-gray-100 w-full" :class="classes">
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