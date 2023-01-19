<template>
    <div class="w-full mt-24">
        <div class="w-full flex items-center">
            <div class="text-secondary text-md">Kostenträger: {{ costCenter.name }}</div>
            <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                           @click="openProjectDataModal"/>
            <ProjectDataEditModal :show="showProjectDataModal" :close-modal="closeProjectDataModal" :project="project" :traits="traits" />
        </div>
        <div class="text-secondary text-md">Urheberrecht: {{ copyright.own_copyright ? 'Ja' : 'Nein' }}</div>
        <div class="text-secondary text-sm mt-2">
            {{ copyright.collecting_society }},
            {{ copyright.law_size === "small" ? 'kleines Recht' : 'großes Recht' }},
            {{ copyright.live_music ? 'mit Livemusik' : 'ohne Livemusik' }}
        </div>
        <div class="text-secondary text-sm">{{ costCenter.description }}</div>

        <hr class="my-10 border-darkGray">

        <div class="w-full flex items-center mb-4">
            <div class="text-secondary text-md">Dokumente</div>
            <ChevronDownIcon class="w-4 h-4 ml-4" :class="[ showProjectFiles ? 'rotate-180' : '']"
                             @click="showProjectFiles = !showProjectFiles"/>
            <UploadIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                        @click="openFileUploadModal"/>
            <ProjectFileUploadModal :show="showFileUploadModal" :close-modal="closeFileUploadModal" :project-id="project.id"/>
        </div>
        <div v-if="showProjectFiles">
            <div v-if="projectFiles.length > 0">
                <div class="w-full flex items-center mb-2 cursor-pointer text-secondary hover:text-white"
                     v-for="projectFile in projectFiles"
                >
                    <DownloadIcon class="w-4 h-4 mr-2" @click="downloadProjectFile(projectFile)"/>
                    <div @click="openFileEditModal">{{ projectFile.name }}</div>
                    <XCircleIcon class="w-4 h-4 ml-auto" @click="openFileDeleteModal"/>
                    <ProjectFileDeleteModal :show="showFileDeleteModal" :close-modal="closeFileDeleteModal" :project-file="projectFile" />
                    <ProjectFileEditModal :show="showFileEditModal" :close-modal="closeFileEditModal" :file="projectFile" />

                </div>
            </div>
            <div v-else>
                <div class="text-secondary text-sm mt-2">Keine Dokumente vorhanden</div>
            </div>
        </div>

        <hr class="my-10 border-darkGray">

        <div class="w-full flex items-center mb-4">
            <div class="text-secondary text-md">Verträge</div>
            <ChevronDownIcon class="w-4 h-4 ml-4" :class="[ showContracts ? 'rotate-180' : '']"
                             @click="showContracts = !showContracts"/>
            <UploadIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                        @click="openContractUploadModal"/>
            <ContractUploadModal :show="showContractUploadModal" :close-modal="closeContractUploadModal" :project-id="project.id" />
        </div>
        <div v-if="showContracts">
            <div v-if="contracts.length > 0">
                <div class="w-full flex items-center mb-2 cursor-pointer text-secondary hover:text-white"
                     v-for="contract in contracts"
                >
                    <DownloadIcon class="w-4 h-4 mr-2" @click="downloadContract(contract)"/>
                    <div @click="openContractEditModal(contract)">{{ contract.name }}</div>
                    <ContractDeleteModal :show="showContractDeleteModal === contract.id" :close-modal="closeContractDeleteModal" :contract="contract" />
                    <ContractEditModal :show="showContractEditModal === contract.id" :close-modal="closeContractEditModal" :contract="contract" />
                    <XCircleIcon class="w-4 h-4 ml-auto" @click="openContractDeleteModal(contract)"/>
                </div>
            </div>
            <div v-else>
                <div class="text-secondary text-sm mt-2">Keine Verträge vorhanden</div>
            </div>

        </div>


        <hr class="my-10 border-darkGray">

        <div class="w-full flex items-center mb-4">
            <div class="text-secondary text-md">Verlinkte Finanzierungsquellen</div>
            <ChevronDownIcon class="w-4 h-4 ml-4" :class="[ showMoneySources ? 'rotate-180' : '']"
                             @click="showMoneySources = !showMoneySources"/>
        </div>
        <div v-if="showMoneySources">
            <div v-if="moneySources.length > 0">
                <div class="w-full flex items-center mb-2 cursor-pointer text-secondary hover:text-white"
                     v-for="moneySource in moneySources"
                >
                    <div>{{ moneySource.name }}</div>
                </div>
            </div>
            <div v-else>
                <div class="text-secondary text-sm mt-2">Keine Finanzierungsquellen vorhanden</div>
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
import ProjectFileUploadModal from "@/Layouts/Components/ProjectFileUploadModal";
import ContractDeleteModal from "@/Layouts/Components/ContractDeleteModal";
import ProjectFileDeleteModal from "@/Layouts/Components/ProjectFileDeleteModal";
import ProjectFileEditModal from "@/Layouts/Components/ProjectFileEditModal";
import ContractUploadModal from "@/Layouts/Components/ContractUploadModal";
import ProjectDataEditModal from "@/Layouts/Components/ProjectDataEditModal";
import ContractEditModal from "@/Layouts/Components/ContractEditModal.vue";

export default {
    name: "ProjectSidenav",
    components: {
        ContractEditModal,
        ProjectDataEditModal,
        ContractUploadModal,
        ProjectFileEditModal,
        ProjectFileDeleteModal,
        ContractDeleteModal,
        ProjectFileUploadModal,
        ContractModuleDeleteModal,
        DownloadIcon,
        UploadIcon,
        XCircleIcon,
        ContractModuleUploadModal,
        PencilAltIcon,
        ChevronDownIcon,
    },
    props: {
        project: Object,
        costCenter: Object,
        copyright: Object,
        projectFiles: Array,
        contracts: Array,
        moneySources: Array,
        traits: Object
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
            showProjectDataModal: false,


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
        openProjectDataModal() {
            this.showProjectDataModal = true
        },
        closeProjectDataModal() {
            this.showProjectDataModal = false
        },
        openFileEditModal() {
            this.showFileEditModal = true
        },
        openContractEditModal(contract) {
            this.showContractEditModal = contract.id
        },
        closeFileEditModal() {
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
        openFileDeleteModal() {
            this.showFileDeleteModal = true
        },
        closeFileDeleteModal() {
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
