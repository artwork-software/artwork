<template>
    <app-layout title="Startseite">

        <div class="artwork-container">
            <div>
                <PageTitle
                    :title="$t('Dashboard')"
                    :description="$t('Here you can find all important information at a glance')"
                />
            </div>

            <div class="grid grid-cols-6 gap-10">
                <div class="col-span-2 space-y-6">
                    <div>
                        <BaseCard>
                            <CardHeadline title="Today's appointments" description="Hier siehst du alle Termine, die für heute geplant sind."/>
                            <div v-if="eventsOfDay?.length > 0" class="px-5 pb-5">
                                <div v-for="event of eventsOfDay" :key="event.id" class="py-1 w-full">
                                    <WhiteInnerCard>
                                        <div class="flex items-stretch gap-x-3 min-w-full w-full h-full p-4">
                                            <div class="p-1 rounded-lg w-1" :style="{backgroundColor: event?.event_type.hex_code}"></div>
                                            <div class="w-full">
                                                <p class="text-sm font-lexend font-semibold text-gray-900" :style="{color: event?.event_type.hex_code}">
                                                    {{ event?.event_type.abbreviation }}: {{ event?.eventName ?? event?.project?.name }}
                                                </p>
                                                <div class="mt-1 flex items-center gap-x-1 text-xs text-gray-500">
                                                    <p v-if="!event.allDay" class="flex items-center gap-x-1">
                                                        <span class="font-lexend font-bold">{{ $t('Start') }}:</span>
                                                        <span class="font-lexend">{{ event?.start_time }}</span>
                                                        <span class="font-lexend font-bold">{{ $t('End') }}:</span>
                                                        <span class="font-lexend">{{ event?.end_time }}</span>
                                                    </p>
                                                    <p v-else>
                                                        <span class="font-lexend font-bold">{{ $t('All day') }}</span>
                                                    </p>
                                                </div>
                                                <p class="mt-1 flex items-center gap-x-1 text-xs text-gray-500">
                                                    <span class="font-lexend font-bold">{{ $t('Room') }}:</span>
                                                    <span class="font-lexend">{{ event?.room?.name }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </WhiteInnerCard>
                                </div>
                            </div>
                            
                            <div v-else class="px-5">
                                <BaseAlertComponent message="No appointments found" type="info" use-translation />
                            </div>

                            <div class="flex item-center justify-end px-5 pb-5">
                                <a :href="route('events')" class="text-artwork-buttons-create font-lexend text-xs flex items-center gap-x-1">
                                    <component is="IconCalendarMonth" class="size-4" />
                                    {{ $t("to calendar") }}
                                </a>
                            </div>
                        </BaseCard>
                    </div>
                    <div>
                        <BaseCard>
                            <CardHeadline title="Shifts today" description=""/>
                            <div v-if="shiftsOfDay?.length > 0" class="px-5 pb-5">
                                <div v-for="shift of shiftsOfDay" :key="shift.id" class="py-1 w-full">
                                    <WhiteInnerCard>
                                        <div class="p-4">
                                            <SingleUserEventShift type='user'
                                                                  :event="shift.event"
                                                                  :shift="shift"
                                                                  :project="this.findProjectById(shift.event.project_id)"
                                                                  :event-type="this.findEventTypeById(shift.event.event_type_id)"
                                                                  :user-to-edit-id="this.$page.props.auth.user.id"
                                                                  :first-project-shift-tab-id="this.first_project_shift_tab_id"/>
                                        </div>
                                    </WhiteInnerCard>
                                </div>
                            </div>


                            <div v-else class="px-5">
                                <BaseAlertComponent message="You don't have any shifts today." type="info" use-translation />
                            </div>

                            <div class="flex item-center justify-end px-5 pb-5" v-if="this.$can('can view shift plan') || this.hasAdminRole()">
                                <a :href="route('shifts.plan')" class="text-artwork-buttons-create font-lexend text-xs flex items-center gap-x-1">
                                    <component is="IconCalendarUser" class="size-4" />
                                    {{ $t("to the shift plan") }}
                                </a>
                            </div>
                        </BaseCard>
                    </div>
                </div>
                <div class="col-start-3 col-span-4 space-y-6">
                    <div>
                        <BaseCard>
                            <CardHeadline title="Notifications today" description=""/>
                            <div class="px-5 pb-4" v-if="globalNotification.image_url || globalNotification.title">
                                <WhiteInnerCard>
                                    <div class="p-4">
                                        <div class="flex items-center gap-x-4 text-xs text-gray-500">
                                            <img v-if="globalNotification.image_url" alt="Benachrichtigungs Bild" class="w-20 h-20 object-cover rounded-full"
                                                 :src="globalNotification.image_url"/>
                                            <div class="">
                                                <div class="headline2 mb-1">
                                                    {{ globalNotification.title }}
                                                </div>
                                                <div class="xsLight">
                                                    {{ globalNotification.description }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </WhiteInnerCard>
                            </div>

                            <div v-if="notificationOfToday.length > 0" class="space-y-4 px-5 pb-5">
                                <div v-for="notification in notificationOfToday">
                                    <WhiteInnerCard>
                                        <div class="p-4">
                                            <NotificationBlock :history-objects="historyObjects"
                                                               :notification="notification"
                                                               :event="event"
                                                               :event-types="eventTypes"
                                                               :rooms="rooms"
                                                               :event-statuses="this.eventStatuses"
                                                               :first_project_shift_tab_id="first_project_shift_tab_id"
                                                               :first_project_budget_tab_id="first_project_budget_tab_id"
                                                               :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                                               :is-dashboard="true"
                                            />
                                        </div>
                                    </WhiteInnerCard>
                                </div>
                            </div>
                            <div v-else class="px-5">
                                <BaseAlertComponent message="There are no new announcements for today." type="info" use-translation />
                            </div>
                            <div class="flex item-center justify-end px-5 py-5">
                                <a :href="route('notifications.index')" class="text-artwork-buttons-create font-lexend text-xs flex items-center gap-x-1">
                                    <component is="IconBell" class="size-4" />
                                    {{ $t("Go to the notifications")}}
                                </a>
                            </div>
                        </BaseCard>
                    </div>
                    <div>
                        <BaseCard>
                            <CardHeadline title="Next tasks" description=""/>
                            <div  v-if="tasks.length > 0">
                                <div class="space-y-4 px-5 pb-5">
                                    <WhiteInnerCard v-for="task in tasks" :key="task.id">
                                        <div class="p-4">
                                            <div class="flex w-full items-center justify-between pt-2">
                                                <div class="flex items-center w-full">
                                                    <input @change="updateTaskStatus(task)"
                                                           v-model="task.done"
                                                           type="checkbox"
                                                           class="input-checklist mt-0.5"/>
                                                    <div class="ml-2 mDark truncate w-96"
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
                                                  class="my-1 flex ml-8 text-xs">
                                                {{ task.projectName }}
                                                <ChevronRightIcon class="h-3 w-3 my-auto mx-2" aria-hidden="true"/>
                                                {{ task.checklistName }}
                                            </Link>
                                            <div class="ml-8 my-3 xsLight">
                                                {{ task.description }}
                                            </div>
                                        </div>
                                    </WhiteInnerCard>
                                </div>
                            </div>
                            <div v-else class="px-5">
                                <BaseAlertComponent message="You have no open tasks." type="info" use-translation />
                            </div>
                            <div class="flex item-center justify-end px-5 py-5">
                                <a :href="route('tasks.own')" class="text-artwork-buttons-create font-lexend text-xs flex items-center gap-x-1">
                                    <component is="IconListCheck" class="size-4" />
                                    {{ $t("To the task overview")}}
                                </a>
                            </div>
                        </BaseCard>
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
import {Link, router, useForm, usePage} from "@inertiajs/vue3";
import TeamTooltip from "@/Layouts/Components/TeamTooltip.vue";
import Permissions from "@/Mixins/Permissions.vue";
import VueMathjax from "vue-mathjax-next";
import {CheckIcon} from "@heroicons/vue/outline";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import NotificationButtons from "@/Layouts/Components/NotificationComponents/NotificationButtons.vue";
import NotificationBlock from "@/Layouts/Components/NotificationComponents/NotificationBlock.vue";
import DashboardCard from "@/Components/DashboardCard.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import SingleUserEventShift from "@/Layouts/Components/ShiftPlanComponents/SingleUserEventShift.vue";
import DayServiceComponent from "@/Layouts/Components/DayService/DayServiceComponent.vue";
import { reloadRolesAndPermissions } from 'laravel-permission-to-vuejs'
import PageTitle from "@/Artwork/Titles/PageTitle.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import BaseCard from "@/Artwork/Cards/BaseCard.vue";
import CardHeadline from "@/Artwork/Cards/CardHeadline.vue";
import SingleMyEventVerificationRequests
    from "@/Pages/EventVerification/Components/SingleMyEventVerificationRequests.vue";
import SinglePlannedEventInVerificationPage
    from "@/Pages/EventVerification/Components/SinglePlannedEventInVerificationPage.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import WhiteInnerCard from "@/Artwork/Cards/WhiteInnerCard.vue";
import BaseCardButton from "@/Artwork/Buttons/BaseCardButton.vue";
export default defineComponent({
    mixins: [Permissions],
    props: [
        'tasks',
        'shiftsOfDay',
        'todayDate',
        'eventsOfDay',
        'globalNotification',
        'notificationOfToday',
        'notificationCount',
        'event',
        'eventTypes',
        'projects',
        'rooms',
        'historyObjects',
        'users_day_services_of_day',
        'eventStatuses',
        'first_project_tab_id',
        'first_project_shift_tab_id',
        'first_project_tasks_tab_id',
        'first_project_budget_tab_id',
        'first_project_calendar_tab_id'
    ],
    components: {
        BaseCardButton,
        WhiteInnerCard,
        BaseAlertComponent,
        SinglePlannedEventInVerificationPage,
        SingleMyEventVerificationRequests,
        CardHeadline,
        BaseCard,
        BasePaginator,
        PageTitle,
        DayServiceComponent,
        SingleUserEventShift,
        AlertComponent,
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
        usePage,
        backgroundColorWithOpacity(event){
            const color = event.event_type?.hex_code;
            return `rgb(${parseInt(color?.slice(-6, -4), 16)}, ${parseInt(color?.slice(-4, -2), 16)}, ${parseInt(color?.slice(-2), 16)}, 15%)`;
        },
        TextColorWithDarken(event){
            const color = event.event_type?.hex_code;
            return `rgb(${parseInt(color?.slice(-6, -4), 16) - 75}, ${parseInt(color?.slice(-4, -2), 16) - 75}, ${parseInt(color?.slice(-2), 16) - 75})`;
        },
        updateTaskStatus(task) {
            this.doneTaskForm.done = task.done;
            this.doneTaskForm.patch(route('tasks.done', {task: task.id}));
        },
        getHref(project) {
            return route('projects.tab', {project: project?.id, projectTab: this.first_project_tab_id});
        },
        findProjectById(projectId) {
            return this.projects.find(project => project.id === projectId);
        },
        findEventTypeById(eventTypeId) {
            return this.eventTypes.find(eventType => eventType.id === eventTypeId);
        },
    },
    data() {
        return {
            doneTaskForm: useForm({
                done: false
            }),
        }
    },
    mounted() {
        reloadRolesAndPermissions()
    }

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
