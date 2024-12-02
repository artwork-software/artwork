<template>
    <MenuItem v-slot="{ active }">
        <a :href="link" v-if="asLink"
           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
            <component :is="icon" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" aria-hidden="true"/>
            {{ $t(title)}}
        </a>
        <div v-else
           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
            <component :is="icon" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" aria-hidden="true"/>
            {{ $t(title)}}
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
    }
})

</script>

<style scoped>

</style>