<template>
    <app-layout title="Startseite">
        <div class="max-w-screen-2xl mb-40 ml-14 mr-40">
            <div class="headline1 mb-10">
                Dashboard
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-x-20">
                <div class="col-span-2">
                    <div class="mb-10">
                        <!-- Termin Widget -->
                        <div class="flex justify-between items-center w-fit gap-x-5 mb-5">
                            <div>
                                <h3 class=" tracking-widest uppercase font-semibold text-base">{{ $t("Today's appointments")}}</h3>
                            </div>
                            <div class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                {{ eventsOfDay?.length ?? 0 }}
                            </div>
                        </div>

                        <DashboardCard>
                            <div class="font-semibold flex items-center gap-x-3 mb-3">
                                <svg id="Gruppe_1806" data-name="Gruppe 1806" xmlns="http://www.w3.org/2000/svg" width="22.065" height="18.527" viewBox="0 0 22.065 18.527">
                                    <path id="Pfad_1003" data-name="Pfad 1003" d="M685.02,351.271h-2.832a.7.7,0,1,0,0,1.4h2.832a.7.7,0,1,0,0-1.4Z" transform="translate(-676.376 -341.693)" fill="#27233c"/>
                                    <path id="Pfad_1004" data-name="Pfad 1004" d="M695.9,351.271h-2.832a.7.7,0,1,0,0,1.4H695.9a.7.7,0,1,0,0-1.4Z" transform="translate(-679.643 -341.693)" fill="#27233c"/>
                                    <path id="Pfad_1005" data-name="Pfad 1005" d="M685.02,356.806h-2.832a.7.7,0,1,0,0,1.4h2.832a.7.7,0,1,0,0-1.4Z" transform="translate(-676.376 -343.355)" fill="#27233c"/>
                                    <path id="Pfad_1006" data-name="Pfad 1006" d="M695.9,356.806h-2.832a.7.7,0,1,0,0,1.4H695.9a.7.7,0,1,0,0-1.4Z" transform="translate(-679.643 -343.355)" fill="#27233c"/>
                                    <path id="Pfad_1007" data-name="Pfad 1007" d="M695.546,338.995h-4.721v-.716a.7.7,0,1,0-1.4,0v.716h-3.514v-.716a.7.7,0,1,0-1.4,0v.716h-3.971v-.716a.7.7,0,0,0-1.4,0v.716H674.88a.7.7,0,0,0-.7.7v15.712a.7.7,0,0,0,.7.7h20.666a.7.7,0,0,0,.7-.7V339.694A.7.7,0,0,0,695.546,338.995Zm-16.4,1.4v.716a.7.7,0,0,0,1.4,0v-.716h3.971v.716a.7.7,0,1,0,1.4,0v-.716h3.514v.716a.7.7,0,1,0,1.4,0v-.716h4.021v3.068H675.579v-3.068Zm-3.564,14.313v-9.846h19.267v9.846Z" transform="translate(-674.18 -337.579)" fill="#27233c"/>
                                </svg>
                                {{todayDate}}
                            </div>
                            <div v-if="eventsOfDay?.length > 0" class=" max-h-64 overflow-scroll">
                                <div v-for="event of eventsOfDay" :key="event.id" class="py-1 w-full">
                                    <div :style="{backgroundColor: backgroundColorWithOpacity(event), color: TextColorWithDarken(event)}"  class="py-1 px-2 rounded">
                                        <a :href="getHref(event.project)" class="font-semibold" :class="event.project? 'underline cursor-pointer' : ''" >
                                            {{ event.event_type?.abbreviation }}: {{ event.project?.name }}
                                        </a>
                                        <div class="text-sm">
                                            <div v-if="event.allDay">
                                                Ganztags
                                            </div>
                                            <div v-else>
                                                {{ event.start_time }} - {{ event.end_time }}
                                            </div>
                                            <div>
                                                {{ event.room?.name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="mt-3">
                                <div class="flex justify-start">
                                    <div class="bg-gray-50 p-2">
                                        <p class="text-sm text-gray-500">
                                            {{ $t("You have no appointments today.")}}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </DashboardCard>
                        <div class="flex justify-end mt-3">
                            <a :href="route('events')" class="text-artwork-buttons-create underline font-semibold text-sm">{{ $t("to calendar")}}</a>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center w-fit gap-x-5 mb-5">
                            <div>
                                <h3 class=" tracking-widest uppercase font-semibold text-base">{{ $t("Shifts today")}}</h3>
                            </div>
                            <div class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                {{ shiftsOfDay?.length ?? 0 }}
                            </div>
                        </div>
                        <DashboardCard>
                            <div class="font-semibold flex items-center gap-x-3">
                                {{todayDate}}
                            </div>
                            <div v-if="shiftsOfDay?.length > 0">
                                <div v-for="shift of shiftsOfDay" :key="shift.event.id" class="py-2 w-full">
                                    <div>
                                        <div>
                                            <div class="text-secondaryHover xsWhiteBold px-1 py-1"
                                                 :class="shift.event?.event_type?.svg_name + 'Shift'">
                                                {{ shift.event?.event_type?.abbreviation }}: {{ shift.event?.project?.name }}
                                            </div>
                                        </div>
                                        <div class="bg-backgroundGray">
                                            <div class="flex items-center xsLight text-shiftText subpixel-antialiased">
                                                <div>
                                                    {{ shift.craft?.abbreviation }} {{ shift.start }} - {{ shift.end }}
                                                </div>
                                                <div v-if="shift.event?.room" class="truncate">
                                                    , {{ shift.event?.room?.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="mt-3">
                                <div class="flex justify-start">
                                    <div class="bg-gray-50 p-2 text-center">
                                        <p class="text-sm text-gray-500">
                                            {{ $t("You don't have any shifts today.")}}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </DashboardCard>
                        <div class="flex justify-end mt-3" v-if="this.$can('can view shift plan') || this.hasAdminRole()">
                            <a :href="route('shifts.plan')" class="text-artwork-buttons-create underline font-semibold text-sm">{{ $t("to the shift plan")}}</a>
                        </div>
                    </div>
                </div>
                <div class="col-span-3">
                    <div class="mb-10">
                        <!-- Notification Widget -->
                        <div class="flex justify-between items-center w-fit gap-x-5 mb-5">
                            <div>
                                <h3 class=" tracking-widest uppercase font-semibold text-base">{{ $t("Notifications today")}}</h3>
                            </div>
                            <div class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                {{ notificationCount }}
                            </div>
                        </div>
                        <div>
                            <div v-if="Object.values(notificationOfToday)?.length > 0">
                                <div v-for="notificationGroup in Object.values(notificationOfToday)">
                                    <div v-for="(notification, index) in notificationGroup">
                                        <DashboardCard>
                                            <NotificationBlock :history-objects="historyObjects"
                                                               :notification="notification"
                                                               :event="event"
                                                               :event-types="eventTypes"
                                                               :rooms="rooms"
                                                               :first_project_shift_tab_id="first_project_shift_tab_id"
                                                               :first_project_budget_tab_id="first_project_budget_tab_id"
                                                               :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                            />
                                        </DashboardCard>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="">
                                <DashboardCard >
                                    <div class="flex items-center gap-x-4">
                                        <svg id="Gruppe_642" data-name="Gruppe 642" xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 45 45">
                                            <g id="icon_note" data-name="icon–note">
                                                <g id="Gruppe_642-2" data-name="Gruppe 642">
                                                    <circle id="Bildschirmfoto_2022-08-28_um_10.42.36" data-name="Bildschirmfoto 2022-08-28 um 10.42.36" cx="22.5" cy="22.5" r="22.5" fill="#cecdd8"/>
                                                </g>
                                                <g id="clock" transform="translate(9.5 6.313)">
                                                    <path id="Pfad_1078" data-name="Pfad 1078" d="M13.124,13.124h0A13.139,13.139,0,0,0,26.231,0H13.106A13.139,13.139,0,0,0,0,13.124Z" transform="translate(13.124 28.419) rotate(-90)" fill="#a7a6b1"/>
                                                    <path id="Pfad_1079" data-name="Pfad 1079" d="M13.124,0h0A13.139,13.139,0,0,1,26.231,13.124H13.106A13.139,13.139,0,0,1,0,0Z" transform="translate(0 28.428) rotate(-90)" fill="#fcfcfb"/>
                                                    <g id="Pfad_1507" data-name="Pfad 1507" transform="translate(17.07)" fill="#27233c">
                                                        <path d="M 5.471749305725098 10.19349956512451 C 2.86816930770874 10.19349956512451 0.7499992847442627 8.075329780578613 0.7499992847442627 5.471749305725098 C 0.7499992847442627 2.86816930770874 2.86816930770874 0.7499992847442627 5.471749305725098 0.7499992847442627 C 8.075329780578613 0.7499992847442627 10.19349956512451 2.86816930770874 10.19349956512451 5.471749305725098 C 10.19349956512451 8.075329780578613 8.075329780578613 10.19349956512451 5.471749305725098 10.19349956512451 Z" stroke="none"/>
                                                        <path d="M 5.471749305725098 1.499999046325684 C 3.281719207763672 1.499999046325684 1.499999046325684 3.281719207763672 1.499999046325684 5.471749305725098 C 1.499999046325684 7.661779403686523 3.281719207763672 9.443499565124512 5.471749305725098 9.443499565124512 C 7.661779403686523 9.443499565124512 9.443499565124512 7.661779403686523 9.443499565124512 5.471749305725098 C 9.443499565124512 3.281719207763672 7.661779403686523 1.499999046325684 5.471749305725098 1.499999046325684 M 5.471749305725098 -9.5367431640625e-07 C 8.493709564208984 -9.5367431640625e-07 10.94349956512451 2.449789047241211 10.94349956512451 5.471749305725098 C 10.94349956512451 8.493709564208984 8.493709564208984 10.94349956512451 5.471749305725098 10.94349956512451 C 2.449789047241211 10.94349956512451 -9.5367431640625e-07 8.493709564208984 -9.5367431640625e-07 5.471749305725098 C -9.5367431640625e-07 2.449789047241211 2.449789047241211 -9.5367431640625e-07 5.471749305725098 -9.5367431640625e-07 Z" stroke="none" fill="#cecdd8"/>
                                                    </g>
                                                </g>
                                            </g>
                                        </svg>
                                        <div class="xsLight">
                                            {{ $t("There are no new announcements for today.")}}
                                        </div>
                                    </div>
                                </DashboardCard>
                            </div>
                        </div>
                        <div class="flex justify-end mt-3">
                            <a :href="route('notifications.index')" class="text-artwork-buttons-create underline font-semibold text-sm">{{ $t("Go to the notifications")}}</a>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center w-fit gap-x-5 mb-5">
                            <div>
                                <h3 class=" tracking-widest uppercase font-semibold text-base">{{ $t("Next tasks")}}</h3>
                            </div>
                            <div class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                {{ tasks?.length ?? 0 }}
                            </div>
                        </div>
                        <DashboardCard :has-padding="false">
                            <div v-if="tasks.length > 0" class="p-4">
                                <div v-for="task in tasks" :key="task.id" class="py-2 w-full">
                                    <div class="flex w-full justify-between">
                                        <div class="flex w-full">
                                            <input @change="updateTaskStatus(task)"
                                                   v-model="task.done"
                                                   type="checkbox"
                                                   class="cursor-pointer h-6 w-6 text-success border-2 my-2 border-success"/>
                                            <div class="ml-4 my-auto mDark truncate w-96"
                                                 :class="task.done ? 'text-secondary line-through' : 'text-primary'">
                                                {{ task.name }}
                                            </div>
                                        </div>
                                        <div v-if="!task.done && task.deadline"
                                             class=" my-auto pt-1 xsLight w-52"
                                             :class="task.isDeadlineInFuture ? '' : 'text-error'">
                                            bis {{ task.deadline }}
                                        </div>
                                    </div>


                                    <Link v-if="task.projectId" :href="route('projects.tab', {project: task.projectId, projectTab: this.first_project_tasks_tab_id})"
                                          class="my-1 flex ml-10 xsDark">
                                        {{ task.projectName }}
                                        <ChevronRightIcon class="h-5 w-5 my-auto mx-3" aria-hidden="true"/>
                                        {{ task.checklistName }}
                                    </Link>

                                    <div class="ml-10 my-3 xsLight">
                                        {{ task.description }}
                                    </div>

                                </div>
                            </div>
                            <div v-else class="relative">
                                <div class="p-6 flex justify-start">
                                    <div class="bg-gray-50 p-2 text-center">
                                        <p class="text-sm text-gray-500">
                                            {{ $t('You have no open tasks.')}}
                                        </p>
                                    </div>
                                </div>
                                <div class="absolute bottom-0 right-0 rounded-br-lg">
                                    <img src="/Svgs/IconSvgs/empty_state.svg" class=" rounded-br-lg" alt="">
                                </div>
                            </div>
                        </DashboardCard>
                        <div class="flex justify-end mt-3">
                            <a :href="route('tasks.own')" class="text-artwork-buttons-create underline font-semibold text-sm">{{ $t("To the task overview")}}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    CalendarIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    DotsHorizontalIcon,
} from '@heroicons/vue/solid'
import {Menu, MenuButton, MenuItem, MenuItems} from '@headlessui/vue'
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {Link, useForm} from "@inertiajs/vue3";
import TeamTooltip from "@/Layouts/Components/TeamTooltip.vue";
import {router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import VueMathjax from "vue-mathjax-next";
import {CheckIcon} from "@heroicons/vue/outline";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import NotificationButtons from "@/Layouts/Components/NotificationComponents/NotificationButtons.vue";
import NotificationBlock from "@/Layouts/Components/NotificationComponents/NotificationBlock.vue";
import DashboardCard from "@/Components/DashboardCard.vue";

export default defineComponent({
    mixins: [Permissions],
    props: [
        'tasks',
        'shiftsOfDay',
        'todayDate',
        'eventsOfDay',
        'notificationOfToday',
        'notificationCount',
        'event',
        'eventTypes',
        'rooms',
        'historyObjects',
        'first_project_tab_id',
        'first_project_shift_tab_id',
        'first_project_tasks_tab_id',
        'first_project_budget_tab_id',
        'first_project_calendar_tab_id'
    ],
    components: {
        DashboardCard,
        NotificationBlock,
        NotificationButtons,
        NewUserToolTip,
        CheckIcon,
        VueMathjax,
        AppLayout,
        CalendarIcon,
        ChevronRightIcon,
        Menu,
        MenuItem,
        MenuButton,
        MenuItems,
        ChevronLeftIcon,
        DotsHorizontalIcon,
        ChevronDownIcon,
        TeamIconCollection,
        Link,
        TeamTooltip,
    },
    created() {
        Echo.private('events')
            .listen('OccupancyUpdated', () => {
                router.reload({only: ['rooms', 'calendar', 'days']})
            });
    },
    methods: {
        backgroundColorWithOpacity(event){
            const color = event.event_type.hex_code;
            return `rgb(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, 15%)`;
        },
        TextColorWithDarken(event){
            const color = event.event_type.hex_code;
            return `rgb(${parseInt(color.slice(-6, -4), 16) - 75}, ${parseInt(color.slice(-4, -2), 16) - 75}, ${parseInt(color.slice(-2), 16) - 75})`;
        },
        updateTaskStatus(task) {
            this.doneTaskForm.done = task.done;
            this.doneTaskForm.patch(route('tasks.update', {task: task.id}));
        },
        getHref(project) {
            return route('projects.tab', {project: project?.id, projectTab: this.first_project_tab_id});
        },
    },
    data() {
        return {
            doneTaskForm: useForm({
                done: false
            }),
        }
    },

})
</script>
<style scoped>

.eventType0Shift {
    background-color: #7F7E88;
}

.eventType1Shift {
    background-color: #631D53;
}

.eventType2Shift {
    background-color: #D84387;
}

.eventType3Shift {
    background-color: #E97A45;
}

.eventType4Shift {
    background-color: #CB8913;
}

.eventType5Shift {
    background-color: #648928;
}

.eventType6Shift {
    background-color: #35A965;
}

.eventType7Shift {
    background-color: #35ACB2;
}

.eventType8Shift {
    background-color: #2290C1;
}

.eventType9Shift {
    background-color: #50908E;
}

.eventType10Shift {
    background-color: #23485B;
}
.eventType0 {
    background-color: #A7A6B115;
    stroke: #7F7E88;
    color: #7F7E88
}

.eventType1 {
    background-color: #641a5415;
    stroke: #631D53;
    color: #631D53
}

.eventType2 {
    background-color: #da3f8715;
    stroke: #D84387;
    color: #D84387
}

.eventType3 {
    background-color: #eb7a3d15;
    stroke: #E97A45;
    color: #E97A45
}

.eventType4 {
    background-color: #f1b64015;
    stroke: #CB8913;
    color: #CB8913
}

.eventType5 {
    background-color: #86c55415;
    stroke: #648928;
    color: #648928
}

.eventType6 {
    background-color: #2eaa6315;
    stroke: #35A965;
    color: #35A965
}

.eventType7 {
    background-color: #3dc3cb15;
    stroke: #35ACB2;
    color: #35ACB2
}

.eventType8 {
    background-color: #168fc315;
    stroke: #2290C1;
    color: #2290C1
}

.eventType9 {
    background-color: #4d908e15;
    stroke: #50908E;
    color: #50908E
}

.eventType10 {
    background-color: #21485C15;
    stroke: #23485B;
    color: #23485B
}
</style>
