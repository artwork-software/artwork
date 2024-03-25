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
                                    {{ $t('Organize shift') }}
                                </div>
                                <p class="xsLight subpixel-antialiased">
                                    {{ $t('Determine how long your shift lasts and how many people should work in your shift.') }}
                                </p>
                                <div class="mt-10">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                                        <div>
                                            <input :type="shift?.start_date ? 'date' : 'text'" onfocus="(this.type='date')" dataformatas="dd-mm-yyyy"
                                                   :placeholder="$t('Shift start date')"
                                                   v-model="shiftForm.start_date"
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                   required
                                                   @change="checkInfringement"
                                            />
                                            <span class="text-xs text-red-500" v-show="helpTexts.date_start.length > 0">{{ helpTexts.date_start }} <br></span>
                                            <span class="text-xs text-orange-500" v-show="helpTexts.warnings.date_start.length > 0">{{ helpTexts.warnings.date_start }}</span>
                                        </div>
                                        <div>
                                            <input :type="shift?.end_date ? 'date' : 'text'" onfocus="(this.type='date')"
                                                   :placeholder="$t('Shift end date')"
                                                   v-model="shiftForm.end_date"
                                                   maxlength="3"
                                                   required
                                                   @change="checkInfringement"
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                            <span class="text-xs text-red-500" v-show="helpTexts.date_end.length > 0">{{ helpTexts.date_end }} <br></span>
                                            <span class="text-xs text-orange-500" v-show="helpTexts.warnings.date_end.length > 0">{{ helpTexts.warnings.date_end }}</span>
                                        </div>
                                        <div>
                                            <input type="text" onfocus="(this.type='time')"
                                                   :placeholder="$t('Shift start')"
                                                   v-model="shiftForm.start"
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                   required
                                                   @change="checkInfringement"
                                            />
                                             <span class="text-xs text-red-500" v-show="helpTexts.start.length > 0">{{ helpTexts.start }} <br></span>
                                             <span class="text-xs text-orange-500" v-show="helpTexts.warnings.start.length > 0">{{ helpTexts.warnings.start }}</span>
                                        </div>
                                        <div>
                                            <input type="text" onfocus="(this.type='time')"
                                                   :placeholder="$t('Shift end')"
                                                   v-model="shiftForm.end"
                                                   maxlength="3"
                                                   required
                                                   @change="checkInfringement"
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                            <span class="text-xs text-red-500" v-show="helpTexts.end.length > 0">{{ helpTexts.end }} <br></span>
                                            <span class="text-xs text-red-500" v-show="helpTexts.time.length > 0">{{ helpTexts.time }} <br></span>
                                            <span class="text-xs text-orange-500" v-show="helpTexts.warnings.end.length > 0">{{ helpTexts.warnings.end }}</span>
                                        </div>
                                        <div>
                                            <input type="number"
                                                   :placeholder="$t('Length of break in minutes*')"
                                                   v-model="shiftForm.break_minutes"
                                                   @change="checkInfringement"
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                   required
                                            />
                                            <span class="text-xs text-red-500" v-show="helpTexts.breakText.length > 0">{{ helpTexts.breakText }}</span>
                                        </div>
                                        <div>
                                            <Listbox as="div" v-model="selectedCraft">
                                                <div class="relative">
                                                    <ListboxButton class="w-full h-10 border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow">
                                                        <span class="block truncate text-left pl-3">{{ selectedCraft?.name ?? $t('Craft') + '*'}} </span>
                                                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                                        </span>
                                                    </ListboxButton>
                                                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                        <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                                            <ListboxOption as="template" v-for="craft in selectableCrafts" :key="craft.id" :value="craft" v-slot="{ active, selected }">
                                                                <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                                    <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ craft.name }} ({{ craft.abbreviation }})</span>
                                                                    <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                                        <CheckIcon class="h-5 w-5" aria-hidden="true" />
                                                                    </span>
                                                                </li>
                                                            </ListboxOption>
                                                        </ListboxOptions>
                                                    </transition>
                                                </div>
                                            </Listbox>
                                            <span class="text-xs text-red-500" v-show="helpTexts.craftText.length > 0">{{ helpTexts.craftText }}</span>
                                        </div>
                                        <div v-for="computedShiftQualification in this.computedShiftQualifications"
                                             v-show="this.canComputedShiftQualificationBeShown(computedShiftQualification)">
                                            <input v-if="this.canComputedShiftQualificationBeShown(computedShiftQualification)"
                                                   v-model="computedShiftQualification.value"
                                                   type="number"
                                                   :placeholder="$t('Amount {0}', [computedShiftQualification.name])"
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                            />
                                        </div>
                                        <span class="text-xs text-red-500" v-show="helpTexts.employeeText.length > 0">{{ helpTexts.employeeText }}</span>
                                        <span class="text-xs text-red-500" v-show="helpTexts.masterText.length > 0">{{ helpTexts.masterText }}</span>
                                        <div class="mt-2 col-span-2">
                                            <textarea v-model="shiftForm.description"
                                                      :placeholder="$t('Is there any important information about this shift?')"
                                                      rows="4"
                                                      name="comment"
                                                      id="comment"
                                                      class="block w-full inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-center mt-5">
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
import {defineComponent} from 'vue'
import {CheckIcon, XIcon} from "@heroicons/vue/solid";
import Permissions from "@/mixins/Permissions.vue";
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
import {
    ChevronDownIcon,
    PlusCircleIcon
} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/inertia-vue3";
import ConfirmationModal from "@/Jetstream/ConfirmationModal.vue";
import ChangeAllSubmitModal from "@/Layouts/Components/ChangeAllSubmitModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default defineComponent({
    name: "AddShiftModal",
    mixins: [Permissions],
    components: {
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
        Listbox
    },
    props: [
        'event',
        'crafts',
        'shift',
        'edit',
        'buffer',
        'currentUserCrafts',
        'shiftQualifications'
    ],
    data(){
        return {
            open: true,
            shiftForm: useForm({
                id: this.shift ? this.shift.id : null,
                start_date: this.shift ? this.shift?.formatted_dates.frontend_start : null,
                end_date: this.shift ? this.shift?.formatted_dates.frontend_end : null,
                start: this.shift ? this.shift.start : null,
                end: this.shift ? this.shift.end : null,
                break_minutes: this.shift ? this.shift.break_minutes : null,
                craft_id: this.shift ? this.shift.craft.id : null,
                number_employees: this.shift ? this.shift.number_employees : null,
                number_masters: this.shift ? this.shift.number_masters : null,
                description: this.shift ? this.shift.description : '',
                event_id: this.event.id,
                changeAll: false,
                seriesId: null,
                changes_start: null,
                changes_end: null,
                shiftsQualifications: []
            }),
            selectedCraft: this.shift ? this.shift.craft : null,
            helpTexts: {
                craftText: '',
                breakText: '',
                start: '',
                end: '',
                time: '',
                employeeText:'',
                masterText:'',
                date_start: '',
                date_end: '',
                warnings: {
                    date_start: '',
                    date_end: '',
                    start: '',
                    end: '',
                }
            },
            showChangeAllModal: false,
        }
    },
    emits: ['closed'],
    methods: {
        closeModal(bool){
            this.$emit('closed', bool);
        },
        checkInfringement() {
            // Initialisiere die Warnungen
            this.helpTexts.warnings.start = '';
            this.helpTexts.warnings.end = '';
            this.helpTexts.time = '';
            this.helpTexts.breakText = '';

            // Erstelle DateTime Objekte für besseren Vergleich
            const eventStartDateTime = new Date(this.event.start_time);
            const eventEndDateTime = new Date(this.event.end_time);
            const shiftStartDateTime = new Date(this.shiftForm.start_date + 'T' + this.shiftForm.start);
            const shiftEndDateTime = new Date(this.shiftForm.end_date + 'T' + this.shiftForm.end);

            // Überprüfe, ob die Schicht vor oder nach dem Event beginnt oder endet
            if (shiftStartDateTime < eventStartDateTime) {
                this.helpTexts.warnings.start = this.$t('The shift starts before the event starts!');
            }

            if (shiftEndDateTime > eventEndDateTime) {
                this.helpTexts.warnings.end = this.$t('The shift ends after the event ends!');
            }

            if (shiftStartDateTime > shiftEndDateTime) {
                this.helpTexts.warnings.start = this.$t('The shift ends before it starts!');
            }

            // Überprüfe, ob die Schicht nach dem Event beginnt
            if (shiftStartDateTime > eventEndDateTime) {
                this.helpTexts.warnings.start = this.$t('The shift starts after the event ends!');
            }

            // Überprüfe, ob die Schicht endet, bevor das Event beginnt
            if (shiftEndDateTime < eventStartDateTime) {
                this.helpTexts.warnings.end = this.$t('The shift ends before the event starts!');
            }

            // Überprüfe ob die Endzeit vor der Startzeit liegt
            if (shiftStartDateTime > shiftEndDateTime) {
                this.helpTexts.time = this.$t('The end time must be after the start time.');
            }

            // Überprüfungen für Schichtlänge und Pausen
            let diffMinutes = (shiftEndDateTime - shiftStartDateTime) / 60000; // Umrechnung von Millisekunden in Minuten

            if (diffMinutes > 600) { // 10 Stunden = 600 Minuten
                this.helpTexts.time = this.$t('The shift is over 10 hours long!');
            }

            if (diffMinutes > 360 && this.shiftForm.break_minutes < 30) { // 6 Stunden = 360 Minuten
                this.helpTexts.breakText = this.$t('The break is shorter than required by law!');
            }
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
        /*saveShift() {
            if (this.event.is_series) {
                if (!this.buffer?.onlyThisDay) {
                    this.shiftForm.changeAll = true;
                    this.shiftForm.seriesId = this.event.series_id;
                    this.shiftForm.changes_start = this.buffer?.start;
                    this.shiftForm.changes_end = this.buffer?.end;
                }
            }

            this.shiftForm.craft_id = this.selectedCraft?.id;

            if (this.shiftForm.start > this.shiftForm.start) {
                this.helpTexts.time = this.$t('The shift cannot start before the deadline.');
                return;
            } else {
                this.helpTexts.time = '';
            }

            if (this.event.end < this.shiftForm.end) {
                this.helpTexts.time = this.$t('The shift cannot end after the deadline.');
                return;
            } else {
                this.helpTexts.time = '';
            }

            if (this.shiftForm.start === null) {
                this.helpTexts.start = this.$t('Please enter a start time.');
                return;
            } else {
                this.helpTexts.start = '';
            }

            if (this.shiftForm.end === null) {
                this.helpTexts.end = this.$t('Please enter an end date.');
                return;
            } else {
                this.helpTexts.end = '';
            }

            if (this.selectedCraft === null) {
                this.helpTexts.craftText = this.$t('Please select a trade.');
                return;
            } else {
                this.helpTexts.craftText = '';
            }

            if (this.shiftForm.break_minutes === null) {
                this.helpTexts.breakText = this.$t('Please enter a break time.');
                return;
            } else {
                this.helpTexts.breakText = '';
            }

            if (this.shiftForm.start >= this.shiftForm.end) {
                this.helpTexts.time = this.$t('The end time must be after the start time.');
                return;
            } else {
                this.helpTexts.time = '';
            }

            // set the craft id
            this.shiftForm.craft_id = this.selectedCraft.id;

            if (this.shiftForm.number_employees === '' || this.shiftForm.number_employees === null) {
                this.shiftForm.number_employees = 0;
            }

            if (this.shiftForm.number_masters === '' || this.shiftForm.number_masters === null) {
                this.shiftForm.number_masters = 0;
            }

            this.appendComputedShiftQualificationsToShiftForm();

            let onSuccess = () => {
                this.shiftForm.reset();
                this.closeModal(true);
            };

            if (this.shiftForm.id !== null && this.shiftForm.id !== undefined) {
                this.shiftForm.patch(
                    route('event.shift.update', this.shift.id),
                    {
                        preserveScroll: true,   // preserve scroll position
                        preserveState: true,    // preserve the state of the form
                        onSuccess: onSuccess
                    }
                );
            } else {
                this.shiftForm.post(
                    route('event.shift.store', this.event.id),
                    {
                        preserveScroll: true,   // preserve scroll position
                        preserveState: true,    // preserve the state of the form
                        onSuccess: onSuccess
                    }
                );
            }
        }*/
        saveShift() {
            if (this.event.is_series) {
                if (!this.buffer?.onlyThisDay) {
                    this.shiftForm.changeAll = true;
                    this.shiftForm.seriesId = this.event.series_id;
                    this.shiftForm.changes_start = this.buffer?.start;
                    this.shiftForm.changes_end = this.buffer?.end;
                }
            }

            this.shiftForm.craft_id = this.selectedCraft?.id;

            const eventStartDateTime = new Date(this.event.start_time);
            const eventEndDateTime = new Date(this.event.end_time);
            // Konvertiere die Datums- und Zeitstrings in Date-Objekte für den Vergleich
            const shiftStartDate = new Date(this.shiftForm.start_date + 'T' + this.shiftForm.start);
            const shiftEndDate = new Date(this.shiftForm.end_date + 'T' + this.shiftForm.end);
            const eventStartDate = new Date(this.event.start_time);
            const eventEndDate = new Date(this.event.end_time);

            // Überprüfungen unter Einbeziehung der Datumsangaben
            if (shiftStartDate >= shiftEndDate) {
                // Überprüfung hinzugefügt, um sicherzustellen, dass die Schichtendzeit nicht vor der Schichtstartzeit liegt
                this.helpTexts.time = this.$t('The shift end time cannot be before the shift start time.');
                return;
            }

            if (this.shiftForm.start === null || this.shiftForm.start_date === null) {
                this.helpTexts.start = this.$t('Please enter a start time and date.');
                return;
            } else {
                this.helpTexts.start = '';
            }

            if (this.shiftForm.end === null || this.shiftForm.end_date === null) {
                this.helpTexts.end = this.$t('Please enter an end time and date.');
                return;
            } else {
                this.helpTexts.end = '';
            }

            if (this.selectedCraft === null) {
                this.helpTexts.craftText = this.$t('Please select a trade.');
                return;
            } else {
                this.helpTexts.craftText = '';
            }

            if (this.shiftForm.break_minutes === null) {
                this.helpTexts.breakText = this.$t('Please enter a break time.');
                return;
            } else {
                this.helpTexts.breakText = '';
            }

            if (shiftStartDate >= shiftEndDate) {
                this.helpTexts.time = this.$t('The end time and date must be after the start time and date.');
                return;
            }

            this.shiftForm.craft_id = this.selectedCraft.id;

            this.shiftForm.number_employees = this.shiftForm.number_employees || 0;
            this.shiftForm.number_masters = this.shiftForm.number_masters || 0;

            this.appendComputedShiftQualificationsToShiftForm();

            let onSuccess = () => {
                this.shiftForm.reset();
                this.closeModal(true);
            };

            // Logik zum Speichern oder Aktualisieren der Schicht
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

            return computedShiftQualifications;
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
        }
    }
})
</script>
