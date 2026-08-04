<template>
    <!-- Dünner Wrapper auf BaseUIButton (Design-Basis v1) — Props bleiben kompatibel.
         Glas-Optik (shadow-glass, backdrop-blur) und disabled:opacity entfallen. -->
    <BaseUIButton
        :type="type"
        :variant="variantMap[variant] ?? 'secondary'"
        :size="sizeMap[size] ?? 'md'"
        :disabled="disabled"
        hide-icon
    >
        <slot />
    </BaseUIButton>
</template>

<script setup>
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

const props = defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' }, // 'primary', 'secondary', 'success', 'danger'
    size: { type: String, default: 'md' }, // 'xs', 'sm', 'md', 'lg'
    disabled: { type: Boolean, default: false }
})

const variantMap = {
    primary: 'primary',
    secondary: 'secondary',
    success: 'primary',
    danger: 'danger'
}

const sizeMap = {
    xs: 'sm',
    sm: 'sm',
    md: 'md',
    lg: 'lg'
}
</script>
