<template>
    <div class=" rounded-2xl border border-border-subtle shadow-sm p-6 text-center hover:shadow-md cursor-pointer transition-all duration-300 ease-in-out relative overflow-hidden" :class="{ 'bg-success-surface': isActive, 'bg-white': !isActive }">
        <div :class="['mx-auto mb-4 flex items-center justify-center rounded-full', color]" class="absolute top-1 opacity-25">
            <component :is="icon" class="size-16" aria-hidden="true" stroke-width="1.5" />
        </div>
        <div class="text-lg font-semibold font-lexend capitalize text-text">
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
        default: 'text-text-subtle'
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