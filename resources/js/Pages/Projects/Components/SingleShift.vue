<template>
    <div :class="[highlight, 'w-[190px] flex flex-col relative']" :id="'shift-container-' + event.id + '-' + shift.id">
        <div class="h-[36px] rounded-t-lg flex items-center justify-between px-4 text-white text-xs relative shadow-md"
             :class="[
                 this.computedMaxWorkerCount === this.computedUsedWorkerCount ?
                    'bg-green-500' :
                    'bg-gray-500',
                    anyoneHasVacation ? '!bg-red-500' : ''
            ]">
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
                    <BaseMenu v-if="this.$can('can plan shifts') || this.hasAdminRole()" dots-size="h-5 w-5 text-white">
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
        <div class="mt-1 rounded-b-lg bg-gray-200 px-1 py-2 overflow-y-scroll h-full w-full">
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
                <ShiftBookedElementComponent
                    :user="user"
                    :type="0"
                    :shift="shift"
                    :event="event"
                    :shift-qualifications="shiftQualifications"
                    :craft-id="this.shift.craft.id"
                    :shift-id="shift.id"
                    :shift-user-ids="shiftUserIds"
                    :event-is-series="event.is_series"
                    :all-shift-qualification-drop-elements="this.computedShiftQualificationDropElements"
                    :crafts="crafts"
                    @dropFeedback="dropFeedback"
                    :craft-with-entities="getUsersInCraftOfShiftAndUsersFromCraftsWhereUniversalApplicable"
                />
            </div>
            <div v-for="freelancer in shift.freelancer">
                <ShiftBookedElementComponent
                    :user="freelancer"
                    :type="1"
                    :shift="shift"
                    :event="event"
                    :shift-qualifications="shiftQualifications"
                    :craft-id="this.shift.craft.id"
                    :shift-id="shift.id"
                    :shift-user-ids="shiftUserIds"
                    :event-is-series="event.is_series"
                    :all-shift-qualification-drop-elements="this.computedShiftQualificationDropElements"
                    :crafts="crafts"
                    @dropFeedback="dropFeedback"
                    :craft-with-entities="getUsersInCraftOfShiftAndUsersFromCraftsWhereUniversalApplicable"
                />
            </div>
            <div v-for="serviceProvider in shift.service_provider">
                <ShiftBookedElementComponent
                    :user="serviceProvider"
                    :type="2"
                    :shift="shift"
                    :event="event"
                    :shift-qualifications="shiftQualifications"
                    :craft-id="this.shift.craft.id"
                    :shift-id="shift.id"
                    :shift-user-ids="shiftUserIds"
                    :event-is-series="event.is_series"
                    :all-shift-qualification-drop-elements="this.computedShiftQualificationDropElements"
                    :crafts="crafts"
                    @dropFeedback="dropFeedback"
                    :craft-with-entities="getUsersInCraftOfShiftAndUsersFromCraftsWhereUniversalApplicable"
                />
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
                    :shift-crafts-with-users="getUsersInCraftOfShiftAndUsersFromCraftsWhereUniversalApplicable"
                    :crafts="crafts"
                    @dropFeedback="dropFeedback"
                />
            </div>
            <div class="my-3 mx-0.5">
                <component is="IconCirclePlus" @click="showAddShiftQualificationModal = true" class="h-5 w-5 xsLight cursor-pointer hover:text-artwork-buttons-hover transition-colors duration-300 ease-in-out" stroke-width="1.5" />
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

    <AddShiftQualificationToShiftModel
        v-if="showAddShiftQualificationModal"
        @close="showAddShiftQualificationModal = false"
        :shift="shift"
        :shift-qualifications="shiftQualifications"
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
import ShiftsQualificationsDropElement from "@/Pages/Projects/Components/ShiftsQualificationsDropElement.vue";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import {router} from "@inertiajs/vue3";
import IconLib from "@/Mixins/IconLib.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ShiftNoteComponent from "@/Layouts/Components/ShiftNoteComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import AddShiftQualificationToShiftModel from "@/Pages/Projects/Components/AddShiftQualificationToShiftModel.vue";
import ShiftBookedElementComponent from "@/Pages/Projects/Components/ShiftBookedElementComponent.vue";

export default defineComponent({
    name: "SingleShift",
    components: {
        ShiftBookedElementComponent,
        AddShiftQualificationToShiftModel,
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
    mixins: [IconLib, Permissions],
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
            showAddShiftQualificationModal: false
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
        getUsersInCraftOfShiftAndUsersFromCraftsWhereUniversalApplicable() {
            // return craft from shift and return crafts where universally_applicable is true
            return this.crafts.filter(
                (craft) => craft.id === this.shift.craft.id || craft.universally_applicable
            );
        },
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

    },
})
</script>
