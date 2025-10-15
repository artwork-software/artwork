<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_money_source_create.svg">
            <div class="mx-4">
                <ModalHeader
                    :title="$t('Link projects')"
                    :description="$t('Assign projects to this funding source. Only these projects can later be linked to this source of funding.')"
                />
                <!--   Heading   -->
                <div>
                    <div class="flex w-full mt-6">
                        <div class="flex w-full relative">
                            <div class="w-full">
                                <BaseInput id="projectSearch" v-model="project_query" label="Suche nach Projekten"/>
                            </div>
                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100"
                                        leave-to-class="opacity-0">
                                <div v-if="project_search_results.length > 0 && project_query.length > 0"
                                     class="absolute w-full top-16 z-10 max-h-60 bg-primary shadow-lg rounded-lg text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
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

                    <LastedProjects
                        :limit="10"
                        @select="addProjectToArray"
                    />

                    <div class="sDark mt-4" v-if="this.linkedProjectsArray.length > 0">
                        {{$t('Linked projects')}}:
                    </div>
                    <div class="mb-4 divide-gray-200 divide-y divide-dashed mt-2">
                        <div v-for="project in linkedProjectsArray" class="xsDark py-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="flex">
                                        {{ project.name }}
                                    </span>
                                </div>
                                <button type="button" @click="deleteProjectFromArray(project)">
                                    <span class="sr-only">{{$t('Remove user from team')}}</span>
                                    <IconCircleX stroke-width="1.5" class="ml-3 text-artwork-buttons-create h-5 w-5 hover:text-error "/>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-center mt-8">
                        <FormButton @click="updateLinkedProjects()"
                                    :text="$t('Save')"
                        ></FormButton>
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {CheckIcon, ChevronDownIcon, PlusCircleIcon, XIcon} from '@heroicons/vue/outline';
import {XCircleIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";


export default {
    name: 'LinkProjectsToMoneySourcesComponent',
    mixins: [Permissions, IconLib],
    components: {
        LastedProjects,
        BaseInput,
        TextInputComponent,
        ModalHeader,
        BaseModal,
        FormButton,
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
