<template>
    <div class="w-full flex flex-wrap">
        <CalendarFunctionBar @change-calendar-type="changeCalendarType" :dateValue="dateValue" @change-at-a-glance="changeAtAGlance" :at-a-glance="atAGlance"></CalendarFunctionBar>
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
                <td class="w-52 h-36 cell overflow-y-auto border-t-2 border-dashed" v-for="room in calendarData">
                    <div class="py-0.5" v-for="event in room[day].data">
                        <SingleCalendarEvent :event="event"/>
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
    props: ['calendarData', 'rooms', 'days','atAGlance','dateValue'],
    emits:['changeAtAGlance','changeCalendarType'],
    methods: {
        changeAtAGlance(atAGlance){
            this.$emit('changeAtAGlance', atAGlance)
        },
        changeCalendarType(){
            this.$emit('changeCalendarType')
        }
    }
}
</script>

<style scoped>

/* this only works in some browsers but is wanted by the client */
.cell{
    overflow: overlay;
}
::-webkit-scrollbar {
    width: 16px;
}
::-webkit-scrollbar-track {
    background-color: transparent;
}
::-webkit-scrollbar-thumb {
    background-color: #A7A6B170;
    border-radius: 16px;
    border: 6px solid transparent;
    background-clip: content-box;
}
::-webkit-scrollbar-thumb:hover {
    background-color: #a8bbbf;
}
</style>
