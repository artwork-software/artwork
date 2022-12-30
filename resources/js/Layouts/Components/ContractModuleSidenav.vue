<template>
    <div class="w-full mt-36">
        <div class="w-full flex items-center mb-4">
            <div class="text-secondary text-md font-semibold">
                Vertragsbausteine
            </div>
            <input
                @change="upload"
                class="hidden"
                id="file"
                type="file"
                ref="module_files"
                multiple
            />
            <UploadIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg" @click="selectNewFiles"/>
        </div>
        <div class="w-full flex items-center mb-2 cursor-pointer text-secondary hover:text-white"
             v-for="contractModule in contractModules.data"
        >
            <DownloadIcon class="w-4 h-4 mr-2" @click="download(contractModule)"/>
            <div @click="download(contractModule)">{{ contractModule.name }}</div>
            <XCircleIcon class="w-4 h-4 ml-auto" @click="openDeleteModal"/>

            <ContractModuleDeleteModal
                :show="showDeleteModal"
                :close-modal="closeDeleteModal"
                :contract-module="contractModule"
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

export default {
    name: "ContractModuleSidenav",
    props: {
        contractModules: Object
    },
    components: {
        ContractModuleDeleteModal,
        DownloadIcon,
        UploadIcon,
        XCircleIcon
    },
    data() {
        return {
            showDeleteModal: false
        }
    },
    methods: {
        download(module) {
            let link = document.createElement('a');
            link.href = route('contracts.module.download', {module: module});
            link.target = '_blank';
            link.click();
        },
        upload(event) {
            this.validateTypeAndUpload([...event.target.files])
        },
        storeFile(file) {
            this.$inertia.post('/contract_modules', {module: file}, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.$emit('upload')
                }

            })
        },
        selectNewFiles() {
            this.$refs.module_files.click();
        },
        validateTypeAndUpload(files) {
            this.uploadDocumentFeedback = "";
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]
            for (let file of files) {
                if (forbiddenTypes.includes(file.type) || file.type.match('video.*') || file.type === "") {
                    this.uploadDocumentFeedback = "Videos, .exe und .dmg Dateien werden nicht unterstützt"
                } else {
                    this.storeFile(file)
                }
            }
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
