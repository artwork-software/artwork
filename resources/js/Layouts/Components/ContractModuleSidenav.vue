<template>
    <div class="w-full mt-36" v-if="$canAny(['view edit upload contracts','can see and download contract modules']) || hasAdminRole()">
        <div class="w-full flex items-center mb-4">
            <div class="text-secondary text-md font-semibold">
                {{ $t('Contract modules')}}
            </div>
            <IconUpload stroke-width="1.5" class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg" @click="openUploadModal" v-if="$can('view edit upload contracts') || hasAdminRole()"/>
        </div>
        <div class="w-full flex items-center mb-2 cursor-pointer text-secondary hover:text-white"
             v-for="contractModule in contractModules.data"
        >
            <IconDownload stroke-width="1.5" class="w-4 h-4 mr-2" @click="download(contractModule)" v-if="$canAny(['view edit upload contracts','can see and download contract modules']) || hasAdminRole()"/>
            <div v-if="$canAny(['view edit upload contracts']) || hasAdminRole()" @click="download(contractModule)">{{ contractModule.name }}</div>
            <div v-else>
                {{ contractModule.name }}
            </div>
            <IconCircleX stroke-width="1.5" class="w-4 h-4 ml-auto bg-error rounded-full text-white" @click="openDeleteModal(contractModule)" v-if="$canAny(['view edit upload contracts']) || hasAdminRole()"/>
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
        {{ $t('You do not have the necessary authorization to view uploaded contract modules.')}}
    </div>
</template>

<script>
import {
    DownloadIcon,
    UploadIcon,
    XCircleIcon
} from '@heroicons/vue/outline';
import ContractModuleDeleteModal from "@/Layouts/Components/ContractModuleDeleteModal.vue";
import ContractModuleUploadModal from "@/Layouts/Components/ContractModuleUploadModal.vue";
import {usePage} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "ContractModuleSidenav",
    mixins: [Permissions, IconLib],
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
