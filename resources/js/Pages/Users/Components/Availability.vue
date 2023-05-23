<template>

    <div class="flex w-full">
        <div class="w-2/3">
            <UserAvailabilityCalendar />
        </div>
        <div class="w-1/3">
            <UserVacations :user="user" />
        </div>
    </div>

</template>

<script>
import {defineComponent} from 'vue'
import UserAvailabilityCalendar from "@/Pages/Users/Components/UserAvailabilityCalendar.vue";
import UserVacations from "@/Pages/Users/Components/UserVacations.vue";

export default defineComponent({
    name: "Availability",
    props: ['user'],
    components: {UserVacations, UserAvailabilityCalendar}
})
</script>

<style scoped>

</style>
