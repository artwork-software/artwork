<template>
    <ArtworkBaseModal
        title="Organize shift"
        description="Determine how long your shift lasts and how many people should work in your shift."
        modal-size="sm:max-w-2xl"
        z-index="150"
        @close="closeModal(false)"
    >
        <form id="addEditShiftPresetForm" class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="saveShift">
            <div>
                <BaseInput
                    id="shiftPresetStart"
                    v-model="shiftForm.start"
                    type="time"
                    label="Start-Time"
                    required
                    :error="validationMessages.errors.shift_start.join(' ')"
                    @change="validateShiftDates()"
                />
                <p v-for="warning in validationMessages.warnings.shift_start" :key="warning" class="mt-1 text-[11.5px] text-warning">
                    {{ warning }}
                </p>
            </div>
            <div>
                <BaseInput
                    id="shiftPresetEnd"
                    v-model="shiftForm.end"
                    type="time"
                    label="End-Time"
                    required
                    :error="validationMessages.errors.shift_end.join(' ')"
                    @change="validateShiftDates()"
                />
                <p v-for="warning in validationMessages.warnings.shift_end" :key="warning" class="mt-1 text-[11.5px] text-warning">
                    {{ warning }}
                </p>
            </div>
            <div>
                <BaseInput
                    id="shiftPresetBreakMinutes"
                    v-model="shiftForm.break_minutes"
                    type="number"
                    label="Length of break in minutes"
                    required
                    :min="0"
                    :max="1000"
                    :error="validationMessages.errors.break_length.join(' ')"
                    @input="autoBreak.markManual()"
                    @change="validateShiftBreak()"
                />
                <p v-for="warning in validationMessages.warnings.break_length" :key="warning" class="mt-1 text-[11.5px] text-warning">
                    {{ warning }}
                </p>
                <LegalBreakHint
                    :break-minutes="shiftForm.break_minutes"
                    :legal-minutes="autoBreak.legalMinutes.value"
                    :has-times="autoBreak.hasTimes.value"
                    @reset="autoBreak.resetToLegal(); validateShiftBreak()"
                />
            </div>
            <div>
                <ArtworkBaseListbox
                    v-model="selectedCraft"
                    :items="sortedCrafts"
                    by="id"
                    :option-label="craftLabel"
                    label="Craft"
                    placeholder="Select craft"
                    @change="validateShiftCraft()"
                >
                    <template #label>
                        {{ $t('Craft') }} <span class="text-danger">*</span>
                    </template>
                </ArtworkBaseListbox>
                <p v-for="error in validationMessages.errors.craft" :key="error" class="mt-1 text-[11.5px] text-danger">
                    {{ error }}
                </p>
            </div>
            <template v-for="(computedShiftQualification, index) in computedShiftQualifications" :key="computedShiftQualification.id">
                <div v-if="canComputedShiftQualificationBeShown(computedShiftQualification)">
                    <BaseInput
                        :id="'shift-qualification-' + index"
                        v-model="computedShiftQualification.value"
                        type="number"
                        :min="0"
                        :label="$t('Amount {0}', [computedShiftQualification.name])"
                        without-translation
                        :error="computedShiftQualification.error ?? ''"
                        @change="validateShiftsQualification(computedShiftQualification)"
                    />
                    <p v-if="computedShiftQualification.warning" class="mt-1 text-[11.5px] text-warning">
                        {{ computedShiftQualification.warning }}
                    </p>
                </div>
            </template>
            <BaseTextarea
                id="shiftPresetDescription"
                v-model="shiftForm.description"
                label="Is there any important information about this shift?"
                rows="4"
                class="sm:col-span-2"
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="shiftForm.processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                type="submit"
                form="addEditShiftPresetForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="shiftForm.processing"
            />
        </template>
    </ArtworkBaseModal>
</template>
<script>
import {defineComponent, reactive, toRef} from 'vue'
import {useForm} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import LegalBreakHint from "@/Components/Inputs/LegalBreakHint.vue";
import {useAutoBreak} from "@/Composeables/useAutoBreak";
import Permissions from "@/Mixins/Permissions.vue";

export default defineComponent({
    name: "AddEditShiftPresetModal",
    mixins: [Permissions],
    components: {
        ArtworkBaseModal,
        ArtworkBaseListbox,
        BaseInput,
        BaseTextarea,
        LegalBreakHint,
        BaseUIButton,
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
            craft_id: props.presetShift?.craft?.id ?? null,
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
            selectedCraft: this.presetShift?.craft ?? null,
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
        }
    },
    emits: ['closed'],
    computed: {
        sortedCrafts() {
            return [...(this.crafts || [])].sort((a, b) => (a.position ?? 0) - (b.position ?? 0));
        },
        computedShiftQualifications() {
            let computedShiftQualifications = [];

            (this.shiftQualifications ?? []).forEach((shiftQualification) => {
                //on edit lookup qualifications already assigned to shift for their values
                let foundShiftsQualification = this.edit ?
                    this.presetShift?.shifts_qualifications?.find(
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
        craftLabel(craft) {
            return craft ? `${craft.name} ${craft.abbreviation ?? ''}`.trim() : '';
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
            return typeof this.presetShift?.shifts_qualifications?.find(
                (shiftsQualification) => shiftsQualification.shift_qualification_id === computedShiftQualification.id
            ) !== 'undefined'
        },
        appendComputedShiftQualificationsToShiftForm() {
            // Neu aufbauen, sonst doppeln sich die Einträge bei erneutem Absenden nach einem Fehler
            this.shiftForm.presetShiftsQualifications = [];
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
            if (this.shiftForm.processing) {
                return;
            }
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
            if (this.shiftForm.processing || this.validate()) {
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
                this.shiftForm.presetShiftsQualifications = [];
                this.$emit('closed', true);
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
