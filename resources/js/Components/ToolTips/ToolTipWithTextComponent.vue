<template>
    <div class="flex items-center group/tooltip" :class="noRelative ? '' : 'relative' ">
        <button class="focus:outline-none flex items-center gap-x-1" type="button" :class="classes" :disabled="disabled">
            <component v-if="!iconRight" :is="icon" class=" cursor-pointer" :class="[iconSize, classes]" :stroke-width="stroke"/>
            {{ text }}
            <component v-if="iconRight" :is="icon" class=" cursor-pointer" :class="[iconSize, classes]" :stroke-width="stroke"/>
        </button>

        <div class="hidden group-hover/tooltip:block">
            <div v-if="direction === 'top'" :class="tooltipWidth" class="absolute z-50 -top-3 text-center  p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-x-1/2 -translate-y-full left-1/2">
                {{ tooltipText }}
                <div class="absolute bg-black h-3 w-3 transform rounded-sm rotate-45 left-1/2 -translate-x-1/2 -bottom-1.5"></div>
            </div>
            <div v-if="direction === 'left'" :class="tooltipWidth" class="absolute z-50  p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-y-1/2 right-full mr-3 top-1/2">
                {{ tooltipText }}
                <div class="absolute bg-black h-3 w-3 transform rounded-sm rotate-45 left-full -translate-x-1/2 top-1/2 -mt-1.5"></div>
            </div>
            <div v-if="direction === 'bottom'" :class="tooltipWidth" class="absolute z-50 -bottom-3  text-center p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-x-1/2 translate-y-full left-1/2">
                {{ tooltipText }}
                <div class="absolute bg-black h-3 w-3 transform rounded-sm rotate-45 left-1/2 -translate-x-1/2 -top-1.5"></div>
            </div>
            <!-- right -->
            <div v-if="direction === 'right'" :class="tooltipWidth" class="absolute z-50  p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-y-1/2 left-full ml-3 top-1/2">
                {{ tooltipText }}
                <div class="absolute bg-black h-3 w-3 transform rounded-sm rotate-45 right-full translate-x-1/2 top-1/2 -mt-1.5"></div>
            </div>
        </div>

    </div>
</template>

<script setup>
import {ref} from "vue";
const show = ref(false)

const props = defineProps({
    direction: {
        type: String,
        default: 'top',
        validator: (value) => ['top', 'left', 'right', 'bottom'].includes(value)
    },
    tooltipText: {
        type: String,
        default: ''
    },
    text: {
        type: String,
        default: ''
    },
    iconRight: {
        type: Boolean,
        default: false
    },
    classes: {
        // string or array
        type: [String, Array],
        default: ''
    },
    icon: {
        type: [String, Object, Function],
        default: null
    },
    iconSize: {
        type: String,
        default: 'w-6 h-6'
    },
    disabled: {
        type: Boolean,
        default: false
    },
    stroke: {
        type: [String, Number],
        default: '1'
    },
    whiteIcon: {
        type: Boolean,
        default: false
    },
    grayIcon: {
        type: Boolean,
        default: false
    },
    noRelative: {
        type: Boolean,
        default: false
    },
    tooltipWidth: {
        type: String,
        default: 'w-fit'
    }
})

</script>
