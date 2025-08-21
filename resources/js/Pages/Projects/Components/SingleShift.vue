<template>
    <div :class="[highlight, 'flex flex-col relative']" :id="'shift-container-' + event.id + '-' + shift.id" class="w-56">
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
                    <BaseMenu white-menu-background v-if="this.$can('can plan shifts') || this.hasAdminRole()" dots-size="h-5 w-5 text-white">
                        <BaseMenuItem white-menu-background title="Edit" icon="IconEdit" @click="editShift"/>
                        <BaseMenuItem white-menu-background title="Clear" icon="IconCircleX" @click="clearShiftUsers(shift)"/>
                        <BaseMenuItem white-menu-background title="Delete" icon="IconTrash" @click="deleteShift(shift.id)"/>
                    </BaseMenu>
                </div>
            </div>
        </div>
        <div class="mt-1 rounded-b-lg bg-gray-200 px-1 py-2 overflow-y-scroll h-full w-full">
            <div class="text-xs mb-1 hover:bg-gray-50 cursor-pointer px-1 py-0.5 rounded-lg w-fit" v-if="!editTimes" @click="openEditInLineTimes()">
                <span>
                    {{ shift.start }} - {{ shift.end }}
                </span>
            </div>
            <div v-else>
                <div class="absolute right-4 cursor-pointer hover:text-red-600 duration-300 ease-in-out">
                    <component is="IconX" @click="resetForm" class="h-4 w-4" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2" :id="'container-' + this.shift.id">
                    <BaseInput
                        type="time"
                        v-model="updateTimeForm.start"
                        :label="$t('Start-Time')"
                        :id="'shift-start' + this.shift.id"
                        required
                        is-small
                        @focusout="checkFocus"
                        @change="validateShiftDates"
                        classes="h-8 peer-placeholder-shown:top-[8px] text-xs -top-5"
                    />
                    <BaseInput
                        type="time"
                        v-model="updateTimeForm.end"
                        :label="$t('End-Time')"
                        :id="'shift-end' + this.shift.id"
                        required
                        is-small
                        @focusout="checkFocus"
                        @change="validateShiftDates"
                        classes="h-8 peer-placeholder-shown:top-[8px] text-xs -top-5"
                    />
                </div>
                <div v-if="this.validationMessages.warnings.shift_start.length > 0 ||
                                                    this.validationMessages.errors.shift_start.length > 0 ||
                                                    this.validationMessages.warnings.shift_end.length > 0 ||
                                                    this.validationMessages.errors.shift_end.length > 0">
                    <div v-if="this.validationMessages.warnings.shift_start.length > 0"
                         class="flex flex-col">
                                                <span v-for="warning in this.validationMessages.warnings.shift_start"
                                                      class="text-xs text-orange-500">
                                                    {{ warning }}
                                                </span>
                    </div>
                    <div v-if="this.validationMessages.errors.shift_start.length > 0"
                         class="flex flex-col">
                                                <span v-for="error in this.validationMessages.errors.shift_start"
                                                      class="text-xs errorText">
                                                    {{ error }}
                                                </span>
                    </div>
                </div>
                <div v-if="this.validationMessages.warnings.shift_start.length > 0 ||
                                                    this.validationMessages.errors.shift_start.length > 0 ||
                                                    this.validationMessages.warnings.shift_end.length > 0 ||
                                                    this.validationMessages.errors.shift_end.length > 0">
                    <div v-if="this.validationMessages.warnings.shift_end.length > 0"
                         class="flex flex-col">
                                                <span v-for="warning in this.validationMessages.warnings.shift_end"
                                                      class="text-xs text-orange-500">
                                                    {{ warning }}
                                                </span>
                    </div>
                    <div v-if="this.validationMessages.errors.shift_end.length > 0"
                         class="flex flex-col">
                                                <span v-for="error in this.validationMessages.errors.shift_end"
                                                      class="text-xs errorText">
                                                    {{ error }}
                                                </span>
                    </div>
                </div>
            </div>
            <div class="text-xs mb-1 hover:bg-gray-50 cursor-pointer px-1 py-0.5 rounded-lg w-fit" v-if="!editBreakTime" @click="openEditBreakTime">
                <span v-if="shift.break_minutes">{{ shift.break_formatted }}</span>
            </div>
            <div v-else class="pt-1">
                <div class="absolute right-4 cursor-pointer hover:text-red-600 duration-300 ease-in-out">
                    <component is="IconX" @click="resetForm" class="h-4 w-4" />
                </div>
                <BaseInput
                    type="number"
                    v-model="updateTimeForm.break_minutes"
                    :label="$t('Length of break in minutes*')"
                    :id="'break-time-' + this.shift.id"
                    required
                    is-small
                    @focusout="saveTimeChanges"
                    @change="validateShiftBreak"
                    :disabled="!canEditComponent"
                    classes="h-8 peer-placeholder-shown:top-[8px] text-xs -top-5"
                />
                <div v-if="this.validationMessages.warnings.break_length.length > 0 ||
                                                    this.validationMessages.errors.break_length.length > 0 ||
                                                    this.validationMessages.warnings.craft.length > 0 ||
                                                    this.validationMessages.errors.craft.length > 0">
                    <div v-if="this.validationMessages.warnings.break_length.length > 0"
                         class="flex flex-col">
                                                <span v-for="warning in this.validationMessages.warnings.break_length"
                                                      class="text-xs text-orange-500">
                                                    {{ warning }}
                                                </span>
                    </div>
                    <div v-if="this.validationMessages.errors.break_length.length > 0"
                         class="flex flex-col">
                                                <span v-for="error in this.validationMessages.errors.break_length"
                                                      class="text-xs errorText">
                                                    {{ error }}
                                                </span>
                    </div>
                </div>
                <div v-if="this.validationMessages.warnings.break_length.length > 0 ||
                                                    this.validationMessages.errors.break_length.length > 0 ||
                                                    this.validationMessages.warnings.craft.length > 0 ||
                                                    this.validationMessages.errors.craft.length > 0">
                    <div v-if="this.validationMessages.warnings.craft.length > 0"
                         class="flex flex-col">
                                                <span v-for="warning in this.validationMessages.warnings.craft"
                                                      class="text-xs text-orange-500">
                                                    {{ warning }}
                                                </span>
                    </div>
                    <div v-if="this.validationMessages.errors.craft.length > 0"
                         class="flex flex-col">
                                                <span v-for="error in this.validationMessages.errors.craft"
                                                      class="text-xs errorText">
                                                    {{ error }}
                                                </span>
                    </div>
                </div>
            </div>
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
                    :can-edit-component="canEditComponent"
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
                    :can-edit-component="canEditComponent"
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
                    :can-edit-component="canEditComponent"
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
                    :can-edit-component="canEditComponent"
                    @dropFeedback="dropFeedback"
                />
            </div>
            <div class="my-3 mx-0.5" v-if="canEditComponent">
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
                   :shift-plan-modal="false"
    />

    <AddShiftQualificationToShiftModel
        v-if="showAddShiftQualificationModal"
        @close="showAddShiftQualificationModal = false"
        :shift="shift"
        :shift-qualifications="shiftQualifications"
    />
