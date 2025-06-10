<template>
    <div class="button glassy group relative overflow-hidden">
        <span>
            <component :is="icon" class="group-hover:text-artwork-buttons-create" :class="[iconSize]" />
        </span>
        <p class="max-w-0 overflow-hidden opacity-0 transform -translate-x-2 transition-all duration-300 ease-in-out group-hover:max-w-xs group-hover:opacity-100 group-hover:translate-x-0 whitespace-nowrap group-hover:px-2 group-hover:text-artwork-buttons-create">
            {{ $t(text) }}
        </p>
    </div>

</template>

<script setup>

const props = defineProps({
    icon: {
        type: String,
        required: true
    },
    iconSize: {
        type: String,
        default: 'size-5'
    },
    text: {
        type: String,
        required: true
    }
})

</script>

<style scoped>

</style>