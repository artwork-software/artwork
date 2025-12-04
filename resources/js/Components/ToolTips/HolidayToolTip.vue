<template>
    <Menu as="div" class="">
        <Float auto-placement portal :offset="{ mainAxis: 5, crossAxis: 35}">
            <div class="font-semibold text-artwork-buttons-context flex items-center justify-end" ref="menuButtonRef">
                <MenuButton>
                    <PropertyIcon name="IconBeach" class="flex-shrink-0 h-4 w-4 text-white" aria-hidden="true" />
                </MenuButton>
            </div>

            <transition enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95">
                <MenuItems class="z-50 rounded-lg bg-white shadow-xl ring-1 ring-zinc-300 ring-opacity-5 focus:outline-none min-w-44" >
                    <div class="text-white px-3 py-2 text-xs ">
                        <slot />
                    </div>
                </MenuItems>
            </transition>
        </Float>
    </Menu>
</template>

<script setup>

import {Float} from "@headlessui-float/vue";
import {MenuItems, MenuButton, Menu} from "@headlessui/vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const props = defineProps({
    placement: {
        type: String,
        default: 'top',
        validator: (value) => ['top', 'left', 'right', 'bottom'].includes(value)
    }
})

</script>

<style scoped>

</style>
