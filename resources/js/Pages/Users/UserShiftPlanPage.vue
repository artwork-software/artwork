<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="user_to_edit">
        <UserShiftPlan type="user"
                       :totalPlannedWorkingHours="totalPlannedWorkingHours"
                       :weeklyWorkingHours="user_to_edit.weekly_working_hours"
                       :date-value="dateValue"
                       :whole-week-date-period="wholeWeekDatePeriod"
                       :events-with-total-planned-working-hours="eventsWithTotalPlannedWorkingHours"
                       :projects="projects"
                       :event-types="eventTypes"
                       :rooms="rooms"
                       :vacations="vacations"
                       :shift-qualifications="shiftQualifications"
                       :firstProjectShiftTabId="firstProjectShiftTabId"
                       :user-to-edit-whole-week-date-period-vacations="user_to_edit_whole_week_date_period_vacations"
                       :user-to-edit-id="user_to_edit.id"/>
        <Availability type="user"
                      :availabilities="availabilities"
                      :showVacationsAndAvailabilitiesDate="showVacationsAndAvailabilitiesDate"
                      :calendar-data="calendarData"
                      :vacationSelectCalendar="vacationSelectCalendar"
                      :createShowDate="createShowDate"
                      :date-to-show="dateToShow"
                      :user="user_to_edit"
                      :vacations="vacations"
                      v-if="$can('can manage availability') || user_to_edit.id === usePage().props.user.id || this.hasAdminRole()"/>
    </UserEditHeader>
</template>

<script>

import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import UserInfoTab from "@/Pages/Users/Tabs/UserInfoTab.vue";
import Availability from "@/Pages/Users/Components/Availability.vue";
import UserShiftPlan from "@/Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue";
import UserAvailabilityCalendar from "@/Pages/Users/Components/UserAvailabilityCalendar.vue";
import Permissions from "@/Mixins/Permissions.vue";
import {usePage} from "@inertiajs/vue3";

export default {
    components: {
        UserAvailabilityCalendar,
        UserShiftPlan,
        Availability,
        UserInfoTab,
        UserEditHeader,
        BaseSidenav
    },
    mixins: [Permissions],
    props: [
        'user_to_edit',
        'user_to_edit_whole_week_date_period_vacations',
        'currentTab',
        'dateValue',
        'wholeWeekDatePeriod',
        'eventsWithTotalPlannedWorkingHours',
        'totalPlannedWorkingHours',
        'projects',
        'eventTypes',
        'rooms',
        'vacations',
        'calendarData',
        'dateToShow',
        'shifts',
        'vacationSelectCalendar',
        'createShowDate',
        'showVacationsAndAvailabilitiesDate',
        'availabilities',
        'shiftQualifications',
        'firstProjectShiftTabId'
    ],
    data() {
        return {
            show: false,
        }
    },
    methods: {usePage}
}
</script>
