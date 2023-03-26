<template>
    <div class="w-full flex flex-wrap">
        <CalendarFunctionBar @change-at-a-glance="changeAtAGlance" :at-a-glance="atAGlance"></CalendarFunctionBar>
        <!-- Calendar -->
        <table class="w-full flex flex-wrap">
            <thead class="w-full">
            <tr class=" w-full flex bg-userBg">
                <th class="w-16">

                </th>
                <th v-for="room in rooms" class="w-52 py-3 border-r-4 border-secondaryHover">
                    <div class="flex calendarRoomHeader font-semibold items-center ml-4">
                            {{ room.name }}
                    </div>
                </th>
            </tr>
            </thead>
            <tbody class="flex w-full pt-3 flex-wrap">
            <tr class="w-full h-36 flex" v-for="day in days">
                <th class="w-16 eventTime text-secondary text-right -mt-2 pr-1">
                    {{ day }}
                </th>
                <td class="w-52 h-36 overflow-y-auto border-t-2 border-dashed" v-for="room in calendarData">
                    <div class="py-0.5 pr-2" v-for="event in room[day].data">
                        <SingleCalendarEvent :event="event" :event-types="eventTypes"/>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

</template>

<script>
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import CalendarFunctionBar from "@/Layouts/Components/CalendarFunctionBar.vue";



export default {
    name: "IndividualCalendarComponent",
    components: {
        CalendarFunctionBar,
        SingleCalendarEvent,
        IndividualCalendarFilterComponent,
    },
    data() {
      return {

      }
    },
    props: ['calendarData', 'rooms', 'days','atAGlance', 'eventTypes'],
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
