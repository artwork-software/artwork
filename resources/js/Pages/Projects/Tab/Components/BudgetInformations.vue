<template>
    <div class="w-full">
        <div class="w-full flex items-center">
            <div class="text-secondary text-md">{{$t('Cost unit:')}} {{ project?.cost_center?.name }}</div>
            <IconEdit class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                           @click="openCopyrightModal"/>
            <ProjectCopyrightModal
                :show="showCopyrightModal"
                @close-modal="closeCopyrightModal"
                :project="project"
                :collecting-societies="loadedProjectInformation['BudgetInformation'].collecting_societies"
            />
        </div>
        <div class="text-secondary text-md">{{$t('Copyright')}}: {{ project.own_copyright ? $t('Yes') : $t('No') }}</div>
        <div class="text-secondary text-sm mt-2" v-if="project.own_copyright">
            {{ project?.collecting_society?.name }},
            {{ project.law_size === "SMALL" ? $t('Small law') : $t('Big law') }},
            {{ project.live_music ? $t('with live music') : $t('without live music') }}
        </div>
        <div class="text-secondary text-sm"  v-if="project.own_copyright">{{ project.cost_center_description }}</div>
        <hr class="my-10 border-darkGray">
        <div class="w-full flex items-center mb-4" v-if="this.$canAny(['can manage global project budgets']) || this.hasAdminRole() || this.hasBudgetAccess() || this.loadedProjectInformation['BudgetInformation'].project_manager_ids.includes(this.$page.props.user.id)">
            <div class="text-secondary text-md">{{$t('Documents')}}</div>
            <IconChevronDown class="w-4 h-4 ml-4" :class="[ showProjectFiles ? 'rotate-180' : '']"
                             @click="showProjectFiles = !showProjectFiles"/>
            <IconUpload class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                        @click="openFileUploadModal"/>
            <ProjectFileUploadModal :show="showFileUploadModal"
                                    :close-modal="closeFileUploadModal"
                                    :project-id="project.id"
                                    :budget-access="loadedProjectInformation['BudgetInformation'].access_budget"/>
        </div>
        <div v-if="showProjectFiles">
            <div v-if="loadedProjectInformation['BudgetInformation'].project_files.length > 0">
                <div v-for="projectFile in loadedProjectInformation['BudgetInformation'].project_files">
                    <div
                        v-if="projectFile.accessibleUsers?.filter(user => user.id === $page.props.user.id).length > 0 || this.hasAdminRole()"
                        class="flex items-center w-full mb-2 cursor-pointer text-secondary hover:text-white"
                    >
                        <IconDownload class="w-4 h-4 mr-2" @click="downloadProjectFile(projectFile)"/>
                        <div @click="openFileEditModal(projectFile)">{{ projectFile.name }}</div>
                        <IconCircleX class="w-4 h-4 ml-auto bg-error rounded-full text-white" @click="openFileDeleteModal(projectFile)"/>
                    </div>
                </div>

                <ProjectFileEditModal
                    :show="showFileEditModal"
                    :close-modal="closeFileEditModal"
                    :file="projectFileToEdit"
                />

                <FileDeleteModal
                    :show="showFileDeleteModal"
                    :close-modal="closeFileDeleteModal"
                    :file="projectFileToDelete"
                    type="project"
                />
            </div>
            <div v-else>
                <div class="text-secondary text-sm mt-2">{{$t('No documents available')}}</div>
            </div>
        </div>
        <div
            v-if="$can('view edit upload contracts') || this.hasBudgetAccess()">
            <hr class="my-10 border-darkGray">

            <div class="w-full flex items-center mb-4">
                <div class="text-secondary text-md">{{ $t('Contracts')}}</div>
                <IconChevronDown class="w-4 h-4 ml-4" :class="[ showContracts ? 'rotate-180' : '']"
                                 @click="showContracts = !showContracts"/>
                <IconUpload class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                            @click="openContractUploadModal"/>
                <ContractUploadModal
                    :show="showContractUploadModal"
                    :project-id="project.id"
                    :budget-access="this.loadedProjectInformation['BudgetInformation'].access_budget"
                    :contract-types="this.loadedProjectInformation['BudgetInformation'].contract_types"
                    :company-types="this.loadedProjectInformation['BudgetInformation'].company_types"
                    :currencies="this.loadedProjectInformation['BudgetInformation'].currencies"
                    @close-modal="closeContractUploadModal"
                />
            </div>
            <div v-if="showContracts">
                <div v-if="this.loadedProjectInformation['BudgetInformation'].contracts.length > 0">
                    <div v-for="contract in this.loadedProjectInformation['BudgetInformation'].contracts">
                        <div
                            v-if="contract.accessibleUsers?.filter(user => user.id === $page.props.user.id).length > 0 || hasAdminRole()"
                            class="flex items-center w-full mb-2 cursor-pointer text-secondary hover:text-white"
                        >
                            <IconDownload class="w-4 h-4 mr-2" @click="downloadContract(contract)"/>
                            <div @click="openContractEditModal(contract)">{{ contract.name }}</div>
                            <ContractDeleteModal v-if="showContractDeleteModal"
                                                 :show="showContractDeleteModal === contract?.id"
                                                 :close-modal="closeContractDeleteModal"
                                                 :contract="contract"/>
                            <ContractEditModal v-if="showContractEditModal"
                                               :show="showContractEditModal === contract?.id"
                                               :close-modal="closeContractEditModal"
                                               :contract="contract"
                                               :currencies="this.loadedProjectInformation['BudgetInformation'].currencies"
                                               :company-types="this.loadedProjectInformation['BudgetInformation'].company_types"
                                               :contract-types="this.loadedProjectInformation['BudgetInformation'].contract_types"
                            />
                            <IconCircleX class="w-4 h-4 ml-auto bg-error rounded-full text-white" @click="openContractDeleteModal(contract)"/>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div class="text-secondary text-sm mt-2">{{$t('No contracts available')}}</div>
                </div>
            </div>
            <div
                v-if="this.$can('view edit add money_sources') || this.hasAdminRole() || this.hasBudgetAccess()">
                <hr class="my-10 border-darkGray">

                <div class="w-full flex items-center mb-4">
                    <div class="text-secondary text-md">{{$t('Linked sources of funding')}}</div>
                    <IconChevronDown class="w-4 h-4 ml-4" :class="[ showMoneySources ? 'rotate-180' : '']"
                                     @click="showMoneySources = !showMoneySources"/>
                </div>
                <div v-if="showMoneySources">
                    <div v-if="this.loadedProjectInformation['BudgetInformation'].project_money_sources.length > 0">
                        <div class="w-full flex items-center mb-2 text-secondary"
                             v-for="moneySource in this.loadedProjectInformation['BudgetInformation'].project_money_sources">
                            <Link v-if="this.$can('view edit add money_sources') || this.hasAdminRole()"
                                  class="cursor-pointer hover:text-secondaryHover text-linkOnDarkColor  underline" :href="route('money_sources.show', {moneySource: moneySource.id})">
                                {{moneySource.name}}
                            </Link>
                            <div v-else>{{moneySource.name}}</div>
                        </div>
                    </div>
                    <div v-else>
                        <div class="text-secondary text-sm mt-2">{{$t('No sources of funding available')}}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
