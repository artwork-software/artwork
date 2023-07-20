<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="user_to_edit">
        <UserShiftPlan :weeklyWorkingHours="this.user_to_edit.weekly_working_hours" :date-value="dateValue"
                       :days-with-events="daysWithEvents"
                       :projects="projects" :event-types="eventTypes" :rooms="rooms"
                       :vacations="vacations"></UserShiftPlan>
        <Availability :calendar-data="calendarData" :date-to-show="dateToShow"
                      :user="user_to_edit" :vacations="vacations"/>
    </UserEditHeader>
    <BaseSidenav :show="show" @toggle="this.show =! this.show">
        // Here User Sidenav
    </BaseSidenav>
</template>

<script>

import AppLayout from "@/Layouts/AppLayout.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import UserInfoTab from "@/Pages/Users/Tabs/UserInfoTab.vue";
import Availability from "@/Pages/Users/Components/Availability.vue";
import UserShiftPlan from "@/Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue";
import UserAvailabilityCalendar from "@/Pages/Users/Components/UserAvailabilityCalendar.vue";

export default {
    components: {
        UserAvailabilityCalendar,
        UserShiftPlan,
        Availability,
        UserInfoTab,
        UserEditHeader,
        BaseSidenav,
        AppLayout
    },
    props: [
        'user_to_edit',
        'currentTab',
        'dateValue',
        'daysWithEvents',
        'projects',
        'eventTypes',
        'rooms',
        'vacations',
        'calendarData',
        'dateToShow',
        'shifts'
    ],
    data() {
        return {
            show: false,
        }
    },
    mounted() {
        this.show = true;
        setTimeout(() => {
            this.show = false;
        }, 1000)
    },
    methods: {}
}
</script>


<style scoped>

</style>
