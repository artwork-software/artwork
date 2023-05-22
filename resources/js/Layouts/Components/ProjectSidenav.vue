<template>
    <div class="w-full mt-24">




        <div class="w-full flex items-center">
            <div class="text-secondary text-md">Kostenträger: {{ costCenter?.name }}</div>
            <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                           @click="openCopyrightModal"/>
            <ProjectCopyrightModal
                :show="showCopyrightModal"
                @close-modal="closeCopyrightModal"
                :project="project"
                :copyright="copyright"
                :costCenter="costCenter"
            />
        </div>
        <div class="text-secondary text-md">Urheberrecht: {{ copyright?.own_copyright ? 'Ja' : 'Nein' }}</div>
        <div class="text-secondary text-sm mt-2">
            {{ copyright?.collecting_society.name }},
            {{ copyright?.law_size === "small" ? 'kleines Recht' : 'großes Recht' }},
            {{ copyright?.live_music ? 'mit Livemusik' : 'ohne Livemusik' }}
        </div>
        <div class="text-secondary text-sm">{{ costCenter?.description }}</div>

        <hr class="my-10 border-darkGray">

        <div class="w-full flex items-center mb-4">
            <div class="text-secondary text-md">Dokumente</div>
            <ChevronDownIcon class="w-4 h-4 ml-4" :class="[ showProjectFiles ? 'rotate-180' : '']"
                             @click="showProjectFiles = !showProjectFiles"/>
            <UploadIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                        @click="openFileUploadModal"/>
            <ProjectFileUploadModal :show="showFileUploadModal" :close-modal="closeFileUploadModal"
                                    :project-id="project.id"
                                    :budget-access="budgetAccess"/>
        </div>
        <div v-if="showProjectFiles">
            <div v-if="projectFiles.length > 0">
                <div v-for="projectFile in projectFiles">
                    <div
                        v-if="projectFile.accessibleUsers.filter(user => user.id === $page.props.user.id).length > 0 || $page.props.is_admin"
                        class="flex items-center w-full mb-2 cursor-pointer text-secondary hover:text-white"
                    >
                        <DownloadIcon class="w-4 h-4 mr-2" @click="downloadProjectFile(projectFile)"/>
                        <div @click="openFileEditModal(projectFile)">{{ projectFile.name }}</div>
                        <XCircleIcon class="w-4 h-4 ml-auto" @click="openFileDeleteModal(projectFile)"/>
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
                <div class="text-secondary text-sm mt-2">Keine Dokumente vorhanden</div>
            </div>
        </div>
        <div
            v-if="$can('view edit upload contracts') || this.project.access_budget.includes(this.$page.props.user.id)">
            <hr class="my-10 border-darkGray">

            <div class="w-full flex items-center mb-4">
                <div class="text-secondary text-md">Verträge</div>
                <ChevronDownIcon class="w-4 h-4 ml-4" :class="[ showContracts ? 'rotate-180' : '']"
                                 @click="showContracts = !showContracts"/>
                <UploadIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                            @click="openContractUploadModal"/>
                <ContractUploadModal
                    :show="showContractUploadModal"
                    :close-modal="closeContractUploadModal"
                    :project-id="project.id"
                    :budget-access="budgetAccess"
                />
            </div>
            <div v-if="showContracts">
                <div v-if="contracts?.length > 0">
                    <div v-for="contract in contracts">
                        <div
                            v-if="contract.accessibleUsers.filter(user => user.id === $page.props.user.id).length > 0 || $page.props.is_admin"
                            class="flex items-center w-full mb-2 cursor-pointer text-secondary hover:text-white"
                        >
                            <DownloadIcon class="w-4 h-4 mr-2" @click="downloadContract(contract)"/>
                            <div @click="openContractEditModal(contract)">{{ contract.name }}</div>
                            <ContractDeleteModal :show="showContractDeleteModal === contract?.id"
                                                 :close-modal="closeContractDeleteModal" :contract="contract"/>
                            <ContractEditModal :show="showContractEditModal === contract?.id"
                                               :close-modal="closeContractEditModal" :contract="contract"/>
                            <XCircleIcon class="w-4 h-4 ml-auto" @click="openContractDeleteModal(contract)"/>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div class="text-secondary text-sm mt-2">Keine Verträge vorhanden</div>
                </div>

            </div>

            <div
                v-if="$can('view edit add money_sources') || this.project.access_budget.includes(this.$page.props.user.id)">
                <hr class="my-10 border-darkGray">

                <div class="w-full flex items-center mb-4">
                    <div class="text-secondary text-md">Verlinkte Finanzierungsquellen</div>
                    <ChevronDownIcon class="w-4 h-4 ml-4" :class="[ showMoneySources ? 'rotate-180' : '']"
                                     @click="showMoneySources = !showMoneySources"/>
                </div>
                <div v-if="showMoneySources">
                    <div v-if="moneySources?.length > 0">
                        <div class="w-full flex items-center mb-2 text-secondary"
                             v-for="moneySource in moneySources">
                            <div>{{ moneySource.name }}</div>
                        </div>
                    </div>
                    <div v-else>
                        <div class="text-secondary text-sm mt-2">Keine Finanzierungsquellen vorhanden</div>
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
import ContractModuleDeleteModal from "@/Layouts/Components/ContractModuleDeleteModal";
import ContractModuleUploadModal from "@/Layouts/Components/ContractModuleUploadModal";
import ProjectFileUploadModal from "@/Layouts/Components/ProjectFileUploadModal.vue";
import ContractDeleteModal from "@/Layouts/Components/ContractDeleteModal";
import FileDeleteModal from "@/Layouts/Components/FileDeleteModal.vue";
import ProjectFileEditModal from "@/Layouts/Components/ProjectFileEditModal.vue";
import ContractUploadModal from "@/Layouts/Components/ContractUploadModal";
import ContractEditModal from "@/Layouts/Components/ContractEditModal.vue";
import ProjectCopyrightModal from "@/Layouts/Components/ProjectCopyrightModal.vue";
import Permissions from "@/mixins/Permissions.vue";

export default {
    mixins: [Permissions],
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
        ProjectCopyrightModal
    },
    props: {
        project: Object,
        costCenter: Object,
        copyright: Object,
        projectFiles: Array,
        contracts: Array,
        moneySources: Array,
        traits: Object,
        budgetAccess: Object,
    },
    data() {
        return {
            showFileDeleteModal: false,
            showFileUploadModal: false,
            showContractUploadModal: false,
            showContractDeleteModal: false,
            showProjectFiles: false,
            showContracts: false,
            showMoneySources: false,
            showFileEditModal: false,
            showContractEditModal: false,
            showCopyrightModal: false,
            projectFileToEdit: null,
            projectFileToDelete: null

        }
    },
    methods: {
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