import {
    DownloadIcon,
    UploadIcon,
    XCircleIcon,
    PencilAltIcon,
    ChevronDownIcon
} from '@heroicons/vue/outline';
import ContractModuleDeleteModal from "@/Layouts/Components/ContractModuleDeleteModal.vue";
import ContractModuleUploadModal from "@/Layouts/Components/ContractModuleUploadModal.vue";
import ProjectFileUploadModal from "@/Layouts/Components/ProjectFileUploadModal.vue";
import ContractDeleteModal from "@/Layouts/Components/ContractDeleteModal.vue";
import FileDeleteModal from "@/Layouts/Components/FileDeleteModal.vue";
import ProjectFileEditModal from "@/Layouts/Components/ProjectFileEditModal.vue";
import ContractUploadModal from "@/Layouts/Components/ContractUploadModal.vue";
import ContractEditModal from "@/Layouts/Components/ContractEditModal.vue";
import ProjectCopyrightModal from "@/Layouts/Components/ProjectCopyrightModal.vue";
import Permissions from "@/Mixins/Permissions.vue";
import { Link } from '@inertiajs/vue3';
import IconLib from "@/Mixins/IconLib.vue";

export default {
    mixins: [Permissions, IconLib],
    name: "ProjectSidenav",
    components: {
        ContractEditModal,
        ContractUploadModal,
        ProjectFileEditModal,
        FileDeleteModal,
        ContractDeleteModal,
        ProjectFileUploadModal,
        ContractModuleDeleteModal,
        DownloadIcon,
        UploadIcon,
        XCircleIcon,
        ContractModuleUploadModal,
        PencilAltIcon,
        ChevronDownIcon,
        ProjectCopyrightModal,
        Link
    },
    props: [
        'loadedProjectInformation',
        'project',
    ],
    data() {
        return {
            showFileDeleteModal: false,
            showFileUploadModal: false,
            showContractUploadModal: false,
            showContractDeleteModal: null,
            showProjectFiles: false,
            showContracts: false,
            showMoneySources: false,
            showFileEditModal: false,
            showContractEditModal: null,
            showCopyrightModal: false,
            projectFileToEdit: null,
            projectFileToDelete: null

        }
    },
    methods: {
        hasBudgetAccess() {
          return this.loadedProjectInformation['BudgetInformation'].access_budget.filter(
              (user) => user.id === this.$page.props.user.id
          ).length > 0;
        },
        downloadContract(contract) {
            let link = document.createElement('a');
            link.href = route('contracts.download', {contract: contract});
            link.target = '_blank';
            link.click();
        },
        downloadProjectFile(file) {
            let link = document.createElement('a');
            link.href = route('download_file', {project_file: file});
            link.target = '_blank';
            link.click();
        },
        openCopyrightModal() {
            this.showCopyrightModal = true
        },
        closeCopyrightModal() {
            this.showCopyrightModal = false
        },
        openFileEditModal(projectFile) {
            this.projectFileToEdit = projectFile
            this.showFileEditModal = true
        },
        openContractEditModal(contract) {
            this.showContractEditModal = contract.id
        },
        closeFileEditModal() {
            this.projectFileToEdit = null
            this.showFileEditModal = false
        },
        closeContractEditModal() {
            this.showContractEditModal = null
        },
        openFileUploadModal() {
            this.showFileUploadModal = true
        },
        closeFileUploadModal() {
            this.showFileUploadModal = false
        },
        openFileDeleteModal(projectFile) {
            this.projectFileToDelete = projectFile
            this.showFileDeleteModal = true
        },
        closeFileDeleteModal() {
            this.projectFileToDelete = null
            this.showFileDeleteModal = false
        },
        openContractUploadModal() {
            this.showContractUploadModal = true
        },
        closeContractUploadModal() {
            this.showContractUploadModal = false
        },
        openContractDeleteModal(contract) {
            this.showContractDeleteModal = contract.id
        },
        closeContractDeleteModal() {
            this.showContractDeleteModal = null;
        },
    }
}
</script>

<style scoped>

</style>
