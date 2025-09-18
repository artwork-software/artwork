<template>
    <AppLayout :title="$t('Calendar')">
        <!-- Warnleiste -->
        <transition name="fade" appear>
            <div
                v-if="showCalendarWarning"
                class="pointer-events-none fixed inset-x-0 top-5 z-50 sm:flex sm:justify-center sm:px-6 sm:pb-5 lg:px-8"
            >
                <div
                    class="pointer-events-auto flex items-center justify-between gap-x-6 bg-gray-900 px-6 py-2.5 sm:rounded-xl sm:py-3 sm:pl-4 sm:pr-3.5"
                >
                    <component :is="IconAlertSquareRounded" class="size-5 text-yellow-400" aria-hidden="true" />
                    <p class="text-sm/6 text-white">
                        {{ showCalendarWarning }}
                    </p>
                    <button type="button" class="-m-1.5 flex-none p-1.5" @click="showCalendarWarning = ''">
                        <span class="sr-only">Dismiss</span>
                        <component :is="IconX" class="size-5 text-white" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </transition>

        <!-- Kalender -->
        <div class="w-full">
            <!-- synchron (calendar bereits vorhanden) -->
            <template v-if="!isCalendarLazy">
                <BaseCalendar
                    v-memo="[rooms, period, calendar]"
                    :rooms="rooms"
                    :days="period"
                    :calendar-data="calendar"
                    :eventsWithoutRoom="eventsWithoutRoom"
                    :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                    :first-project-shift-tab-id="first_project_shift_tab_id"
                    :event-statuses="eventStatuses"
                />
            </template>

            <!-- lazy (calendar ist Lazy-Prop von Inertia v2) -->
            <WhenVisible v-else data="calendar">
                <template #fallback>
                    <div class="mt-6 text-sm text-zinc-500">{{ $t('Loading calendar…') }}</div>
                </template>
                <Suspense>
                    <template #default>
                        <BaseCalendar
                            v-memo="[rooms, period, calendar]"
                            :rooms="rooms"
                            :days="period"
                            :calendar-data="calendar"
                            :eventsWithoutRoom="eventsWithoutRoom"
                            :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                            :first-project-shift-tab-id="first_project_shift_tab_id"
                            :event-statuses="eventStatuses"
                        />
                    </template>
                    <template #fallback>
                        <div class="mt-6 text-sm text-zinc-500">{{ $t('Loading calendar component…') }}</div>
                    </template>
                </Suspense>
            </WhenVisible>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { WhenVisible } from '@inertiajs/vue3'
import { defineAsyncComponent, onMounted, onUnmounted, provide, ref, computed, type PropType } from 'vue'
import { IconAlertSquareRounded, IconX } from '@tabler/icons-vue'

// Async Code-Splitting
const BaseCalendar = defineAsyncComponent(() => import('@/Components/Calendar/BaseCalendar.vue'))

type PeriodItem = {
    day: string; dayString: string; fullDay: string; withoutFormat: string; shortDay: string;
    weekNumber: number; isMonday: boolean; isWeekend: boolean; isFirstDayOfMonth: boolean;
    isSunday?: boolean; addWeekSeparator?: boolean; hoursOfDay?: string[]; isExtraRow?: boolean; holidays?: unknown[];
}
type Room = { id:number; name:string; has_events:boolean; admins:number[] }

const props = defineProps({
    period: { type: Array as PropType<PeriodItem[]>, required: true },
    rooms: { type: Array as PropType<Room[]>, required: true },
    months: { type: Object as PropType<Record<string,{ first_day_in_period:string; month:string; year:string }>>, required: true },
    calendar: { type: [Array, Object] as PropType<any>, required: false, default: undefined },
    eventStatuses: { type: [Array, Object] as PropType<any>, required: true },
    dateValue: { type: Array as PropType<[string,string]>, required: true },
    personalFilters: { type: [Array, Object] as PropType<any>, required: true },
    filterOptions: { type: [Array, Object] as PropType<any>, required: true },
    user_filters: { type: Object as PropType<any>, required: true },
    eventTypes: { type: [Array, Object] as PropType<any>, required: true },
    event_properties: { type: [Array, Object] as PropType<any>, required: true },
    first_project_tab_id: { type: Number, required: true },
    first_project_calendar_tab_id: { type: Number, required: true },
    first_project_shift_tab_id: { type: Number, required: true },
    eventsWithoutRoom: { type: [Array, Object] as PropType<any[]>, required: false, default: () => [] },
    projectNameUsedForProjectTimePeriod: { type: String as PropType<string|null>, required: false, default: null },
    areas: { type: [Array, Object] as PropType<any>, required: true },
    calendarWarningText: { type: String, required: false, default: '' },
})

// provide (bestehend lassen)
provide('eventTypes', props.eventTypes)
provide('dateValue', props.dateValue)
provide('first_project_tab_id', props.first_project_tab_id)
provide('first_project_calendar_tab_id', props.first_project_calendar_tab_id)
provide('user_filters', props.user_filters)
provide('personalFilters', props.personalFilters)
provide('filterOptions', props.filterOptions)
provide('rooms', props.rooms)
provide('eventStatuses', props.eventStatuses)
provide('months', props.months)
provide('event_properties', props.event_properties)
provide('areas', props.areas)

// Warnleiste
const showCalendarWarning = ref<string>(props.calendarWarningText)
let timer:number|undefined
onMounted(() => { if (showCalendarWarning.value) timer = window.setTimeout(() => (showCalendarWarning.value = ''), 5000) })
onUnmounted(() => { if (timer) clearTimeout(timer) })

// Lazy?
const isCalendarLazy = computed(() => typeof props.calendar === 'undefined')
const calendar = computed(() => props.calendar ?? [])
const rooms = computed(() => props.rooms)
const period = computed(() => props.period)
</script>

<style scoped>
/* keine Designänderungen */
</style>
