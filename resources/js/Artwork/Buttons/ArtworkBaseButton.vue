<template>
    <button
        :class="['inline-flex items-center justify-center rounded-md font-medium shadow-sm transition duration-200 ease-in-out font-lexend',variantClass[variant], sizeClass[size]]"
        :type="type"
        :disabled="disabled">
        <slot />
    </button>
</template>

<script setup>
const props = defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' }, // 'primary', 'secondary', 'danger'
    size: { type: String, default: 'md' }, // 'sm', 'md', 'lg'
    disabled: { type: Boolean, default: false }
})

const variantClass = {
    primary: 'bg-artwork-buttons-create text-white hover:bg-artwork-buttons-hover disabled:bg-gray-500',
    secondary: 'bg-gray-800 text-white hover:bg-gray-700 disabled:bg-gray-100',
    danger: 'bg-red-600 text-white hover:bg-red-700 disabled:bg-red-300'
}

const sizeClass = {
    xs: 'text-xs px-4 py-1',
    sm: 'px-6 py-1.5 text-sm',
    md: 'px-8 py-2 text-sm',
    lg: 'px-10 py-3 text-lg'
}
</script>
