<template>
    <div class="w-full mt-24">
        <div class="w-full flex items-center">
            <div class="text-secondary text-md">Kostenträger: {{ costCenter.name }}</div>
            <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                           @click="openContractUploadModal"/>
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
            <ProjectFileUploadModal :show="showFileUploadModal" :close-modal="closeFileUploadModal" :project-id="projectId"/>
        </div>
        <div v-if="showProjectFiles">
            <div v-if="projectFiles.length > 0">
                <div class="w-full flex items-center mb-2 cursor-pointer text-secondary hover:text-white"
                     v-for="projectFile in projectFiles"
                >
                    <DownloadIcon class="w-4 h-4 mr-2" @click="download(projectFile)"/>
                    <div @click="download(projectFile)">{{ projectFile.name }}</div>
                    <XCircleIcon class="w-4 h-4 ml-auto" @click="openFileDeleteModal"/>
                    <ProjectFileDeleteModal :show="showFileDeleteModal" :close-modal="closeFileDeleteModal" :project-file="projectFile" />

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
        </div>
        <div v-if="showContracts">
            <div v-if="contracts.length > 0">
                <div class="w-full flex items-center mb-2 cursor-pointer text-secondary hover:text-white"
                     v-for="contract in contracts"
                >
                    <DownloadIcon class="w-4 h-4 mr-2" @click="download(contract)"/>
                    <div @click="download(contract)">{{ contract.name }}</div>
                    <XCircleIcon class="w-4 h-4 ml-auto" @click="openContractDeleteModal"/>
                    <ContractDeleteModal :show="showContractDeleteModal" :close-modal="closeContractDeleteModal" :contract="contract" />
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

export default {
    name: "ProjectSidenav",
    components: {
        ProjectFileDeleteModal,
        ContractDeleteModal,
        ProjectFileUploadModal,
        ContractModuleDeleteModal,
        DownloadIcon,
        UploadIcon,
        XCircleIcon,
        ContractModuleUploadModal,
        PencilAltIcon,
        ChevronDownIcon
    },
    props: {
        projectId: Number,
        costCenter: Object,
        copyright: Object,
        projectFiles: Array,
        contracts: Array,
        moneySources: Array
    },
    data() {
        return {
            showFileDeleteModal: false,
            showFileUploadModal: false,
            showContractUploadModal: false,
            showContractDeleteModal: false,
            showProjectFiles: false,
            showContracts: false,
            showMoneySources: false

        }
    },
    methods: {
        download(module) {
            let link = document.createElement('a');
            link.href = route('contracts.module.download', {module: module});
            link.target = '_blank';
            link.click();
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
        openContractDeleteModal() {
            this.showContractDeleteModal = true
        },
        closeContractDeleteModal() {
            this.showContractDeleteModal = false
        },
    }
}
</script>

<style scoped>

</style>
