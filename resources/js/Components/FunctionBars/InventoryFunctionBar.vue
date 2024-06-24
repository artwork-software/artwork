<template>
    <div class="flex items-center w-full">
        <date-picker-component v-if="dateValue" :dateValueArray="dateValue" :is_shift_plan="false"></date-picker-component>
        <div class="flex items-center mx-4 gap-x-1">
            <IconChevronLeft stroke-width="1.5" class="h-7 w-7 text-artwork-buttons-context cursor-pointer" @click="scrollToPreviousDay"/>
            <Menu as="div" class="relative inline-block text-left">
                <div class="flex items-center">
                    <MenuButton class="">
                        <IconCalendarMonth stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context" v-if="$page.props.user.goto_mode === 'month'"/>
                        <IconCalendarWeek stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context" v-if="$page.props.user.goto_mode === 'week'"/>
                        <IconCalendar stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context" v-if="$page.props.user.goto_mode === 'day'"/>
                    </MenuButton>
                </div>

                <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                    <MenuItems class="absolute right-0 z-10 mt-2 w-fit origin-top-right rounded-md bg-artwork-navigation-background shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                        <div class="py-1">
                            <MenuItem v-slot="{ active }">
                                <div @click="changeUserSelectedGoTo('day')" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                    <IconCalendar stroke-width="1.5" class="h-5 w-5 text-white"/>
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="changeUserSelectedGoTo('week')" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                    <IconCalendarWeek stroke-width="1.5" class="h-5 w-5 text-white"/>
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="changeUserSelectedGoTo('month')" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                    <IconCalendarMonth stroke-width="1.5" class="h-5 w-5 text-white"/>
                                </div>
                            </MenuItem>
                        </div>
                    </MenuItems>
                </transition>
            </Menu>
            <IconChevronRight stroke-width="1.5" class="h-7 w-7 text-artwork-buttons-context cursor-pointer" @click="scrollToNextDay"/>
        </div>


    </div>
</template>

<script setup>

import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import {IconCalendar, IconCalendarMonth, IconCalendarWeek, IconChevronLeft, IconChevronRight} from "@tabler/icons-vue";
import {MenuButton, Menu, MenuItems, MenuItem} from "@headlessui/vue";
import {router, usePage} from "@inertiajs/vue3";

const props = defineProps({
    dateValue: {
        type: Array,
        required: true
    }
})

const changeUserSelectedGoTo = (type) => {
    router.patch(route('user.calendar.go.to.stepper', {user: usePage().props.user.id}), {
        goto_mode: type,
    }, {
        preserveScroll: true,
    });
}

const scrollToPreviousDay = () => {
    console.log('scrollToPreviousDay');
}

const scrollToNextDay = () => {
    console.log('scrollToNextDay');
}

</script>

<style scoped>

</style>