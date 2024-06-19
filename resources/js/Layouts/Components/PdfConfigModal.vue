<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-50">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-6 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40 pl-4">
                                <div class="mb-4">
                                    <label for="title" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('Heading')}}</label>
                                    <div class="mt-2">
                                        <input v-model="pdf.title" type="text" :placeholder="pdf.title" name="title" id="title" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-artwork-buttons-create sm:text-sm sm:leading-6" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="start" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('Start-Time')}}</label>
                                        <div class="mt-2">
                                            <input v-model="pdf.start" type="date" name="start" id="start" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-artwork-buttons-create sm:text-sm sm:leading-6" />
                                        </div>
                                    </div>
                                    <div>
                                        <label for="end" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('End-Time')}}</label>
                                        <div class="mt-2">
                                            <input v-model="pdf.end" type="date" name="end" id="end" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-artwork-buttons-create sm:text-sm sm:leading-6" />
                                        </div>
                                    </div>
                                </div>
                                <!-- defaultPaperSize -->
                                <div>
                                    <Listbox as="div" v-model="selectedPaperSize">
                                        <ListboxLabel class="block text-sm font-medium leading-6 text-gray-900">{{$t('Paper size')}}</ListboxLabel>
                                        <div class="relative mt-2">
                                            <ListboxButton class="relative w-full cursor-default rounded-md bg-white py-1.5 pl-3 pr-10 text-left text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                <span class="block truncate">{{ selectedPaperSize.name }}</span>
                                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-gray-400">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                                    </svg>
                                                </span>
                                            </ListboxButton>

                                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                                    <ListboxOption as="template" v-for="paperSize in paperSizes" :key="paperSize.id" :value="paperSize" v-slot="{ active, selected }">
                                                        <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ paperSize.name }}</span>

                                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                                </svg>
                                                            </span>
                                                        </li>
                                                    </ListboxOption>
                                                </ListboxOptions>
                                            </transition>
                                        </div>
                                    </Listbox>
                                </div>
                                <!-- defaultPaperOrientation -->
                                <div class="mt-4 mb-4">
                                    <label class="block text-sm font-medium leading-6 text-gray-900">{{$t('Paper orientation')}}</label>
                                    <fieldset class="">
                                        <legend class="sr-only">Notification method</legend>
                                        <div class="space-y-4 sm:flex sm:items-center sm:space-x-10 sm:space-y-0">
                                            <div v-for="paperOrientation in paperOrientations" :key="paperOrientation.id" class="flex items-center">
                                                <input :id="paperOrientation.id" name="notification-method" type="radio" @change="changePaperOrientation(paperOrientation)" :checked="paperOrientation.id === checkedOrientation" class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-600" :disabled="orientationDisabled"/>
                                                <label :for="paperOrientation.id" class="ml-3 block text-sm font-medium leading-6 text-gray-900">{{ paperOrientation.title }}</label>
                                            </div>

                                        </div>
                                        <span class="text-red-500 text-xs" v-if="orientationDisabled"> {{$t('The A6 format is only possible in landscape format.')}}  </span>
                                    </fieldset>
                                </div>

                                <div class="mb-4">
                                    <label for="dpi" class="block text-sm font-medium leading-6 text-gray-900">{{$t('Resolution (DPI) (Standard: 72) (Maximum: 300)')}}</label>
                                    <div class="mt-2">
                                        <input v-model="pdf.dpi" type="number" :placeholder="pdf.dpi" name="dpi" id="dpi" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                    </div>
                                </div>

                              <div class="flex justify-center">
                                  <FormButton @click="createPdf()" :text="$t('Export PDF')"/>
                              </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script>
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot, Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions} from '@headlessui/vue'
import {XIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import {useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
export default {
    name: "PdfConfigModal",
    mixins: [Permissions],
    components: {
        FormButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel, Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions
    },
    data(){
        return {
            open: true,
            paperSizes: [
                { id: 'a4', name: 'A4 (Standard)' },
                { id: 'a3', name: 'A3' },
                { id: 'a5', name: 'A5' },
                { id: 'a6', name: 'A6' },
            ],
            paperOrientations: [
                    { id: 'portrait', title: this.$t('Portrait format') },
                    { id: 'landscape', title: this.$t('Landscape format') },
            ],
            pdf: useForm({
                title: this.pdfTitle ? this.pdfTitle : null,
                start: null,
                end: null,
                paperSize: null,
                paperOrientation: null,
                project: null,
                dpi: 72,
            }),
            selectedPaperSize: { id: 'a4', name: 'A4 (Standard)' },
            selectedPaperOrientation: { id: 'portrait', title: this.$t('Portrait format') },
            checkedOrientation: 'portrait',
            orientationDisabled: false,
        }
    },
    emits: ['closed'],
    props: ['pdfTitle', 'project'],
    methods: {
        closeModal(bool){
            this.$emit('closed', bool)
        },
        createPdf() {
            this.pdf.paperSize = this.selectedPaperSize.id;
            this.pdf.paperOrientation = this.selectedPaperOrientation.id;

            if (this.project) {
                this.pdf.project = this.project.id;
            }


            this.pdf.post(route('calendar.export.pdf'), {
                preserveScroll: true,
            });
            this.closeModal(true);
        },
        changePaperOrientation(orientation){
            this.selectedPaperOrientation = orientation
        },
    },
    watch:{
        selectedPaperSize(){
            if(this.selectedPaperSize.id === 'a6'){
                this.checkedOrientation = 'landscape'
                this.orientationDisabled = true
                this.changePaperOrientation(this.paperOrientations[1])
            } else {
                this.checkedOrientation = 'portrait'
                this.orientationDisabled = false
                this.changePaperOrientation(this.paperOrientations[0])
            }
        }
    }
}
</script>

<style scoped>

</style>
