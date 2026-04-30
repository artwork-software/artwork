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
    <div class="grid grid-cols-12 w-full mb-20 items-start">
        <div ref="calendarCol" class="col-span-7">
            <UserAvailabilityCalendar :showVacationsAndAvailabilitiesDate="showVacationsAndAvailabilitiesDate" :calendar-data="calendarData" :date-to-show="dateToShow" />
        </div>
        <div class="col-span-1">

        </div>
        <div class="col-span-4 mt-12 overflow-y-auto" :style="{ maxHeight: calendarHeight + 'px' }">
            <UserVacations :availabilities="availabilities" :vacationSelectCalendar="vacationSelectCalendar" :createShowDate="createShowDate" :type="type" :user="user" :vacations="vacations" :showVacationsAndAvailabilitiesDate="showVacationsAndAvailabilitiesDate" />
        </div>
    </div>
</template>

<script>
import {defineComponent, ref, onMounted, onBeforeUnmount} from 'vue'
import UserAvailabilityCalendar from "@/Pages/Users/Components/UserAvailabilityCalendar.vue";
import UserVacations from "@/Pages/Users/Components/UserVacations.vue";
import TemporarilyHired from "@/Pages/Users/Components/TemporarilyHired.vue";
import Permissions from "@/Mixins/Permissions.vue";
import dayjs from "dayjs";
import {usePage} from "@inertiajs/vue3";

export default defineComponent({
    name: "Availability",
    methods: {usePage},
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
    setup() {
        const calendarCol = ref(null)
        const calendarHeight = ref(500)
        let observer = null

        onMounted(() => {
            if (calendarCol.value) {
                calendarHeight.value = calendarCol.value.offsetHeight
                observer = new ResizeObserver((entries) => {
                    for (const entry of entries) {
                        calendarHeight.value = entry.target.offsetHeight
                    }
                })
                observer.observe(calendarCol.value)
            }
        })

        onBeforeUnmount(() => {
            observer?.disconnect()
        })

        return { calendarCol, calendarHeight }
    },
})
</script>
