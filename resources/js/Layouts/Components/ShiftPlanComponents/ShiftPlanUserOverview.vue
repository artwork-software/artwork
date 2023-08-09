<template>
    <div class="w-full fixed bottom-0 -ml-14">
        <div class="flex justify-center " @click="showCloseUserOverview">
            <div class="block h-5 w-8 bg-primary flex justify-center items-center cursor-pointer">
                <div :class="showUserOverview ? 'rotate-180' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14.123" height="6.519" viewBox="0 0 14.123 6.519">
                        <g id="Gruppe_1608" data-name="Gruppe 1608" transform="translate(-275.125 870.166) rotate(-90)">
                            <path id="Pfad_1313" data-name="Pfad 1313" d="M0,0,6.814,3.882,13.628,0" transform="translate(865.708 289) rotate(-90)" fill="none" stroke="#a7a6b1" stroke-width="1"/>
                            <path id="Pfad_1314" data-name="Pfad 1314" d="M0,0,4.4,2.509,8.809,0" transform="translate(864.081 286.591) rotate(-90)" fill="none" stroke="#a7a6b1" stroke-width="1"/>
                        </g>
                    </svg>
                </div>
            </div>
        </div>
        <div class="w-full h-[40vh] bg-primary overflow-x-scroll overflow-y-scroll" v-if="showUserOverview">
            <table class="w-full text-white relative">
                <!-- Outer Div is needed for Safari to apply Stickyness to Header -->
                <div>
                    <tr class="flex w-full">
                        <th class="w-56"></th>
                        <th v-for="day in days" class="flex w-48 p-3 h-16 items-center">
                            <div class="flex calendarRoomHeader font-semibold">
                                {{ day.day_string }} {{ day.day }}
                            </div>
                        </th>
                    </tr>
                    <tbody class="w-full pt-3">
                    <tr v-for="(user,index) in users" class="w-full flex">
                        <th class=" flex items-center text-right -mt-2 pr-1 w-56" :class="index % 2 === 0 ? '' : ''">
                            <DragElement  :item="user.element" :type="user.type" />
                        </th>
                        <td v-for="day in days">
                            <div class="w-48 h-12 p-2 bg-gray-50/10 text-white text-xs rounded-lg shiftCell cursor-pointer" @click="openShowUserShiftModal(user.element, day)">
                                <span v-for="shift in user.element?.shifts[day.full_day]">
                                    {{ shift.start }} - {{ shift.end }} {{ shift.event.room?.name }},
                                </span>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </div>
            </table>
        </div>
    </div>

    <show-user-shifts-modal v-if="showUserShifts" @closed="showUserShifts = false" :user="userToShow" :day="dayToShow" :projects="projects"/>

</template>

<script>
import {defineComponent} from 'vue'
import {Link} from "@inertiajs/inertia-vue3";
import SingleShiftPlanEvent from "@/Layouts/Components/ShiftPlanComponents/SingleShiftPlanEvent.vue";
import DragElement from "@/Pages/Projects/Components/DragElement.vue";
import ShowUserShiftsModal from "@/Pages/Shifts/Components/showUserShiftsModal.vue";

export default defineComponent({
    name: "ShiftPlanUserOverview",
    components: {ShowUserShiftsModal, DragElement, SingleShiftPlanEvent, Link},
    props: ['users', 'days', 'projects'],
    data(){
        return {
            showUserOverview: true,
            showUserShifts: false,
            userToShow: null,
            dayToShow: null,
        }
    },
    emits: ['isOpen'],
    methods: {
        showCloseUserOverview(){
            this.showUserOverview = !this.showUserOverview
            //this.$emit('isOpen', this.showUserOverview)
        },
        openShowUserShiftModal(user, day){
            this.userToShow = user
            this.dayToShow = day
            this.showUserShifts = true
        }
    },

    computed: {
        filteredUsers(){
            if(!this.showExtern && !this.showIntern && !this.showProvider){
                return this.users;
            }
        }
    },

})
</script>

<style scoped>

.shiftUsers {
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
