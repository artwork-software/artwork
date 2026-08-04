<template>
    <div class="bg-white border-b border-border-subtle shadow-sm py-2 sticky top-0 z-50">
        <div class="flex justify-between items-center mt-2 mb-2 px-5">
            <div class="inline-flex items-center">
                <div class="flex">
                    <DateRangeControl
                        v-if="dateValue"
                        :date-value-array="dateValue"
                        mode="shift-list"
                    />
                    <div class="flex items-center mx-4 gap-x-1 select-none">
                        <ToolTipComponent
                            direction="bottom"
                            :tooltip-text="scrollBackTooltip"
                            icon="IconChevronLeft"
                            icon-size="h-7 w-7"
                            @click="scrollToPreviousDay"
                        />
                        <Menu as="div" class="relative inline-block text-left">
                            <div class="flex items-center">
                                <MenuButton>
                                    <ToolTipComponent
                                        direction="bottom"
                                        :tooltip-text="$t('Change scroll mode')"
                                        :icon="userGotoMode === 'month' ? 'IconCalendarMonth' : (userGotoMode === 'week' ? 'IconCalendarWeek' : 'IconCalendar')"
                                        icon-size="h-5 w-5"
                                    />
                                </MenuButton>
                            </div>
                            <transition enter-active-class="transition-enter-active"
                                        enter-from-class="transition-enter-from"
                                        enter-to-class="transition-enter-to"
                                        leave-active-class="transition-leave-active"
                                        leave-from-class="transition-leave-from"
                                        leave-to-class="transition-leave-to">
                                <MenuItems
                                    class="absolute right-0 z-50 mt-2 w-fit origin-top-right rounded-md bg-artwork-navigation-background shadow-lg ring-1 ring-black ring-opacity-5">
                                    <div class="py-1">
                                        <MenuItem v-slot="{ active }">
                                            <div @click="changeUserSelectedGoTo('day')"
                                                 :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-white', 'block px-4 py-2 text-sm']">
                                                <ToolTipComponent direction="right" :tooltip-text="$t('Jump around') + ' ' + $t('Day')" icon="IconCalendar" icon-size="h-5 w-5 text-white"/>
                                            </div>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <div @click="changeUserSelectedGoTo('week')"
                                                 :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-white', 'block px-4 py-2 text-sm']">
                                                <ToolTipComponent direction="right" :tooltip-text="$t('Jump around') + ' ' + $t('Calendar week')" icon="IconCalendarWeek" icon-size="h-5 w-5 text-white"/>
                                            </div>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <div @click="changeUserSelectedGoTo('month')"
                                                 :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-white', 'block px-4 py-2 text-sm']">
                                                <ToolTipComponent direction="right" :tooltip-text="$t('Jump around') + ' ' + $t('Month')" icon="IconCalendarMonth" icon-size="h-5 w-5 text-white"/>
                                            </div>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                        <ToolTipComponent
                            direction="bottom"
                            :tooltip-text="scrollForwardTooltip"
                            icon="IconChevronRight"
                            icon-size="h-7 w-7"
                            @click="scrollToNextDay"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center">
                <div class="flex items-center gap-x-3">
                    <slot name="moreButtons" />

                    <FunctionBarSetting :is-planning="false" :is-in-shift-plan="false" :is-list-view="true" />

                    <FunctionBarFilter
                        :user_filters="user_filters"
                        :personal-filters="personalFilters"
                        :filter-options="filterOptions"
                        :crafts="crafts"
                        :filter-type="filterType"
                    />

                    <ToolTipComponent direction="bottom" :tooltip-text="$t('History')" icon="IconHistory"
                                      icon-size="h-5 w-5" classes-button="ui-button" @click="$emit('openHistoryModal')"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
} from "@headlessui/vue";

import {router, usePage} from "@inertiajs/vue3";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {computed, defineAsyncComponent} from 'vue';
import {useI18n} from "vue-i18n";
const {t: $t} = useI18n();
import axios from 'axios';
import FunctionBarFilter from "@/Artwork/Filter/FunctionBarFilter.vue";
import FunctionBarSetting from "@/Artwork/Filter/FunctionBarSetting.vue";
import DateRangeControl from "@/Artwork/DateRange/DateRangeControl.vue";

const props = defineProps({
    dateValue: Array,
    filterOptions: Object,
    personalFilters: Array,
    user_filters: Object,
    crafts: Array,
    filterType: String,
});

defineEmits(['openHistoryModal']);

const userGotoMode = computed(() => {
    return usePage().props.auth.user.goto_mode;
});

const scrollBackTooltip = computed(() => {
    const mode = userGotoMode.value;
    if (mode === 'day') return $t('Scroll back by day');
    if (mode === 'week') return $t('Scroll back by week');
    if (mode === 'month') return $t('Scroll back by month');
    return $t('Scroll back by day');
});

const scrollForwardTooltip = computed(() => {
    const mode = userGotoMode.value;
    if (mode === 'day') return $t('Scroll forward by day');
    if (mode === 'week') return $t('Scroll forward by week');
    if (mode === 'month') return $t('Scroll forward by month');
    return $t('Scroll forward by day');
});

const changeUserSelectedGoTo = (type) => {
    axios.patch(route('user.calendar.go.to.stepper', {user: usePage().props.auth.user.id}), {
        goto_mode: type,
    }).then(() => {
        usePage().props.auth.user.goto_mode = type;
    });
};

const updateDates = (startDate, endDate) => {
    router.patch(route('update.user.shift-list-view.filter.dates', usePage().props.auth.user.id), {
        start_date: startDate,
        end_date: endDate,
    }, {
        preserveScroll: true,
        preserveState: false
    });
};

const getDateRange = () => {
    if (!props.dateValue || !props.dateValue[0] || !props.dateValue[1]) return null;
    return {
        start: new Date(props.dateValue[0]),
        end: new Date(props.dateValue[1]),
    };
};

const addDaysToRange = (days) => {
    const range = getDateRange();
    if (!range) return;
    range.start.setDate(range.start.getDate() + days);
    range.end.setDate(range.end.getDate() + days);
    updateDates(range.start.toISOString().slice(0, 10), range.end.toISOString().slice(0, 10));
};

const getStepSize = () => {
    const mode = userGotoMode.value;
    if (mode === 'month') return 30;
    if (mode === 'week') return 7;
    return 1;
};

const scrollToNextDay = () => addDaysToRange(getStepSize());
const scrollToPreviousDay = () => addDaysToRange(-getStepSize());
</script>
