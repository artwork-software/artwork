<template>
    <div class="mb-5 px-5 py-3 text-left">
        <h3 class="font-lexend font-bold">{{ $t(props.title) }}</h3>
        <p class="text-sm xsLight mt-0.5">
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