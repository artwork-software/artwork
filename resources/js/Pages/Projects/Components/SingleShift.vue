<template>
    <div class="flex h-full gap-2">
        <Timeline :time-line="timeLine" :event="event"/>
        <div class="w-[175px]" v-for="shift in shifts">
            <div class=" flex items-center justify-between px-4 text-white text-xs relative" :class="shift.user_count === shift.number_employees && shift.master_count === shift.number_masters ? 'bg-green-500' : 'bg-gray-500'">
                <div class="h-9 flex items-center">
                    {{shift.craft.abbreviation}} ({{ shift.user_count }} / {{ shift.number_employees}})
                    <span v-if="shift.number_masters > 0">
                        ({{ shift.master_count }} / {{ shift.number_masters }})
                    </span>
                </div>
                <div class="absolute flex items-center right-0">
                    <div v-if="shift.user_count === shift.number_employees" class="h-9 flex items-center w-fit right-0 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10.414" height="8.032" viewBox="0 0 10.414 8.032">
                            <path id="Pfad_1498" data-name="Pfad 1498" d="M-1151.25,4789.2l3.089,3.088,5.911-5.911" transform="translate(1151.957 -4785.674)" fill="none" stroke="#fcfcfb" stroke-width="2"/>
                        </svg>
                    </div>
                    <div v-if="!shift.break_minutes" class="h-9 bg-red-500 flex items-center w-fit right-0 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12.21" height="12.2" viewBox="0 0 12.21 12.2">
                            <g id="Gruppe_1639" data-name="Gruppe 1639" transform="translate(-523.895 -44.9)" opacity="0.9">
                                <path id="Icon_metro-warning" data-name="Icon metro-warning" d="M8.571,3.015,13.6,13.037H3.542L8.571,3.015Zm0-1.087a.867.867,0,0,0-.713.523L2.735,12.66c-.392.7-.059,1.268.742,1.268H13.664c.8,0,1.134-.571.742-1.268h0L9.284,2.451A.867.867,0,0,0,8.571,1.928Zm.75,9.75a.75.75,0,1,1-.75-.75A.75.75,0,0,1,9.321,11.678Zm-.75-1.5a.75.75,0,0,1-.75-.75V7.178a.75.75,0,1,1,1.5,0v2.25A.75.75,0,0,1,8.571,10.178Z" transform="translate(521.429 43.072)" fill="#fcfcfb" stroke="#fcfcfb" stroke-width="0.2"/>
                            </g>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="mt-1 h-[calc(100%-2.7rem)] bg-gray-200 p-1 max-h-96 overflow-x-scroll">
                <p class="text-xs mb-1">
                    {{ shift.start }} - {{ shift.end }}
                    <span v-if="shift.break_minutes">| {{ shift.break_formatted }}</span>
                </p>
                <p class="text-xs mb-3">{{ shift.description }}</p>
                <div v-for="user in shift.empty_master_count">
                    <DropElement :users="shift.users"  :shift-id="shift.id" :master="true"/>
                </div>
                <div v-for="user in shift.users" class="">
                    <div class="flex items-center justify-between p-1 hover:bg-gray-50/40 rounded cursor-pointer group">
                        <div class="flex gap-2 items-center">
                            <img :src="user.profile_photo_url" class="h-4 w-4 rounded-full block bg-gray-500 object-cover">
                            <span class="text-xs">{{ user.full_name }}</span>
                            <span v-if="user.pivot.is_master">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13.2" height="10.8" viewBox="0 0 13.2 10.8">
                                  <path id="Icon_awesome-crown" data-name="Icon awesome-crown" d="M9.9,8.4H2.1a.3.3,0,0,0-.3.3v.6a.3.3,0,0,0,.3.3H9.9a.3.3,0,0,0,.3-.3V8.7A.3.3,0,0,0,9.9,8.4Zm1.2-6a.9.9,0,0,0-.9.9.882.882,0,0,0,.083.371l-1.358.814A.6.6,0,0,1,8.1,4.268L6.568,1.594a.9.9,0,1,0-1.136,0L3.9,4.267a.6.6,0,0,1-.829.218L1.719,3.671A.9.9,0,1,0,.9,4.2a.919.919,0,0,0,.144-.015L2.4,7.8H9.6l1.356-3.615A.919.919,0,0,0,11.1,4.2a.9.9,0,0,0,0-1.8Z" transform="translate(0.6 0.6)" fill="none" stroke="#82818a" stroke-width="1.2"/>
                                </svg>
                            </span>
                        </div>
                        <div class="hidden group-hover:block bg-buttonBlue rounded-full p-0.5" @click="removeUserFromShift(user.id, shift.id)">
                            <XIcon class="h-3 w-3 text-white" />
                        </div>
                    </div>
                </div>
                <div v-for="user in shift.empty_user_count ? shift.empty_user_count : 0">
                    <DropElement :users="shift.users" :shift-id="shift.id" :master="false"/>
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
import DropElement from "@/Pages/Projects/Components/DropElement.vue";
import {XIcon} from "@heroicons/vue/solid";

export default defineComponent({
    name: "SingleShift",

    props: ['timeLine', 'shifts', 'event', 'crafts'],
    components: {
        DropElement,
        AddShiftModal,
        Timeline,
        PlusCircleIcon,
        XIcon
    },
    data(){
        return {
            showAddShiftModal: false
        }
    },
    methods: {
        dayjs,
        removeUserFromShift(user_id, shift_id){
            this.$inertia.delete(route('shifts.removeUser', shift_id),{
                data: {
                    user_id: user_id
                },
                preserveScroll: true,
            });
        },
    },
})
</script>


<style scoped>

</style>
