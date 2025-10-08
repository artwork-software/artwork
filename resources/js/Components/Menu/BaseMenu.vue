<template>
    <Menu
        as="div"
        class="inline-block print:hidden float-left"
        :class="[!noRelative ? 'relative' : '', classes]"
    >
        <Float
            auto-placement
            portal
            :offset="{ mainAxis: hasNoOffset ? 5 : -10, crossAxis: hasNoOffset ? 25 : 75 }"
        >
            <div
                class="font-semibold flex items-center justify-center w-full"
                ref="menuButtonRef"
                :class="[whiteIcon ? 'text-white' : 'text-artwork-buttons-context', dotsColor]"
            >
                <MenuButton :id="buttonId" class="w-full cursor-pointer">
                    <div class="flex items-center gap-x-1 w-full">
                        <div v-if="showIcon">
                            <!-- Default Dots Icon -->
                            <IconDotsVertical
                                v-if="!showSortIcon && !showCustomIcon"
                                stroke-width="1.5"
                                class="flex-shrink-0"
                                aria-hidden="true"
                                :class="[dotsColor, dotsSize, whiteIcon ? 'text-white' : '', classesButton]"
                            />

                            <!-- Sort Icon mit Tooltip -->
                            <ToolTipComponent
                                v-else-if="!showCustomIcon"
                                direction="bottom"
                                :tooltip-text="$t('Sorting')"
                                :icon="IconSortDescending"
                                :icon-size="dotsSize"
                                :white-icon="whiteIcon"
                                :class="[dotsColor, dotsSize, whiteIcon ? 'text-white' : '']"
                                :no-tooltip="!noTooltip"
                                :classes-button="classesButton"
                            />

                            <!-- Benutzerdefiniertes Icon mit Tooltip -->
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
                                :classes-button="classesButton"
                            />
                        </div>

                        <div
                            v-if="menuButtonText && showMenuButtonText"
                            :class="[textWithMarginLeft ? 'ml-2' : '']"
                        >
                            {{ $t(menuButtonText) }}
                        </div>
                    </div>
                </MenuButton>
            </div>

            <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <MenuItems
                    class="z-50 focus:outline-none"
                    :class="[menuWidth, needsMaxHeight ? 'max-h-72 overflow-scroll xl:max-h-none' : '']"
                >
                    <div class="card white p-1.5 !rounded-xl">
                        <slot />
                    </div>
                </MenuItems>
            </transition>
        </Float>
    </Menu>
</template>

<script setup lang="ts">
import { ref, type Component } from 'vue'
import { Menu, MenuButton, MenuItems } from '@headlessui/vue'
import { Float } from '@headlessui-float/vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import { IconSortDescending, IconDotsVertical } from '@tabler/icons-vue'

// Icon-Prop: erlaubt String (dynamic component via <component :is="...">) oder echte Vue-Komponente
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
}>(), {
    dotsColor: 'text-artwork-navigation-text',
    dotsSize: 'h-6 w-6',
    noRelative: false,
    showSortIcon: false,
    menuWidth: 'w-56',
    whiteIcon: false,
    hasNoOffset: false,
    showCustomIcon: false,
    icon: null,
    translationKey: 'Sorting',
    buttonId: 'menuButton',
    showIcon: true,
    whiteMenuBackground: false,
    strokeWidth: 1.5,
    tooltipDirection: 'top',
    menuButtonText: '',
    showMenuButtonText: false,
    noTooltip: false,
    needsMaxHeight: false,
    textWithMarginLeft: false,
    classes: '',
    classesButton: '',
})

const menuButtonRef = ref<HTMLDivElement | null>(null)

// Optional: Re-Export für Template-Nutzung
// (damit <ToolTipComponent :icon="IconSortDescending" /> funktioniert)
defineExpose({
    IconSortDescending,
    IconDotsVertical,
})
</script>

<style scoped>
/* Keine Styles notwendig – Tailwind übernimmt alles */
</style>
