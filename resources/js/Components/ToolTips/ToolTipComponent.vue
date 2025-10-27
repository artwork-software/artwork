<template>
    <div class="flex items-center group/tooltip" :class="[noRelative ? '' : 'relative']">
        <!-- Tooltip aktiv: je Richtung eigener Button mit Modifier -->
        <template v-if="!noTooltip">
            <div v-tooltip.top="tooltipBinding"  v-if="direction === 'top'" :class="classesButton">
                <button
                    class="focus:outline-none"
                    type="button"
                    :class="classes"
                    :disabled="disabled"
                >
                    <PropertyIcon
                        :name="icon"
                        class="cursor-pointer"
                        :class="[iconSize, classes, whiteIcon ? 'text-white' : (grayIcon ? 'text-gray-400' : 'text-artwork-buttons-context')]"
                        :stroke-width="stroke"
                        :style="iconStyle"
                    />
                </button>
            </div>

            <div v-tooltip.bottom="tooltipBinding"  v-else-if="direction === 'bottom'" :class="classesButton">
                <button
                    class="focus:outline-none"
                    type="button"
                    :class="classes"
                    :disabled="disabled"
                >
                    <PropertyIcon
                        :name="icon"
                        class="cursor-pointer"
                        :class="[iconSize, classes, whiteIcon ? 'text-white' : (grayIcon ? 'text-gray-400' : 'text-artwork-buttons-context')]"
                        :stroke-width="stroke"
                        :style="iconStyle"
                    />
                </button>
            </div>
            <div v-tooltip.left="tooltipBinding" v-else-if="direction === 'left'" :class="classesButton">
                <button
                    class="focus:outline-none"
                    type="button"
                    :class="classes"
                    :disabled="disabled"
                >
                    <PropertyIcon
                        :name="icon"
                        class="cursor-pointer"
                        :class="[iconSize, classes, whiteIcon ? 'text-white' : (grayIcon ? 'text-gray-400' : 'text-artwork-buttons-context')]"
                        :stroke-width="stroke"
                        :style="iconStyle"
                    />
                </button>
            </div>
            <div v-tooltip.right="tooltipBinding" v-else :class="classesButton">
                <button
                    class="focus:outline-none"
                    type="button"
                    :class="classes"
                    :disabled="disabled"
                >
                    <PropertyIcon
                        :name="icon"
                        class="cursor-pointer"
                        :class="[iconSize, classes, whiteIcon ? 'text-white' : (grayIcon ? 'text-gray-400' : 'text-artwork-buttons-context')]"
                        :stroke-width="stroke"
                        :style="iconStyle"
                    />
                </button>
            </div>
        </template>

        <!-- Tooltip komplett aus -->
        <button
            v-else
            class="focus:outline-none"
            type="button"
            :class="[classes, classesButton]"
            :disabled="disabled"
        >
            <PropertyIcon
                :name="icon"
                class="cursor-pointer"
                :class="[iconSize, classes, whiteIcon ? 'text-white' : (grayIcon ? 'text-gray-400' : 'text-artwork-buttons-context')]"
                :stroke-width="stroke"
                :style="iconStyle"
            />
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const props = defineProps({
    direction: {
        type: String,
        default: 'top',
        validator: (value) => ['top', 'left', 'right', 'bottom'].includes(value),
    },
    tooltipText: { type: String, default: '' },
    classes: { type: [String, Array], default: '' },
    classesButton: { type: [String, Array], default: 'mt-1' },
    icon: {
        // erlaubt String oder echte Komponente (Function/Object)
        type: [String, Function, Object] as PropType<string | Component>,
        default: null,
    },
    iconSize: { type: String, default: 'w-6 h-6' },
    iconStyle: { type: [String, Object], default: null },
    disabled: { type: Boolean, default: false },
    stroke: { type: [String, Number], default: '1' },
    whiteIcon: { type: Boolean, default: false },
    grayIcon: { type: Boolean, default: false },
    noRelative: { type: Boolean, default: false },
    tooltipCssClass: { type: String, default: 'w-fit' }, // bleibt erhalten, falls du es extern nutzt
    noTooltip: { type: Boolean, default: false },
    useTranslation: { type: Boolean, default: false },
})

/**
 * Einheitliches Binding für PrimeVue Tooltip.
 * 'position' bleibt als Fallback gesetzt, falls du mal ohne Modifier verwendest.
 */
const tooltipBinding = computed(() => ({
    value: props.tooltipText,
    disabled: props.noTooltip,
    useTranslation: props.useTranslation,
    position: props.direction, // Fallback, wenn kein Modifier genutzt wird
    pt: {
        text: '!bg-primary !text-primary-contrast !font-medium !text-xs !px-2 !py-1 !border !border-gray-800 !rounded-lg !shadow-lg !rounded-md !w-auto !max-w-xs',
    },
}))
</script>
