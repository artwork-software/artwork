<script>
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "ToolTipDefault",
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
        }
    }
}
</script>

<template>
    <div class="relative flex items-center z-10">
        <!-- Button oder Icon, das den Tooltip triggert -->
        <button @mouseover="show = true" @mouseleave="show = false" class="focus:outline-none">
            <!-- Ihr SVG-Icon -->
            <IconExclamationCircle class="h-5 w-5 text-artwork-buttons-context" />
        </button>
        <!-- Tooltip-Text, der beim Hover erscheint -->
        <div v-if="show && top" class="absolute z-10 -top-3 z-10 w-64 p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-x-1/2 -translate-y-full left-1/2">
            {{ tooltipText }}
            <!-- Tooltip Pfeil unten -->
            <div class="absolute bg-black h-3 w-3 transform rotate-45 left-1/2 -translate-x-1/2 -bottom-1.5"></div>
        </div>
        <div v-if="show && left" class="absolute z-10 w-64 p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-y-1/2 right-full mr-3 top-1/2">
            {{ tooltipText }}
            <div class="absolute bg-black h-3 w-3 transform rotate-45 left-full -translate-x-1/2 top-1/2 -mt-1.5"></div>
        </div>
    </div>
</template>

<style scoped>

</style>
