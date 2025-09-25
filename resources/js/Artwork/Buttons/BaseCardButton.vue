<template>
    <button :type="props.type"
            class="ui-button">
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
