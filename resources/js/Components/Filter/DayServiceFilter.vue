<script>
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {IconChevronDown} from "@tabler/icons-vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "DayServiceFilter",
    emits: ['update:currentSelectedDayService'],
    mixins: [IconLib],
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

    components: {Menu, MenuItems, IconChevronDown, MenuButton, MenuItem}
}
</script>

<template>
<Menu as="div" class="relative inline-block text-left">
    <div>
        <MenuButton>
            <IconChevronDown class="-mr-1 h-5 w-5 text-gray-400" aria-hidden="true" />
        </MenuButton>
    </div>

    <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
        <MenuItems class="absolute left-0 z-10 mt-2 w-56 origin-top-left rounded-lg bg-artwork-navigation-background shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
            <div class="py-1">
                <MenuItem v-slot="{ active }" v-for="dayService in dayServices">
                    <div @click="updateDayService(dayService)" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']" class="cursor-pointer">
                        <div class="flex items-center">
                            <component :is="dayService.icon" class="h-5 w-5" :style="{color: dayService.hex_color}"/>
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
