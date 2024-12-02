<template>
    <Menu as="div" class="inline-block" :class="!noRelative ? 'relative' : ''">
        <Float auto-placement portal :offset="{ mainAxis: hasNoOffset ? 5 : -10, crossAxis: hasNoOffset ? 25 : 75}">
            <div class="font-semibold text-artwork-buttons-context flex items-center justify-center" ref="menuButtonRef">
                <MenuButton>
                    <IconDotsVertical
                        v-if="!showSortIcon && !showCustomIcon"
                        stroke-width="1.5"
                        class="flex-shrink-0"
                        aria-hidden="true"
                        :class="[dotsColor, dotsSize, whiteIcon ? 'text-white' : '']"
                    />
                    <ToolTipComponent
                        v-else-if="!showCustomIcon"
                        direction="bottom"
                        :tooltip-text="$t('Sorting')"
                        icon="IconSortDescending"
                        icon-size="h-8 w-8"
                        :class="[dotsColor, dotsSize, whiteIcon ? 'text-white' : '']"
                    />

                    <ToolTipComponent
                        v-if="showCustomIcon"
                        direction="bottom"
                        :tooltip-text="$t(translationKey)"
                        :icon="icon"
                        :icon-size="dotsSize"
                        :class="[dotsColor, dotsSize, whiteIcon ? 'text-white' : '']"
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
    },

});
</script>
