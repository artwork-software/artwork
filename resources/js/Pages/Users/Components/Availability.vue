<template>

    <div class="grid grid-cols-12 w-full">
        <div class="col-span-7">
            <UserAvailabilityCalendar :calendar-data="calendarData" :date-to-show="dateToShow" />
        </div>
        <div class="col-span-1">

        </div>
        <div class="col-span-4 mt-12">
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
