<template>
    <div class="w-full flex flex-wrap">
        <CalendarFunctionBar @change-at-a-glance="changeAtAGlance" :at-a-glance="atAGlance"></CalendarFunctionBar>

        <!-- Calendar -->
        <div class="flex">
            <div v-if="eventsAtAGlance.length > 0" class="first:pl-16" v-for="roomEvents in eventsAtAGlance">
                <div class="w-52 py-3 border-r-4 border-secondaryHover bg-userBg">
                    <div class="flex calendarRoomHeader font-semibold items-center ml-4">
                        {{roomEvents[0].roomName}}
                    </div>

                </div>
                <div class="py-0.5 pr-1" v-for="event in roomEvents">
                    <SingleProjectCalendarEvent :event="event"/>
                </div>
            </div>
            <div v-else>
                <div class="pl-6 pb-12 mt-10 xsDark">
                    Keine Termine für dieses Projekt im gewählten Zeitraum
                </div>
            </div>

        </div>
    </div>

</template>

<script>

import CalendarFunctionBar from "@/Layouts/Components/CalendarFunctionBar.vue";
import SingleProjectCalendarEvent from "@/Layouts/Components/SingleProjectCalendarEvent.vue";



export default {
    name: "IndividualCalendarAtGlanceComponent",
    components: {
        CalendarFunctionBar,
        SingleProjectCalendarEvent

    },
    data() {
      return {

      }
    },
    props: ['eventsAtAGlance','atAGlance'],
    emits:['changeAtAGlance'],
    methods: {
        changeAtAGlance(atAGlance){
            this.$emit('changeAtAGlance', atAGlance)
        }
    }
}
</script>

<style scoped>

</style>
