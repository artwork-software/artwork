<template>
    <!-- Dünner Wrapper auf BaseUIButton (Design-Basis v1) -->
    <BaseUIButton type="button" variant="primary" hide-icon>
        <span class="flex items-center gap-x-1">
            <slot></slot>
        </span>
    </BaseUIButton>
</template>

<script setup>
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
</script>
