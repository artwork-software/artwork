<template>
    <div class="flex items-center group/tooltip" :class="noRelative ? '' : 'relative' ">
        <!-- Tooltip aktiv: je Richtung eigener Button mit Modifier -->
        <template v-if="!noTooltip">
            <button
                v-if="direction === 'top'"
                class="focus:outline-none"
                type="button"
                :class="classes"
                :disabled="disabled"
                v-tooltip.top="tooltipBinding"
            >
                <component
                    :is="icon"
                    class="cursor-pointer"
                    :class="[iconSize, classes, whiteIcon ? 'text-white' : (grayIcon ? 'text-gray-400' : 'text-artwork-buttons-context')]"
                    :stroke-width="stroke"
                />
            </button>

            <button
                v-else-if="direction === 'bottom'"
                class="focus:outline-none"
                type="button"
                :class="classes"
                :disabled="disabled"
                v-tooltip.bottom="tooltipBinding"
            >
                <component
                    :is="icon"
                    class="cursor-pointer"
                    :class="[iconSize, classes, whiteIcon ? 'text-white' : (grayIcon ? 'text-gray-400' : 'text-artwork-buttons-context')]"
                    :stroke-width="stroke"
                />
            </button>

            <button
                v-else-if="direction === 'left'"
                class="focus:outline-none"
                type="button"
                :class="classes"
                :disabled="disabled"
                v-tooltip.left="tooltipBinding"
            >
                <component
                    :is="icon"
                    class="cursor-pointer"
                    :class="[iconSize, classes, whiteIcon ? 'text-white' : (grayIcon ? 'text-gray-400' : 'text-artwork-buttons-context')]"
                    :stroke-width="stroke"
                />
            </button>

            <button
                v-else
                class="focus:outline-none"
                type="button"
                :class="classes"
                :disabled="disabled"
                v-tooltip.right="tooltipBinding"
            >
                <component
                    :is="icon"
                    class="cursor-pointer"
                    :class="[iconSize, classes, whiteIcon ? 'text-white' : (grayIcon ? 'text-gray-400' : 'text-artwork-buttons-context')]"
                    :stroke-width="stroke"
                />
            </button>
        </template>

        <!-- Tooltip komplett aus -->
        <button
            v-else
            class="focus:outline-none"
            type="button"
            :class="classes"
            :disabled="disabled"
        >
            <component
                :is="icon"
                class="cursor-pointer"
                :class="[iconSize, classes, whiteIcon ? 'text-white' : (grayIcon ? 'text-gray-400' : 'text-artwork-buttons-context')]"
                :stroke-width="stroke"
            />
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    direction: {
        type: String,
        default: 'top',
        validator: (value) => ['top', 'left', 'right', 'bottom'].includes(value),
    },
    tooltipText: { type: String, default: '' },
    classes: { type: [String, Array], default: '' },
    icon: { type: [String, Object, Function], default: null },
    iconSize: { type: String, default: 'w-6 h-6' },
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
        text: '!bg-primary !text-primary-contrast !font-medium !text-xs !px-2 !py-1 !border !border-gray-800 !rounded-lg !shadow-lg !rounded-md',
    },
}))
</script>
