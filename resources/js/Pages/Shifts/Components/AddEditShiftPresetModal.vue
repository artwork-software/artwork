<template>
<BaseModal @closed="$emit('closed')">
    <div>
        <ModalHeader
            :title="$t('Organize shift')"
            :description="$t('Determine how long your shift lasts and how many people should work in your shift.')"
        />
    </div>
    <form @submit.prevent="saveShift">
        <div class="mt-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                <div class="flex flex-row">
                    <TimeInputComponent v-model="shiftForm.start"
                                        :label="$t('Start-Time')"
                                        @change="validateShiftDates()" id=""/>
                </div>
                <div class="flex flex-row">
                    <TimeInputComponent v-model="shiftForm.end"
                                        :label="$t('End-Time')"
                                        @change="validateShiftDates()"
                                        id=""/>
                </div>
                <div v-if="this.validationMessages.warnings.shift_start.length > 0 ||
                                                    this.validationMessages.errors.shift_start.length > 0 ||
                                                    this.validationMessages.warnings.shift_end.length > 0 ||
                                                    this.validationMessages.errors.shift_end.length > 0">
                    <div v-if="this.validationMessages.warnings.shift_start.length > 0"
                         class="flex flex-col">
                                                <span v-for="warning in this.validationMessages.warnings.shift_start"
                                                      class="text-xs text-special-orange">
                                                    {{ warning }}
                                                </span>
                    </div>
                    <div v-if="this.validationMessages.errors.shift_start.length > 0"
                         class="flex flex-col">
                                                <span v-for="error in this.validationMessages.errors.shift_start"
                                                      class="text-xs text-sm/5 text-danger">
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
                                                      class="text-xs text-special-orange">
                                                    {{ warning }}
                                                </span>
                    </div>
                    <div v-if="this.validationMessages.errors.shift_end.length > 0"
                         class="flex flex-col">
                                                <span v-for="error in this.validationMessages.errors.shift_end"
                                                      class="text-xs text-sm/5 text-danger">
                                                    {{ error }}
                                                </span>
                    </div>
                </div>
                <div>
                    <BaseInput id="shift-break-minutes-input"
                               type="number"
                               :label="$t('Length of break in minutes*')"
                               v-model="shiftForm.break_minutes"
                               :min="0"
                               :max="1000"
                               @input="autoBreak.markManual()"
                               @change="validateShiftBreak()"
                    />
                    <LegalBreakHint
                        :break-minutes="shiftForm.break_minutes"
                        :legal-minutes="autoBreak.legalMinutes.value"
                        :has-times="autoBreak.hasTimes.value"
                        @reset="autoBreak.resetToLegal(); validateShiftBreak()"
                    />
                </div>
                <div>
                    <SelectComponent id="addShiftCraftSelectComponent"
                                     :label="$t('Craft') + '*'"
                                     v-model="this.selectedCraft"
                                     :options="this.sortedCrafts"
                                     selected-property-to-display="name"
                                     :getter-for-options-to-display="(option) => option.name + ' ' + option.abbreviation"
                    />
                </div>
                <div v-if="this.validationMessages.warnings.break_length.length > 0 ||
                                                    this.validationMessages.errors.break_length.length > 0 ||
                                                    this.validationMessages.warnings.craft.length > 0 ||
                                                    this.validationMessages.errors.craft.length > 0">
                    <div v-if="this.validationMessages.warnings.break_length.length > 0"
                         class="flex flex-col">
                                                <span v-for="warning in this.validationMessages.warnings.break_length"
                                                      class="text-xs text-special-orange">
                                                    {{ warning }}
                                                </span>
                    </div>
                    <div v-if="this.validationMessages.errors.break_length.length > 0"
                         class="flex flex-col">
                                                <span v-for="error in this.validationMessages.errors.break_length"
                                                      class="text-xs text-sm/5 text-danger">
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
                                                      class="text-xs text-special-orange">
                                                    {{ warning }}
                                                </span>
                    </div>
                    <div v-if="this.validationMessages.errors.craft.length > 0"
                         class="flex flex-col">
                                                <span v-for="error in this.validationMessages.errors.craft"
                                                      class="text-xs text-sm/5 text-danger">
                                                    {{ error }}
                                                </span>
                    </div>
                </div>
                <div v-for="(computedShiftQualification, index) in this.computedShiftQualifications"
                     v-show="this.canComputedShiftQualificationBeShown(computedShiftQualification)">
                    <NumberComponent v-if="this.canComputedShiftQualificationBeShown(computedShiftQualification)"
                                     v-model="computedShiftQualification.value"
                                     :id="'shift-qualification-' + index"
                                     :label="$t('Amount {0}', [computedShiftQualification.name])"
                                     @change="this.validateShiftsQualification(computedShiftQualification)"/>
                    <div v-if="computedShiftQualification.warning || computedShiftQualification.error"
                         class="mt-2 space-y-2"
                    >
                        <div v-if="computedShiftQualification.warning" class="flex flex-col">
                                                    <span class="text-xs text-sm/5 text-danger">
                                                        {{ computedShiftQualification.warning }}
                                                    </span>
                        </div>
                        <div v-if="computedShiftQualification.error" class="flex flex-col">
                                                    <span class="text-xs text-sm/5 text-danger">
                                                        {{ computedShiftQualification.error }}
                                                    </span>
                        </div>
                    </div>
                </div>
                <div class="col-span-2">
                    <TextareaComponent v-model="shiftForm.description" :label="$t('Is there any important information about this shift?')" rows="4" name="comment" id="comment" />
                </div>
            </div>
        </div>
        <div class="flex justify-center">
            <BaseUIButton :label="$t('Save')" is-add-button type="submit" :disabled="shiftForm.processing" />
        </div>
    </form>

