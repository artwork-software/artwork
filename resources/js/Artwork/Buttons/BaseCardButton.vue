<template>
    <button :type="props.type"
            class="rounded-lg transition-all ease-in-out duration-200 bg-artwork-buttons-create px-4 py-1.5 text-xs font-lexend text-white hover:bg-artwork-buttons-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create">
        <span class="flex items-center gap-x-2">
            <slot></slot>
            {{ $t(props.text) }}
        </span>
    </button>
</template>

<script setup>

const props = defineProps({
    text: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        default: 'button',
    },
})

</script>

<style scoped>

</style>