<script>
import ShiftHeader from "@/Pages/Shifts/ShiftHeader.vue";
import UserShiftPlan from "@/Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue";
import Availability from "@/Pages/Users/Components/Availability.vue";

export default {
    name: "UserOperationPlan",
    components: {Availability, UserShiftPlan, ShiftHeader},
    props: [
        'user_to_edit',
        'currentTab',
        'dateValue',
        'daysWithEvents',
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
        'shiftQualifications'
    ]
}
</script>

<template>
    <ShiftHeader>
        <div class="mx-5">
            <UserShiftPlan type="user"
                           :totalPlannedWorkingHours="this.totalPlannedWorkingHours"
                           :weeklyWorkingHours="this.user_to_edit.weekly_working_hours"
                           :date-value="this.dateValue"
                           :days-with-events="this.daysWithEvents"
                           :projects="this.projects"
                           :event-types="this.eventTypes"
                           :rooms="this.rooms"
                           :vacations="this.vacations"
                           :shift-qualifications="this.shiftQualifications"
            />
            <Availability :availabilities="availabilities"
                          :showVacationsAndAvailabilitiesDate="showVacationsAndAvailabilitiesDate"
                          type="user"
                          :calendar-data="calendarData"
                          :vacationSelectCalendar="vacationSelectCalendar"
                          :createShowDate="createShowDate"
                          :date-to-show="dateToShow"
                          :user="user_to_edit"
                          :vacations="vacations"
            />
        </div>
    </ShiftHeader>
</template>

<style scoped>

</style>
