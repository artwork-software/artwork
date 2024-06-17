<script>
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    mixins: [IconLib],
    name: "BaseMenu",
    components: {
        Menu,
        MenuItem,
        MenuItems,
        MenuButton,
    },
    props: {
        dotsColor: {
            type: String,
            default: 'text-artwork-navigation-text'
        },
        dotsSize: {
            type: String,
            default: 'h-6 w-6'
        },
        right: {
            type: Boolean,
            default: true
        }
    }
}
</script>

<template>
    <Menu as="div" class="relative">
        <div class="flex">
            <MenuButton class="flex">
                <IconDotsVertical stroke-width="1.5" class=" flex-shrink-0" aria-hidden="true" :class="[dotsColor, dotsSize]"/>
            </MenuButton>
        </div>
        <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
            <MenuItems class="z-50 absolute rounded-lg w-80 shadow-lg bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none" :class="right ? 'origin-top-right right-0' : 'origin-top-left left-0'">
                <div class="py-1">
                    <slot />
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>
