<template>
    <!-- Ex-Glassy-Button: jetzt BaseUIButton variant="ghost" mit Tooltip statt
         Max-Width-Hover-Animation (Design-Basis v1). Props bleiben kompatibel. -->
    <BaseUIButton
        variant="ghost"
        :icon="icon"
        :aria-label="$t(text)"
        v-tooltip="{ value: $t(text), class: 'aw-tooltip' }"
    />
</template>

<script setup lang="ts">
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import { IconCirclePlus } from '@tabler/icons-vue'
import type { Component, PropType } from 'vue'

const props = defineProps({
    icon: {
        // erlaubt String oder echte Komponente (Function/Object)
        type: [String, Function, Object] as PropType<string | Component>,
        default: IconCirclePlus,
    },
    iconSize: {
        type: String,
        default: 'size-5'
    },
    text: {
        type: String,
        required: true
    },
    left: {
        type: Boolean,
        default: false
    }
})
</script>
