<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden bg-white pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl rounded-lg">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4" alt="illustration"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="px-6">
                                    <div class="font-black font-lexend text-primary text-3xl my-2">
                                        {{ $t('Organize shift') }}
                                    </div>
                                    <p class="xsLight subpixel-antialiased">
                                        {{ $t('Determine how long your shift lasts and how many people should work in your shift.') }}
                                    </p>
                                </div>
                                <div class="mt-10">
                                    <div class="bg-lightBackgroundGray px-6 py-2 mb-3">
                                        <div class="flex items-center justify-between my-2">
                                            <div>
                                                <SwitchGroup as="div" class="flex items-center" v-if="!shift?.id">
                                                    <SwitchLabel as="span" class="mr-3 text-sm" :class="shiftForm.automaticMode ? 'font-bold' : 'text-gray-400'">
                                                        Automatischer Modus
                                                    </SwitchLabel>
                                                    <Switch v-model="shiftForm.automaticMode" :disabled="buffer?.cameFormBuffer" :class="[shiftForm.automaticMode ? 'bg-artwork-buttons-create' : 'bg-artwork-buttons-create', buffer?.cameFormBuffer ? 'bg-artwork-context-dark cursor-not-allowed' : ' cursor-pointer', 'relative inline-flex h-3 w-6 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none']">
                                                        <span aria-hidden="true" :class="[!shiftForm.automaticMode  ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                                                    </Switch>
                                                    <SwitchLabel as="span" class="ml-3 text-sm" :class="!shiftForm.automaticMode? 'font-bold' : 'text-gray-400'">
                                                        Manueller Modus
                                                    </SwitchLabel>
                                                </SwitchGroup>
                                                <AlertComponent
                                                    type="info"
                                                    show-icon
                                                    icon-size="w-4 h-4"
                                                    v-if="!buffer?.cameFormBuffer && shiftForm.automaticMode"
                                                    :text="$t('Automatic mode is activated. The shift times are calculated automatically.')"
                                                    class="mt-1"
                                                />
                                                <AlertComponent
                                                    type="info"
                                                    show-icon
                                                    icon-size="w-4 h-4"
                                                    v-if="!buffer?.cameFormBuffer && !shiftForm.automaticMode"
                                                    :text="$t('Manual mode is activated. The shift times must be entered manually.')"
                                                    class="mt-1"
                                                />
                                                <AlertComponent
                                                    type="info"
                                                    show-icon
                                                    icon-size="w-4 h-4"
                                                    :text="$t('Manual mode is deactivated as the date is from the repeat event.')"
                                                    v-if="buffer?.cameFormBuffer"
                                                    class="mt-1"
                                                />
                                            </div>
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
                                                        <SearchInput no-label v-model="searchPreset" placeholder="Suche nach Vorlagen" />
                                                        <IconX v-if="showSearchbar" class="cursor-pointer h-5 w-5" @click="closeSearchbar"/>
                                                    </div>
                                                    <IconSearch v-if="!showSearchbar" class="cursor-pointer h-5 w-5" @click="showSearchbar = !showSearchbar"/>
                                                </div>
                                                <div v-if="filteredShiftTimePresets?.length > 0">
                                                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                                        <div v-for="(shiftTimePreset) in filteredShiftTimePresets" :key="shiftTimePreset.id" @click="takePreset(shiftTimePreset)" class="cursor-pointer">
                                                            <div class="border rounded-lg border-dashed p-2 bg-white flex-col justify-center hover:shadow-sm transition-all ease-in-out" :class="[shiftTimePreset.active ? 'border-green-500' : '']">
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
                                    <div class="grid grid-cols-1 sm:grid-cols-2 mb-3 px-6 gap-x-3.5">
                                        <div class="flex flex-row">
                                            <DateInputComponent v-if="!shiftForm.automaticMode"
                                                                v-model="shiftForm.start_date"
                                                                :label="$t('Shift start date')"
                                                                @change="validateShiftDates()"/>
                                            <TimeInputComponent v-model="shiftForm.start"
                                                                :label="$t('Start-Time')"
                                                                :class="[!shiftForm.automaticMode ? '!w-1/4' : '']"
                                                                @change="validateShiftDates()"/>
                                        </div>
                                        <div class="flex flex-row">
                                            <DateInputComponent v-if="!shiftForm.automaticMode"
                                                                v-model="shiftForm.end_date"
                                                                :label="$t('Shift end date')"
                                                                @change="validateShiftDates()"
                                            />
                                            <TimeInputComponent v-model="shiftForm.end"
                                                                :label="$t('End-Time')"
                                                                :class="[!shiftForm.automaticMode ? '!w-1/4' : '']"
                                                                @change="validateShiftDates()"
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
                                            <NumberComponent id="shift-break-minutes-input"
                                                   :label="$t('Length of break in minutes*')"
                                                   v-model="shiftForm.break_minutes"
                                                   @change="validateShiftBreak()"/>
                                        </div>
                                        <SelectComponent id="addShiftCraftSelectComponent"
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
                                                <NumberComponent v-if="this.canComputedShiftQualificationBeShown(computedShiftQualification)"
                                                                 v-model="computedShiftQualification.value"
                                                                 :id="'shift-qualification-' + index"
                                                                 :label="$t('Amount {0}', [computedShiftQualification.name])"
                                                                 @change="this.validateShiftsQualification(computedShiftQualification)"/>
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
                                            <TextareaComponent v-model="shiftForm.description"
                                                      :label="$t('Is there any important information about this shift?')"
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
                            </div>
                            <div class="flex justify-center px-6">
                                <FormButton :text="$t('Save')" @click="saveShift"/>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
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
import {useForm} from "@inertiajs/vue3";
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

