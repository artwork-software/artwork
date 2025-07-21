<template>
    <MenuItem v-slot="{ active }">
        <Link :href="link" v-if="asLink"
           :class="[whiteMenuBackground ? 'hover:text-artwork-buttons-create text-gray-500' : 'hhover:text-white text-white', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer rounded-3xl']">
            <component :is="icon" class="mr-3 h-5 w-5 text-primaryText" :class="whiteMenuBackground ? 'group-hover:text-artwork-buttons-create text-gray-500' : ' group-hover:text-artwork-buttons-hover text-gray-400'" aria-hidden="true"/>
            {{ withoutTranslation ? title : $t(title) }}
        </Link>
        <div v-else
           :class="[whiteMenuBackground ? 'hover:text-artwork-buttons-create text-gray-500' : 'hover:text-white text-white', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer rounded-3xl']">
            <component :is="icon" class="mr-3 h-5 w-5 text-primaryText" :class="whiteMenuBackground ? 'group-hover:text-artwork-buttons-create text-gray-500' : 'group-hover:text-artwork-buttons-hover text-gray-400'" aria-hidden="true"/>
            {{ withoutTranslation ? title : $t(title) }}
        </div>
    </MenuItem>
</template>

<script setup>

import {MenuItem} from "@headlessui/vue";
import {Link} from "@inertiajs/vue3";

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