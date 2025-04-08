<template>
    <Popover v-if="component?.note" v-slot="{ open }" class="relative print:hidden">
        <PopoverButton class="group inline-flex items-center ring-0 focus:outline-none focus-within:outline-none">
            <component is="IconInfoCircle" class="size-4" />
        </PopoverButton>

        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-1 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-1 opacity-0">
            <PopoverPanel class="absolute left-1/2 z-10 mt-1 w-fit  -translate-x-1/2 transform px-4 sm:px-0">
                <div class="rounded-lg shadow-lg ring-1 ring-black/5 bg-white py-3 px-5 xxsDark">
                    {{ component?.note }}
                </div>
            </PopoverPanel>
        </transition>
    </Popover>
</template>

<script setup>

import {Popover, PopoverButton, PopoverPanel} from "@headlessui/vue";

const props = defineProps({
    component: {
        type: Object,
        required: true,
        default: {},
    },
})

</script>

<style scoped>

</style>