<template>
    <Menu as="div" class="relative flex items-center text-left">
        <div class="flex items-center">
            <MenuButton v-if="!onlyIcon" class="w-52 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                <span class="float-left xsDark">Filter</span>
                <IconChevronDown stroke-width="1.5"
                    class="ml-2 -mr-1 h-5 w-5 text-artwork-buttons-context float-right"
                    aria-hidden="true"
                />
            </MenuButton>
            <MenuButton v-else>
                <IconFilter class="h-7 w-7 text-artwork-buttons-context" />
            </MenuButton>
        </div>
        <transition
            enter-active-class="transition duration-50 ease-out"
            enter-from-class="transform scale-100 opacity-100"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <MenuItems v-if="left" class="w-80 absolute left-0 top-12 origin-top-left divide-y divide-gray-200 rounded-sm bg-artwork-navigation-background ring-1 ring-black p-2 text-white opacity-100 z-50 max-h-[calc(100vh-10rem)] overflow-auto">
                <slot></slot>
            </MenuItems>
            <MenuItems v-else class="w-80 absolute right-0 top-12 origin-top-right divide-y divide-gray-200 rounded-sm bg-artwork-navigation-background ring-1 ring-black p-2 text-white opacity-100 z-50 max-h-[calc(100vh-10rem)] overflow-auto">
                <slot></slot>
            </MenuItems>
        </transition>
    </Menu>
</template>

<script>
import {
    Menu,
    MenuButton,
    MenuItems,
} from "@headlessui/vue";

import {
    ChevronDownIcon,
} from '@heroicons/vue/outline';
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "BaseFilter",
    mixins: [Permissions, IconLib],
    components: {
        Menu,
        MenuItems,
        MenuButton,
        ChevronDownIcon
    },
    props:['onlyIcon', 'left']
}
</script>

<style scoped>

</style>
