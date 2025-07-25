<template>
    <div v-if="project.state">
        <span class="rounded-full items-center font-medium px-3 py-1 my-2 text-xs ml-2 mb-1 inline-flex border"
            :class="isColorCloseToWhite(project.state.color) ? 'text-black' : 'text-white'"
              :style="{backgroundColor: backgroundColorWithOpacity(project.state.color), color: TextColorWithDarken(project.state.color), borderColor: TextColorWithDarken(project.state.color)}">
            {{ project?.state?.name }}
        </span>
    </div>
</template>

<script setup>
import ColorHelper from '../../../Mixins/ColorHelper.vue';

// Extract methods from ColorHelper mixin
const { backgroundColorWithOpacity, TextColorWithDarken } = ColorHelper.methods;

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
})

/**
 * Checks if a color is close to white
 * @param {string} hexColor - The hex color code
 * @returns {boolean} - True if the color is close to white
 */
function isColorCloseToWhite(hexColor) {
    // Handle empty or invalid colors
    if (!hexColor) return false;

    // Remove # if present
    const hex = hexColor.replace('#', '');

    // Convert hex to RGB
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);

    // Calculate perceived brightness using the formula:
    // (0.299*R + 0.587*G + 0.114*B)
    // This formula considers human perception of brightness
    const brightness = (0.299 * r + 0.587 * g + 0.114 * b);

    // Calculate color lightness (average of highest and lowest RGB)
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const lightness = (max + min) / 2;

    // Calculate color saturation
    const saturation = max === min ? 0 :
        lightness > 127 ? (max - min) / (510 - max - min) : (max - min) / (max + min);

    // A color is close to white if:
    // 1. It has high brightness (> 230)
    // 2. It has high lightness (> 200)
    // 3. It has low saturation (< 0.3)
    return brightness > 230 && lightness > 200 && saturation < 0.3;
}

</script>

<style scoped>

</style>
