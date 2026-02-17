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
        'crafts',
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
        'eventsWithTotalPlannedWorkingHours',
        'wholeWeekDatePeriod',
        'firstProjectShiftTabId'
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
                           :crafts="this.crafts"
                           :rooms="this.rooms"
                           :vacations="this.vacations"
                           :shift-qualifications="this.shiftQualifications"
                           :events-with-total-planned-working-hours="eventsWithTotalPlannedWorkingHours"
                           :whole-week-date-period="wholeWeekDatePeriod"
                           :first-project-shift-tab-id="firstProjectShiftTabId"
                           :user-to-edit-id="user_to_edit.id"
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