</template>
<script>
import {defineComponent, nextTick} from 'vue'
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
import {router, useForm} from "@inertiajs/vue3";
import IconLib from "@/Mixins/IconLib.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ShiftNoteComponent from "@/Layouts/Components/ShiftNoteComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import AddShiftQualificationToShiftModel from "@/Pages/Projects/Components/AddShiftQualificationToShiftModel.vue";
import ShiftBookedElementComponent from "@/Pages/Projects/Components/ShiftBookedElementComponent.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import NumberInputComponent from "@/Components/Inputs/NumberInputComponent.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default defineComponent({
    name: "SingleShift",
    components: {
        BaseInput,
        ToolTipComponent,
        NumberInputComponent,
        TimeInputComponent,
        BaseMenuItem,
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
    props: {
        shift: Object,
        crafts: Array,
        event: Object,
        currentUserCrafts: Array,
        shiftQualifications: Array,
        shiftTimePresets: Array,
        canEditComponent: {
            type: Boolean,
            default: false
        }
    },
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
            showAddShiftQualificationModal: false,
            editTimes: false,
            updateTimeForm: useForm({
                start: this.shift.start,
                end: this.shift.end,
                break_minutes: this.shift.break_minutes,
                closeEditAfterSave: false,
            }),
            validationMessages: {
                warnings: {
                    shift_start: [],
                    shift_end: [],
                    break_length: [],
                    craft: [],
                },
                errors: {
                    shift_start: [],
                    shift_end: [],
                    break_length: [],
                    craft: [],
                }
            },
            isFocused: false,
            editBreakTime: false,
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
        checkFocus(event) {
            nextTick(() => {
                const startField = document.getElementById('shift-start' + this.shift.id);
                const endField = document.getElementById('shift-end' + this.shift.id);

                // Prüfen, ob das Ziel des Fokusverlusts eines der relevanten Felder ist
                if (event.relatedTarget === startField || event.relatedTarget === endField) {
                    return;
                }
                this.saveTimeChanges();
            })
        },
        saveTimeChanges(){
            if (this.validateShiftBreak() || this.validateShiftDates()) {
                return;
            }

            if (this.updateTimeForm.isDirty){
                this.updateTimeForm.patch(
                    route('event.shift.update.updateTime', {shift: this.shift.id}),
                    {
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: () => {
                            this.editTimes = false;
                            this.editBreakTime = false;
                            this.wantsFreshPlacements();
                        }
                    }
                );
            } else {
                this.editTimes = false;
                this.editBreakTime = false;
            }
        },
        resetForm(){
            this.editTimes = false;
            this.editBreakTime = false;
            this.updateTimeForm.reset();
        },
        validateShiftDates() {
            this.validationMessages.warnings.shift_start = [];
            this.validationMessages.warnings.shift_end = [];
            this.validationMessages.errors.shift_start = [];
            this.validationMessages.errors.shift_end = [];

            let eventStartDateTime = new Date(this.event.start_time);
            let eventEndDateTime = new Date(this.event.end_time);
            let shiftStartDateTime = new Date(this.shift.start_date + 'T' + this.updateTimeForm.start);
            let shiftEndDateTime = new Date(this.shift.end_date + 'T' + this.updateTimeForm.end);

            let hasErrors = false;

            //check warnings
            if (shiftStartDateTime < eventStartDateTime) {
                this.validationMessages.warnings.shift_start.push(
                    this.$t('The shift starts before the event starts!')
                );
            }
            if (shiftStartDateTime > eventEndDateTime) {
                this.validationMessages.warnings.shift_start.push(
                    this.$t('The shift starts after the event ends!')
                );
            }
            if (((shiftEndDateTime - shiftStartDateTime) / 60000) > 600) {
                this.validationMessages.warnings.shift_start.push(this.$t('The shift is over 10 hours long!'));
            }
            if (shiftStartDateTime > shiftEndDateTime) {
                this.validationMessages.warnings.shift_end.push(this.$t('The shift ends before it starts!'));
            }
            if (shiftEndDateTime < eventStartDateTime) {
                this.validationMessages.warnings.shift_end.push(this.$t('The shift ends before the event starts!'));
            }
            if (shiftStartDateTime > shiftEndDateTime) {
                this.validationMessages.warnings.shift_end.push(
                    this.$t('The end time must be after the start time.')
                );
            }

            return hasErrors;
        },
        validateShiftBreak() {
            this.validationMessages.warnings.break_length = [];
            this.validationMessages.errors.break_length = [];

            let shiftStartDateTime = new Date(this.shift.start_date + 'T' + this.updateTimeForm.start);
            let shiftEndDateTime = new Date(this.shift.end_date + 'T' + this.updateTimeForm.end);

            let hasErrors = false;

            //check warnings
            if (((shiftEndDateTime - shiftStartDateTime) / 60000) > 360 && this.updateTimeForm.break_minutes < 30) {
                this.validationMessages.warnings.break_length.push(
                    this.$t('The break is shorter than required by law!')
                );
            }

            //check errors
            if (this.updateTimeForm.break_minutes === null || this.updateTimeForm.break_minutes === '') {
                this.validationMessages.errors.break_length.push(this.$t('Please enter a break time.'));

                hasErrors = true;
            }

            return hasErrors;
        },
        openEditInLineTimes(){
            if(!this.$can('can plan shifts') || !this.hasAdminRole()){
                return;
            }
            this.editTimes = true;

            nextTick(() => {
               // focus on :id="'shift-start' + this.shift.id"
                const startField = document.getElementById('shift-start' + this.shift.id);
                if (startField) {
                    startField.focus();
                }
            })

        },
        openEditBreakTime() {
            if(!this.$can('can plan shifts') || !this.hasAdminRole()){
                return;
            }
            this.editBreakTime = true;

            nextTick(() => {
                // focus on :id="'break-time-' + this.shift.id"
                const breakField = document.getElementById('break-time-' + this.shift.id);
                if (breakField) {
                    breakField.focus();
                }
            })
        },
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
            router.delete(
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
