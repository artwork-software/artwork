<template>
    <Menu as="div" :class="classes">
        <Float
            auto-placement
            portal
            :offset="{ mainAxis: -5, crossAxis: -10 }"
        >
        <div class="flex items-center">
            <!-- Button: Text + Chevron -->
            <MenuButton v-if="!onlyIcon" class="w-52 border-white">
                <span class="float-left text-sm font-medium text-zinc-800 dark:text-zinc-100">Filter</span>
                <IconChevronDown
                    stroke-width="1.5"
                    class="ml-2 -mr-1 h-5 w-5 text-zinc-500 dark:text-zinc-400 float-right"
                    aria-hidden="true"
                />
            </MenuButton>

            <!-- Button: Nur Icon mit Tooltip -->
            <MenuButton v-else>
                <ToolTipComponent
                    direction="bottom"
                    :tooltip-text="$t('Filter')"
                    :icon="IconFilter"
                    :whiteIcon="whiteIcon"
                    :grayIcon="grayIcon"
                    icon-size="size-6"
                    classes-button="ui-button hover:!bg-artwork-navigation-color/10 text-artwork-buttons-context"
                />
            </MenuButton>
        </div>

        <transition
            enter-active-class="transition duration-50 ease-out"
            enter-from-class="transform scale-100 opacity-100"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <!-- Dropdown -->
            <MenuItems
                v-if="left"
                :class="whiteBackground ? 'bg-white ring-1 ring-zinc-200' : 'bg-zinc-900/95 ring-1 ring-black/80'"
                class="w-96 absolute left-0 top-12 z-50 origin-top-left rounded-lg shadow-lg  p-2 text-white max-h-[calc(100vh-10rem)] overflow-auto"
            >
                <slot />
            </MenuItems>

            <MenuItems
                v-else
                :class="whiteBackground ? 'bg-white ring-1 ring-zinc-200' : 'bg-zinc-900/95 ring-1 ring-black/80'"
                class="w-96  absolute right-0 top-12 z-50 origin-top-right rounded-lg shadow-lg  p-2 text-white max-h-[calc(100vh-10rem)] overflow-auto"
            >
                <slot />
            </MenuItems>
        </transition>
        </Float>
    </Menu>
</template>

<script setup>
import { defineProps } from 'vue'
import { Menu, MenuButton, MenuItems } from '@headlessui/vue'
import { IconChevronDown, IconFilter } from '@tabler/icons-vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import {Float} from "@headlessui-float/vue";

const props = defineProps({
    onlyIcon: { type: Boolean, default: false },
    left: { type: Boolean, default: false },
    whiteIcon: { type: Boolean, default: false },
    grayIcon: { type: Boolean, default: false },
    classes: { type: [String, Array, Object], default: 'relative flex items-center text-left' },
    whiteBackground: { type: Boolean, default: false },
})
</script>
