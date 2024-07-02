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
        <transition enter-active-class="transition-enter-active"
                    enter-from-class="transition-enter-from"
                    enter-to-class="transition-enter-to"
                    leave-active-class="transition-leave-active"
                    leave-from-class="transition-leave-from"
                    leave-to-class="transition-leave-to">
            <MenuItems class="z-50 absolute rounded-lg w-80 shadow-lg bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none" :class="right ? 'origin-top-right right-0' : 'origin-top-left left-0'">
                <div class="py-1">
                    <slot />
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>
