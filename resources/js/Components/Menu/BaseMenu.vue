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
                <ToolTipComponent
                    v-else
                    direction="bottom"
                    :tooltip-text="$t('Sorting')"
                    icon="IconSortDescending"
                    icon-size="h-8 w-8"
                />
            </MenuButton>
        </div>

        <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
            <MenuItems class="absolute z-10 rounded-lg bg-artwork-navigation-background shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" :class="[menuWidth, right ? 'origin-top-left left-0' : 'origin-top-right right-0']">
                <div>
                    <slot />
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>

<script>
import {defineComponent} from 'vue';
import {Menu, MenuButton, MenuItems} from '@headlessui/vue';
import IconLib from '@/Mixins/IconLib.vue';
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

export default defineComponent({
    name: 'BaseMenu',
    mixins: [IconLib],
    components: {
        ToolTipComponent,
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
            default: false,
        },
        noRelative: {
            type: Boolean,
            default: false,
        },
        showSortIcon: {
            type: Boolean,
            default: false,
        },
        menuWidth: {
            type: String,
            default: 'w-56',
        }
    },
});
</script>
