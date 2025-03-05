<template>
    <div class="w-full">
        <div>
            <div class="mb-6 -ml-4">
                <UserShiftPlanFunctionBar :type="type"
                                          :totalPlannedWorkingHours="totalPlannedWorkingHours"
                                          :weeklyWorkingHours="weeklyWorkingHours"
                                          :dateValue="dateValue"
                                          :eventTypes="eventTypes"
                                          @previousTimeRange="previousTimeRange"
                                          @next-time-range="nextTimeRange"/>
            </div>
            <div class="w-full grid grid-cols-7 gap-x-2">
                <template v-for="day in wholeWeekDatePeriod">
                    <span v-if="day.is_monday" class="sDark text-md col-span-7">KW {{ day.week_number }}</span>
                    <div :class="[day.is_weekend ? 'bg-backgroundGray' : 'bg-white', 'min-h-48 flex flex-col gap-y-2 rounded-lg']" class="px-3 py-3">
                        <div :class="[!day.inRequestedTimeSpan ? 'opacity-30' : '','calendarRoomHeader']">
                            {{ day.day_string }} {{ day.full_day }}
                            <span class="text-shiftText subpixel-antialiased">
                                <span>(</span>
                                <span >
                                    {{ $page.props.daysWithData[day.day_without_format]?.totalWorkTime }}
                                </span>
                                <span>
                                    | {{ $page.props.daysWithData[day.day_without_format]?.totalBreakTime }}
                                </span>
                                <span>)</span>
                            </span>
                        </div>
                        <!-- @todo: fix wrong backend datatype -->
<!--                        <div v-if="userToEditIsOnVacation(day)">-->
<!--                            <div class="bg-shiftText text-sm p-1 flex items-center text-secondaryHover h-10">-->
<!--                                {{ $t('not available') }}-->
<!--                            </div>-->
<!--                        </div>-->
                        <div :class="[!day.inRequestedTimeSpan ? 'opacity-30' : '', 'flex flex-col gap-y-2']">
                            <template v-for="group in getUniqueProjectGroupsForDay(day)" :key="group.id">
                                <Link :disabled="checkIfUserIsAdminOrInGroup(group)" :href="route('projects.tab', { project: group.id, projectTab: firstProjectShiftTabId })"  class="bg-artwork-navigation-background text-white text-xs font-bold px-2 py-1 rounded-lg mb-0.5 flex items-center gap-x-1">
                                    <component :is="group.icon" class="size-4" aria-hidden="true"/>
                                    <span>{{ group.name }}</span>
                                </Link>
                            </template>
                            <template v-for="shift in $page.props.daysWithData[day.day_without_format]?.shifts">
                                <SingleUserEventShift
                                    :user-to-edit-id="userToEditId"
                                    :first-project-shift-tab-id="firstProjectShiftTabId"
                                    :event-type="eventTypes.find(eventType => eventType.id === shift?.event?.event_type_id) ?? null"
                                    :shift="shift"
                                    :event="shift?.event ?? null"
                                    :type="type"
                                    :project="shift?.project ?? null"
                                />

                            </template>
                            <template v-for="dayService in getUserToEditDayServicesOfShift($page.props.daysWithData[day.day_without_format]?.shifts)">
                                <DayServiceComponent :day-service="dayService"/>
                            </template>
                        </div>
                    </div>

                </template>
            </div>
        </div>
    </div>

</template>

<script>
import Permissions from "@/Mixins/Permissions.vue";
import UserShiftPlanFunctionBar from "@/Layouts/Components/ShiftPlanComponents/UserShiftPlanFunctionBar.vue";
import ShiftPlanFunctionBar from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFunctionBar.vue";
import {router, Link, usePage} from "@inertiajs/vue3";
import SingleShiftPlanEvent from "@/Layouts/Components/ShiftPlanComponents/SingleShiftPlanEvent.vue";
import SingleUserEventShift from "@/Layouts/Components/ShiftPlanComponents/SingleUserEventShift.vue";
import DayServiceComponent from "@/Layouts/Components/DayService/DayServiceComponent.vue";
import IconLib from "@/Mixins/IconLib.vue";
import {IconCalendarMonth, IconLock} from "@tabler/icons-vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ShiftNoteComponent from "@/Layouts/Components/ShiftNoteComponent.vue";

