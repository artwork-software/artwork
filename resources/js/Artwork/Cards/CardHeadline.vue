<template>
    <div class="px-4 py-3 text-left border-b border-border-subtle">
        <h3 class="font-lexend font-semibold text-lg text-text">{{ $t(props.title) }}</h3>
        <p v-if="props.description" class="text-xs text-text-muted mt-0.5">
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
