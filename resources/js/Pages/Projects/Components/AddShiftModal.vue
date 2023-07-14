

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
                                    Schicht einteilen
                                </div>
                                <p class="xsLight subpixel-antialiased">
                                    Lege fest wie lange deine Schicht dauert und wie viele Personen in deiner Schicht arbeiten sollen.
                                </p>
                                <div class="mt-10">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                                       <div>
                                           <input type="time"
                                                  placeholder="Schicht Start"
                                                  v-model="shift.start"
                                                  class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                  required
                                           />
                                            <span class="text-xs text-red-500" v-show="helpTexts.start.length > 0">{{ helpTexts.start }}</span>

                                       </div>
                                        <div>
                                            <input type="time"
                                                   placeholder="Schicht Ende"
                                                   v-model="shift.end"
                                                   maxlength="3"
                                                   required
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                            <span class="text-xs text-red-500" v-show="helpTexts.end.length > 0">{{ helpTexts.end }}</span>
                                            <span class="text-xs text-red-500" v-show="helpTexts.time.length > 0">{{ helpTexts.time }}</span>

                                        </div>
                                       <div>
                                           <input type="number"
                                                  placeholder="Pausenlänge in Minuten*"
                                                  v-model="shift.break_minutes"
                                                  class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                  required
                                           />
                                           <span class="text-xs text-red-500" v-show="helpTexts.breakText.length > 0">{{ helpTexts.breakText }}</span>
                                       </div>

                                        <div>
                                            <Listbox as="div" v-model="selectedCraft">
                                                <div class="relative">
                                                    <ListboxButton class="w-full h-10 border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow">
                                                        <span class="block truncate text-left pl-3">{{ selectedCraft?.name ?? 'Gewerk*'}} </span>
                                                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                                        </span>
                                                    </ListboxButton>

                                                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                        <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                                            <ListboxOption as="template" v-for="craft in crafts" :key="craft.id" :value="craft" v-slot="{ active, selected }">
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
                                        <input type="number"
                                               placeholder="Anzahl Mitarbeiter*innen"
                                               v-model="shift.number_employees"
                                               class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                        />
                                        <input type="number"
                                               placeholder="Anzahl Meister*innen"
                                               v-model="shift.number_masters"
                                               maxlength="3"
                                               class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                        <span class="text-xs text-red-500" v-show="helpTexts.employeeText.length > 0">{{ helpTexts.employeeText }}</span>
                                        <span class="text-xs text-red-500" v-show="helpTexts.masterText.length > 0">{{ helpTexts.masterText }}</span>

                                        <div class="mt-2 col-span-2">
                                            <textarea v-model="shift.description" placeholder="Gibt es wichtige Informationen zu dieser Schicht?" rows="4" name="comment" id="comment" class="block w-full inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between mt-5">
                                <AddButton mode="modal" text="Speichern" @click="checkSeriesEvent"/>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <ChangeAllSubmitModal v-if="showChangeAllModal" @closed="showChangeAllModal = false" @all="saveAllEvents" @single="saveShift" />
