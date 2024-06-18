<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="font-black font-lexend text-primary text-3xl my-2">
                                    {{ $t('Organize shift')}}
                                </div>
                                <p class="xsLight subpixel-antialiased">
                                    {{ $t('Determine how long your shift lasts and how many people should work in your shift.')}}
                                </p>
                                <div class="mt-10">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                                        <div>
                                            <input type="time"
                                                   :placeholder="$t('Shift start')"
                                                   v-model="shiftForm.start"
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                   required
                                                   @change="this.validateShiftDates()"
                                            />
                                        </div>
                                        <div>
                                            <input type="time"
                                                   :placeholder="$t('Shift end')"
                                                   v-model="shiftForm.end"
                                                   maxlength="3"
                                                   required
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                   @change="this.validateShiftDates()"
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
                                        <div>
                                            <input type="number"
                                                   :placeholder="$t('Length of break in minutes*')"
                                                   v-model="shiftForm.break_minutes"
                                                   minlength="0"
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                   required
                                                   @change="this.validateShiftBreak()"
                                            />
                                        </div>
                                        <div>
                                            <Listbox as="div"
                                                     v-model="selectedCraft"
                                                     @update:modelValue="this.validateShiftCraft()"
                                                     by="id"
                                            >
                                                <div class="relative">
                                                    <ListboxButton class="w-full h-10 border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow">
                                                        <span class="block truncate text-left pl-3">{{ selectedCraft?.name ?? $t('Craft') + '*'}} </span>
                                                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                                        </span>
                                                    </ListboxButton>
                                                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                        <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                                            <ListboxOption as="template" v-for="craft in crafts" :key="craft.id" :value="craft" v-slot="{ active, selected }">
                                                                <li :class="[active ? 'bg-artwork-buttons-create text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                                    <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ craft.name }} ({{ craft.abbreviation }})</span>
                                                                    <span v-if="selected" :class="[active ? 'text-white' : 'text-artwork-buttons-create', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                                        <CheckIcon class="h-5 w-5" aria-hidden="true" />
                                                                    </span>
                                                                </li>
                                                            </ListboxOption>
                                                        </ListboxOptions>
                                                    </transition>
                                                </div>
                                            </Listbox>
                                        </div>
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
                                        <div v-for="computedShiftQualification in this.computedShiftQualifications"
                                             v-show="this.canComputedShiftQualificationBeShown(computedShiftQualification)">
                                            <input v-if="this.canComputedShiftQualificationBeShown(computedShiftQualification)"
                                                   v-model="computedShiftQualification.value"
                                                   type="number"
                                                   :placeholder="$t('Amount {0}', [computedShiftQualification.name])"
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                   @change="this.validateShiftsQualification(computedShiftQualification)"
                                            />
                                            <div v-if="computedShiftQualification.warning || computedShiftQualification.error"
                                                 class="mt-2 space-y-2"
                                            >
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
                                        <div class="col-span-2">
                                            <textarea v-model="shiftForm.description" :placeholder="$t('Is there any important information about this shift?')" rows="4" name="comment" id="comment" class="block w-full inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <FormButton
                                    :text="$t('Save')"
                                    @click="saveShift" />
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
    ListboxOptions,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import Input from "@/Jetstream/Input.vue";
import {ChevronDownIcon, PlusCircleIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/vue3";
import ConfirmationModal from "@/Jetstream/ConfirmationModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default defineComponent({
    name: "AddEditShiftPresetModal",
    mixins: [Permissions],
    components: {
        FormButton,
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
        Listbox
    },
    props: ['crafts', 'presetShift', 'edit', 'presetId', 'shiftQualifications'],
    data(){
        return {
            open: true,
            shiftForm: useForm({
                id: this.presetShift ? this.presetShift.id : null,
                start: this.presetShift ? this.presetShift.start : null,
                end: this.presetShift ? this.presetShift.end : null,
                break_minutes: this.presetShift ? this.presetShift.break_minutes : null,
                craft_id: this.presetShift ? this.presetShift.craft.id : null,
                description: this.presetShift ? this.presetShift.description : '',
                presetShiftsQualifications: []
            }),
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
