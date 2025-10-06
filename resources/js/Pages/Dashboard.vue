<template>
    <AppLayout title="Dashboard">
        <div class="artwork-container">
            <!-- Welcome Bar mit Profil + Quicklinks -->
            <header class="rounded-2xl border border-gray-100 bg-white shadow-sm px-5 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <!-- Greeting -->
                    <div class="flex items-center gap-3 min-w-0">
                        <img
                            :src="user.profile_photo_url"
                            alt="Avatar"
                            class="size-12 rounded-full object-cover bg-gray-50 ring-4 ring-indigo-50"
                        />

                        <div class="min-w-0">
                            <h1 class="text-xl lg:text-2xl font-semibold tracking-tight">
                                {{ $t('Guten Tag') }}, {{ user.full_name }}
                            </h1>
                            <p class="text-xs lg:text-sm text-gray-500 mt-0.5">
                                {{ formattedToday }} · {{ $t('Alles Wichtige auf einen Blick') }}
                            </p>
                        </div>

                    </div>
                    <!-- Profil + Quicklinks -->
                    <div class="flex items-center gap-4 w-full lg:w-auto">


                        <!-- Trenner -->
                        <div class="hidden lg:block h-10 w-px bg-gray-200"></div>

                        <!-- Quicklinks -->
                        <nav class="grid grid-cols-2 sm:flex gap-2">
                            <a :href="route('events')" class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/70 px-3 py-2 text-xs text-indigo-700 hover:bg-indigo-50 hover:border-indigo-300 transition">
                                <component :is="IconCalendarMonth" class="size-4" />
                                {{ $t('Kalender') }}
                            </a>
                            <a v-if="canViewShifts" :href="route('shifts.plan')" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50/70 px-3 py-2 text-xs text-emerald-700 hover:bg-emerald-50 hover:border-emerald-300 transition">
                                <component :is="IconCalendarUser" class="size-4" />
                                {{ $t('Schichtplan') }}
                            </a>
                            <a :href="route('tasks.own')" class="inline-flex items-center gap-2 rounded-xl border border-fuchsia-200 bg-fuchsia-50/70 px-3 py-2 text-xs text-fuchsia-700 hover:bg-fuchsia-50 hover:border-fuchsia-300 transition">
                                <component :is="IconListCheck" class="size-4" />
                                {{ $t('Aufgaben') }}
                            </a>
                            <a :href="route('notifications.index')" class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2 text-xs text-amber-700 hover:bg-amber-50 hover:border-amber-300 transition">
                                <component :is="IconBell" class="size-4" />
                                {{ $t('Meldungen') }}
                            </a>
                        </nav>
                    </div>
                </div>
            </header>

            <!-- KPI Row -->
            <section class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ $t('Heutige Termine') }}</p>
                            <p class="mt-1 text-3xl font-semibold">{{ eventsCountToday }}</p>
                        </div>
                        <div class="size-10 rounded-xl bg-gradient-to-br from-indigo-500/15 to-sky-300/15 flex items-center justify-center">
                            <component :is="IconCalendarMonth" class="size-5 text-indigo-600" />
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">{{ $t('Alle geplanten Termine heute.') }}</p>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ $t('Heutige Schichten') }}</p>
                            <p class="mt-1 text-3xl font-semibold">{{ shiftsCountToday }}</p>
                        </div>
                        <div class="size-10 rounded-xl bg-gradient-to-br from-emerald-500/15 to-teal-300/15 flex items-center justify-center">
                            <component :is="IconCalendarUser" class="size-5 text-emerald-600" />
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">{{ $t('Deine Einsätze auf einen Blick.') }}</p>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ $t('Meldungen heute') }}</p>
                            <p class="mt-1 text-3xl font-semibold">{{ notificationsCountToday }}</p>
                        </div>
                        <div class="size-10 rounded-xl bg-gradient-to-br from-amber-500/15 to-orange-300/15 flex items-center justify-center">
                            <component :is="IconBell" class="size-5 text-amber-600" />
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">{{ $t('Aktuelle Hinweise & News.') }}</p>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ $t('Offene Aufgaben') }}</p>
                            <p class="mt-1 text-3xl font-semibold">{{ openTasksCount }}</p>
                        </div>
                        <div class="size-10 rounded-xl bg-gradient-to-br from-fuchsia-500/15 to-pink-300/15 flex items-center justify-center">
                            <component :is="IconListCheck" class="size-5 text-fuchsia-600" />
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">{{ $t('Die nächsten fälligen To-Dos.') }}</p>
                </div>
            </section>

            <!-- Content Grid -->
            <main class="mt-8 grid grid-cols-6 gap-6">
                <!-- Left: Events (neu) + Shifts -->
                <section class="col-span-6 xl:col-span-3 space-y-6">
                    <!-- Events neu: Kacheln -->
                    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <div class="flex items-center justify-between px-5 py-4">
                            <div>
                                <h2 class="text-base font-semibold">{{ $t("Today's appointments") }}</h2>
                                <p class="text-xs text-gray-500">{{ $t('Schneller Überblick über alle Termine des Tages') }}</p>
                            </div>
                            <a :href="route('events')" class="text-xs text-artwork-buttons-create inline-flex items-center gap-1">
                                <component :is="IconCalendarMonth" class="size-4" /> {{ $t('to calendar') }}
                            </a>
                        </div>

                        <div v-if="eventsSorted.length" class="px-5 pb-5 gap-4 space-y-3">
                            <div v-for="event in eventsSorted" :key="event.id" class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                                <div class="flex items-stretch gap-x-3 min-w-full w-full h-full p-4">
                                    <div class="p-1 rounded-lg w-1" :style="{backgroundColor: event?.event_type.hex_code}"></div>
                                    <div class="w-full">
                                        <p class="text-sm font-semibold text-gray-900" :style="{color: event?.event_type.hex_code}">
                                            {{ event?.event_type.abbreviation }}: {{ event?.eventName ?? event?.project?.name }}
                                        </p>
                                        <div class="mt-1 flex items-center gap-x-1 text-xs text-gray-500">
                                            <p v-if="!event.allDay" class="flex items-center gap-x-1">
                                                <span class="font-bold">{{ $t('Start') }}:</span>
                                                <span class="">{{ event?.start_time }}</span>
                                                <span class="font-bold">{{ $t('End') }}:</span>
                                                <span class="">{{ event?.end_time }}</span>
                                            </p>
                                            <p v-else>
                                                <span class="font-bold">{{ $t('All day') }}</span>
                                            </p>
                                        </div>
                                        <p class="mt-1 flex items-center gap-x-1 text-xs text-gray-500">
                                            <span class="font-bold">{{ $t('Room') }}:</span>
                                            <span class="">{{ event?.room?.name }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="px-5 pb-5">
                            <BaseAlertComponent message="No events found" type="info" use-translation />
                        </div>
                    </div>

                    <!-- Schichten -->
                    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <div class="flex items-center justify-between px-5 py-4">
                            <div>
                                <h2 class="text-base font-semibold">{{ $t('Shifts today"') }}</h2>
                                <p class="text-xs text-gray-500">{{ $t('Deine heutigen Einsätze') }}</p>
                            </div>
                            <a v-if="canViewShifts" :href="route('shifts.plan')" class="text-xs text-artwork-buttons-create inline-flex items-center gap-1">
                                <component :is="IconCalendarUser" class="size-4" /> {{ $t('to the shift plan') }}
                            </a>
                        </div>

                        <div v-if="shiftsOfDay?.length" class="px-5 pb-5">
                            <div v-for="shift in props.shiftsOfDay" :key="shift.id" class="mb-3 last:mb-0">
                                <SingleUserEventShift
                                    type="user"
                                    :event="shift.event"
                                    :shift="shift"
                                    :project="findProjectById(shift.event?.project_id)"
                                    :event-type="shift.event ? findEventTypeById(shift.event?.event_type_id) : null"
                                    :user-to-edit-id="user.id"
                                    :first-project-shift-tab-id="first_project_shift_tab_id"
                                />
                            </div>
                        </div>

                        <div v-else class="px-5 pb-5">
                            <BaseAlertComponent message="You don't have any shifts today." type="info" use-translation />
                        </div>
                    </div>
                </section>

                <!-- Right: Announcements groß + Tasks -->
                <section class="col-span-6 xl:col-span-3 space-y-6">
                    <!-- Ankündigungen – groß -->
                    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <div class="px-5 pt-5">
                            <h2 class="text-base font-semibold">{{ $t('Notifications today') }}</h2>
                            <p class="text-xs text-gray-500">{{ $t('Wichtige Meldungen & Änderungen') }}</p>
                        </div>

                        <div v-if="globalNotification?.image_url || globalNotification?.title" class="mt-4">
                            <!-- Hero Card -->
                            <div class="mx-5 mb-5 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                                <div class="relative">
                                    <img
                                        v-if="globalNotification?.image_url"
                                        :src="globalNotification.image_url"
                                        class="h-44 w-full object-cover"
                                        alt="Notification hero"
                                    />
                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/50 to-transparent p-4">
                                        <p class="text-white text-lg font-semibold leading-tight">
                                            {{ globalNotification.title }}
                                        </p>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <p class="text-sm text-gray-700">
                                        {{ globalNotification.description }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="notificationOfToday?.length" class="px-5 pb-5 grid grid-cols-1 gap-3">
                            <div v-for="n in notificationOfToday" :key="n.id" class="rounded-xl border border-gray-100 bg-white shadow-sm p-4">
                                <NotificationBlock
                                    :history-objects="historyObjects"
                                    :notification="n"
                                    :event="event"
                                    :event-types="eventTypes"
                                    :rooms="rooms"
                                    :event-statuses="eventStatuses"
                                    :first_project_shift_tab_id="first_project_shift_tab_id"
                                    :first_project_budget_tab_id="first_project_budget_tab_id"
                                    :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                    :is-dashboard="true"
                                />
                            </div>
                        </div>

                        <div v-else class="px-5 pb-5">
                            <BaseAlertComponent message="There are no new announcements for today." type="info" use-translation />
                        </div>

                        <div class="px-5 pb-5">
                            <a :href="route('notifications.index')" class="text-xs text-artwork-buttons-create inline-flex items-center gap-1">
                                <component :is="IconBell" class="size-4" /> {{ $t('Go to the notifications') }}
                            </a>
                        </div>
                    </div>

                    <!-- Aufgaben -->
                    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <div class="flex items-center justify-between px-5 py-4">
                            <div>
                                <h2 class="text-base font-semibold">{{ $t('Next tasks') }}</h2>
                                <p class="text-xs text-gray-500">{{ $t('Deine offenen To-Dos') }}</p>
                            </div>
                            <a :href="route('tasks.own')" class="text-xs text-artwork-buttons-create inline-flex items-center gap-1">
                                <component :is="IconListCheck" class="size-4" /> {{ $t('To the task overview') }}
                            </a>
                        </div>

                        <div v-if="tasks?.length" class="px-5 pb-5 space-y-3">
                            <div v-for="task in tasks" :key="task.id" class="rounded-xl border border-gray-100 bg-white shadow-sm p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="task.done"
                                            @change="updateTaskStatus(task)"
                                            class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium leading-tight truncate" :class="task.done ? 'line-through text-gray-400' : ''">
                                                {{ task.name }}
                                            </p>
                                            <p v-if="task.description" class="text-xs text-gray-500 line-clamp-2 mt-0.5">
                                                {{ task.description }}
                                            </p>
                                        </div>
                                    </label>
                                    <div
                                        v-if="!task.done && task.deadline"
                                        class="text-[11px] text-right"
                                        :class="task.isDeadlineInFuture ? 'text-gray-500' : 'text-red-600'"
                                    >
                                        {{ $t('bis') }} {{ task.deadline }}
                                    </div>
                                </div>

                                <Link
                                    v-if="task.projectId"
                                    :href="route('projects.tab', { project: task.projectId, projectTab: first_project_tasks_tab_id })"
                                    class="mt-2 inline-flex items-center gap-1 text-[11px] text-indigo-700 hover:text-indigo-800"
                                >
                                    {{ task.projectName }}
                                    <ChevronRightIcon class="h-3 w-3" />
                                    {{ task.checklistName }}
                                </Link>
                            </div>
                        </div>

                        <div v-else class="px-5 pb-5">
                            <BaseAlertComponent message="You have no open tasks." type="info" use-translation />
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, defineOptions } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import NotificationBlock from '@/Layouts/Components/NotificationComponents/NotificationBlock.vue'
import SingleUserEventShift from '@/Layouts/Components/ShiftPlanComponents/SingleUserEventShift.vue'
import Permissions from '@/Mixins/Permissions.vue'
import { is, can } from 'laravel-permission-to-vuejs'

// Icons
import { ChevronRightIcon } from '@heroicons/vue/solid'
import { IconBell, IconCalendarMonth, IconCalendarUser, IconListCheck } from '@tabler/icons-vue'

defineOptions({ mixins: [Permissions] })

const props = defineProps<{
    tasks: any[],
    shiftsOfDay: any[],
    todayDate: string,
    eventsOfDay: any[],
    globalNotification: any,
    notificationOfToday: any[],
    notificationCount: number,
    event: any,
    eventTypes: any[],
    projects: any[],
    rooms: any[],
    historyObjects: any[],
    eventStatuses: any[],
    first_project_tab_id: number | string,
    first_project_shift_tab_id: number | string,
    first_project_tasks_tab_id: number | string,
    first_project_budget_tab_id: number | string,
    first_project_calendar_tab_id: number | string
}>()

const page = usePage()
const user = computed(() => page.props.auth.user)

const doneTaskForm = useForm({ done: false })
const canViewShifts = computed(() => can('can view shift plan') || is('artwork admin'))

const eventsCountToday = computed(() => props.eventsOfDay?.length ?? 0)
const shiftsCountToday = computed(() => props.shiftsOfDay?.length ?? 0)
const notificationsCountToday = computed(() => props.notificationOfToday?.length ?? 0)
const openTasksCount = computed(() => (props.tasks?.filter(t => !t.done).length) ?? 0)

const formattedToday = computed(() => {
    try {
        const dt = new Date()
        return dt.toLocaleDateString('de-DE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
    } catch {
        return props.todayDate || ''
    }
})

// Events sortiert (Startzeit)
const eventsSorted = computed(() => {
    const arr = (props.eventsOfDay ?? []).slice()
    return arr.sort((a: any, b: any) => {
        const as = a.earliest_start_datetime ?? a?.formatted_dates?.start_with_time ?? ''
        const bs = b.earliest_start_datetime ?? b?.formatted_dates?.start_with_time ?? ''
        return as.localeCompare(bs)
    })
})

function updateTaskStatus(task: any) {
    doneTaskForm.done = task.done
    doneTaskForm.patch(route('tasks.done', { task: task.id }))
}

function findProjectById(projectId?: number) {
    return props.projects.find(p => p.id === projectId)
}

function findEventTypeById(eventTypeId?: number) {
    return props.eventTypes.find(e => e.id === eventTypeId)
}

// Echo live updates
let echoChannel: any = null
onMounted(() => {
    // @ts-ignore
    echoChannel = Echo?.private?.('events')
    echoChannel?.listen?.('OccupancyUpdated', () => {
        router.reload({ only: ['rooms', 'calendar', 'days'] })
    })
})
onBeforeUnmount(() => {
    try {
        echoChannel?.stopListening?.('OccupancyUpdated')
        // @ts-ignore
        Echo?.leave?.('events')
    } catch {}
})
</script>

<style scoped>
/* nur minimale Scopes; keine @apply */
</style>
