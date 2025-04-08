<template>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-center hover:shadow-md cursor-pointer transition-all duration-300 ease-in-out relative overflow-hidden">
        <div :class="['mx-auto mb-4 flex items-center justify-center rounded-full', color]" class="absolute top-1 opacity-25">
            <component :is="icon" class="size-16" aria-hidden="true" stroke-width="1.5" />
        </div>
        <div class="text-lg font-semibold font-lexend capitalize text-gray-800">
            {{ count }} {{ label }}
        </div>
    </div>
</template>

<script setup>

const props = defineProps({
    icon: {
        type: String,
        required: true
    },
    color: {
        type: String,
        default: 'text-gray-500'
    },
    label: {
        type: String,
        required: true
    },
    count: {
        type: Number,
        default: 0
    },
    isActive: {
        type: Boolean,
        default: false
    }
})

</script>

<style scoped>

</style>