<template>
    <ArtworkBaseModal @close="closeModal" v-if="open" full-modal title="Organize shift" description="Determine how long your shift lasts and how many people should work in your shift.">
        <form @submit.prevent="saveShift" class="relative z-40 mb-5 artwork">
            <div class="">
                <div class="px-5 py-3 mb-5">
                    <div class="flex items-center justify-end my-2">
                        <div class="flex items-center justify-end">
                            <button type="button" class="text-xs text-artwork-buttons-create underline cursor-pointer" @click="showPresetBox = !showPresetBox">
                                {{ showPresetBox ? $t('Hide time presets') : $t('Show time presets') }}
                            </button>
                        </div>
                    </div>
                    <transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-out"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <div v-if="showPresetBox" class="max-h-48 overflow-y-scroll my-5 py-2">
                            <div class="flex items-center justify-end mb-1">
                                <div class="w-52 flex items-center gap-x-2" v-if="showSearchbar">
                                    <BaseInput is-small id="search" no-label v-model="searchPreset" label="Suche nach Vorlagen" />
                                    <IconX v-if="showSearchbar" class="cursor-pointer h-5 w-5" @click="closeSearchbar"/>
                                </div>
                                <IconSearch v-if="!showSearchbar" class="cursor-pointer h-5 w-5" @click="showSearchbar = !showSearchbar"/>
                            </div>
                            <div v-if="filteredShiftTimePresets?.length > 0">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                    <div v-for="(shiftTimePreset) in filteredShiftTimePresets" :key="shiftTimePreset.id" @click="takePreset(shiftTimePreset)" class="cursor-pointer">
                                        <div class="border rounded-lg border-dashed px-3 py-2 card white flex-col justify-center hover:shadow-sm transition-all ease-in-out" :class="[shiftTimePreset.active ? 'border-green-500' : '']">
                                            <div class="text-xs font-bold truncate">
                                                {{ shiftTimePreset.name }}
                                            </div>
                                            <div class="text-gray-500 text-xs mt-1">
                                                {{ shiftTimePreset.start_time }} - {{ shiftTimePreset.end_time}}
                                                <div>
                                                    {{ shiftTimePreset.break_time }} {{  $t('Minutes') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex items-center justify-center">
                                <AlertComponent
                                    type="info"
                                    show-icon
                                    icon-size="w-4 h-4"
                                    :text="$t('No presets found.')"
                                    class="w-fit"
                                />
                            </div>
                        </div>
                    </transition>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 mb-3 px-6 gap-4">
                    <div class="flex flex-row">
                        <BaseInput
                            type="date"
                            v-if="!shiftForm.automaticMode"
                            v-model="shiftForm.start_date"
                            label="Shift start date"
                            @change="validateShiftDates()" id="start_date"
                            :required="!shiftForm.automaticMode"
                        />
                        <BaseInput
                            v-model="shiftForm.start"
                            label="Start-Time"
                            :class="[!shiftForm.automaticMode ? '!w-1/4' : '']"
                            @change="validateShiftDates()" id="start"
                            required
                            type="time"
                        />
                    </div>
                    <div class="flex flex-row">
                        <BaseInput
                            type="date"
                            v-if="!shiftForm.automaticMode"
                            v-model="shiftForm.end_date"
                            label="Shift end date"
                            @change="validateShiftDates()"
                            :required="!shiftForm.automaticMode"
                            id="end_date"
                        />
                        <BaseInput
                            v-model="shiftForm.end"
                            label="End-Time"
                            :class="[!shiftForm.automaticMode ? '!w-1/4' : '']"
                            @change="validateShiftDates()"
                            required
                            type="time"
                            id="end"
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
                    <div class="w-full">
                        <BaseInput
                            type="number"
                            id="shift-break-minutes-input"
                            :label="$t('Length of break in minutes*')"
                            v-model="shiftForm.break_minutes"
                            @change="validateShiftBreak()"
                            :min="0"
                            :max="1000"
                            required
                        />
                    </div>
                    <SelectComponent
                        id="addShiftCraftSelectComponent"
                        :label="$t('Craft') + '*'"
                        v-model="this.selectedCraft"
                        :options="this.selectableCrafts"
                        selected-property-to-display="name"
                        :getter-for-options-to-display="(option) => option.name + ' ' + option.abbreviation"
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
                    <div v-for="(computedShiftQualification, index) in this.computedShiftQualifications"
                         v-show="this.canComputedShiftQualificationBeShown(computedShiftQualification)">
                        <div class="w-full">
                            <BaseInput type="number" v-if="this.canComputedShiftQualificationBeShown(computedShiftQualification)"
                                             v-model="computedShiftQualification.value"
                                             :id="'shift-qualification-' + index"
                                             :label="$t('Amount {0}', [computedShiftQualification.name])"
                                             @change="this.validateShiftsQualification(computedShiftQualification)" without-translation/>
                        </div>
                        <div v-if="computedShiftQualification.warning || computedShiftQualification.error"
                             class="space-y-2">
                            <div v-if="computedShiftQualification.warning" class="flex flex-col">
                                                    <span class="text-xs errorText">
                                                        {{ computedShiftQualification.warning }}
                                                    </span>
                            </div>
                            <div v-if="computedShiftQualification.error" class="flex flex-col">
                                                    <span class="text-xs errorText">
                                                        {{ computedShiftQualification.error }}
                                                    </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col col-span-2 mt-1">
                        <BaseTextarea v-model="shiftForm.description"
                                           label="Is there any important information about this shift?"
                                           rows="4"
                                           name="comment"
                                           id="comment"
                                           maxlength="250"
                        />
                        <div class="text-xs text-end mt-1 text-artwork-buttons-context">
                            {{ shiftForm.description?.length ?? 0 }} / 250
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex w-full items-center px-6" :class="!shift?.roomId ? 'justify-center' : 'justify-between'">
                <ArtworkBaseModalButton variant="primary" type="submit" :disabled="shiftForm.processing || !shiftForm.start || !shiftForm.end || !shiftForm.break_minutes || !selectedCraft">
                    {{ $t('Save') }}
                </ArtworkBaseModalButton>

                <div @click="showComfirmDeleteModal = true" class="text-sm underline cursor-pointer hover:text-red-600 ease-in-out duration-300 transition-colors" v-if="shift?.roomId">
                    {{ $t('Delete shift without Event') }}
                </div>
            </div>
        </form>
        <ConfirmDeleteModal
            v-if="showComfirmDeleteModal"
            @closed="showComfirmDeleteModal = false"
            @delete="deleteShift"
            :description="$t('Do you really want to delete this shift?')"
            :title="$t('Delete shift')"
        />
    </ArtworkBaseModal>


</template>
<script>
import {defineComponent, reactive} from 'vue'
import {CheckIcon, XIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions, Switch, SwitchGroup, SwitchLabel,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import Input from "@/Jetstream/Input.vue";
import {
    ChevronDownIcon,
    PlusCircleIcon
} from "@heroicons/vue/outline";
import {router, useForm} from "@inertiajs/vue3";
import ConfirmationModal from "@/Jetstream/ConfirmationModal.vue";
import ChangeAllSubmitModal from "@/Layouts/Components/ChangeAllSubmitModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {IconEdit, IconTrash} from "@tabler/icons-vue";
import IconLib from "@/Mixins/IconLib.vue";
import SearchInput from "@/Components/Form/SearchInput.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import NumberComponent from "@/Components/Inputs/NumberInputComponent.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import PlaceholderLabel from "@/Components/Inputs/Labels/PlaceholderLabel.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import SelectComponent from "@/Components/Inputs/SelectComponent.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

export default defineComponent({
    name: "AddShiftModal",
    mixins: [Permissions, IconLib],
    components: {
        ArtworkBaseModalButton,
        ArtworkBaseModal,
        BaseTextarea,
        BaseInput,
        ConfirmDeleteModal,
        BaseModal,
        ModalHeader,
        SelectComponent,
        TimeInputComponent,
        DateInputComponent,
        TextareaComponent,
        PlaceholderLabel,
        TextInputComponent,
        NumberComponent,
        AlertComponent,
        SearchInput,
        IconEdit, IconTrash,
        FormButton,
        ChangeAllSubmitModal,
        ConfirmationModal,
        CheckIcon,
        ChevronDownIcon,
        Input,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon,
        DialogPanel,
        PlusCircleIcon,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        Listbox, Switch, SwitchGroup, SwitchLabel
    },
    props: [
        'event',
        'crafts',
        'shift',
        'edit',
        'buffer',
        'currentUserCrafts',
        'shiftQualifications',
        'shiftTimePresets',
        'room',
        'day',
        'shiftPlanModal',
        'multiAddMode',
        'roomsAndDatesForMultiEdit'
    ],
    data(){
        return {
            showPresetBox: false,
            searchPreset: '',
            showSearchbar: false,
            showComfirmDeleteModal: false,
            open: true,
            shiftForm: useForm({
                id: this.shift ? this.shift.id : null,
                start_date: this.shift ? this.shift.formatted_dates.frontend_start : null,
                end_date: this.shift ? this.shift.formatted_dates.frontend_end : null,
                start: this.shift ? this.shift.start : null,
                end: this.shift ? this.shift.end : null,
                break_minutes: this.shift ? this.shift.break_minutes : 30,
                craft_id: this.shift ? this.shift.craft.id : null,
                description: this.shift ? this.shift.description : '',
                event_id: this.event ? this.event.id : null,
                changeAll: false,
                seriesId: null,
                changes_start: null,
                changes_end: null,
                shiftsQualifications: [],
                automaticMode: true,
                room_id: this.room ? this.room : null,
                day: this.day ? this.day : null,
                roomsAndDatesForMultiEdit: this.roomsAndDatesForMultiEdit ? this.roomsAndDatesForMultiEdit : null,
                updateOrCreateInShiftPlan: this.shiftPlanModal
            }),
            selectedCraft: this.shift ? this.shift.craft : null,
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
            showChangeAllModal: false,
        }
    },
    emits: ['closed'],
    mounted() {
        if (this.edit) {
            this.validate();
        }
    },
    methods: {
        deleteShift() {
            router.delete(route('shifts.destroy', { shift: this.shift.id}), {
                onSuccess: () => {
                    this.closeModal(true);
                },
                onFinish: () => {
                    this.closeModal(true);
                }
            });
        },
        takePreset(shiftTimePreset) {
            this.shiftForm.start = shiftTimePreset.start_time;
            this.shiftForm.end = shiftTimePreset.end_time;
            this.shiftForm.break_minutes = shiftTimePreset.break_time;

            // add active state to the selected preset and remove it from the others
            this.shiftTimePresets.forEach((preset) => {
                preset.active = preset.id === shiftTimePreset.id;
            });
        },
        closeModal(bool){
            // reset active state of all presets
            this.shiftTimePresets.forEach((preset) => {
                preset.active = false;
            });
            this.$emit('closed', bool);
        },
        appendComputedShiftQualificationsToShiftForm() {
            this.computedShiftQualifications.forEach(
                (computedShiftQualification) => {
                    //only append if they also can be shown
                    if (this.canComputedShiftQualificationBeShown(computedShiftQualification)) {
                        this.shiftForm.shiftsQualifications.push({
                            shift_qualification_id: computedShiftQualification.id,
                            value: computedShiftQualification.value
                        });
                    }
                }
            );
        },
        canComputedShiftQualificationBeShown(computedShiftQualification) {
            //computedShiftQualification is shown if its either available or the modal is opened for edit and the
            //given shift contains the shift_qualification_id already, even if it's not available for new shifts anymore
            return computedShiftQualification.available ||
                (this.edit && this.shiftContainsComputedShiftQualification(computedShiftQualification));
        },
        shiftContainsComputedShiftQualification(computedShiftQualification) {
            //if shift contains shift qualficiation id return true to show it even if it's not available anymore
            //it also gets appended to patch request then but not to create requests
            return typeof this.shift.shifts_qualifications.find(
                (shiftsQualification) => shiftsQualification.shift_qualification_id === computedShiftQualification.id
            ) !== 'undefined'
        },
        validateShiftDates() {
            this.validationMessages.warnings.shift_start = [];
            this.validationMessages.warnings.shift_end = [];
            this.validationMessages.errors.shift_start = [];
            this.validationMessages.errors.shift_end = [];


            let shiftStartDateTime = new Date(this.shiftForm.start_date + 'T' + this.shiftForm.start);
            let shiftEndDateTime = new Date(this.shiftForm.end_date + 'T' + this.shiftForm.end);

            let hasErrors = false;


            if (((shiftEndDateTime - shiftStartDateTime) / 60000) > 600) {
                this.validationMessages.warnings.shift_start.push(this.$t('The shift is over 10 hours long!'));
            }
            /*if (shiftStartDateTime > shiftEndDateTime) {
                this.validationMessages.warnings.shift_end.push(this.$t('The shift ends before it starts!'));
            }*/

            if(!this.shiftPlanModal){
                // check warnings
                let eventStartDateTime = new Date(this.event.start_time);
                let eventEndDateTime = new Date(this.event.end_time);
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

                /*if (shiftEndDateTime < eventStartDateTime) {
                    this.validationMessages.warnings.shift_end.push(this.$t('The shift ends before the event starts!'));
                }*/
            }
            /*if (shiftStartDateTime > shiftEndDateTime) {
                this.validationMessages.warnings.shift_end.push(
                    this.$t('The end time must be after the start time.')
                );
            }*/

            //check errors
            if (!this.shiftForm.automaticMode) {
                if ((this.shiftForm.start === null || this.shiftForm.start === '') || this.shiftForm.start_date === null) {
                    this.validationMessages.errors.shift_start.push(this.$t('Please enter a start time and date.'));
                    hasErrors = true;
                }
                /*if (shiftStartDateTime >= shiftEndDateTime) {
                    this.validationMessages.errors.shift_end.push(
                        this.$t('The shift end time cannot be before the shift start time.')
                    );
                    hasErrors = true;
                }*/
                if ((this.shiftForm.end === null || this.shiftForm.end === '') || this.shiftForm.end_date === null) {
                    this.validationMessages.errors.shift_end.push(this.$t('Please enter an end time and date.'));
                    hasErrors = true;
                }
            }

            // if automatic mode is active, the shift start and end date are not required
            if (this.automaticMode) {
                this.validationMessages.errors.shift_start = [];
                this.validationMessages.errors.shift_end = [];
            }

            // check if the shift description is too long


            return hasErrors;
        },
        validateShiftBreak() {
            this.validationMessages.warnings.break_length = [];
            this.validationMessages.errors.break_length = [];

            let shiftStartDateTime = new Date(this.shiftForm.start_date + 'T' + this.shiftForm.start);
            let shiftEndDateTime = new Date(this.shiftForm.end_date + 'T' + this.shiftForm.end);

            let hasErrors = false;

            //check warnings
            if (((shiftEndDateTime - shiftStartDateTime) / 60000) > 360 && this.shiftForm.break_minutes < 30) {
                this.validationMessages.warnings.break_length.push(
                    this.$t('The break is shorter than required by law!')
                );
            }

            //check errors
            if (this.shiftForm.break_minutes === null || this.shiftForm.break_minutes === '') {
                this.validationMessages.errors.break_length.push(this.$t('Please enter a break time.'));

                hasErrors = true;
            }

            return hasErrors;
        },
        validateShiftCraft() {
            this.validationMessages.warnings.craft = [];
            this.validationMessages.errors.craft = [];

            //check errors
            if (this.selectedCraft === null) {
                this.validationMessages.errors.craft.push(this.$t('Please select a trade.'));

                return true;
            }

            return false;
        },
        validateShiftsQualification(computedShiftsQualification) {
            computedShiftsQualification.warning = null;
            computedShiftsQualification.error = null;

            if (computedShiftsQualification.value < 0) {
                computedShiftsQualification.error = this.$t("Value can't be lower than 0.");

                return true;
            }

            return false;
        },
        validateShiftsQualifications() {
            let hasErrors = false;

            this.computedShiftQualifications.forEach((computedShiftQualification) => {
                if (this.validateShiftsQualification(computedShiftQualification)) {
                    hasErrors = true;
                }
            });

            return hasErrors;
        },
        validate() {
            let hasShiftDateError = this.validateShiftDates(),
                hasShiftBreakError = this.validateShiftBreak(),
                hasShiftCraftError = this.validateShiftCraft(),
                hasShiftsQualificationsError = this.validateShiftsQualifications();

            return hasShiftDateError || hasShiftBreakError || hasShiftCraftError || hasShiftsQualificationsError;
        },
        saveShift() {
            if (this.validate()) {
                return;
            }

            if (!this.shiftPlanModal && this.event?.is_series) {
                if (!this.buffer?.onlyThisDay) {
                    this.shiftForm.changeAll = true;
                    this.shiftForm.seriesId = this.event.series_id;
                    this.shiftForm.changes_start = this.buffer?.start;
                    this.shiftForm.changes_end = this.buffer?.end;
                }
            }

            this.shiftForm.craft_id = this.selectedCraft?.id;
            this.appendComputedShiftQualificationsToShiftForm();


            if(this.multiAddMode){
                this.shiftForm.post(
                    route('event.shift.store.multi.add'), {
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: () => {
                            this.shiftForm.reset();
                            this.closeModal(true);
                        },
                        onError: (error) => {
                            console.log(error);
                        },
                        onFinish: () => {
                            this.shiftForm.reset();
                            this.closeModal(true);
                        }
                    }
                );
            } else {
                if(this.shiftPlanModal && !this.shiftForm.id){
                    this.shiftForm.post(
                        route('event.shift.store.without.event'), {
                            preserveScroll: true,
                            preserveState: true,
                            onSuccess: () => {
                                this.shiftForm.reset();
                                this.closeModal(true);
                            },
                            onError: (error) => {
                                console.log(error);
                            },
                            onFinish: () => {
                                this.shiftForm.reset();
                                this.closeModal(true);
                            }
                        }
                    );
                } else {
                    if (this.shiftForm.id) {
                        this.shiftForm.patch(
                            route('event.shift.update', this.shift.id), {
                                preserveScroll: true,
                                preserveState: true,
                                onSuccess: () => {
                                    this.shiftForm.reset();
                                    router.reload({
                                        only: ['loadedProjectInformation']
                                    })
                                    this.closeModal(true);

                                },
                                onError: (error) => {
                                    console.log(error);
                                },
                                onFinish: () => {
                                    this.shiftForm.reset();
                                    this.closeModal(true);
                                }
                            }
                        );
                    } else {
                        this.shiftForm.post(
                            route('event.shift.store', this.event.id), {
                                preserveScroll: true,
                                preserveState: true,
                                onSuccess: () => {
                                    this.shiftForm.reset();
                                    router.reload({
                                        only: ['loadedProjectInformation']
                                    })
                                    this.closeModal(true);
                                },
                                onError: (error) => {
                                    console.log(error);
                                },
                                onFinish: () => {
                                    this.shiftForm.reset();
                                    this.closeModal(true);
                                }
                            }
                        );
                    }
                }
            }
        },
        closeSearchbar() {
            this.showSearchbar = false;
            this.searchPreset = '';
        }
    },
    computed: {
        computedShiftQualifications() {
            let computedShiftQualifications = [];

            this.shiftQualifications.forEach((shiftQualification) => {
                //on edit lookup qualifications already assigned to shift for their values
                let foundShiftsQualification = this.edit ?
                    this.shift.shifts_qualifications.find(
                        (shiftsQualification) => shiftsQualification.shift_qualification_id === shiftQualification.id
                    ) :
                    undefined;

                //push with given value or empty string
                computedShiftQualifications.push({
                    id: shiftQualification.id,
                    name: shiftQualification.name,
                    available: shiftQualification.available,
                    value: typeof foundShiftsQualification !== 'undefined' ? foundShiftsQualification.value : ''
                });
            });

            //console.log(computedShiftQualifications)

            return reactive(computedShiftQualifications);
        },
        selectableCrafts() {
            let crafts = [];

            if (this.selectedCraft) {
                let selectedCraftIncluded = false;

                this.currentUserCrafts.forEach((userCraft) => {
                    if (userCraft.id === this.selectedCraft.id) {
                        selectedCraftIncluded = true;
                    }
                });

                if (!selectedCraftIncluded) {
                    crafts.push(this.selectedCraft);
                }
            }
            return crafts.concat(this.currentUserCrafts);
        },
        filteredShiftTimePresets() {
            return this.shiftTimePresets.filter((shiftTimePreset) => {
                return shiftTimePreset.name.toLowerCase().includes(this.searchPreset.toLowerCase());
            });
        }
    }
})
</script>
