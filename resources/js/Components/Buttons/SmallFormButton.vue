<template>
    <button type="button" class="rounded-lg duration-200 ease-in-out bg-artwork-buttons-create px-6 py-2 text-sm flex items-center gap-x-1 font-semibold text-white hover:bg-artwork-buttons-hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create cursor-pointer">
        <slot></slot>
    </button>

</template>

<script setup>

const props = defineProps({})

</script>

<style scoped>

</style>
