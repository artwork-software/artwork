<template>
    <div class="h-full" :class="highlight">
        <div class=" flex items-center justify-between px-4 text-white text-xs relative"
             :class="[
                 this.computedMaxWorkerCount === this.computedUsedWorkerCount ?
                    'bg-green-500' :
                    'bg-gray-500',
                    anyoneHasVacation ? '!bg-red-500' : ''
            ]"
        >
            <div class="h-9 flex items-center">
                {{ shift.craft.abbreviation }} ({{ this.computedUsedWorkerCount }}/{{ this.computedMaxWorkerCount }})
            </div>
            <div class="absolute flex items-center right-0">
                <div
                    v-if="this.computedMaxWorkerCount === this.computedUsedWorkerCount"
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
                                        <a href="#" @click="this.clearShiftUsers(shift)"
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
        <div class="mt-1 h-[calc(100%-2.7rem)] bg-gray-200 p-1">
            <p class="text-xs mb-1">
                {{ shift.start }} - {{ shift.end }}
                <span v-if="shift.break_minutes">| {{ shift.break_formatted }}</span>
            </p>
            <p class="text-xs mb-3">{{ shift.description }}</p>
            <div v-for="user in shift.users">
                <div class="flex items-center justify-between p-1 hover:bg-gray-50/40 rounded cursor-pointer group">
                    <div class="flex gap-2 items-center">
                        <img :src="user.profile_photo_url" class="h-4 w-4 rounded-full block bg-gray-500 object-cover">
                        <span class="text-xs">{{ user.full_name }}</span>
                        <span v-if="user.pivot.shift_count > 1" class="text-xs"> 1/{{ user.pivot.shift_count }}</span>
                        <ShiftQualificationIconCollection
                            class="w-5 h-5"
                            :icon-name="this.getShiftQualificationById(user.pivot.shift_qualification_id).icon"/>
                    </div>
                    <div class="hidden group-hover:block"
                         @click="
                            this.event.is_series ?
                                openDeleteUserModal(user.pivot.id, 0) :
                                deleteUserFromShift(user.pivot.id, 0)
                         ">
                        <SvgCollection svg-name="xMarkIcon"/>
                    </div>
                </div>
            </div>
            <div v-for="freelancer in shift.freelancer">
                <div class="flex items-center justify-between p-1 hover:bg-gray-50/40 rounded cursor-pointer group">
                    <div class="flex gap-2 items-center">
                        <img :src="freelancer.profile_photo_url" class="h-4 w-4 rounded-full block bg-gray-500 object-cover">
                        <span class="text-xs">{{ freelancer.name }}</span>
                        <span v-if="freelancer.pivot.shift_count > 1" class="text-xs"> 1/{{ freelancer.pivot.shift_count }}</span>
                        <ShiftQualificationIconCollection
                            class="w-5 h-5"
                            :icon-name="this.getShiftQualificationById(freelancer.pivot.shift_qualification_id).icon"/>
                    </div>
                    <div class="hidden group-hover:block"
                         @click="
                            this.event.is_series ?
                                openDeleteUserModal(freelancer.pivot.id, 1) :
                                deleteUserFromShift(freelancer.pivot.id, 1)
                         ">
                        <SvgCollection svg-name="xMarkIcon"/>
                    </div>
                </div>
            </div>
            <div v-for="serviceProvider in shift.service_provider">
                <div class="flex items-center justify-between p-1 hover:bg-gray-50/40 rounded cursor-pointer group">
                    <div class="flex gap-2 items-center">
                        <img :src="serviceProvider.profile_photo_url" class="h-4 w-4 rounded-full block bg-gray-500 object-cover">
                        <span class="text-xs">{{ serviceProvider.name }}</span>
                        <span v-if="serviceProvider.pivot.shift_count > 1" class="text-xs">  1/{{ serviceProvider.pivot.shift_count }} </span>
                        <ShiftQualificationIconCollection
                            class="w-5 h-5"
                            :icon-name="this.getShiftQualificationById(serviceProvider.pivot.shift_qualification_id).icon"/>
                    </div>
                    <div class="hidden group-hover:block"
                         @click="
                            this.event.is_series ?
                                openDeleteUserModal(serviceProvider.pivot.id, 2) :
                                deleteUserFromShift(serviceProvider.pivot.id, 2)
                         ">
                        <SvgCollection svg-name="xMarkIcon"/>
                    </div>
                </div>
            </div>
            <div v-for="dropElement in this.computedShiftQualificationDropElements">
                <ShiftsQualificationsDropElement
                    v-for="count in dropElement.requiredDropElementsCount"
                    :craft-id="this.shift.craft.id"
                    :shift-id="shift.id"
                    :shift-user-ids="shiftUserIds"
                    :event-is-series="event.is_series"
                    :shift-qualification="this.getShiftQualificationById(dropElement.shift_qualification_id)"
                    :all-shift-qualification-drop-elements="this.computedShiftQualificationDropElements"
                    @dropFeedback="dropFeedback"
                />
            </div>
        </div>
    </div>
    <AddShiftModal v-if="openEditShiftModal"
                   :shift="shift"
                   :event="event"
                   :crafts="crafts"
                   @closed="openEditShiftModal = false"
                   :currentUserCrafts="currentUserCrafts"
                   :edit="true"
                   :shift-qualifications="shiftQualifications"
    />
    <ChooseDeleteUserShiftModal v-if="showDeleteUserModal"
                                @close-modal="this.closeDeleteUserModal"
                                @returnBuffer="deleteUserWithSeriesShiftData"
    />
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
import ShiftsQualificationsDropElement from "@/Pages/Projects/Components/ShiftsQualificationsDropElement.vue";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import {Inertia} from "@inertiajs/inertia";

