<template>
    <div class="flex items-center w-full">
        <date-picker-component v-if="dateValue" :dateValueArray="dateValue" :is_shift_plan="false"></date-picker-component>

        <div class="flex items-center mx-4 gap-x-1 select-none">
            <BaseUIButton variant="ghost" size="sm" :icon="IconChevronLeftPipe" :aria-label="$t('Previous')" @click="previousTimeRange"/>
            <BaseUIButton variant="ghost" size="sm" :icon="IconChevronLeft" :aria-label="$t('Previous')" @click="scrollToPreviousDay"/>
            <Menu as="div" class="relative inline-block text-left">
                <div class="flex items-center">
                    <MenuButton class="">
                        <IconCalendarMonth stroke-width="1.5" class="h-5 w-5 text-text-muted" v-if="$page.props.auth.user.goto_mode === 'month'"/>
                        <IconCalendarWeek stroke-width="1.5" class="h-5 w-5 text-text-muted" v-if="$page.props.auth.user.goto_mode === 'week'"/>
                        <IconCalendar stroke-width="1.5" class="h-5 w-5 text-text-muted" v-if="$page.props.auth.user.goto_mode === 'day'"/>
                    </MenuButton>
                </div>

                <transition enter-active-class="transition-enter-active"
                            enter-from-class="transition-enter-from"
                            enter-to-class="transition-enter-to"
                            leave-active-class="transition-leave-active"
                            leave-from-class="transition-leave-from"
                            leave-to-class="transition-leave-to">
                    <MenuItems class="absolute right-0 z-50 mt-2 w-fit origin-top-right rounded-lg bg-surface-inverse shadow-overlay">
                        <div class="py-1">
                            <MenuItem v-slot="{ active }">
                                <div @click="changeUserSelectedGoTo('day')" :class="[active ? 'bg-white/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                    <ToolTipComponent
                                        direction="right"
                                        :tooltip-text="$t('Jump around') + ' ' + $t('Day')"
                                        :icon="IconCalendar"
                                        icon-size="h-5 w-5 text-white" />
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="changeUserSelectedGoTo('week')" :class="[active ? 'bg-white/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                    <ToolTipComponent
                                        direction="right"
                                        :tooltip-text="$t('Jump around') + ' ' + $t('Calendar week')"
                                        :icon="IconCalendarWeek"
                                        icon-size="h-5 w-5 text-white" />
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="changeUserSelectedGoTo('month')" :class="[active ? 'bg-white/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                    <ToolTipComponent
                                        direction="right"
                                        :tooltip-text="$t('Jump around') + ' ' + $t('Month')"
                                        :icon="IconCalendarMonth"
                                        icon-size="h-5 w-5 text-white" />
                                </div>
                            </MenuItem>
                        </div>
                    </MenuItems>
                </transition>
            </Menu>
            <BaseUIButton variant="ghost" size="sm" :icon="IconChevronRight" :aria-label="$t('Next')" @click="scrollToNextDay"/>

            <BaseUIButton variant="ghost" size="sm" :icon="IconChevronRightPipe" :aria-label="$t('Next')" @click="nextTimeRange"/>
        </div>

        <div class="flex items-center mx-4 gap-x-1 select-none invisible">
            <BaseUIButton variant="ghost" size="sm" :icon="IconChevronLeft" :aria-label="$t('Previous')" @click="scrollToPreviousDay"/>
            <Menu as="div" class="relative inline-block text-left">
                <div class="flex items-center">
                    <MenuButton class="has">
                        <IconCalendarMonth stroke-width="1.5" class="h-5 w-5 text-text-muted" v-if="$page.props.auth.user.goto_mode === 'month'"/>
                        <IconCalendarWeek stroke-width="1.5" class="h-5 w-5 text-text-muted" v-if="$page.props.auth.user.goto_mode === 'week'"/>
                        <IconCalendar stroke-width="1.5" class="h-5 w-5 text-text-muted" v-if="$page.props.auth.user.goto_mode === 'day'"/>
                    </MenuButton>
                </div>
                <transition enter-active-class="transition-enter-active"
                            enter-from-class="transition-enter-from"
                            enter-to-class="transition-enter-to"
                            leave-active-class="transition-leave-active"
                            leave-from-class="transition-leave-from"
                            leave-to-class="transition-leave-to">
                    <MenuItems class="absolute right-0 z-50 mt-2 w-fit origin-top-right rounded-lg bg-surface-inverse shadow-overlay">
                        <div class="py-1">
                            <MenuItem v-slot="{ active }">
                                <div @click="changeUserSelectedGoTo('day')" :class="[active ? 'bg-white/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']" class="has-tooltip">
                                    <IconCalendar stroke-width="1.5" class="h-5 w-5 text-white"/>
                                    <span class="tooltip rounded shadow-overlay p-1 text-xs text-text-inverse bg-surface-inverse">Tag</span>
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="changeUserSelectedGoTo('week')" :class="[active ? 'bg-white/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']" class="has-tooltip">
                                    <IconCalendarWeek stroke-width="1.5" class="h-5 w-5 text-white"/>
                                    <span class="tooltip rounded shadow-overlay p-1 text-xs text-text-inverse bg-surface-inverse">KW</span>
                                </div>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <div @click="changeUserSelectedGoTo('month')" :class="[active ? 'bg-white/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']" class="has-tooltip">
                                    <IconCalendarMonth stroke-width="1.5" class="h-5 w-5 text-white"/>
                                    <span class="tooltip rounded shadow-overlay p-1 text-xs text-text-inverse bg-surface-inverse">Monat</span>
                                </div>
                            </MenuItem>
                        </div>
                    </MenuItems>
                </transition>
            </Menu>
            <BaseUIButton variant="ghost" size="sm" :icon="IconChevronRight" :aria-label="$t('Next')" @click="scrollToNextDay"/>

        </div>


    </div>
</template>

<script setup>

import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import {
    IconCalendar,
    IconCalendarMonth,
    IconCalendarWeek,
    IconChevronLeft,
    IconChevronLeftPipe,
    IconChevronRight, IconChevronRightPipe
} from "@tabler/icons-vue";
import {MenuButton, Menu, MenuItems, MenuItem} from "@headlessui/vue";
import {router, usePage} from "@inertiajs/vue3";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    dateValue: {
        type: Array,
        required: true
    }
})

const emits = defineEmits([
    'scrollToPrevious',
    'scrollToNext',
    'nextTimeRange',
    'previousTimeRange'
])

const changeUserSelectedGoTo = (type) => {
    router.patch(route('user.calendar.go.to.stepper', {user: usePage().props.auth.user.id}), {
        goto_mode: type,
    }, {
        preserveScroll: true,
    });
}

const scrollToPreviousDay = (event) => {
    event.preventDefault();
    emits('scrollToPrevious');
}

const scrollToNextDay = (event) => {
    event.preventDefault();
    emits('scrollToNext');
}

const previousTimeRange = (event) => {
    event.preventDefault();
    emits('previousTimeRange');
}

const nextTimeRange = (event) => {
    event.preventDefault();
    emits('nextTimeRange');
}

</script>

<style scoped>

</style>
