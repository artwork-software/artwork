<template>
    <div class="h-full" :class="highlight">
    <div class=" flex items-center justify-between px-4 text-white text-xs relative"
         :class="[shift.user_count === shift.number_employees && Math.floor(shift.master_count) === shift.number_masters ? 'bg-green-500' : 'bg-gray-500', anyoneHasVacation ? '!bg-red-500' : '']">
        <div class="h-9 flex items-center">
            {{ shift.craft.abbreviation }} ({{ decimalToCommonFraction(shift.user_count) }}/{{ shift.number_employees }})
            <span class="ml-1" v-if="shift.number_masters > 0">
                 ({{ decimalToCommonFraction(shift.master_count) }}/{{ shift.number_masters }})
            </span>
        </div>

        <div class="absolute flex items-center right-0">
            <div
                v-if="shift.user_count === shift.number_employees && Math.floor(shift.master_count) === shift.number_masters"
                class="h-9 flex items-center w-fit right-0 p-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="10.414" height="8.032" viewBox="0 0 10.414 8.032">
                    <path id="Pfad_1498" data-name="Pfad 1498" d="M-1151.25,4789.2l3.089,3.088,5.911-5.911"
                          transform="translate(1151.957 -4785.674)" fill="none" stroke="#fcfcfb" stroke-width="2"/>
                </svg>
            </div>
            <div v-if="shift.infringement || anyoneHasVacation" class="h-9 bg-red-500 flex items-center w-fit right-0 p-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="12.21" height="12.2" viewBox="0 0 12.21 12.2">
                    <g id="Gruppe_1639" data-name="Gruppe 1639" transform="translate(-523.895 -44.9)" opacity="0.9">
                        <path id="Icon_metro-warning" data-name="Icon metro-warning"
                              d="M8.571,3.015,13.6,13.037H3.542L8.571,3.015Zm0-1.087a.867.867,0,0,0-.713.523L2.735,12.66c-.392.7-.059,1.268.742,1.268H13.664c.8,0,1.134-.571.742-1.268h0L9.284,2.451A.867.867,0,0,0,8.571,1.928Zm.75,9.75a.75.75,0,1,1-.75-.75A.75.75,0,0,1,9.321,11.678Zm-.75-1.5a.75.75,0,0,1-.75-.75V7.178a.75.75,0,1,1,1.5,0v2.25A.75.75,0,0,1,8.571,10.178Z"
                              transform="translate(521.429 43.072)" fill="#fcfcfb" stroke="#fcfcfb" stroke-width="0.2"/>
                    </g>
                </svg>
            </div>
            <div>
                <Menu as="div" class="relative">
                    <div class="flex p-0.5 rounded-full">
                        <MenuButton
                            class="flex p-0.5 rounded-full">
                            <DotsVerticalIcon
                                class=" flex-shrink-0 h-4 w-4 my-auto"
                                aria-hidden="true"/>
                        </MenuButton>

                    </div>
                    <transition enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                        <MenuItems
                            class="origin-top-right z-100 absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                            <div class="py-1">
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="editShift"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <DuplicateIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Bearbeiten
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="clearEmployeesAndMaster(shift.id)"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Leeren
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="deleteShift(shift.id)"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Löschen
                                    </a>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
        </div>
    </div>
    <div class="mt-1 h-[calc(100%-2.7rem)] bg-gray-200 p-1 max-h-96">
        <p class="text-xs mb-1">
            {{ shift.start }} - {{ shift.end }}
            <span v-if="shift.break_minutes">| {{ shift.break_formatted }}</span>
        </p>
        <p class="text-xs mb-3">{{ shift.description }}</p>
        <div v-for="user in shift?.allUsers" class="">
            <div v-for="intern in user">
                <div v-if="intern.full_name"
                     class="flex items-center justify-between p-1 hover:bg-gray-50/40 rounded cursor-pointer group">
                    <div class="flex gap-2 items-center">
                        <img :src="intern.profile_photo_url"
                             class="h-4 w-4 rounded-full block bg-gray-500 object-cover" :class="intern?.formatted_vacation_days?.includes(this.shift.event_start_day) ? 'ring-2 ring-red-500' : ''">
                        <span class="text-xs" :class="intern?.formatted_vacation_days?.includes(this.shift.event_start_day) ? '!text-red-500' : ''">{{ intern.full_name }} </span>
                        <span v-if="intern.pivot.shift_count > 1"
                              class="text-xs"> {{ `1/${intern.pivot.shift_count}` }} </span>
                        <span v-if="intern.pivot.is_master">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13.2" height="10.8"
                                     viewBox="0 0 13.2 10.8">
                                  <path id="Icon_awesome-crown" data-name="Icon awesome-crown"
                                        d="M9.9,8.4H2.1a.3.3,0,0,0-.3.3v.6a.3.3,0,0,0,.3.3H9.9a.3.3,0,0,0,.3-.3V8.7A.3.3,0,0,0,9.9,8.4Zm1.2-6a.9.9,0,0,0-.9.9.882.882,0,0,0,.083.371l-1.358.814A.6.6,0,0,1,8.1,4.268L6.568,1.594a.9.9,0,1,0-1.136,0L3.9,4.267a.6.6,0,0,1-.829.218L1.719,3.671A.9.9,0,1,0,.9,4.2a.919.919,0,0,0,.144-.015L2.4,7.8H9.6l1.356-3.615A.919.919,0,0,0,11.1,4.2a.9.9,0,0,0,0-1.8Z"
                                        transform="translate(0.6 0.6)" fill="none" stroke="#82818a" stroke-width="1.2"/>
                                </svg>
                            </span>
                    </div>
                    <div class="hidden group-hover:block"
                         @click="openDeleteUserModal(intern.id, shift.id, 'user')">
                        <SvgCollection svg-name="xMarkIcon"/>
                    </div>
                </div>
                <div v-else
                     class="flex items-center justify-between p-1 hover:bg-gray-50/40 rounded cursor-pointer group">
                    <div class="flex gap-2 items-center">
                        <img :src="intern.profile_image" class="h-4 w-4 rounded-full block bg-gray-500 object-cover">
                        <span class="text-xs">{{ intern.name }}</span>
                        <span v-if="intern.pivot.shift_count > 1"
                              class="text-xs"> {{ `1/${intern.pivot.shift_count}` }} </span>

                        <span v-if="intern.pivot.is_master">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13.2" height="10.8"
                                     viewBox="0 0 13.2 10.8">
                                  <path id="Icon_awesome-crown" data-name="Icon awesome-crown"
                                        d="M9.9,8.4H2.1a.3.3,0,0,0-.3.3v.6a.3.3,0,0,0,.3.3H9.9a.3.3,0,0,0,.3-.3V8.7A.3.3,0,0,0,9.9,8.4Zm1.2-6a.9.9,0,0,0-.9.9.882.882,0,0,0,.083.371l-1.358.814A.6.6,0,0,1,8.1,4.268L6.568,1.594a.9.9,0,1,0-1.136,0L3.9,4.267a.6.6,0,0,1-.829.218L1.719,3.671A.9.9,0,1,0,.9,4.2a.919.919,0,0,0,.144-.015L2.4,7.8H9.6l1.356-3.615A.919.919,0,0,0,11.1,4.2a.9.9,0,0,0,0-1.8Z"
                                        transform="translate(0.6 0.6)" fill="none" stroke="#82818a" stroke-width="1.2"/>
                                </svg>
                            </span>
                    </div>
                    <div v-if="intern.full_name" class="hidden group-hover:block"
                         @click="openDeleteUserModal(intern.id, shift.id, 'user')">
                        <SvgCollection svg-name="xMarkIcon"/>
                    </div>
                    <div v-if="intern.provider_name" class="hidden group-hover:block"
                         @click="openDeleteUserModal(intern.id, shift.id, 'service_provider')">
                        <SvgCollection svg-name="xMarkIcon"/>
                    </div>
                    <div v-if="intern.name && !intern.provider_name"
                         class="hidden group-hover:block"
                         @click="openDeleteUserModal(intern.id, shift.id, 'freelancer')">
                        <SvgCollection svg-name="xMarkIcon"/>
                    </div>
                </div>
            </div>
        </div>
        <div v-for="user in Math.floor(shift.empty_master_count)">
            <MasterDropElement @dropFeedback="dropFeedback" :craft-id="shift.craft.id" :users="shift.users" :shift-id="shift.id" :currentCount="shift.currentCount"
                         :maxCount="shift.maxCount" :free-employee-count="shift.empty_user_count"
                         :free-master-count="shift.empty_master_count" :userIds="shiftUserIds" :master="true"
                         :is_series="event.is_series"/>
        </div>
        <div v-for="user in Math.floor(shift.empty_user_count) ? Math.floor(shift.empty_user_count) : 0">
            <EmployeeDropElement @dropFeedback="dropFeedback" :craft-id="shift.craft.id" :users="shift.allUsers" :shift-id="shift.id" :currentCount="shift.currentCount"
                                 :maxCount="shift.maxCount" :free-employee-count="shift.empty_user_count"
                                 :free-master-count="shift.empty_master_count" :userIds="shiftUserIds" :master="false"
                                 :is_series="event.is_series"/>
        </div>
    </div>
    </div>

    <AddShiftModal v-if="openEditShiftModal"
                   :shift="shift"
                   :event="event"
                   :crafts="crafts"
                   @closed="openEditShiftModal = false"
                   :currentUserCrafts="currentUserCrafts"
                   :edit="true"/>

    <ChooseDeleteUserShiftModal :buffer="buffer" :event="event" v-if="showDeleteUserModal"
                                @close-modal="showDeleteUserModal = false" @returnBuffer="deleteUser"/>
