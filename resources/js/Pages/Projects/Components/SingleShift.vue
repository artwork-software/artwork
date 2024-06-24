<template>
    <div :class="[highlight, 'w-[175px] flex flex-col relative']" :id="'shift-container-' + event.id + '-' + shift.id">
        <div class="h-[36px] rounded-t-lg flex items-center justify-between px-4 text-white text-xs relative"
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
            <div class="flex items-center justify-between">
                <div
                    v-if="this.computedMaxWorkerCount === this.computedUsedWorkerCount"
                    class="h-9 flex items-center w-fit right-0 p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10.414" height="8.032" viewBox="0 0 10.414 8.032">
                        <path id="Pfad_1498" data-name="Pfad 1498" d="M-1151.25,4789.2l3.089,3.088,5.911-5.911"
                              transform="translate(1151.957 -4785.674)" fill="none" stroke="#fcfcfb" stroke-width="2"/>
                    </svg>
                </div>
                <div v-if="shift.infringement || anyoneHasVacation" class="h-9 bg-red-500 flex items-center w-fit right-0 p-3">
                    <IconExclamationCircle class="h-5 w-5" stroke-width="1.5" />
                </div>

                <div>
                    <BaseMenu dots-size="h-4 w-4">
                        <MenuItem v-slot="{ active }">
                            <div @click="editShift"
                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                <IconEdit
                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                    aria-hidden="true"/>
                                {{ $t('Edit') }}
                            </div>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                            <div @click="this.clearShiftUsers(shift)"
                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                <IconCircleX
                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                    aria-hidden="true"/>
                                {{ $t('Clear') }}
                            </div>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                            <div @click="deleteShift(shift.id)"
                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                <IconTrash
                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                    aria-hidden="true"/>
                                {{ $t('Delete') }}
                            </div>
                        </MenuItem>
                    </BaseMenu>
                </div>
            </div>
        </div>
        <div class="h-full mt-1 rounded-b-lg bg-gray-200 px-1 py-2">
            <p class="text-xs mb-1">
                <span v-if="shift.start_date && shift.end_date && shift.start_date !== shift.end_date">
                    {{ shift.formatted_dates.start }} {{ shift.start }} - {{ shift.formatted_dates.end }} {{ shift.end }}
                </span>
                <span v-if="shift.start_date && shift.end_date && shift.start_date === shift.end_date">
                    {{ shift.formatted_dates.start }} {{ shift.start }} - {{ shift.end }}
                </span>

                <span v-if="shift.break_minutes"> | {{ shift.break_formatted }}</span>
            </p>
            <ShiftNoteComponent :shift="shift" />
            <div v-for="user in shift.users">
                <div class="flex items-center justify-between p-1 hover:bg-gray-50/40 rounded cursor-pointer group">
                    <div class="flex gap-2 items-center">
                        <UserPopoverTooltip :user="user" height="4" width="4" class="flex items-center" />
                        <span class="text-xs">{{ user.full_name }}</span>
                        <span v-if="user.pivot.shift_count > 1" class="text-xs"> 1/{{ user.pivot.shift_count }}</span>
                        <ShiftQualificationIconCollection
                            :classes="'w-4 h-4'"
                            :icon-name="this.getShiftQualificationById(user.pivot.shift_qualification_id).icon"/>
                    </div>
                    <div class="hidden group-hover:block"
                         @click="
                            this.event.is_series ?
                                openDeleteUserModal(user.pivot.id, 0) :
                                deleteUserFromShift(user.pivot.id, 0)
                         ">
                        <span class="flex items-center justify-center">
                            <span class="rounded-full bg-red-400 p-0.5 h-4 w-4 flex items-center justify-center border border-white shadow-[0px_0px_5px_0px_#fc8181]">
                                <IconX class="w-2 h-2 text-white" />
                            </span>
                        </span>
                    </div>
                </div>
            </div>
            <div v-for="freelancer in shift.freelancer">
                <div class="flex items-center justify-between p-1 hover:bg-gray-50/40 rounded cursor-pointer group">
                    <div class="flex gap-2 items-center">
                        <UserPopoverTooltip :user="freelancer" height="4" width="4" class="flex items-center" />
                        <span class="text-xs">{{ freelancer.name }}</span>
                        <span v-if="freelancer.pivot.shift_count > 1" class="text-xs"> 1/{{ freelancer.pivot.shift_count }}</span>
                        <ShiftQualificationIconCollection
                            class="w-5 h-5"  :classes="'w-4 h-4'"
                            :icon-name="this.getShiftQualificationById(freelancer.pivot.shift_qualification_id).icon"/>
                    </div>
                    <div class="hidden group-hover:block"
                         @click="
                            this.event.is_series ?
                                openDeleteUserModal(freelancer.pivot.id, 1) :
                                deleteUserFromShift(freelancer.pivot.id, 1)
                         ">
                        <span class="flex items-center justify-center">
                            <span class="rounded-full bg-red-400 p-0.5 h-4 w-4 flex items-center justify-center border border-white shadow-[0px_0px_5px_0px_#fc8181]">
                                <IconX class="w-2 h-2 text-white" />
                            </span>
                        </span>
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
                            class="w-5 h-5"  :classes="'w-4 h-4'"
                            :icon-name="this.getShiftQualificationById(serviceProvider.pivot.shift_qualification_id).icon"/>
                    </div>
                    <div class="hidden group-hover:block"
                         @click="
                            this.event.is_series ?
                                openDeleteUserModal(serviceProvider.pivot.id, 2) :
                                deleteUserFromShift(serviceProvider.pivot.id, 2)
                         ">
                        <span class="flex items-center justify-center">
                            <span class="rounded-full bg-red-400 p-0.5 h-4 w-4 flex items-center justify-center border border-white shadow-[0px_0px_5px_0px_#fc8181]">
                                <IconX class="w-2 h-2 text-white" />
                            </span>
                        </span>
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
                   @closed="this.closeAddShiftModal()"
                   :currentUserCrafts="currentUserCrafts"
                   :edit="true"
                   :shift-qualifications="shiftQualifications"
                   :shift-time-presets="shiftTimePresets"
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
import {
    DotsVerticalIcon,
    DuplicateIcon,
    PencilAltIcon,
    TrashIcon
} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import AddShiftModal from "@/Pages/Projects/Components/AddShiftModal.vue";
import ChooseDeleteUserShiftModal from "@/Pages/Projects/Components/ChooseDeleteUserShiftModal.vue";
import ShiftsQualificationsDropElement from "@/Pages/Projects/Components/ShiftsQualificationsDropElement.vue";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import {router} from "@inertiajs/vue3";
import IconLib from "@/Mixins/IconLib.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ShiftNoteComponent from "@/Layouts/Components/ShiftNoteComponent.vue";

export default defineComponent({
    name: "SingleShift",
    components: {
        ShiftNoteComponent,
        UserPopoverTooltip,
        BaseMenu,
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
    mixins: [IconLib],
    props: [
        'shift',
        'crafts',
        'event',
        'currentUserCrafts',
        'shiftQualifications',
        'shiftTimePresets'
    ],
    emits: ['dropFeedback', 'wantsFreshPlacements'],
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
        wantsFreshPlacements() {
            this.$emit('wantsFreshPlacements');
        },
        closeAddShiftModal() {
            this.wantsFreshPlacements();
            this.openEditShiftModal = false;
        },
        getShiftQualificationById(id) {
            return this.shiftQualifications.find((shiftQualification) => shiftQualification.id === id);
        },
        dropFeedback(feedback){
            this.$emit('dropFeedback', feedback);
        },
        dayjs,
        clearShiftUsers(shift) {
            if (shift.users.length > 0 || shift.freelancer.length > 0 || shift.service_provider.length > 0) {
                router.delete(
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
            this.$inertia.delete(
                route('shifts.destroy', {shift: shift_id}),
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.wantsFreshPlacements();
                    }
                }
            );
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
            router.delete(
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
