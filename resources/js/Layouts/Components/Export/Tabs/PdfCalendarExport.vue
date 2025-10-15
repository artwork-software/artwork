<template>
    <div class="flex flex-col gap-y-1.5">
        <BaseInput
            id="title"
            v-model="pdf.title"
            :label="$t('Heading')"/>
        <div v-if="showModalInformation"
             class="p-2 bg-blue-50 text-blue-500 rounded-lg border border-blue-200">
            <div class="flex items-start p-3 gap-x-2">
                <IconExclamationCircle class="w-6 h-6" stroke-width="2"/>
                <p class="text-sm w-fit">
                    {{ $t('If a project is specified, the project period is used for the export. If no project is specified, you can either specify the start and end date yourself or your current calendar start and end date will be used automatically.') }}
                </p>
            </div>
            <div class="text-end px-3 py-2 text-xs underline cursor-pointer" @click="showModalInformation = false">
                {{ $t('Close note') }}
            </div>
        </div>
        <div :class="pdfSelectedProject ? 'pt-4' : 'pt-8'" class="px-4 pb-4 bg-backgroundGray rounded-lg border border-gray-300">
            <ProjectSearch v-if="!pdfSelectedProject"
                           class="-mt-5"
                           @project-selected="addProjectToPdf"
                           no-project-groups
                           get-first-last-event/>
            <div  v-else class="flex flex-col">
                <h3 class="font-semibold">{{ pdfSelectedProject.name }}</h3>
                <div class="text-sm" v-if="pdfSelectedProject?.first_event && pdfSelectedProject?.last_event">
                    {{ $t('Project period') }}:
                    <span class="text-blue-500" v-if="pdfSelectedProject.first_event.start_time">{{ pdfSelectedProject.first_event.start_time }}</span>
                    -
                    <span class="text-blue-500" v-if="pdfSelectedProject.last_event.end_time">{{ pdfSelectedProject.last_event.end_time }}</span>
                </div>
                <div class="text-blue-500 underline text-xs cursor-pointer" @click="pdfSelectedProject = null">
                    {{ $t('Cancel project selection') }}
                </div>
            </div>
            <LastedProjects
                :limit="10"
                @select="addProjectToPdf"
            />
        </div>
        <div class="flex flex-row gap-x-2" v-if="!pdfSelectedProject">
            <BaseInput type="date"
                v-model="pdf.start"
                :label="$t('Start date')"
                id="start"/>
            <BaseInput type="date"
                v-model="pdf.end"
                :label="$t('End date')"
                id="end"/>
        </div>
        <hr class="mt-2.5"/>
        <Listbox as="div" v-model="selectedPaperSize">
            <ListboxLabel class="xsLight">{{$t('Paper size')}}</ListboxLabel>
            <div class="relative mt-2">
                <ListboxButton class="menu-button">
                    <div class="block truncate">{{ selectedPaperSize.name }}</div>
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
        <div class="flex flex-col mt-1">
            <label class="xsLight">{{$t('Paper orientation')}}</label>
            <fieldset class="">
                <legend class="sr-only">Notification method</legend>
                <div class="space-y-4 sm:flex sm:items-center sm:space-x-10 sm:space-y-0">
                    <div v-for="paperOrientation in paperOrientations" :key="paperOrientation.id" class="flex items-center">
                        <input :id="paperOrientation.id" name="notification-method" type="radio" @change="changePaperOrientation(paperOrientation)" :checked="paperOrientation.id === checkedOrientation" class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-600" :disabled="orientationDisabled"/>
                        <label :for="paperOrientation.id" class="ml-2 block text-sm font-medium leading-6 text-gray-900">{{ paperOrientation.title }}</label>
                    </div>

                </div>
                <span class="text-red-500 text-xs" v-if="orientationDisabled"> {{$t('The A6 format is only possible in landscape format.')}}  </span>
            </fieldset>
        </div>
        <div class="mt-1">
            <NumberInputComponent
                id="dpi"
                v-model="pdf.dpi"
                :label="$t('Resolution (DPI) (Standard: 72) (Maximum: 300)')"
            />
        </div>
        <div class="flex flex-row justify-center mt-4">
            <FormButton @click="createPdf()" :text="$t('Export PDF')"/>
        </div>
    </div>
</template>

<script>
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Switch,
    SwitchGroup,
    SwitchLabel,
    TransitionChild,
    TransitionRoot
} from '@headlessui/vue'
import {XIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import {useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import NumberInputComponent from "@/Components/Inputs/NumberInputComponent.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import IconLib from "@/Mixins/IconLib.vue";
import {DocumentReportIcon} from "@heroicons/vue/outline";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";

export default {
    name: "PdfCalendarExport",
    mixins: [Permissions, IconLib],
    components: {
        LastedProjects,
        BaseInput,
        BaseButton,
        Switch,
        SwitchGroup,
        DocumentReportIcon,
        SwitchLabel,
        ProjectSearch,
        BaseModal,
        NumberInputComponent,
        DateInputComponent,
        ModalHeader,
        TextInputComponent,
        FormButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon,
        DialogPanel,
        Listbox,
        ListboxButton,
        ListboxLabel,
        ListboxOption,
        ListboxOptions
    },
    props: ['pdfTitle', 'project'],
    emits: ['closed'],
    data(){
        return {
            open: true,
            showModalInformation: true,
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
                title: this.project ? this.project.name : this.$t('Room assignment'),
                start: null,
                end: null,
                paperSize: null,
                paperOrientation: null,
                project: null,
                dpi: 72,
            }),
            pdfSelectedProject: null,
            selectedPaperSize: { id: 'a4', name: 'A4 (Standard)' },
            selectedPaperOrientation: { id: 'portrait', title: this.$t('Portrait format') },
            checkedOrientation: 'portrait',
            orientationDisabled: false,
        }
    },
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

            if (this.pdfSelectedProject) {
                this.pdf.project = this.pdfSelectedProject.id;
            }

            this.pdf.post(route('calendar.export.pdf'), {
                preserveScroll: true,
            });
            this.closeModal(true);
        },
        changePaperOrientation(orientation){
            this.selectedPaperOrientation = orientation
        },
        addProjectToPdf(project){
            this.pdfSelectedProject = project;
        }
    },
    watch:{
        selectedPaperSize(){
            if (this.selectedPaperSize.id === 'a6') {
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
