<template>
    <Menu as="div" class="relative inline-block">
        <div class="flex items-center justify-center w-full font-semibold text-artwork-buttons-context">
            <MenuButton>
                <IconDotsVertical v-if="!showSortIcon"
                    stroke-width="1.5"
                    class="flex-shrink-0"
                    aria-hidden="true"
                    :class="[dotsColor, dotsSize]"
                />
                <IconSortDescending v-else
                    stroke-width="1.5"
                    class="flex-shrink-0"
                    aria-hidden="true"
                    :class="[dotsColor, dotsSize]"
                />
            </MenuButton>
        </div>

        <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
            <MenuItems class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-lg bg-artwork-navigation-background shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div>
                    <slot />
                </div>
            </MenuItems>
        </transition>
    </Menu>
    <!--<Menu as="div" class="z-50" :class="noRelative ? '' : 'relative'">
        <div class="flex">
            <MenuButton ref="menuButtonRef" class="flex">
                <IconDotsVertical
                    stroke-width="1.5"
                    class="flex-shrink-0"
                    aria-hidden="true"
                    :class="[dotsColor, dotsSize]"
                />
            </MenuButton>
        </div>
        <Teleport to="body">
            <transition
                enter-active-class="transition-enter-active"
                enter-from-class="transition-enter-from"
                enter-to-class="transition-enter-to"
                leave-active-class="transition-leave-active"
                leave-from-class="transition-leave-from"
                leave-to-class="transition-leave-to"
            >
                <div class="absolute left-1/2 z-50 -translate-x-1/2 transform sm:px-0 bg-artwork-navigation-background ring-0 py-3 px-5">
                    <MenuItems ref="menuRef" class="rounded-lg w-80 shadow-lg bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                        <div class="py-1">
                            <slot />
                        </div>
                    </MenuItems>
                </div>
            </transition>
        </Teleport>
    </Menu>-->
</template>

<script>
import { defineComponent } from 'vue';
import { Menu, MenuButton, MenuItems } from '@headlessui/vue';
import IconLib from '@/Mixins/IconLib.vue';

export default defineComponent({
    name: 'BaseMenu',
    mixins: [IconLib],
    components: {
        Menu,
        MenuButton,
        MenuItems,
    },
    props: {
        dotsColor: {
            type: String,
            default: 'text-artwork-navigation-text',
        },
        dotsSize: {
            type: String,
            default: 'h-6 w-6',
        },
        right: {
            type: Boolean,
            default: true,
        },
        noRelative: {
            type: Boolean,
            default: false,
        },
        showSortIcon: {
            type: Boolean,
            default: false,
        },
    },
});
</script>

<style scoped>
</style>
