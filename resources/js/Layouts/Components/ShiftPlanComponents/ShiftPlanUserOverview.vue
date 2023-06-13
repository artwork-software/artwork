<template>
    <div class="w-full fixed bottom-0">
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
                        <th class="w-40"></th>
                        <th v-for="day in days" class="flex w-64 h-16 items-center">
                            <div class="flex calendarRoomHeader font-semibold">
                                {{ day.day_string }} {{ day.day }}
                            </div>
                        </th>
                    </tr>
                    <tbody class="w-full pt-3">
                    <tr v-for="(user,index) in users" class="w-full flex">
                        <th class=" flex items-center text-right -mt-2 pr-1 w-40" :class="index % 2 === 0 ? '' : ''">
                            <DragElement :item="user" />
                        </th>
                        <td v-for="day in days">
                            <div class="w-64 h-10 p-2 bg-gray-50/10 text-white text-xs rounded-lg overflow-x-scroll">
                                <span v-for="shift in user.shifts[day.full_day]">
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


</template>

<script>
import {defineComponent} from 'vue'
import {Link} from "@inertiajs/inertia-vue3";
import SingleShiftPlanEvent from "@/Layouts/Components/ShiftPlanComponents/SingleShiftPlanEvent.vue";
import DragElement from "@/Pages/Projects/Components/DragElement.vue";

export default defineComponent({
    name: "ShiftPlanUserOverview",
    components: {DragElement, SingleShiftPlanEvent, Link},
    props: ['users', 'days'],
    data(){
        return {
            showUserOverview: false
        }
    },
    emits: ['isOpen'],
    methods: {
        showCloseUserOverview(){
            this.showUserOverview = !this.showUserOverview
            this.$emit('isOpen', this.showUserOverview)
        },
    }

})
</script>

<style scoped>

</style>
