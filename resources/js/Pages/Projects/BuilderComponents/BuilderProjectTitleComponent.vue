<template>
    <div class="flex font-black font-lexend text-primary tracking-wide text-sm items-center">
        <h3 class=" group-hover/project:text-artwork-buttons-create duration-300 ease-in-out">{{ project.title }}</h3>
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