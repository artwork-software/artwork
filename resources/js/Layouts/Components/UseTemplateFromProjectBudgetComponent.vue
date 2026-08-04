<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_budget_edit.svg">
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text">
                            {{ $t('Read from project') }}
                        </div>
                    </h1>
                    <h2 class="text-sm/5 font-bold text-text-subtle mb-2 mt-8">
                        {{ $t('To make your work easier, use an existing calculation from another project.') }}
                    </h2>
                    <div v-if="selectedProject !== null" class="flex items-center my-3 text-sm/5 font-semibold text-text">
                        {{ $t('Currently selected project template') }}: {{ this.selectedProject?.name }}
                        <div v-if="this.selectedProject" class="flex items-center my-auto">
                            <button type="button"
                                    @click="selectedProject = null">
                                <IconCircleX class="pl-2 h-6 w-6 hover:text-danger text-text"/>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center w-full mr-2">
                        <div class="w-full">
                            <inputComponent v-model="this.project_query" :placeholder="$t('From which project?*')"/>
                            <div
                                v-if="project_search_results.length > 0"
                                class="bg-surface-inverse truncate sm:text-sm">
                                <div v-for="(project, index) in project_search_results"
                                     :key="index"
                                     @click="this.selectedProject = project;"
                                     class="p-4 text-white border-l-4 hover:border-l-success border-l-surface-inverse cursor-pointer">
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
import {IconCheck, IconChevronDown, IconCircleX, IconX} from "@tabler/icons-vue";

import JetDialogModal from "@/Jetstream/DialogModal.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
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
        IconX,
        IconCheck,
        IconChevronDown,
        InputComponent,
        IconCircleX
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
