<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_budget_edit.svg">
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            {{ $t('Read from project') }}
                        </div>
                    </h1>
                    <h2 class="xsLight mb-2 mt-8">
                        {{ $t('To make your work easier, use an existing calculation from another project.') }}
                    </h2>
                    <div v-if="selectedProject !== null" class="flex items-center my-3 xsDark">
                        {{ $t('Currently selected project template') }}: {{ this.selectedProject?.name }}
                        <div v-if="this.selectedProject" class="flex items-center my-auto">
                            <button type="button"
                                    @click="selectedProject = null">
                                <XCircleIcon class="pl-2 h-6 w-6 hover:text-error text-primary"/>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center w-full mr-2">
                        <div class="w-full">
                            <inputComponent v-model="this.project_query" :placeholder="$t('From which project?*')"/>
                            <div
                                v-if="project_search_results.length > 0"
                                class="bg-primary truncate sm:text-sm">
                                <div v-for="(project, index) in project_search_results"
                                     :key="index"
                                     @click="this.selectedProject = project;"
                                     class="p-4 text-white border-l-4 hover:border-l-success border-l-primary cursor-pointer">
                                    {{ project.name }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-center">
                        <FormButton @click="useProjectBudgetAsTemplate()"
                                   :disabled="selectedProject === null"
                                   :text="$t('Import calculation')"
                        />
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script>

import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon, CheckIcon, ChevronDownIcon} from '@heroicons/vue/outline';
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'UseTemplateFromProjectBudgetComponent',
    mixins: [Permissions],
    components: {
        BaseModal,
        FormButton,
        JetDialogModal,
        XIcon,
        CheckIcon,
        ChevronDownIcon,
        InputComponent,
        XCircleIcon
    },
    data() {
        return {
            selectedProject: null,
            project_query: '',
            project_search_results: [],
        }
    },
    props: [
        'projectId',
        'templates'
    ],
    emits: ['closed'],
    watch: {
        project_query: {
            handler() {
                if (this.project_query.length > 0) {
                    axios.get('/projects/search', {
                        params: {query: this.project_query}
                    }).then(response => {
                        this.project_search_results = response.data
                    })
                }
            },
            deep: true
        },
    },
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        useProjectBudgetAsTemplate() {
            this.$inertia.post(route('project.budget.template.project'), {
                template_project_id: this.selectedProject.id,
                project_id: this.projectId
            });
            this.closeModal(true);
        }
    },
}
</script>