</BaseModal>
</template>
<script>
import {IconCheck, IconChevronDown, IconCirclePlus, IconX} from "@tabler/icons-vue";
import {defineComponent, reactive, toRef} from 'vue'
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import LegalBreakHint from "@/Components/Inputs/LegalBreakHint.vue";
import {useAutoBreak} from "@/Composeables/useAutoBreak";
import Permissions from "@/Mixins/Permissions.vue";
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import Input from "@/Jetstream/Input.vue";
import {useForm} from "@inertiajs/vue3";
import ConfirmationModal from "@/Jetstream/ConfirmationModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import NumberComponent from "@/Components/Inputs/NumberInputComponent.vue";
import SelectComponent from "@/Components/Inputs/SelectComponent.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default defineComponent({
    name: "AddEditShiftPresetModal",
    mixins: [Permissions],
    components: {
        BaseInput,
        LegalBreakHint,
        BaseUIButton,
        TextareaComponent,
        ModalHeader,
        BaseModal,
        SelectComponent,
        NumberComponent,
        DateInputComponent, TimeInputComponent,
        FormButton,
        ConfirmationModal,
        IconCheck,
        IconChevronDown,
        Input,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        IconX,
        DialogPanel,
        IconCirclePlus,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        Listbox
    },
    props: ['crafts', 'presetShift', 'edit', 'presetId', 'shiftQualifications'],
    setup(props) {
        // Formular im setup, damit das Auto-Pausen-Composable (ArbZG) die Felder
        // reaktiv beobachten kann; in data() stünde es dem Composable nicht zur Verfügung.
        const shiftForm = useForm({
            id: props.presetShift ? props.presetShift.id : null,
            start: props.presetShift ? props.presetShift.start : null,
            end: props.presetShift ? props.presetShift.end : null,
            break_minutes: props.presetShift ? props.presetShift.break_minutes : null,
            craft_id: props.presetShift ? props.presetShift.craft.id : null,
            description: props.presetShift ? props.presetShift.description : '',
            presetShiftsQualifications: []
        });

        const autoBreak = useAutoBreak(
            toRef(shiftForm, 'start'),
            toRef(shiftForm, 'end'),
            toRef(shiftForm, 'break_minutes')
        );

        return { shiftForm, autoBreak };
    },
    data(){
        return {
            open: true,
            selectedCraft: this.presetShift ? this.presetShift.craft : null,
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
            showChangeAllModal: false
        }
    },
    emits: ['closed'],
    computed: {
        sortedCrafts() {
            return [...(this.crafts || [])].sort((a, b) => (a.position ?? 0) - (b.position ?? 0));
        },
        computedShiftQualifications() {
            let computedShiftQualifications = [];

            this.shiftQualifications.forEach((shiftQualification) => {
                //on edit lookup qualifications already assigned to shift for their values
                let foundShiftsQualification = this.edit ?
                    this.presetShift.shifts_qualifications.find(
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
    },
    methods: {
        canComputedShiftQualificationBeShown(computedShiftQualification) {
            //computedShiftQualification is shown if its either available or the modal is opened for edit and the
            //given shift contains the shift_qualification_id already, even if it's not available for new shifts anymore
            return computedShiftQualification.available ||
                (this.edit && this.shiftContainsComputedShiftQualification(computedShiftQualification));
        },
        shiftContainsComputedShiftQualification(computedShiftQualification) {
            //if shift contains shift qualficiation id return true to show it even if it's not available anymore
            //it also gets appended to patch request then but not to create requests
            return typeof this.presetShift.shifts_qualifications.find(
                (shiftsQualification) => shiftsQualification.shift_qualification_id === computedShiftQualification.id
            ) !== 'undefined'
        },
        appendComputedShiftQualificationsToShiftForm() {
            this.computedShiftQualifications.forEach(
                (computedShiftQualification) => {
                    //only append if they also can be shown
                    if (this.canComputedShiftQualificationBeShown(computedShiftQualification)) {
                        this.shiftForm.presetShiftsQualifications.push({
                            shift_qualification_id: computedShiftQualification.id,
                            value: computedShiftQualification.value
                        });
                    }
                }
            );
        },
        closeModal(bool){
            this.$emit('closed', bool);
        },
        validateShiftDates() {
            this.validationMessages.warnings.shift_start = [];
            this.validationMessages.warnings.shift_end = [];
            this.validationMessages.errors.shift_start = [];
            this.validationMessages.errors.shift_end = [];

            let hasErrors = false;

            //check errors
            if (this.shiftForm.start === null || this.shiftForm.start === '') {
                this.validationMessages.errors.shift_start.push(this.$t('Please enter a shift start time.'));
                hasErrors = true;
            }
            if (this.shiftForm.end === null || this.shiftForm.end === '') {
                this.validationMessages.errors.shift_end.push(this.$t('Please enter a shift end time.'));
                hasErrors = true;
            }

            return hasErrors;
        },
        validateShiftBreak() {
            this.validationMessages.warnings.break_length = [];
            this.validationMessages.errors.break_length = [];

            let hasErrors = false;

            // ArbZG-Grenzen (> 6h → 30 min, > 9h → 45 min) inkl. Über-Mitternacht
            if (this.autoBreak.isBelowMinimum.value) {
                this.validationMessages.warnings.break_length.push(
                    this.$t('The break is below the legal minimum ({0} min)', [this.autoBreak.legalMinutes.value])
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
        saveShift(){
            if (this.validate()) {
                return;
            }

            this.shiftForm.craft_id = this.selectedCraft.id;

            this.appendComputedShiftQualificationsToShiftForm();

            let onSuccess = () => {
                this.shiftForm.start = null;
                this.shiftForm.end = null;
                this.shiftForm.break_minutes = null;
                this.shiftForm.craft_id = null;
                this.shiftForm.description = '';
                this.shiftForm.shiftsQualifications = [];
                this.closeModal(true);
            };

            if (this.shiftForm.id !== null && this.shiftForm.id !== undefined) {
                this.shiftForm.patch(route('shift.preset.update', {presetShift: this.presetShift.id}), {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: onSuccess
                })
            } else {
                this.shiftForm.post(route('shift.preset.store', this.presetId), {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: onSuccess
                })
            }
        }
    }
})
</script>