export default defineComponent({
    name: "AddShiftModal",
    mixins: [Permissions, IconLib],
    components: {
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
        'shiftTimePresets'
    ],
    data(){
        return {
            showPresetBox: false,
            searchPreset: '',
            showSearchbar: false,
            open: true,
            shiftForm: useForm({
                id: this.shift ? this.shift.id : null,
                start_date: this.shift ? this.shift.formatted_dates.frontend_start : null,
                end_date: this.shift ? this.shift.formatted_dates.frontend_end : null,
                start: this.shift ? this.shift.start : null,
                end: this.shift ? this.shift.end : null,
                break_minutes: this.shift ? this.shift.break_minutes : null,
                craft_id: this.shift ? this.shift.craft.id : null,
                description: this.shift ? this.shift.description : '',
                event_id: this.event.id,
                changeAll: false,
                seriesId: null,
                changes_start: null,
                changes_end: null,
                shiftsQualifications: [],
                automaticMode: true,
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

            let eventStartDateTime = new Date(this.event.start_time);
            let eventEndDateTime = new Date(this.event.end_time);
            let shiftStartDateTime = new Date(this.shiftForm.start_date + 'T' + this.shiftForm.start);
            let shiftEndDateTime = new Date(this.shiftForm.end_date + 'T' + this.shiftForm.end);

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
            if (shiftEndDateTime > eventEndDateTime) {
                this.validationMessages.warnings.shift_end.push(this.$t('The shift ends after the event ends!'));
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

            //check errors
            if (!this.shiftForm.automaticMode) {
                if ((this.shiftForm.start === null || this.shiftForm.start === '') || this.shiftForm.start_date === null) {
                    this.validationMessages.errors.shift_start.push(this.$t('Please enter a start time and date.'));
                    hasErrors = true;
                }
                if (shiftStartDateTime >= shiftEndDateTime) {
                    this.validationMessages.errors.shift_end.push(
                        this.$t('The shift end time cannot be before the shift start time.')
                    );
                    hasErrors = true;
                }
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

            if (this.event.is_series) {
                if (!this.buffer?.onlyThisDay) {
                    this.shiftForm.changeAll = true;
                    this.shiftForm.seriesId = this.event.series_id;
                    this.shiftForm.changes_start = this.buffer?.start;
                    this.shiftForm.changes_end = this.buffer?.end;
                }
            }

            this.shiftForm.craft_id = this.selectedCraft?.id;
            this.appendComputedShiftQualificationsToShiftForm();

            let onSuccess = () => {
                this.shiftForm.reset();
                this.closeModal(true);
            };

            if (this.shiftForm.id) {
                this.shiftForm.patch(
                    route('event.shift.update', this.shift.id), {
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: onSuccess
                    }
                );
            } else {
                this.shiftForm.post(
                    route('event.shift.store', this.event.id), {
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: onSuccess
                    }
                );
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
