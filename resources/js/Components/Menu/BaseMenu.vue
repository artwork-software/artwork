<template>
    <Menu
        as="div"
        class="inline-block print:hidden"
        :class="[!noRelative && 'relative', classes]"
    >
        <Float
            portal
            :offset="computedOffset"
            :placement="placement || undefined"
            flip
            shift
        >
            <!-- Trigger -->
            <div
                class="flex w-full items-center justify-center h-full font-semibold"
                :class="[whiteIcon ? 'text-white' : 'text-artwork-buttons-context']"
            >
                <MenuButton
                    :id="buttonId"
                    type="button"
                    class="flex items-center justify-center rounded-lg h-full outline-none ring-inset transition focus-visible:ring-2 cursor-pointer"
                    :class="[
                        whiteIcon
                          ? 'focus-visible:ring-white/70'
                          : 'focus-visible:ring-indigo-500/70',

                      ]"
                    :aria-label="!showMenuButtonText ? $t(menuButtonAria) : undefined"
                >
                    <!-- Iconbereich -->
                    <template v-if="showIcon">
                        <!-- Standard: Dots -->
                        <PropertyIcon name="IconDotsVertical"
                            v-if="!showSortIcon && !showCustomIcon"
                            :stroke-width="strokeWidth"
                            class="flex-shrink-0"
                            :class="[dotsColor, dotsSize, whiteIcon && 'text-white']"
                            aria-hidden="true"
                        />

                        <!-- Sort-Icon mit optionalem Tooltip -->
                        <ToolTipComponent
                            v-else-if="!showCustomIcon"
                            :direction="tooltipDirection"
                            :tooltip-text="$t('Sorting')"
                            icon="IconSortDescending"
                            :icon-size="dotsSize"
                            :white-icon="whiteIcon"
                            :stroke="strokeWidth"
                            :no-tooltip="noTooltip"
                            :class="[dotsColor, dotsSize, whiteIcon && 'text-white']"
                            :classes-button="classesButton"
                        />

                        <!-- Eigene Icon-Komponente mit optionalem Tooltip -->
                        <ToolTipComponent
                            v-else
                            :direction="tooltipDirection"
                            :tooltip-text="$t(translationKey)"
                            :icon="icon"
                            :icon-size="dotsSize"
                            :stroke="strokeWidth"
                            :white-icon="whiteIcon"
                            :no-tooltip="noTooltip"
                            :class="[dotsColor, dotsSize, whiteIcon && 'text-white']"
                            :classes-button="classesButton"
                        />
                    </template>

                    <!-- Optionaler Text neben dem Icon -->
                    <span v-if="menuButtonText && showMenuButtonText" :class="[textWithMarginLeft && 'ml-2']">
                    {{ $t(menuButtonText) }}
                  </span>
                </MenuButton>
            </div>

            <!-- Menu -->
            <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <MenuItems
                    class="z-50 mt-2 origin-top-right focus:outline-none"
                >
                    <div
                        class="rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl ring-1 ring-black/5"
                        :class="[
                            menuWidth,
                            needsMaxHeight ? 'max-h-72 overflow-auto xl:max-h-none' : ''
                        ]"
                    >
                        <slot />
                    </div>
                </MenuItems>
            </transition>
        </Float>
    </Menu>
</template>

<script setup lang="ts">
import { ref, computed, type Component } from 'vue'
import { Menu, MenuButton, MenuItems } from '@headlessui/vue'
import { Float } from '@headlessui-float/vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

type IconProp = string | Component

const props = withDefaults(defineProps<{
    dotsColor?: string
    dotsSize?: string
    noRelative?: boolean
    showSortIcon?: boolean
    menuWidth?: string
    whiteIcon?: boolean
    hasNoOffset?: boolean
    showCustomIcon?: boolean
    icon?: IconProp | null
    translationKey?: string
    buttonId?: string
    showIcon?: boolean
    whiteMenuBackground?: boolean
    strokeWidth?: string | number
    tooltipDirection?: 'top' | 'bottom' | 'left' | 'right'
    menuButtonText?: string
    showMenuButtonText?: boolean
    noTooltip?: boolean
    needsMaxHeight?: boolean
    textWithMarginLeft?: boolean
    classes?: string | string[]
    classesButton?: string | string[]
    /** Optional: explizite Platzierung nach Floating UI (z. B. "bottom-end") */
    placement?: string | null
    /** ARIA-Label, wenn kein sichtbarer Text vorhanden ist */
    menuButtonAria?: string
}>(), {
    dotsColor: 'text-artwork-navigation-text',
    dotsSize: 'size-5',
    noRelative: false,
    showSortIcon: false,
    menuWidth: '!w-fit max-w-xs',
    whiteIcon: false,
    hasNoOffset: false,
    showCustomIcon: false,
    icon: null,
    translationKey: 'Sorting',
    buttonId: 'menuButton',
    showIcon: true,
    whiteMenuBackground: false,
    strokeWidth: 1.5,
    tooltipDirection: 'bottom',
    menuButtonText: '',
    showMenuButtonText: false,
    noTooltip: false,
    needsMaxHeight: false,
    textWithMarginLeft: false,
    classes: '',
    classesButton: '',
    placement: null,
    menuButtonAria: 'Open menu',
})

/** Einheitlicher, angenehmer Abstand zwischen Trigger & Menü */
const computedOffset = computed(() => props.hasNoOffset ? 8 : 10)

/** ref momentan optional – gelassen, falls du in Zukunft messen willst */
const menuButtonRef = ref<HTMLDivElement | null>(null)

</script>
