<template>
    <div class="flex items-center group/tooltip" :class="noRelative ? '' : 'relative' ">
        <button class="focus:outline-none" type="button" :class="classes" :disabled="disabled">
            <component :is="icon" class=" cursor-pointer" :class="[iconSize, classes, whiteIcon ? 'text-white' : grayIcon ? 'text-gray-400' : 'text-artwork-buttons-context']" :stroke-width="stroke"/>
        </button>
        <div class="hidden group-hover/tooltip:block" v-if="!noTooltip">
            <div v-if="direction === 'top'" :class="tooltipCssClass" class="absolute z-50 -top-3 text-center  p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-x-1/2 -translate-y-full left-1/2">
                <span v-if="useTranslation" v-html="$t(tooltipText)"></span>
                <span v-else v-html="tooltipText"></span>
                <div class="absolute bg-black h-3 w-3 transform rounded-sm rotate-45 left-1/2 -translate-x-1/2 -bottom-1.5"></div>
            </div>
            <div v-if="direction === 'left'" :class="tooltipCssClass" class="absolute z-50  p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-y-1/2 right-full mr-3 top-1/2">
                <span v-if="useTranslation" v-html="$t(tooltipText)"></span>
                <span v-else v-html="tooltipText"></span>
                <div class="absolute bg-black h-3 w-3 transform rounded-sm rotate-45 left-full -translate-x-1/2 top-1/2 -mt-1.5"></div>
            </div>
            <div v-if="direction === 'bottom'" :class="tooltipCssClass" class="absolute z-50 -bottom-3  text-center p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-x-1/2 translate-y-full left-1/2">
                <span v-if="useTranslation" v-html="$t(tooltipText)"></span>
                <span v-else v-html="tooltipText"></span>
                <div class="absolute bg-black h-3 w-3 transform rounded-sm rotate-45 left-1/2 -translate-x-1/2 -top-1.5"></div>
            </div>
            <!-- right -->
            <div v-if="direction === 'right'" :class="tooltipCssClass" class="absolute z-50  p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg transform -translate-y-1/2 left-full ml-3 top-1/2">
                <span v-if="useTranslation" v-html="$t(tooltipText)"></span>
                <span v-else v-html="tooltipText"></span>
                <div class="absolute bg-black h-3 w-3 transform rounded-sm rotate-45 right-full translate-x-1/2 top-1/2 -mt-1.5"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref} from "vue";
const show = ref(false)
import {Float} from "@headlessui-float/vue";

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
    tooltipCssClass: {
        type: String,
        default: 'w-fit'
    },
    noTooltip: {
        type: Boolean,
        default: false
    },
    useTranslation: {
        type: Boolean,
        default: false
    }
})

</script>
