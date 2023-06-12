<template>
    <div>
        <div>
            <div>
                <UserShiftPlanFunctionBar @previousTimeRange="previousTimeRange"
                                          @next-time-range="nextTimeRange" :dateValue="dateValue"></UserShiftPlanFunctionBar>
            </div>
            <div class="grid-cols-7 grid">
                <div class="col-span-1 h-48" v-for="day in daysWithEvents">
                    <div>
                        {{day.day_string}}.{{day.day}}
                    </div>
                    <div v-for="event in day.events">
                        <SingleShiftPlanEvent :eventType="this.findEventTypeById(event.event_type_id)"
                                              :project="this.findProjectById(event.projectId)" :event="event" ></SingleShiftPlanEvent>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {defineComponent} from 'vue'
import UserAvailabilityCalendar from "@/Pages/Users/Components/UserAvailabilityCalendar.vue";
import UserVacations from "@/Pages/Users/Components/UserVacations.vue";
import TemporarilyHired from "@/Pages/Users/Components/TemporarilyHired.vue";
import Permissions from "@/mixins/Permissions.vue";
import dayjs from "dayjs";
import UserShiftPlanFunctionBar from "@/Layouts/Components/ShiftPlanComponents/UserShiftPlanFunctionBar.vue";
import ShiftPlanFunctionBar from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFunctionBar.vue";
import {Inertia} from "@inertiajs/inertia";
import SingleShiftPlanEvent from "@/Layouts/Components/ShiftPlanComponents/SingleShiftPlanEvent.vue";

export default {
    name: "UserShiftPlan",
    mixins: [Permissions],
    components: {
        SingleShiftPlanEvent,
        ShiftPlanFunctionBar,
        UserShiftPlanFunctionBar
    },
    props:['daysWithEvents','dateValue','projects','eventTypes'],
    methods:{
        previousTimeRange() {
            const dayDifference = this.calculateDateDifference();
            this.dateValue[1] = this.getPreviousDay(this.dateValue[0]);
            const newDate = new Date(this.dateValue[1]);
            newDate.setDate(newDate.getDate() - dayDifference);
            this.dateValue[0] = newDate.toISOString().slice(0, 10);
            this.updateTimes();
        },
        nextTimeRange() {
            const dayDifference = this.calculateDateDifference();
            this.dateValue[0] = this.getNextDay(this.dateValue[1]);
            const newDate = new Date(this.dateValue[1]);
            newDate.setDate(newDate.getDate() + dayDifference + 1);
            this.dateValue[1] = newDate.toISOString().slice(0, 10);
            this.updateTimes();
        },
        calculateDateDifference() {
            const date1 = new Date(this.dateValue[0]);
            const date2 = new Date(this.dateValue[1]);
            const timeDifference = date2.getTime() - date1.getTime();
            return timeDifference / (1000 * 3600 * 24);
        },
        getNextDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() + 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        getPreviousDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() - 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        findProjectById(projectId) {
            return this.projects.find(project => project.id === projectId);
        },
        findEventTypeById(eventTypeId) {
            return this.eventTypes.find(eventType => eventType.id === eventTypeId);
        },
        updateTimes() {
            Inertia.reload({
                data: {
                    startDate: this.dateValue[0],
                    endDate: this.dateValue[1],
                }
            })
        },
    }
}
</script>

<style scoped>

</style>
