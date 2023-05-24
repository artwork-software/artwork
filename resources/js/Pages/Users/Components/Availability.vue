<template>

    <div class="flex w-full">
        <div class="w-2/3">
            <UserAvailabilityCalendar :calendar-data="calendarData" :date-to-show="dateToShow" />
        </div>
        <div class="w-1/3">
            <UserVacations :user="user" :vacations="vacations" />
        </div>
    </div>

</template>

<script>
import {defineComponent} from 'vue'
import UserAvailabilityCalendar from "@/Pages/Users/Components/UserAvailabilityCalendar.vue";
import UserVacations from "@/Pages/Users/Components/UserVacations.vue";

export default defineComponent({
    name: "Availability",
    components: {UserVacations, UserAvailabilityCalendar},
    props:['calendarData','dateToShow','user', 'vacations'],
})
</script>

<style scoped>

</style>
