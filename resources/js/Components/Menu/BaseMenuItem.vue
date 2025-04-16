<template>
    <MenuItem v-slot="{ active }">
        <a :href="link" v-if="asLink"
           :class="[whiteMenuBackground ? 'hover:bg-artwork-navigation-color/30 hover:text-artwork-buttons-create text-gray-500' : 'hover:bg-artwork-navigation-color/10 hover:text-white', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
            <component :is="icon" class="mr-3 h-5 w-5 text-primaryText" :class="whiteMenuBackground ? 'group-hover:bg-artwork-navigation-color/30 group-hover:text-artwork-buttons-create text-gray-500' : 'group-hover:bg-artwork-navigation-color/10 group-hover:text-white'" aria-hidden="true"/>
            {{ withoutTranslation ? title : $t(title) }}
        </a>
        <div v-else
           :class="[whiteMenuBackground ? 'hover:bg-artwork-navigation-color/30 hover:text-artwork-buttons-create text-gray-500' : 'hover:bg-artwork-navigation-color/10 hover:text-white', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
            <component :is="icon" class="mr-3 h-5 w-5 text-primaryText" :class="whiteMenuBackground ? 'group-hover:bg-artwork-navigation-color/30 group-hover:text-artwork-buttons-create text-gray-500' : 'group-hover:bg-artwork-navigation-color/10 group-hover:text-white'" aria-hidden="true"/>
            {{ withoutTranslation ? title : $t(title) }}
        </div>
    </MenuItem>
</template>

<script setup>

import {MenuItem} from "@headlessui/vue";

const props = defineProps({
    asLink: {
        type: Boolean,
        default: false
    },
    link: {
        type: String,
        default: ''
    },
    icon: {
        type: String,
        default: 'IconEdit'
    },
    title: {
        type: String,
        default: ''
    },
    withoutTranslation: {
        type: Boolean,
        required: false,
        default: false
    },
    whiteMenuBackground: {
        type: Boolean,
        default: false
    }
})

</script>

<style scoped>

</style>