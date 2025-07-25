<template>
    <Menu as="div" class="inline-block print:hidden w-full float-left" :class="!noRelative ? 'relative' : ''">
        <Float auto-placement portal :offset="{ mainAxis: hasNoOffset ? 5 : -10, crossAxis: hasNoOffset ? 25 : 75}">
            <div class="font-semibold  flex items-center justify-center w-full" ref="menuButtonRef" :class="[whiteIcon ? 'text-white' : 'text-artwork-buttons-context', dotsColor]">
                <MenuButton :id="buttonId" class="w-full">
                   <div class="flex items-center gap-x-1 w-full">
                       <div v-if="showIcon">
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
                               :white-icon="whiteIcon"
                               :class="[dotsColor, dotsSize, whiteIcon ? 'text-white' : '']"
                               :no-tooltip="!noTooltip"
                           />

                           <ToolTipComponent
                               v-if="showCustomIcon"
                               :direction="tooltipDirection"
                               :tooltip-text="$t(translationKey)"
                               :icon="icon"
                               :no-relative="noRelative"
                               :icon-size="dotsSize"
                               :stroke="strokeWidth"
                               :white-icon="whiteIcon"
                               :class="[dotsColor, dotsSize, whiteIcon ? 'text-white' : '']"
                               :no-tooltip="!noTooltip"
                           />
                       </div>

                       <div v-if="menuButtonText && showMenuButtonText" :class="[textWithMarginLeft ? 'ml-2' : '']">
                            {{ $t(menuButtonText) }}
                       </div>
                   </div>
                </MenuButton>
            </div>

            <transition enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95">
                <MenuItems class="z-50 focus:outline-none" :class="[menuWidth, needsMaxHeight ? 'max-h-72 overflow-scroll xl:max-h-none' : '']">
                    <div class="card white p-1.5 !rounded-xl">
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
        menuButtonText: {
            type: String,
            required: false,
            default: '',
        },
        showMenuButtonText: {
            type: Boolean,
            required: false,
            default: false,
        },
        noTooltip: {
            type: Boolean,
            required: false,
            default: false,
        },
        needsMaxHeight: {
            type: Boolean,
            required: false,
            default: false,
        },
        textWithMarginLeft: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

});
</script>
