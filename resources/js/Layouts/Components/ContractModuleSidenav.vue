<template>
    <div class="w-full mt-36" v-if="$canAny(['view edit upload contracts','can see and download contract modules']) || hasAdminRole()">
        <div class="w-full flex items-center mb-4">
            <div class="text-secondary text-md font-semibold">
                Vertragsbausteine
            </div>
            <UploadIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg" @click="openUploadModal" v-if="$can('view edit upload contracts') || hasAdminRole()"/>
        </div>
        <div class="w-full flex items-center mb-2 cursor-pointer text-secondary hover:text-white"
             v-for="contractModule in contractModules.data"
        >
            <DownloadIcon class="w-4 h-4 mr-2" @click="download(contractModule)" v-if="$canAny(['view edit upload contracts','can see and download contract modules']) || hasAdminRole()"/>
            <div v-if="$canAny(['view edit upload contracts']) || hasAdminRole()" @click="download(contractModule)">{{ contractModule.name }}</div>
            <div v-else>
                {{ contractModule.name }}
            </div>
            <XCircleIcon class="w-4 h-4 ml-auto bg-error rounded-full text-white" @click="openDeleteModal(contractModule)" v-if="$canAny(['view edit upload contracts']) || hasAdminRole()"/>
        </div>
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
    <div v-else class="xsLight">
        Du hast nicht die nötige Berechtigung um hochgeladene Vertragsbausteine einsehen zu können.
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
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: "ContractModuleSidenav",
    mixins: [Permissions],
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
            showUploadModal: false,
            contractModule: null,
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
        openDeleteModal(contractModule) {
            this.contractModule = contractModule;
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
