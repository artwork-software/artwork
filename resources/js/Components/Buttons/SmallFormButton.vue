<template>
    <button type="button" class="rounded-lg bg-artwork-buttons-create px-6 py-2 text-sm font-semibold text-white hover:bg-artwork-buttons-hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create">
        <slot></slot>
    </button>

</template>

<script setup>

const props = defineProps({})

</script>

<style scoped>

</style>