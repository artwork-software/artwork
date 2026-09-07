<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="user_to_edit">
        <UserShiftPlan type="user"
                       :totalPlannedWorkingHours="totalPlannedWorkingHours"
                       :work-time-target="workTimeTarget"
                       :date-value="dateValue"
                       :crafts="crafts"
                       :firstProjectShiftTabId="firstProjectShiftTabId"
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
                      :project-wishes="projectWishes"
                      v-if="$can('can manage availability') || user_to_edit.id === usePage().props.auth.user.id || this.hasAdminRole()"/>
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
        'currentTab',
        'dateValue',
        'totalPlannedWorkingHours',
        'crafts',
        'vacations',
        'calendarData',
        'dateToShow',
        'shifts',
        'vacationSelectCalendar',
        'createShowDate',
        'showVacationsAndAvailabilitiesDate',
        'availabilities',
        'firstProjectShiftTabId',
        'projectWishes',
        'workTimeTarget'
    ],
    data() {
        return {
            show: false,
        }
    },
    methods: {usePage}
}
</script>
