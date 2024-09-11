<script setup>
import { ref, nextTick } from 'vue';

const show = ref(false);
const tooltipStyle = ref({});
let tooltipElement = null;

const props = defineProps({
    direction: {
        type: String,
        default: 'top',
        validator: (value) => ['top', 'left', 'right', 'bottom'].includes(value),
    },
    tooltipText: {
        type: String,
        default: '',
    },
    classes: {
        type: [String, Array],
        default: '',
    },
    icon: {
        type: [String, Object, Function],  // Allow multiple types
        required: true
    },
    iconSize: {
        type: [String, Array],
        default: 'w-6 h-6',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    stroke: {
        type: [String, Number],
        default: '1',
    },
});

const showTooltip = (event) => {
    show.value = true;
    nextTick(() => {
        tooltipElement = document.querySelector('.tooltip-content');
        if (tooltipElement) {
            updateTooltipPosition(event);
        }
    });
};

const hideTooltip = () => {
    show.value = false;
};

const updateTooltipPosition = (event) => {
    const buttonRect = event.target.getBoundingClientRect();
    const tooltipRect = tooltipElement.getBoundingClientRect();

    let top, left;

    // Standardpositionierung des Tooltips basierend auf der Richtung
    switch (props.direction) {
        case 'top':
            top = buttonRect.top - tooltipRect.height - 8;
            left = buttonRect.left + buttonRect.width / 2 - tooltipRect.width / 2;
            break;
        case 'bottom':
            top = buttonRect.bottom + 8;
            left = buttonRect.left + buttonRect.width / 2 - tooltipRect.width / 2;
            break;
        case 'left':
            top = buttonRect.top + (buttonRect.height / 2) - (tooltipRect.height / 2);
            left = buttonRect.left - tooltipRect.width - 5;
            break;
        case 'right':
            top = buttonRect.top + (buttonRect.height / 2) - (tooltipRect.height / 2);
            left = buttonRect.right + 10;
            break;
    }

    // Sicherstellen, dass der Tooltip nicht außerhalb des Bildschirms angezeigt wird
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    // Horizontale Anpassungen
    if (left < 0) {
        left = 8; // Setzt den Tooltip an den Rand des Bildschirms
    }
    if (left + tooltipRect.width > viewportWidth) {
        left = viewportWidth - tooltipRect.width - 8; // Verhindert, dass der Tooltip rechts aus dem Bild läuft
    }

    // Vertikale Anpassungen
    if (top < 0) {
        top = 8; // Verhindert, dass der Tooltip oben aus dem Bild läuft
    }
    if (top + tooltipRect.height > viewportHeight) {
        top = viewportHeight - tooltipRect.height - 8; // Verhindert, dass der Tooltip unten aus dem Bild läuft
    }

    // Setzt die finale Position
    tooltipStyle.value = {
        position: 'fixed',
        top: `${top}px`,
        left: `${left}px`,
        zIndex: 9999,
    };
};
</script>

<template>
    <div class="flex items-center z-50 relative">
        <button
            @mouseover="showTooltip"
            @mouseleave="hideTooltip"
            class="focus:outline-none"
            :class="classes"
            :disabled="disabled"
        >
            <component
                :is="icon"
                class="text-artwork-buttons-context cursor-pointer"
                :class="[iconSize, classes]"
                :stroke-width="stroke"
            />
        </button>

        <!-- Tooltip bleibt im DOM, wird nur über 'display' gesteuert -->
        <div :style="[tooltipStyle, { display: show ? 'block' : 'none' }]" class="tooltip-content">
            <div class="p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg">
                {{ tooltipText }}
            </div>
        </div>
    </div>
</template>

<style scoped>
.tooltip-content {
    display: none; /* Tooltip ausblenden */
    transition: opacity 0.2s;
    position: fixed; /* Tooltip wird außerhalb des Layouts gehalten */
}
</style>
