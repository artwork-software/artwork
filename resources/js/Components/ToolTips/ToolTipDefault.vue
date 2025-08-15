<script>
import IconLib from "@/Mixins/IconLib.vue";
import {IconBackground, IconClock24} from "@tabler/icons-vue";

export default {
    name: "ToolTipDefault",
    components: {IconBackground, IconClock24},
    mixins: [IconLib],
    data() {
        return {
            show: false,
        }
    },
    props: {
        tooltipText: {
            type: String,
            required: true
        },
        left: {
            type: Boolean,
            default: false
        },
        top: {
            type: Boolean,
            default: false
        },
        bottom: {
            type: Boolean,
            default: false
        },
        showBackgroundIcon: {
            type: Boolean,
            default: false
        },
        showDraggable: {
            type: Boolean,
            default: false
        },
        showXIcon: {
            type: Boolean,
            default: false
        },
        show24HIcon: {
            type: Boolean,
            default: false
        },
        classes: {
            type: String,
            default: ''
        },
        iconClasses: {
            type: String,
            default: ''
        }
    }
}
</script>

<template>
    <div class="relative flex items-center z-40">
        <!-- Button oder Icon, das den Tooltip triggert -->
        <button @mouseover="show = true" @mouseleave="show = false" class="focus:outline-none cursor-pointer" :class="classes">
            <!-- Ihr SVG-Icon -->
            <IconExclamationCircle class="h-5 w-5 text-artwork-buttons-context" v-if="!showBackgroundIcon && !showDraggable && !showXIcon && !show24HIcon" :class="iconClasses" />
            <IconBackground stroke-width="1.5" class="h-6 w-6" aria-hidden="true" v-if="showBackgroundIcon" :class="iconClasses"/>
            <IconDragDrop stroke-width="1.5" class="h-6 w-6" aria-hidden="true" v-if="showDraggable" :class="iconClasses"  />
            <IconX stroke-width="1.5" class="h-6 w-6" aria-hidden="true" v-if="showXIcon" :class="iconClasses"  />
            <IconClock24 stroke-width="1.5" class="h-6 w-6" aria-hidden="true" v-if="show24HIcon" :class="iconClasses" />
        </button>
        <!-- Tooltip-Text, der beim Hover erscheint -->
        <div v-if="show && top" class="absolute z-50 -top-3 text-center w-64 p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-x-1/2 -translate-y-full left-1/2">
            {{ tooltipText }}
            <!-- Tooltip Pfeil unten -->
            <div class="absolute bg-black h-3 w-3 transform rotate-45 left-1/2 -translate-x-1/2 -bottom-1.5"></div>
        </div>
        <div v-if="show && left" class="absolute z-50 w-64 p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-y-1/2 right-full mr-3 top-1/2">
            {{ tooltipText }}
            <div class="absolute bg-black h-3 w-3 transform rotate-45 left-full -translate-x-1/2 top-1/2 -mt-1.5"></div>
        </div>
        <div v-if="show && bottom" class="absolute z-50 -bottom-3 w-64  text-center p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-x-1/2 translate-y-full left-1/2">
            {{ tooltipText }}
            <div class="absolute bg-black h-3 w-3 transform rounded-sm rotate-45 left-1/2 -translate-x-1/2 -top-1.5"></div>
        </div>
    </div>
</template>

<style scoped>

</style>
