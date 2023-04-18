<template>
    <app-layout>
        <div class="max-w-screen-lg mt-12 flex flex-row ml-14 mr-40">
            <div class="flex flex-1 flex-wrap">
                <div class="w-full flex justify-between">
                    <div class="flex flex-wrap">
                        <h2 class="headline1">Raumbelegungen</h2>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div v-if="calendarType && calendarType === 'daily'">
                <div class="mr-4">
                    <CalendarComponent :selected-date="selectedDate" :dateValue="dateValue" :eventTypes=this.eventTypes initial-view="day"/>
                </div>
            </div>
            <div v-else>
                <IndividualCalendarAtGlanceComponent :dateValue="dateValue" v-if="atAGlance"
                                                     @change-at-a-glance="changeAtAGlance"
                                                     :atAGlance="this.atAGlance" :eventTypes=this.eventTypes
                                                     :rooms="rooms"
                                                     :eventsAtAGlance="eventsAtAGlance"></IndividualCalendarAtGlanceComponent>
                <IndividualCalendarComponent :dateValue="dateValue" v-else @change-at-a-glance="changeAtAGlance"
                                             :atAGlance="this.atAGlance" :eventTypes=this.eventTypes
                                             :calendarData="calendar" :rooms="rooms" :days="days"/>
            </div>
        </div>
    </app-layout>
</template>
<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import '@vuepic/vue-datepicker/dist/main.css'
import CalendarComponent from "@/Layouts/Components/CalendarComponent";
import IndividualCalendarComponent from "@/Layouts/Components/IndividualCalendarComponent.vue";
import IndividualCalendarAtGlanceComponent from "@/Layouts/Components/IndividualCalendarAtGlanceComponent.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import {Inertia} from "@inertiajs/inertia";

export default defineComponent({
    components: {
        IndividualCalendarAtGlanceComponent,
        IndividualCalendarComponent,
        CalendarComponent,
        AppLayout
    },
    props: ['eventTypes','calendarType','selectedDate','dateValue','calendar','rooms','days','eventsAtAGlance'],
    methods: {
        changeAtAGlance() {
            this.atAGlance = !this.atAGlance;
        }
    },
    data() {
        return {
            atAGlance: this.eventsAtAGlance.length > 0,
        }
    },
    watch: {
        atAGlance: {
            handler() {
                Inertia.reload({
                    data: {
                        atAGlance: this.atAGlance,
                    }
                })
            },
            deep: true
        }
    },
})
</script>