export default defineComponent({
    name: "SingleShift",
    components: {
      ShiftQualificationIconCollection,
        ShiftsQualificationsDropElement,
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
    props: [
        'shift',
        'crafts',
        'event',
        'currentUserCrafts',
        'shiftQualifications'
    ],
    emits: ['dropFeedback'],
    data() {
        return {
            openEditShiftModal: false,
            showDeleteUserModal: false,
            buffer: {
                onlyThisDay: false,
            },
            userTypeToDelete: null,
            usersPivotIdToDelete: null,
            highlight: null,
            anyoneHasVacation: false,
        }
    },
    mounted() {
        if (parseInt(this.$page.props?.urlParameters?.shiftId) === this.shift.id) {
           this.highlight = 'border-2 border-orange-300 rounded-md p-1';
        }

        setTimeout(() => {
            this.highlight = null;
        }, 5000);
    },
    computed: {
        computedMaxWorkerCount() {
            let maxWorkerCount = 0;

            this.shift.shifts_qualifications.forEach(
                (shiftsQualification) => maxWorkerCount += shiftsQualification.value
            );

            return maxWorkerCount;
        },
        computedUsedWorkerCount() {
            return this.shift.users.length + this.shift.freelancer.length + this.shift.service_provider.length;
        },
        computedShiftQualificationDropElements() {
            let computedShiftsQualificationsDropElements = [];

            this.shift.shifts_qualifications.forEach(
                (shiftsQualification) => {
                    let requiredDropElementsCount = shiftsQualification.value;
                    let shiftQualificationId = shiftsQualification.shift_qualification_id;

                    requiredDropElementsCount -= this.shift.users.filter(
                        (user) => user.pivot.shift_qualification_id === shiftQualificationId
                    ).length;

                    requiredDropElementsCount -= this.shift.freelancer.filter(
                        (freelancer) => freelancer.pivot.shift_qualification_id === shiftQualificationId
                    ).length;

                    requiredDropElementsCount -= this.shift.service_provider.filter(
                        (service_provider) => service_provider.pivot.shift_qualification_id === shiftQualificationId
                    ).length;

                    if (requiredDropElementsCount > 0) {
                        computedShiftsQualificationsDropElements.push({
                            shift_qualification_id: shiftQualificationId,
                            requiredDropElementsCount: requiredDropElementsCount
                        })
                    }
                }
            )

            return computedShiftsQualificationsDropElements;
        },
        shiftUserIds() {
            // reset has vacation state
            this.anyoneHasVacation = false;
            const ids = {
                userIds: [],
                freelancerIds: [],
                providerIds: []
            }
            this.shift.users.forEach(user => {
                if (user?.formatted_vacation_days?.includes(this.shift.event_start_day)) {
                    this.anyoneHasVacation = true;
                }
                ids.userIds.push(user.id);
            });
            this.shift.freelancer?.forEach((freelancer) => {
                ids.freelancerIds.push(freelancer.id);
            });
            this.shift.service_provider?.forEach((provider) => {
                ids.providerIds.push(provider.id);
            });

            return ids;
        }
    },
    methods: {
        getShiftQualificationById(id) {
            return this.shiftQualifications.find((shiftQualification) => shiftQualification.id === id);
        },
        dropFeedback(feedback){
            this.$emit('dropFeedback', feedback);
        },
        dayjs,
        clearShiftUsers(shift) {
            if (shift.users.length > 0 || shift.freelancer.length > 0 || shift.service_provider.length > 0) {
                Inertia.delete(
                    route(
                        'shift.removeAllUsers',
                        {
                            shift: shift.id
                        }
                    ),
                    {
                        data: {
                            chooseData: this.buffer
                        },
                        preserveScroll: true
                    }
                );
            }
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
        openDeleteUserModal(usersPivotId, userType) {
            this.showDeleteUserModal = true;
            this.usersPivotIdToDelete = usersPivotId;
            this.userTypeToDelete = userType;
        },
        closeDeleteUserModal() {
            this.showDeleteUserModal = false;
        },
        deleteUserWithSeriesShiftData(removeFromSingleShift) {
            this.closeDeleteUserModal();
            this.deleteUserFromShift(this.usersPivotIdToDelete, this.userTypeToDelete, removeFromSingleShift);

            this.usersPivotIdToDelete = null;
            this.userTypeToDelete = null;
        },
        deleteUserFromShift(usersPivotId, userType, removeFromSingleShift = true) {
            Inertia.delete(
                route(
                    'shift.removeUserByType',
                    {
                        usersPivotId: usersPivotId,
                        userType: userType
                    }
                ),
                {
                    data: {
                        removeFromSingleShift: removeFromSingleShift
                    },
                    preserveScroll: true
                }
            );
        },
    },
})
</script>
