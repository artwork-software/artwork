<template>
    <div class="flex h-full gap-2">
        <Timeline :time-line="timeLine" :event="event"/>
        <div class="w-[175px]" v-for="shift in shifts">
            <div class=" flex items-center justify-between px-4 text-white text-xs relative" :class="shift.employee_count === shift.number_employees ? 'bg-green-500' : 'bg-gray-500'">
                <div class="h-9 flex items-center">
                    {{shift.craft.abbreviation}} ({{ shift.employee_count }} / {{ shift.number_employees}})
                </div>
                <div v-if="!shift.break_minutes" class="h-9 bg-red-500 flex items-center w-fit p-4 absolute right-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12.21" height="12.2" viewBox="0 0 12.21 12.2">
                        <g id="Gruppe_1639" data-name="Gruppe 1639" transform="translate(-523.895 -44.9)" opacity="0.9">
                            <path id="Icon_metro-warning" data-name="Icon metro-warning" d="M8.571,3.015,13.6,13.037H3.542L8.571,3.015Zm0-1.087a.867.867,0,0,0-.713.523L2.735,12.66c-.392.7-.059,1.268.742,1.268H13.664c.8,0,1.134-.571.742-1.268h0L9.284,2.451A.867.867,0,0,0,8.571,1.928Zm.75,9.75a.75.75,0,1,1-.75-.75A.75.75,0,0,1,9.321,11.678Zm-.75-1.5a.75.75,0,0,1-.75-.75V7.178a.75.75,0,1,1,1.5,0v2.25A.75.75,0,0,1,8.571,10.178Z" transform="translate(521.429 43.072)" fill="#fcfcfb" stroke="#fcfcfb" stroke-width="0.2"/>
                        </g>
                    </svg>
                </div>
                <div v-if="shift.employee_count === shift.number_employees" class="h-9 flex items-center w-fit p-4 absolute right-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10.414" height="8.032" viewBox="0 0 10.414 8.032">
                        <path id="Pfad_1498" data-name="Pfad 1498" d="M-1151.25,4789.2l3.089,3.088,5.911-5.911" transform="translate(1151.957 -4785.674)" fill="none" stroke="#fcfcfb" stroke-width="2"/>
                    </svg>
                </div>
            </div>
            <div class="mt-1 h-[calc(100%-2.7rem)] bg-gray-200 p-4 max-h-96 overflow-x-scroll">
                <p class="text-xs mb-2">
                    {{ shift.start }} - {{ shift.end }}
                    <span v-if="shift.break_minutes">| {{ shift.break_formatted }}</span>
                </p>
                <p class="text-xs">{{ shift.description }}</p>
                <div v-for="user in shift.employees" class="flex items-center gap-2 p-1 hover:bg-gray-50/40 rounded cursor-pointer">
                    <img :src="user.profile_photo_url" class="h-4 w-4 rounded-full block bg-gray-500 object-cover">
                    <span class="text-xs">{{ user.full_name }}</span>
                </div>
                <div v-for="user in shift.empty_employee_count" class="flex items-center gap-2 p-1 hover:bg-gray-50/40 rounded cursor-pointer">
                    <span class="h-4 w-4 rounded-full block bg-gray-500"></span>
                    <span class="text-xs">Unbesetzt</span>
                </div>
            </div>
        </div>
        <!-- Empty -->
        <div class="w-[175px] flex items-center justify-center border-2 border-dashed" @click="showAddShiftModal = true">
            <PlusCircleIcon class="h-4 w-4 rounded-full bg-backgroundBlue" />
        </div>
    </div>

    <AddShiftModal :crafts="crafts" :event="event" v-if="showAddShiftModal" @closed="showAddShiftModal = false"/>
</template>
<script>
import {defineComponent} from 'vue'
import {PlusCircleIcon} from "@heroicons/vue/outline";
import Timeline from "@/Pages/Projects/Components/Timeline.vue";
import AddShiftModal from "@/Pages/Projects/Components/AddShiftModal.vue";
import dayjs from "dayjs";

export default defineComponent({
    name: "SingleShift",
    methods: {dayjs},
    props: ['timeLine', 'shifts', 'event', 'crafts'],
    components: {
        AddShiftModal,
        Timeline,
        PlusCircleIcon
    },
    data(){
        return {
            showAddShiftModal: false
        }
    },
})
</script>


<style scoped>

</style>