</template>
<script>
import {defineComponent} from 'vue'
import {XIcon} from "@heroicons/vue/solid";
import DropElement from "@/Pages/Projects/Components/DropElement.vue";
import dayjs from "dayjs";
import {DotsVerticalIcon, DuplicateIcon, PencilAltIcon, TrashIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import AddShiftModal from "@/Pages/Projects/Components/AddShiftModal.vue";
import ChooseDeleteUserShiftModal from "@/Pages/Projects/Components/ChooseDeleteUserShiftModal.vue";
import Helper from "@/mixins/Helper.vue";
import EmployeeDropElement from "@/Pages/Projects/Components/EmployeeDropElement.vue";
import MasterDropElement from "@/Pages/Projects/Components/MasterDropElement.vue";

export default defineComponent({
    name: "SingleShift",
    components: {
        MasterDropElement,
        EmployeeDropElement,
        ChooseDeleteUserShiftModal,
        AddShiftModal,
        DotsVerticalIcon,
        SvgCollection,
        TrashIcon,
        DuplicateIcon,
        PencilAltIcon,
        DropElement,
        XIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems
    },
    mixins: [Helper],
    props: ['shift', 'crafts', 'event', 'currentUserCrafts'],
    emits: ['dropFeedback'],
    data() {
        return {
            openEditShiftModal: false,
            showDeleteUserModal: false,
            buffer: {
                onlyThisDay: false,
            },
            userToDelete: {
                user_id : null,
                shift_id: null,
                type: null
            },
            highlight: null,
            anyoneHasVacation: false,
        }
    },
    mounted() {
        if(parseInt(this.$page.props?.urlParameters?.shiftId) === this.shift.id){
           this.highlight = 'border-2 border-orange-300 rounded-md p-1';
        }

        setTimeout(() => {
            this.highlight = null;
        }, 5000);
    },
    computed: {
        shiftUserIds() {
            // reset has vacation state
            this.anyoneHasVacation = false;
            const ids = {
                userIds: [],
                freelancerIds: [],
                providerIds: []
            }
            this.shift.users.forEach(user => {
                if(user?.formatted_vacation_days?.includes(this.shift.event_start_day)){
                    this.anyoneHasVacation = true;
                }
                ids.userIds.push(user.id)
            })

            this.shift.freelancer?.forEach((freelancer) => {
                ids.freelancerIds.push(freelancer.id)
            })

            this.shift.service_provider?.forEach((provider) => {
                ids.providerIds.push(provider.id)
            })

            return ids;
        }
    },
    methods: {
        dropFeedback(feedback){
            this.$emit('dropFeedback', feedback);
        },
        dayjs,
        truncateDecimal(number) {
            const decimalPart = number.toString().split('.')[1];
            if (decimalPart && decimalPart.length >= 2) {
                return parseFloat(number.toFixed(2));
            }
            return number;
        },
        removeUserFromShift(user_id, shift_id) {
            this.$inertia.delete(route('shifts.removeUser', {shift: shift_id, user: user_id}), {
                data: {
                    chooseData: this.buffer
                },
                preserveScroll: true,
            });
        },
        removeProviderFromShift(provider_id, shift_id) {
            this.$inertia.delete(route('shifts.removeProvider', {shift: shift_id, serviceProvider: provider_id}), {
                data: {
                    chooseData: this.buffer
                },
                preserveScroll: true,
            });
        },
        removeFreelancerFormShift(freelancer_id, shift_id) {
            this.$inertia.delete(route('shifts.removeFreelancer', {shift: shift_id, freelancer: freelancer_id}), {
                data: {
                    chooseData: this.buffer
                },
                preserveScroll: true,
            });
        },
        clearEmployeesAndMaster(shift_id) {
            this.$inertia.delete(route('shifts.clearEmployeesAndMaster', {shift: shift_id}), {
                data: {
                    chooseData: this.buffer
                },
                preserveScroll: true,
                preserveState: true,
            });
        },
        deleteShift(shift_id) {
            this.$inertia.delete(route('shifts.destroy', {shift: shift_id}), {
                preserveScroll: true,
                preserveState: true,
            });
        },
        editShift() {
            this.openEditShiftModal = true;
        },
        openDeleteUserModal(user_id, shift_id, type) {
            if(this.event.is_series){
                this.showDeleteUserModal = true
                this.userToDelete.user_id = user_id
                this.userToDelete.shift_id = shift_id
                this.userToDelete.type = type
            } else {
                if(type === 'user'){
                    this.removeUserFromShift(user_id, shift_id)
                } else if(type === 'service_provider'){
                    this.removeProviderFromShift(user_id, shift_id)
                } else if(type === 'freelancer'){
                    this.removeFreelancerFormShift(user_id, shift_id)
                }
            }
        },
        deleteUser(buffer){
            this.showDeleteUserModal = false
            this.buffer = buffer;
            if(this.userToDelete.type === 'user'){
                this.removeUserFromShift(this.userToDelete.user_id, this.userToDelete.shift_id)
            } else if(this.userToDelete.type === 'service_provider'){
                this.removeProviderFromShift(this.userToDelete.user_id, this.userToDelete.shift_id)
            } else if(this.userToDelete.type === 'freelancer'){
                this.removeFreelancerFormShift(this.userToDelete.user_id, this.userToDelete.shift_id)
            }
        }
    },
})
</script>


<style scoped>

</style>