</template>
<script>
import {defineComponent} from 'vue'
import {CheckIcon, XIcon} from "@heroicons/vue/solid";
import AddButton from "@/Layouts/Components/AddButton.vue";
import Permissions from "@/mixins/Permissions.vue";
import {
    Dialog,
    DialogPanel,
    DialogTitle, Listbox,
    ListboxButton,
    ListboxOption, ListboxOptions,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import Input from "@/Jetstream/Input.vue";
import {ChevronDownIcon, PlusCircleIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/inertia-vue3";
import ConfirmationModal from "@/Jetstream/ConfirmationModal.vue";
import ChangeAllSubmitModal from "@/Layouts/Components/ChangeAllSubmitModal.vue";

export default defineComponent({
    name: "AddShiftModal",
    mixins: [Permissions],
    components: {
        ChangeAllSubmitModal,
        ConfirmationModal,
        CheckIcon, ChevronDownIcon,
        Input,
        AddButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel, PlusCircleIcon, ListboxButton, ListboxOption, ListboxOptions, Listbox
    },
    props: ['event', 'crafts'],
    data(){
        return {
            open: true,
            shift: useForm({
                start: null,
                end: null,
                break_minutes: null,
                craft_id: null,
                number_employees: null,
                number_masters: null,
                description: '',
                event_id: this.event.id,
                changeAll: false,
                seriesId: null,
            }),
            selectedCraft: null,
            helpTexts: {
                craftText: '',
                breakText: '',
                start: '',
                end: '',
                time: '',
                employeeText:'',
                masterText:'',
            },
            showChangeAllModal: false
        }
    },
    emits: ['closed'],
    methods: {
        closeModal(bool){
            this.$emit('closed', bool);
        },
        saveAllEvents(){
            this.shift.changeAll = true;
            this.shift.seriesId = this.event.series_id;
            this.saveShift();
        },
        checkSeriesEvent(){
            if(this.event.is_series){
                this.showChangeAllModal = true;
            } else {
                this.saveShift();
            }
        },
        saveShift(){
            this.shift.craft_id = this.selectedCraft?.id;

            if(this.event.start > this.shift.start){
                this.helpTexts.time = 'Die Schicht kann nicht vor dem Termin starten.';
                return;
            } else {
                this.helpTexts.time = '';
            }

            if(this.event.end < this.shift.end){
                this.helpTexts.time = 'Die Schicht kann nicht nach dem Termin enden.';
                return;
            } else {
                this.helpTexts.time = '';
            }

            if ( this.shift.start === null ){
                this.helpTexts.start = 'Bitte gib einen Startzeitpunkt an.';
                return;
            } else {
                this.helpTexts.start = '';
            }

            if ( this.shift.end === null ){
                this.helpTexts.end = 'Bitte gib einen Endzeitpunkt an.';
                return;
            } else {
                this.helpTexts.end = '';
            }

            if ( this.selectedCraft === null ){
                this.helpTexts.craftText = 'Bitte wähle ein Gewerk aus.';
                return;
            } else {
                this.helpTexts.craftText = '';
            }

            if ( this.shift.break_minutes === null ){
                this.helpTexts.breakText = 'Bitte gib eine Pausenzeit an.';
                return;
            } else {
                this.helpTexts.breakText = '';
            }

            if(this.shift.start >= this.shift.end ){
                this.helpTexts.time = 'Der Endzeitpunkt muss nach dem Startzeitpunkt liegen.';
                return;
            } else {
                this.helpTexts.time = '';
            }

            // set the craft id
            this.shift.craft_id = this.selectedCraft.id;

            /*if(this.shift.number_employees === '' || this.shift.number_employees === undefined || this.shift.number_employees === null && this.shift.number_masters === '' || this.shift.number_masters === undefined || this.shift.number_masters === null){
                this.helpTexts.masterText = 'Es muss mindestens eine Person eingeteilt werden.';
                return;
            } else {
                this.helpTexts.masterText = '';
            }*/

            if(this.shift.number_employees === '' || this.shift.number_employees === null){
                this.shift.number_employees = 0;
            }

            if(this.shift.number_masters === '' || this.shift.number_masters === null){
                this.shift.number_masters = 0;
            }

            // send the request
            this.shift.post(route('event.shift.store', this.event.id), {
                preserveScroll: true,   // preserve scroll position
                preserveState: true,    // preserve the state of the form
                onSuccess: () => {
                    this.shift.start = null;
                    this.shift.end = null;
                    this.shift.break_minutes = null;
                    this.shift.craft_id = null;
                    this.shift.number_employees = null;
                    this.shift.number_masters = null;
                    this.shift.description = '';
                    this.shift.changeAll = false;
                    this.shift.seriesId = null;
                    this.closeModal(true);  // close the modal
                }
            })
        }
    }
})
</script>
<style scoped>

</style>
