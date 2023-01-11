<template>
    <Menu as="div" class="relative inline-block flex items-center text-left">
        <div class="">
            <MenuButton
                class="w-52 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
            >
                <span class="float-left xsDark">Filter</span>
                <ChevronDownIcon
                    class="ml-2 -mr-1 h-5 w-5 text-primary float-right"
                    aria-hidden="true"
                />
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
            <MenuItems class="w-80 absolute right-0 top-12 origin-top-right divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
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

export default {
    name: "BaseFilter",
    components: {
        Menu,
        MenuItems,
        MenuButton,
        ChevronDownIcon
    }
}
</script>

<style scoped>

</style>
