<template>
    <!-- Dünner Wrapper auf BaseUIButton (Design-Basis v1) — Props bleiben kompatibel -->
    <BaseUIButton :type="props.type" variant="secondary" hide-icon>
        <span class="flex items-center gap-x-2">
            <slot></slot>
            {{ $t(props.text) }}
        </span>
    </BaseUIButton>
</template>

<script setup>
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

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
