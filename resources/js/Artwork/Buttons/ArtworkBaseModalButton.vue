<template>
    <button
        :class="[
      'inline-flex items-center justify-center rounded-md font-medium transition duration-200 ease-in-out font-lexend',
      'border shadow-glass backdrop-blur-md',
      'disabled:opacity-50',
      variantClass[variant],
      sizeClass[size],
        disabled ? '!cursor-not-allowed !bg-gray-100 !border-gray-400 !text-gray-700' : 'cursor-pointer',
    ]"
        :type="type"
        :disabled="disabled"
    >
        <slot />
    </button>
</template>

<script setup>
const props = defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    disabled: { type: Boolean, default: false }
})

const variantClass = {
    primary: 'text-blue-500 bg-blue-100 border-blue-200 hover:bg-blue-200 hover:text-blue-500 disabled:bg-gray-100 hover:border-blue-300',
    secondary: 'text-gray-500 bg-gray-100 border-gray-200 hover:bg-gray-200 hover:text-gray-500 disabled:bg-gray-100 hover:border-gray-300',
    danger: 'text-red-500 bg-red-100 border-red-200 hover:bg-red-200 hover:text-red-500 disabled:bg-gray-100 hover:border-red-300'
}

const sizeClass = {
    xs: 'text-xs px-4 py-1',
    sm: 'px-8 py-2 text-xs',
    md: 'px-10 py-3 text-xs',
    lg: 'px-12 py-4 text-md'
}
</script>
