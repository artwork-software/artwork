<template>
    <div class="mb-5 px-5 py-3 px-1">
        <h3 class="font-lexend font-bold">{{ $t(props.title) }}</h3>
        <p class="text-sm text-gray-700 font-medium mt-0.5">
            {{ $t(props.description) }}
        </p>
    </div>
</template>

<script setup>

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: false,
    },
})

</script>

<style scoped>

</style>