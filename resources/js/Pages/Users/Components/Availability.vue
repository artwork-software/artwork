<template>
    <div class="grid grid-cols-12 w-full">
        <div class="col-span-7">
            <h3 class="headline2 mb-6">{{ $t('Availability')}}</h3>
            <div class="mb-10" v-if="type !== 'freelancer'">
                <TemporarilyHired :user="user" v-if="$can('can manage workers') || hasAdminRole()" />
                <div v-if="user.temporary && user.employStart && user.employEnd">
                    {{ $t('Temporarily employed') }}: {{ dayjs(user.employStart).format('DD.MM.YYYY') }} - {{ dayjs(user.employEnd).format('DD.MM.YYYY') }}
                </div>
            </div>
        </div>
        <div class="col-span-1">

        </div>
        <div class="col-span-4 mt-12">
        </div>
    </div>
    <div class="grid grid-cols-12 w-full mb-20">
        <div class="col-span-7">
            <UserAvailabilityCalendar :showVacationsAndAvailabilitiesDate="showVacationsAndAvailabilitiesDate" :calendar-data="calendarData" :date-to-show="dateToShow" />
        </div>
        <div class="col-span-1">

        </div>
        <div class="col-span-4 mt-12">
            <UserVacations :availabilities="availabilities" :vacationSelectCalendar="vacationSelectCalendar" :createShowDate="createShowDate" :type="type" :user="user" :vacations="vacations" />
        </div>
    </div>
    <div class="grid grid-cols-7">
        <div v-for="day in vacationSelectCalendar" :key="day.date" class="day" :class="{ 'today': day.isToday, 'not-in-month': !day.inMonth }">
            {{ day.day }}
        </div>
    </div>
</template>

<script>
import {defineComponent} from 'vue'
import UserAvailabilityCalendar from "@/Pages/Users/Components/UserAvailabilityCalendar.vue";
import UserVacations from "@/Pages/Users/Components/UserVacations.vue";
import TemporarilyHired from "@/Pages/Users/Components/TemporarilyHired.vue";
import Permissions from "@/Mixins/Permissions.vue";
import dayjs from "dayjs";

export default defineComponent({
    name: "Availability",
    computed: {
        dayjs() {
            return dayjs
        }
    },
    mixins: [Permissions],
    components: {TemporarilyHired, UserVacations, UserAvailabilityCalendar},
    props: [
        'calendarData',
        'dateToShow',
        'user',
        'vacations',
        'type',
        'vacationSelectCalendar',
        'createShowDate',
        'showVacationsAndAvailabilitiesDate',
        'availabilities'
    ],
})
</script>
