<template>
    <MenuItem v-slot="{ active }">
        <Link :href="link" v-if="asLink"
           :class="[whiteMenuBackground ? 'hover:text-artwork-buttons-create' : 'hover:text-white text-white', icon === 'IconTrash' ? 'text-red-400' : ' text-gray-500', 'group flex items-center text-xs px-3 py-2 text-sm subpixel-antialiased cursor-pointer rounded-lg hover:bg-gray-100']">
            <component :is="icon" class="mr-2 h-4 w-4" :class="whiteMenuBackground ? 'group-hover:text-artwork-buttons-create' : ' group-hover:text-artwork-buttons-hover'" aria-hidden="true"/>
            {{ withoutTranslation ? title : $t(title) }}
        </Link>
        <div v-else
           :class="[whiteMenuBackground ? 'hover:text-artwork-buttons-create' : 'hover:text-white text-white', icon === 'IconTrash' ? 'text-red-400' : ' text-gray-500' , 'group flex items-center text-xs px-3 py-2 text-sm subpixel-antialiased cursor-pointer rounded-lg hover:bg-gray-100']">
            <component :is="icon" class="mr-2 h-4 w-4" :class="whiteMenuBackground ? 'group-hover:text-artwork-buttons-create' : 'group-hover:text-artwork-buttons-hover'" aria-hidden="true"/>
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
