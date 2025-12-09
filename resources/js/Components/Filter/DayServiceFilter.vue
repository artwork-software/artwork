<script>
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "DayServiceFilter",
    emits: ['update:currentSelectedDayService'],
    props: {
        dayServices: {
            type: Array,
            required: true
        },
        currentSelectedDayService: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            selectedDayService: this.currentSelectedDayService
        }
    },
    methods: {
        updateDayService(dayService) {
            this.selectedDayService = dayService;
            this.$emit('update:currentSelectedDayService', dayService);
        }
    },

    components: {PropertyIcon, Menu, MenuItems, MenuButton, MenuItem}
}
</script>

<template>
<Menu as="div" class="relative inline-block text-left">
    <div>
        <MenuButton>
            <PropertyIcon name="IconChevronDown" class="-mr-1 h-5 w-5 text-gray-400" aria-hidden="true" />
        </MenuButton>
    </div>
    <transition enter-active-class="transition-enter-active"
                enter-from-class="transition-enter-from"
                enter-to-class="transition-enter-to"
                leave-active-class="transition-leave-active"
                leave-from-class="transition-leave-from"
                leave-to-class="transition-leave-to">
        <MenuItems class="absolute left-0 z-10 mt-2 w-56 origin-top-left rounded-lg bg-artwork-navigation-background shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
            <div class="py-1">
                <MenuItem v-slot="{ active }" v-for="dayService in dayServices">
                    <div @click="updateDayService(dayService)" :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-white', 'block px-4 py-2 text-sm']" class="cursor-pointer">
                        <div class="flex items-center">
                            <PropertyIcon :name="dayService.icon" class="h-5 w-5" :style="{color: dayService.hex_color}"/>
                            <span class="ml-2">{{ dayService.name }}</span>
                        </div>
                    </div>
                </MenuItem>
            </div>
        </MenuItems>
    </transition>
</Menu>

</template>

<style scoped>

</style>
