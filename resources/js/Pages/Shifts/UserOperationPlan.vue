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
        'crafts',
        'vacations',
        'calendarData',
        'dateToShow',
        'vacationSelectCalendar',
        'createShowDate',
        'showVacationsAndAvailabilitiesDate',
        'availabilities',
        'firstProjectShiftTabId',
        'workTimeTarget'
    ]
}
</script>

<template>
    <ShiftHeader :title="$t('My Operational plan')">
        <div class="mx-5">
            <UserShiftPlan type="user"
                           :totalPlannedWorkingHours="this.totalPlannedWorkingHours"
                           :work-time-target="workTimeTarget"
                           :date-value="this.dateValue"
                           :crafts="this.crafts"
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
