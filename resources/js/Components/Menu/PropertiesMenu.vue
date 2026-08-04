<template>
    <Menu as="div" class="inline-block print:hidden" :class="!noRelative ? 'relative' : ''">
        <Float auto-placement portal :offset="{ mainAxis: hasNoOffset ? 5 : -10, crossAxis: hasNoOffset ? 25 : 75}">
            <div class="font-semibold  flex items-center justify-center" ref="menuButtonRef" :class="whiteIcon ? 'text-white' : 'text-text-muted'">
                <MenuButton :id="buttonId">
                    <slot name="button"></slot>
                </MenuButton>
            </div>

            <transition enter-active-class="transition ease-out duration-100 motion-reduce:transition-none"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75 motion-reduce:transition-none"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95">
                <MenuItems class="z-50 rounded-lg border border-border-subtle bg-surface shadow-overlay" :class="[menuWidth]">
                    <div class="max-h-60 overflow-y-auto p-1">
                        <slot name="menu"/>
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
    name: 'PropertiesMenu',
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
            default: 'text-text-inverse',
        },
        dotsSize: {
            type: String,
            default: 'h-6 w-6',
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
        hasNoOffset: {
            type: Boolean,
            required: false,
            default: false,
        },
        showCustomIcon: {
            type: Boolean,
            required: false,
            default: false,
        },
        icon: {
            type: String,
            required: false,
            default: 'IconEdit',
        },
        translationKey: {
            type: String,
            required: false,
            default: 'Sorting',
        },
        buttonId: {
            type: String,
            required: false,
            default: 'menuButton',
        },
        showIcon: {
            type: Boolean,
            required: false,
            default: true,
        },
        whiteMenuBackground: {
            type: Boolean,
            required: false,
            default: false,
        },
        strokeWidth: {
            type: [String, Number],
            required: false,
            default: 1.5,
        },
        tooltipDirection: {
            type: String,
            required: false,
            default: 'top',
        },
    },

});
</script>
