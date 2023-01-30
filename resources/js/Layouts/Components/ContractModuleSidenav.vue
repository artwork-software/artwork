<template>
    <div class="w-full mt-36">
        <div class="w-full flex items-center mb-4">
            <div class="text-secondary text-md font-semibold">
                Vertragsbausteine
            </div>
            <UploadIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg" @click="openUploadModal" v-if="this.$page.props.can.contract_edit_upload"/>
        </div>
        <div class="w-full flex items-center mb-2 cursor-pointer text-secondary hover:text-white"
             v-for="contractModule in contractModules.data"
        >
            <DownloadIcon class="w-4 h-4 mr-2" @click="download(contractModule)"/>
            <div @click="download(contractModule)">{{ contractModule.name }}</div>
            <XCircleIcon class="w-4 h-4 ml-auto" @click="openDeleteModal" v-if="this.$page.props.can.contract_edit_upload"/>

            <ContractModuleDeleteModal
                :show="showDeleteModal"
                :close-modal="closeDeleteModal"
                :contract-module="contractModule"
            />
            <ContractModuleUploadModal
                :show="showUploadModal"
                :close-modal="closeUploadModal"
            />
        </div>
    </div>
</template>

<script>
import {
    DownloadIcon,
    UploadIcon,
    XCircleIcon
} from '@heroicons/vue/outline';
import ContractModuleDeleteModal from "@/Layouts/Components/ContractModuleDeleteModal";
import ContractModuleUploadModal from "@/Layouts/Components/ContractModuleUploadModal";
import {usePage} from "@inertiajs/inertia-vue3";

export default {
    name: "ContractModuleSidenav",
    props: {
        contractModules: Object
    },
    components: {
        ContractModuleDeleteModal,
        DownloadIcon,
        UploadIcon,
        XCircleIcon,
        ContractModuleUploadModal
    },
    data() {
        return {
            showDeleteModal: false,
            showUploadModal: false
        }
    },
    methods: {
        usePage,
        download(module) {
            let link = document.createElement('a');
            link.href = route('contracts.module.download', {module: module});
            link.target = '_blank';
            link.click();
        },
        openUploadModal() {
            this.showUploadModal = true
        },
        closeUploadModal() {
            this.showUploadModal = false
        },
        openDeleteModal() {
            this.showDeleteModal = true
        },
        closeDeleteModal() {
            this.showDeleteModal = false
        }
    }
}
</script>

<style scoped>

</style>