export default {
    name: "UserShiftPlan",
    mixins: [Permissions, IconLib],
    components: {
        IconLock, ShiftNoteComponent, UserPopoverTooltip, IconCalendarMonth,
        DayServiceComponent,
        SingleUserEventShift,
        SingleShiftPlanEvent,
        ShiftPlanFunctionBar,
        UserShiftPlanFunctionBar, Link
    },
    props: [
        'eventsWithTotalPlannedWorkingHours',
        'wholeWeekDatePeriod',
        'dateValue',
        'projects',
        'eventTypes',
        'rooms',
        'vacations',
        'weeklyWorkingHours',
        'type',
        'totalPlannedWorkingHours',
        'shiftQualifications',
        'firstProjectShiftTabId',
        'userToEditWholeWeekDatePeriodVacations',
        'userToEditId'
    ],
    methods: {
        getUniqueProjectGroupsForDay(day){
            let projectGroups = new Map();

            const shifts = this.$page.props.daysWithData[day.day_without_format]?.shifts || [];

            shifts.forEach(shift => {
                if (shift?.project) {
                    let project = shift.project;

                    // Falls das Projekt selbst eine Gruppe ist, speichern
                    if (project.is_group && !projectGroups.has(project.id)) {
                        projectGroups.set(project.id, project);
                    }

                    // Falls das Projekt in einer Gruppe ist, die Gruppen speichern
                    if (!project.is_group && Array.isArray(project.groups)) {
                        project.groups.forEach(group => {
                            if (!projectGroups.has(group.id)) {
                                projectGroups.set(group.id, group);
                            }
                        });
                    }
                }
            });

            return Array.from(projectGroups.values());
        },
        checkIfUserIsAdminOrInGroup(group) {
            if (this.hasAdminRole()) {
                return false;
            }

            const userId = usePage().props.user.id;

            // Prüft, ob der Benutzer in der Gruppen-User-Liste vorhanden ist
            return !group.users.some(user => user.id === userId);
        },
        previousTimeRange() {
            const dayDifference = this.calculateDateDifference();
            this.dateValue[1] = this.getPreviousDay(this.dateValue[0]);
            const newDate = new Date(this.dateValue[1]);
            newDate.setDate(newDate.getDate() - dayDifference);
            this.dateValue[0] = newDate.toISOString().slice(0, 10);
            this.updateTimes();
        },
        nextTimeRange() {
            const dayDifference = this.calculateDateDifference();
            this.dateValue[0] = this.getNextDay(this.dateValue[1]);
            const newDate = new Date(this.dateValue[1]);
            newDate.setDate(newDate.getDate() + dayDifference + 1);
            this.dateValue[1] = newDate.toISOString().slice(0, 10);
            this.updateTimes();
        },
        calculateDateDifference() {
            const date1 = new Date(this.dateValue[0]);
            const date2 = new Date(this.dateValue[1]);
            const timeDifference = date2.getTime() - date1.getTime();
            return timeDifference / (1000 * 3600 * 24);
        },
        getNextDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() + 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        getPreviousDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() - 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        findProjectById(projectId) {
            return this.projects.find(project => project.id === projectId);
        },
        findEventTypeById(eventTypeId) {
            return this.eventTypes.find(eventType => eventType.id === eventTypeId);
        },
        findRoomById(roomId) {
            return this.rooms.find(room => room.id === roomId);
        },
        updateTimes() {
            router.patch(
                route('update.user.worker.shift-plan.filters.update', this.$page.props.user.id),
                {
                    start_date: this.dateValue[0],
                    end_date: this.dateValue[1],
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                }
            );
        },
        calculateShiftDurationForDay(full_day) {
            const events = this.getEventsWhereHasShiftsOnDay(full_day);
            const allShifts = events.flatMap(event => event.shifts);
            if (allShifts.length === 0) {
                return '00:00';
            }
            const startTimes = allShifts.map(shift => shift.start);
            const endTimes = allShifts.map(shift => shift.end);

            const earliestStartTime = Math.min(...startTimes.map(time => new Date(`2000-01-01 ${time}`)));
            const latestEndTime = Math.max(...endTimes.map(time => new Date(`2000-01-01 ${time}`)));

            const durationInMinutes = (latestEndTime - earliestStartTime) / 60000;

            const hours = Math.floor(durationInMinutes / 60);
            const minutes = durationInMinutes % 60;

            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
        },
        calculateTotalBreakDuration(events) {
            events = Object.values(events);
            const allShifts = events.flatMap(event => event.shifts);
            if (allShifts.length === 0) {
                return '00:00';
            }
            const breakMinutes = allShifts.map(shift => shift.break_minutes);
            const totalBreakMinutes = breakMinutes.reduce((accumulator, currentValue) => accumulator + currentValue, 0);

            const hours = Math.floor(totalBreakMinutes / 60);
            const minutes = totalBreakMinutes % 60;

            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
        },
        getUserToEditDayServicesOfShift(shifts) {
            let dayServicesToReturn = [];
            let user = null;
            shifts.forEach((shift) => {
                if (!user) {
                    user = shift.users.find((user) => user.id === this.userToEditId);
                }

                if (!user?.day_services) {
                    return [];
                }

                dayServicesToReturn = dayServicesToReturn.concat(
                    user.day_services.filter(
                        (dayService) => shift.days_of_shift.includes(
                            dayService.pivot.date.split('-').reverse().join('.')
                        )
                    )
                );
            });

            return Array.from(new Set(dayServicesToReturn));
        },
        getEventsWhereHasShiftsOnDay(full_day) {
            return this.eventsWithTotalPlannedWorkingHours.filter((eventWithTotalPlannedWorkingHours) => {
                return eventWithTotalPlannedWorkingHours.event.daysOfShifts.includes(full_day);
            }).map((eventWithTotalPlannedWorkingHours) => eventWithTotalPlannedWorkingHours.event);
        },
        userToEditIsOnVacation(desiredDay) {
            const dayDate = new Date(desiredDay.full_day.split(".").reverse().join("-"));

            return this.userToEditWholeWeekDatePeriodVacations?.some((vacation) => {
                let vacationFrom = new Date(vacation.date + ' ' + '00:00');
                let vacationUntil = new Date(vacation.date + ' ' + '23:59');

                return dayDate >= vacationFrom && dayDate <= vacationUntil;
            });
        },
        showShiftDurationWarningForDay(full_day) {
            return this.calculateShiftDurationForDay(
                this.getEventsWhereHasShiftsOnDay(full_day)
            ) > '10:00';
        },
        showBreakWarningForDay(full_day) {
            const events = this.getEventsWhereHasShiftsOnDay(full_day);

            return this.calculateShiftDurationForDay(events) > '04:00' &&
                this.calculateTotalBreakDuration(events) < '00:30';
        }
    }
}
</script>
