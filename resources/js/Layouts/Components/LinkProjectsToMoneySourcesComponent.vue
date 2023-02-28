<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img alt="Details" src="/Svgs/Overlays/illu_money_source_create.svg" class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow flex items-center headline1">
                            Projekte verlinken
                        </div>
                    </h1>
                    <div>
                        <h2 class="xsLight mb-2 mt-4">
                            Weise Projekte zu dieser Finanzierungsquelle zu. Es können später nur diese Projekte mit dieser Finanzierungsquelle verlinkt werden.
                        </h2>
                        <div class="flex w-full mt-6">
                            <div class="flex w-full">
                                    <div class="w-full flex">
                                        <input id="userSearch" v-model="project_query" type="text" autocomplete="off"
                                               placeholder="Suche nach Projekten"
                                               class="h-10 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    </div>
                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100"
                                                leave-to-class="opacity-0">
                                        <div v-if="project_search_results.length > 0 && project_query.length > 0"
                                             class="absolute w-[88%] z-10 mt-10 max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                            <div class="border-gray-200">
                                                <div v-for="(project, index) in project_search_results" :key="index"
                                                     class="flex items-center cursor-pointer">
                                                    <div class="flex-1 text-sm py-4">
                                                        <p @click="addProjectToArray(project)"
                                                           class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                            {{ project.name }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                            </div>
                        </div>
                        <div class="sDark mt-4" v-if="this.linkedProjectsArray.length > 0">
                            Verlinkte Projekte:
                        </div>
                        <span v-for="project in linkedProjectsArray"
                              class="flex justify-between mt-4 mr-1 items-center xsDark border-1 border-b pb-3">
                            <div class="flex items-center">
                                <div class="flex items-center">
                                    <span class="flex">
                                        {{ project.name }}
                                    </span>
                                </div>
                                <button type="button" @click="deleteProjectFromArray(project)">
                                    <span class="sr-only">User aus Team entfernen</span>
                                    <XCircleIcon class="ml-3 text-buttonBlue h-5 w-5 hover:text-error "/>
                                </button>
                            </div>
                            </span>
                        <div class="flex justify-center">
                            <AddButton @click="updateLinkedProjects()"
                                       class="mt-8 py-5 px-24 flex" text="Speichern"
                                       mode="modal"></AddButton>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>

</template>

<script>

import {Listbox, ListboxButton, ListboxOption, ListboxOptions, RadioGroup, RadioGroupOption} from "@headlessui/vue";

import JetDialogModal from "@/Jetstream/DialogModal";
import {CheckIcon, ChevronDownIcon, PlusCircleIcon, XIcon} from '@heroicons/vue/outline';
import AddButton from "@/Layouts/Components/AddButton.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/inertia-vue3";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";


export default {
    name: 'LinkProjectsToMoneySourcesComponent',

    components: {
        AddButton,
        JetDialogModal,
        XIcon,
        CheckIcon,
        ChevronDownIcon,
        PlusCircleIcon,
        XCircleIcon
    },

    data() {
        return {
            project_query: '',
            project_search_results: [],
            linkedProjectsArray: this.linkedProjects ? this.linkedProjects : [],
        }
    },

    props: ['moneySource','linkedProjects'],

    emits: ['closed'],

    watch: {
        project_query: {
            handler() {
                if (this.project_query.length > 0) {
                    axios.get('/projects/search', {
                        params: {query: this.project_query}
                    }).then(response => {
                        this.project_search_results = response.data;
                    })
                }
            },
            deep: true
        },
    },
    computed: {

    },

    methods: {
        openModal() {
        },
        addProjectToArray(projectToAdd){
            if(this.linkedProjectsArray !== [])
            for (let linkedProject of this.linkedProjectsArray) {
                if (projectToAdd.id === linkedProject.id) {
                    this.project_query = ""
                    return;
                }
            }else{
                this.linkedProjectsArray = [projectToAdd]
            }
            this.project_query = "";
            this.linkedProjectsArray.push(projectToAdd);
        },
        deleteProjectFromArray(project) {
            if (this.linkedProjectsArray.includes(project)) {
                this.linkedProjectsArray.splice(this.linkedProjectsArray.indexOf(project), 1);
            }
        },
        updateLinkedProjects(){
            let linkedProjectIds = [];
            if(this.linkedProjectsArray?.length > 0){
                this.linkedProjectsArray.forEach((linkedProject) => {
                    linkedProjectIds.push(linkedProject.id);
                })
            }
            this.$inertia.patch(route('money_sources.update_projects', this.moneySource.id), {
                linkedProjectIds: linkedProjectIds
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    this.closeModal(true);
                }
            });
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>

<style scoped></style>
