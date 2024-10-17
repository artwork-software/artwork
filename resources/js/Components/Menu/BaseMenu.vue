<template>

    <Menu as="div" class="inline-block" :class="!noRelative ? 'relative' : ''">
        <Float auto-placement portal  :offset="{ mainAxis: -10, crossAxis: 75}">
            <div class="font-semibold text-artwork-buttons-context flex items-center justify-center" ref="menuButtonRef">
                <MenuButton>
                    <IconDotsVertical
                        v-if="!showSortIcon"
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
                        :white-icon="whiteIcon"
                    />
                </MenuButton>
            </div>

            <transition enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95">
                <MenuItems class="z-50 rounded-lg bg-artwork-navigation-background shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none" :class="[menuWidth]">
                    <div>
                        <slot />
                    </div>
                </MenuItems>
            </transition>
        </Float>
    </Menu>

</template>

<script>
import { defineComponent } from 'vue';
import { Menu, MenuButton, MenuItems } from '@headlessui/vue';
import IconLib from '@/Mixins/IconLib.vue';
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {Float} from "@headlessui-float/vue";

export default defineComponent({
    name: 'BaseMenu',
    mixins: [IconLib],
    components: {
        Float,
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
        },
        whiteIcon: {
            type: Boolean,
            required: false,
            default: false
        },
        placement: {
            type: String,
            required: false,
            default: 'top',
        }
    },

});
</script>
