<template>
    <AppLayout title="Dashboard">
        <div class="artwork-container ">
            <!-- Welcome Bar mit Profil + Quicklinks -->
            <BaseCard elevation="flat" padding="16">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <!-- Greeting -->
                    <div class="flex items-center gap-3 min-w-0">
                        <img
                            :src="user.profile_photo_url"
                            alt="Avatar"
                            class="size-12 rounded-full object-cover bg-surface-sunken ring-4 ring-accent-50"
                        />

                        <div class="min-w-0">
                            <h1 class="font-lexend text-xl lg:text-2xl font-semibold tracking-tight text-text">
                                {{ $t('Good day') }}, {{ user.full_name }}
                            </h1>
                            <p class="text-xs text-text-muted mt-0.5">
                                {{ formattedToday }} · {{ $t('Everything important at a glance') }}
                            </p>
                        </div>

                    </div>
                    <!-- Profil + Quicklinks -->
                    <div class="flex items-center gap-4 w-full lg:w-auto">


                        <!-- Trenner -->
                        <div class="hidden lg:block h-10 w-px bg-border"></div>

                        <!-- Quicklinks -->
                        <nav class="grid grid-cols-2 sm:flex gap-2">
                            <BaseUIButton
                                variant="secondary"
                                size="sm"
                                icon="IconCalendarMonth"
                                label="Calendar"
                                @click="router.visit(route('events'))"
                            />
                            <BaseUIButton
                                v-if="canViewShifts"
                                variant="secondary"
                                size="sm"
                                icon="IconCalendarUser"
                                label="Shift plan"
                                @click="router.visit(route('shifts.plan'))"
                            />
                            <BaseUIButton
                                variant="secondary"
                                size="sm"
                                icon="IconListCheck"
                                label="Tasks"
                                @click="router.visit(route('tasks.own'))"
                            />
                            <BaseUIButton
                                variant="secondary"
                                size="sm"
                                icon="IconBell"
                                label="Notifications"
                                @click="router.visit(route('notifications.index'))"
                            />
                        </nav>
                    </div>
                </div>
            </BaseCard>

            <!-- KPI Row -->
            <section class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <KpiTile
                    :label="$t('Today\'s appointments')"
                    :value="eventsCountToday"
                    icon="IconCalendarMonth"
                    :trend="$t('All scheduled appointments today.')"
                    inverse
                />
                <KpiTile
                    :label="$t('Today\'s shifts')"
                    :value="shiftsCountToday"
                    icon="IconCalendarUser"
                    :trend="$t('Your shifts at a glance.')"
                />
                <KpiTile
                    :label="$t('Notifications today')"
                    :value="notificationsCountToday"
                    icon="IconBell"
                    :trend="$t('Current notices & news.')"
                />
                <KpiTile
                    :label="$t('Open tasks')"
                    :value="openTasksCount"
                    icon="IconListCheck"
                    :trend="$t('The next due to-dos.')"
                />
            </section>

            <!-- Content Grid -->
            <main class="mt-8 grid grid-cols-6 gap-6">
                <!-- Left: Events (neu) + Shifts -->
                <section class="col-span-6 xl:col-span-3 space-y-6">
                    <!-- Events neu: Kacheln -->
                    <BaseCard elevation="flat">
                        <div class="flex items-center justify-between border-b border-border-subtle pr-4">
                            <CardHeadline
                                class="grow border-none"
                                title="Today's appointments"
                                description="Quick overview of all appointments of the day"
                            />
                            <a :href="route('events')" class="text-xs text-accent-600 hover:text-accent-700 inline-flex items-center gap-1 whitespace-nowrap">
                                <PropertyIcon name="IconCalendarMonth" class="size-4" /> {{ $t('to calendar') }}
                            </a>
                        </div>

                        <div v-if="eventsSorted.length" class="p-4 space-y-3">
                            <div v-for="event in eventsSorted" :key="event.id" class="overflow-hidden rounded-md border border-border-subtle bg-surface">
                                <div class="flex items-stretch gap-x-3 min-w-full w-full h-full p-4">
                                    <div class="p-1 rounded-lg w-1" :style="{backgroundColor: event?.event_type.hex_code}"></div>
                                    <div class="flex items-start justify-between w-full">
                                        <div class="">
                                            <p class="text-sm font-semibold text-text" :style="{color: event?.event_type.hex_code}">
                                                {{ event?.event_type.abbreviation }}:
                                                <template v-if="event?.eventName">{{ event.eventName }}</template>
                                                <Link
                                                    v-else-if="event?.project"
                                                    :href="route('projects.tab', { project: event.project.id, projectTab: first_project_tab_id })"
                                                    class="hover:underline"
                                                >
                                                    {{ event.project.name }}
                                                </Link>
                                            </p>
                                            <p v-if="event?.eventName && event?.project?.name" class="mt-1 flex items-center gap-x-1 text-xs text-text-muted">
                                                <span class="font-bold">{{ $t('Project') }}:</span>
                                                <Link
                                                    :href="route('projects.tab', { project: event.project.id, projectTab: first_project_tab_id })"
                                                    class="hover:underline"
                                                >
                                                    {{ event.project.name }}
                                                </Link>
                                            </p>
                                            <div class="mt-1 flex items-center gap-x-1 text-xs text-text-muted">
                                                <p v-if="!event.allDay" class="flex items-center gap-x-1">
                                                    <span class="font-bold">{{ $t('Start') }}:</span>
                                                    <span class="">{{ event?.start_time_without_day ?? event?.start_time }}</span>
                                                    <span class="font-bold">{{ $t('End') }}:</span>
                                                    <span class="">{{ event?.end_time_without_day ?? event?.end_time }}</span>
                                                </p>
                                                <p v-else>
                                                    <span class="font-bold">{{ $t('All day') }}</span>
                                                </p>
                                            </div>
                                            <p class="mt-1 flex items-center gap-x-1 text-xs text-text-muted">
                                                <span class="font-bold">{{ $t('Room') }}:</span>
                                                <span class="">{{ event?.room?.name }}</span>
                                            </p>
                                            <p v-if="event?.description" class="mt-1 text-xs text-text-muted whitespace-pre-line" :title="event.description">
                                                <span class="font-bold">{{ $t('Description') }}:</span>
                                                {{ event.description }}
                                            </p>

                                        </div>
                                        <div class="break-keep">
                                            <button @click="openCalendarWithEventId(event)" class="inline-flex items-center gap-1 text-xs text-accent-600 hover:text-accent-700 break-keep">
                                                {{ $t('Open in calendar') }}
                                                <PropertyIcon name="IconChevronRight" class="h-3 w-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-4">
                            <EmptyState :title="$t('No events found')" icon="IconCalendarMonth" />
                        </div>
                    </BaseCard>

                    <!-- Schichten -->
                    <BaseCard elevation="flat">
                        <div class="flex items-center justify-between border-b border-border-subtle pr-4">
                            <CardHeadline
                                class="grow border-none"
                                title="Shifts today"
                                description="Your shifts today"
                            />
                            <a v-if="canViewShifts" :href="route('shifts.plan')" class="text-xs text-accent-600 hover:text-accent-700 inline-flex items-center gap-1 whitespace-nowrap">
                                <PropertyIcon name="IconCalendarUser" class="size-4" /> {{ $t('to the shift plan') }}
                            </a>
                        </div>
                        <div v-if="workTimesTodaySorted.length" class="p-4">
                            <div v-for="item in workTimesTodaySorted" :key="item.id" class="mb-3 last:mb-0">
                                <SingleUserEventShift
                                    v-if="item.type === 'shift'"
                                    type="user"
                                    :event="item.shift.event"
                                    :shift="{
                                        ...item.shift,
                                        start_of_shift: item.shift.start_of_shift ?? formatDateDMYFromISO(todayDate),
                                        room: item.shift.room ?? item.shift.event?.room
                                    }"
                                    :project="item.shift.project ?? item.shift.event?.project"
                                    :event-type="item.shift.event ? findEventTypeById(item.shift.event?.event_type_id) : null"
                                    :user-to-edit-id="user.id"
                                    :first-project-shift-tab-id="first_project_shift_tab_id"
                                />

                                <div
                                    v-else
                                    class="rounded-md border border-border bg-surface overflow-hidden"
                                >
                                    <div class="flex items-center justify-between gap-2 px-3 py-2 bg-surface-sunken text-text">
                                        <span class="truncate text-sm font-semibold">
                                            {{ $t('Individual time') }}: {{ item.individualTime?.title ?? '' }}
                                        </span>
                                    </div>

                                    <div class="px-3 py-3 space-y-3">
                                        <div class="flex items-center justify-between gap-3 border-b border-border-subtle pb-2">
                                            <span class="text-sm font-medium text-text">
                                                <template v-if="item.individualTime?.full_day">
                                                    {{ $t('All day') }}
                                                </template>
                                                <template v-else>
                                                    {{ item.individualTime?.start_time }} – {{ item.individualTime?.end_time }}
                                                </template>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-4">
                            <EmptyState :title="$t('You don\'t have any shifts today.')" icon="IconCalendarUser" />
                        </div>
                    </BaseCard>
                </section>

                <!-- Right: Announcements groß + Tasks -->
                <section class="col-span-6 xl:col-span-3 space-y-6">
                    <!-- Ankündigungen – groß -->
                    <BaseCard elevation="flat" class="overflow-hidden">
                        <CardHeadline
                            title="Notifications today"
                            description="Important messages & changes"
                        />

                        <div v-if="globalNotification?.image_url || globalNotification?.title" class="mt-4">
                            <!-- Hero Card -->
                            <div class="mx-4 overflow-hidden rounded-md border border-border-subtle bg-surface">
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
                                    <p class="text-sm text-text">
                                        {{ globalNotification.description }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="notifications.length" class="p-4 grid grid-cols-1 gap-3">
                            <template v-if="notificationsLoading">
                                <BaseSkeleton
                                    v-for="index in NOTIFICATIONS_PER_PAGE"
                                    :key="`notification-skeleton-${index}`"
                                    variant="block"
                                    height="h-24"
                                />
                            </template>
                            <template v-else>
                                <div v-for="n in notifications" :key="n.id" class="rounded-md border border-border-subtle bg-surface p-4">
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
                            </template>

                            <div v-if="notificationPageCount > 1" class="flex items-center justify-between pt-1 text-xs text-text-muted">
                                <BaseUIButton
                                    variant="ghost"
                                    size="sm"
                                    hide-icon
                                    label="Back"
                                    :disabled="notificationPage === 1 || notificationsLoading"
                                    @click="fetchNotificationPage(notificationPage - 1)"
                                />
                                <span>{{ $t('Page') }} {{ notificationPage }} / {{ notificationPageCount }}</span>
                                <BaseUIButton
                                    variant="ghost"
                                    size="sm"
                                    hide-icon
                                    label="Next"
                                    :disabled="notificationPage === notificationPageCount || notificationsLoading"
                                    @click="fetchNotificationPage(notificationPage + 1)"
                                />
                            </div>
                        </div>

                        <div v-else class="p-4">
                            <EmptyState :title="$t('There are no new announcements for today.')" icon="IconBell" />
                        </div>

                        <div class="px-4 pb-4">
                            <a :href="route('notifications.index')" class="text-xs text-accent-600 hover:text-accent-700 inline-flex items-center gap-1">
                                <PropertyIcon name="IconBell" class="size-4" /> {{ $t('Go to the notifications') }}
                            </a>
                        </div>
                    </BaseCard>

                    <!-- Aufgaben -->
                    <BaseCard elevation="flat">
                        <div class="flex items-center justify-between border-b border-border-subtle pr-4">
                            <CardHeadline
                                class="grow border-none"
                                title="Next tasks"
                                description="Your open to-dos"
                            />
                            <a :href="route('tasks.own')" class="text-xs text-accent-600 hover:text-accent-700 inline-flex items-center gap-1 whitespace-nowrap">
                                <PropertyIcon name="IconListCheck" class="size-4" /> {{ $t('To the task overview') }}
                            </a>
                        </div>

                        <div v-if="tasks?.length" class="p-4 space-y-3">
                            <div v-for="task in tasks" :key="task.id" class="rounded-md border border-border-subtle bg-surface p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="task.done"
                                            @change="updateTaskStatus(task)"
                                            class="mt-0.5 rounded border-border text-accent-600 focus:ring-accent-600"
                                        />
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium leading-tight truncate" :class="task.done ? 'line-through text-text-subtle' : 'text-text'">
                                                {{ task.name }}
                                            </p>
                                            <p v-if="task.description" class="text-xs text-text-muted line-clamp-2 mt-0.5">
                                                {{ task.description }}
                                            </p>
                                        </div>
                                    </label>
                                    <div
                                        v-if="!task.done && task.deadline"
                                        class="text-sm font-medium text-right whitespace-nowrap"
                                        :class="task.isDeadlineInFuture ? 'text-text-muted' : 'text-danger'"
                                    >
                                        {{ $t('until') }} {{ task.deadline }}
                                    </div>
                                </div>

                                <Link
                                    v-if="task.projectId"
                                    :href="route('projects.tab', { project: task.projectId, projectTab: first_project_tasks_tab_id })"
                                    class="mt-2 inline-flex items-center gap-1 text-xs text-accent-600 hover:text-accent-700"
                                >
                                    {{ task.projectName }}
                                    <PropertyIcon name="IconChevronRight" class="h-3 w-3" />
                                    {{ task.checklistName }}
                                </Link>
                            </div>
                        </div>

                        <div v-else class="p-4">
                            <EmptyState :title="$t('You have no open tasks.')" icon="IconListCheck" />
                        </div>
                    </BaseCard>
                </section>


            </main>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import {computed, onMounted, onBeforeUnmount, defineOptions, defineAsyncComponent, ref} from 'vue'
import axios from 'axios'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Permissions from '@/Mixins/Permissions.vue'
import { is, can } from 'laravel-permission-to-vuejs'

// Icons
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

// Design-Basis Katalogkomponenten
import KpiTile from '@/Artwork/Cards/KpiTile.vue'
import BaseCard from '@/Artwork/Cards/BaseCard.vue'
import CardHeadline from '@/Artwork/Cards/CardHeadline.vue'
import EmptyState from '@/Artwork/Feedback/EmptyState.vue'
import BaseSkeleton from '@/Artwork/Feedback/BaseSkeleton.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineOptions({ mixins: [Permissions] })

const props = defineProps<{
    tasks: any[],
    shiftsOfDay: any[],
    individualTimesOfDay: any[],
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
const shiftsCountToday = computed(() => (props.shiftsOfDay?.length ?? 0) + (props.individualTimesOfDay?.length ?? 0))
const notificationsCountToday = computed(() => props.notificationCount ?? 0)
const openTasksCount = computed(() => (props.tasks?.filter(t => !t.done).length) ?? 0)

// Server-side pagination for today's notifications: the dashboard ships only the first page
// (see EventController::showDashboardPage), further pages are fetched on demand so a user with
// thousands of notifications neither bloats the payload nor the browser. Keep PER_PAGE in sync
// with the controller.
const NOTIFICATIONS_PER_PAGE = 5
const notifications = ref([...(props.notificationOfToday ?? [])])
const notificationPage = ref(1)
const notificationPageCount = computed(
    () => Math.max(1, Math.ceil((props.notificationCount ?? 0) / NOTIFICATIONS_PER_PAGE))
)
const notificationsLoading = ref(false)

const fetchNotificationPage = async (targetPage: number) => {
    if (targetPage < 1 || targetPage > notificationPageCount.value || notificationsLoading.value) {
        return
    }
    notificationsLoading.value = true
    try {
        const { data } = await axios.get(route('notifications.today'), {
            params: { page: targetPage, perPage: NOTIFICATIONS_PER_PAGE },
        })
        notifications.value = data.data ?? []
        notificationPage.value = data.current_page ?? targetPage
    } finally {
        notificationsLoading.value = false
    }
}

const NotificationBlock = defineAsyncComponent(() => import('@/Layouts/Components/NotificationComponents/NotificationBlock.vue'));
const SingleUserEventShift = defineAsyncComponent(() => import('@/Layouts/Components/ShiftPlanComponents/SingleUserEventShift.vue'));

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

function normalizeTime(value: any): string {
    if (!value) return ''
    const s = String(value)
    // Erwartet i.d.R. "HH:MM" oder "HH:MM:SS"; wir normalisieren auf HH:MM
    if (s.length >= 5) return s.slice(0, 5)
    return s
}

function getSortKeyForWorkItem(item: any): string {
    if (item?.type === 'shift') {
        return normalizeTime(item?.shift?.start ?? item?.shift?.start_time ?? item?.shift?.startPivot) || '00:00'
    }

    if (item?.type === 'individual_time') {
        if (item?.individualTime?.full_day) return '00:00'
        return normalizeTime(item?.individualTime?.start_time) || '00:00'
    }

    return '00:00'
}

const workTimesTodaySorted = computed(() => {
    const shifts = (props.shiftsOfDay ?? []).map((shift: any) => ({
        id: `shift-${shift.id}`,
        type: 'shift',
        shift,
    }))

    const individualTimes = (props.individualTimesOfDay ?? []).map((individualTime: any) => ({
        id: `it-${individualTime.id}`,
        type: 'individual_time',
        individualTime,
    }))

    const merged = [...shifts, ...individualTimes]

    return merged.sort((a: any, b: any) => getSortKeyForWorkItem(a).localeCompare(getSortKeyForWorkItem(b)))
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

const openCalendarWithEventId = (eventForCalendar) => {

    router.get(route('dashboard.redirect-to-calendar', eventForCalendar.id))

};

const formatDateDMYFromISO = (dateStr: string) => {
    if (!dateStr) return null
    const [y, m, d] = dateStr.split('-')
    if (!y || !m || !d) return dateStr
    return `${d}.${m}.${y}`
}
</script>

<style scoped>
/* nur minimale Scopes; keine @apply */
</style>
