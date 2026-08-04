<template>
    <div class="flex items-center group/tooltip" :class="[noRelative ? '' : 'relative']">
        <!-- Tooltip aktiv: je Richtung eigener Button mit Modifier -->
        <template v-if="!noTooltip">
            <div v-tooltip.top="tooltipBinding"  v-if="direction === 'top'" :class="classesButton">
                <button
                    class="cursor-pointer"
                    type="button"
                    :class="classes"
                    :disabled="disabled"
                >
                    <PropertyIcon
                        :name="icon"
                        class="cursor-pointer"
                        :class="[iconSize, classes, finalIconColorClass]"
                        :stroke-width="stroke"
                        :style="[iconStyle]"
                    />
                </button>
            </div>

            <div v-tooltip.bottom="tooltipBinding"  v-else-if="direction === 'bottom'" :class="classesButton">
                <button
                    class="cursor-pointer"
                    type="button"
                    :class="classes"
                    :disabled="disabled"
                >
                    <PropertyIcon
                        :name="icon"
                        class="cursor-pointer"
                        :class="[iconSize, classes, finalIconColorClass]"
                        :stroke-width="stroke"
                        :style="[iconStyle]"
                    />
                </button>
            </div>
            <div v-tooltip.left="tooltipBinding" v-else-if="direction === 'left'" :class="classesButton">
                <button
                    class="cursor-pointer"
                    type="button"
                    :class="classes"
                    :disabled="disabled"
                >
                    <PropertyIcon
                        :name="icon"
                        class="cursor-pointer"
                        :class="[iconSize, classes, finalIconColorClass]"
                        :stroke-width="stroke"
                        :style="[iconStyle]"
                    />
                </button>
            </div>
            <div v-tooltip.right="tooltipBinding" v-else :class="classesButton">
                <button
                    class="cursor-pointer"
                    type="button"
                    :class="classes"
                    :disabled="disabled"
                >
                    <PropertyIcon
                        :name="icon"
                        class="cursor-pointer"
                        :class="[iconSize, classes, finalIconColorClass]"
                        :stroke-width="stroke"
                        :style="[iconStyle]"
                    />
                </button>
            </div>
        </template>

        <!-- Tooltip komplett aus -->
        <button
            v-else
            class="cursor-pointer"
            type="button"
            :class="[classes, classesButton]"
            :disabled="disabled"
        >
            <PropertyIcon
                :name="icon"
                class="cursor-pointer"
                :class="[iconSize, classes, finalIconColorClass]"
                :stroke-width="stroke"
                :style="[iconStyle]"
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
    // Neues Flag: erzwingt schwarze Icon-Farbe (#27233C)
    blackIcon: { type: Boolean, default: false },
    noRelative: { type: Boolean, default: false },
    /** Zusätzliche CSS-Klasse(n) für das Tooltip-Overlay, z.B. 'aw-tooltip-wide' */
    tooltipCssClass: { type: String, default: '' },
    /** Opt-in: tooltipText als HTML rendern (nur für eigene, vertrauenswürdige Inhalte) */
    allowHtml: { type: Boolean, default: false },
    noTooltip: { type: Boolean, default: false },
    useTranslation: { type: Boolean, default: false },
    iconColor: { type: String, default: '' }
})


const finalIconColorClass = computed(() => {
    if (props.iconColor) return props.iconColor;

    if (props.whiteIcon) return 'text-white';
    if (props.grayIcon) return 'text-text-subtle';
    if (props.blackIcon) return 'text-text';

    return 'text-text-muted';
});

/**
 * Einheitliches Binding für PrimeVue Tooltip.
 * 'position' bleibt als Fallback gesetzt, falls du mal ohne Modifier verwendest.
 */
const tooltipBinding = computed(() => ({
    value: props.tooltipText,
    disabled: props.noTooltip,
    useTranslation: props.useTranslation,
    position: props.direction, // Fallback, wenn kein Modifier genutzt wird
    appendTo: 'body', // sicherstellen, dass Overlay am Body hängt
    escape: !props.allowHtml,
    class: ['aw-tooltip', props.tooltipCssClass].filter(Boolean).join(' '),
}))
</script>
