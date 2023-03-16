<template>
    <pre>
        {{ calendarData }}
    </pre>
    <pre>
        {{ rooms }}
    </pre>
    <div class="w-full flex flex-wrap">
        <table class="w-full flex flex-wrap">
            <thead class="w-full ml-24">
            <tr class=" w-full">
                <th v-for="(room,index) in rooms" class=" w-full bg-gray-300">
                    <div class="flex items-center w-64 ">
                        <div>
                            {{ room.name }}
                        </div>
                    </div>
                </th>
            </tr>
            </thead>
            <tbody class="flex w-full flex-wrap">
            <tr class="w-full h-52  flex" v-for="day in days">
                <th class="w-24 eventTime text-secondary text-right mt-1">
                    {{ day }}
                </th>
                <td class="w-64 h-52 overflow-y-auto" v-for="room in calendarData">
                    <div class="p-1" v-for="event in room[day].data">
                        <SingleCalendarEvent :event="event"/>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

</template>

<script>
import Button from "@/Jetstream/Button";
import {PlusCircleIcon} from '@heroicons/vue/outline'
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";


export default {
    name: "AddButton",
    components: {
        Button,
        PlusCircleIcon,
        SingleCalendarEvent
    },
    props: ['calendarData', 'rooms', 'days'],
}
</script>

<style scoped>

</style>
