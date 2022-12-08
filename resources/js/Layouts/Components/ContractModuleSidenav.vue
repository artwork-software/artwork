<template>
    <div class="w-full mt-36">
        <div class="w-full flex items-center mb-4">
            <div class="text-secondary text-md font-semibold">
                Vertragsbausteine
            </div>
            <UploadIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg" @click="upload" />
        </div>
        <div class="w-full flex items-center mb-2 cursor-pointer text-secondary hover:text-white"
             v-for="contractModule in contractModules.data"
             @click="download(contractModule)"
        >
            <DownloadIcon class="w-4 h-4 mr-2 hover:text-white" />
            <div>{{contractModule.name}}</div>
        </div>
    </div>
</template>

<script>
import {
    DownloadIcon,
    UploadIcon
} from '@heroicons/vue/outline';

export default {
    name: "ContractModuleSidenav",
    props: {
        contractModules: Array
    },
    components: {
        DownloadIcon,
        UploadIcon
    },
    methods: {
        download(module) {
            axios.get(`/contract_modules/${module.id}/download`)
                .then(res => console.log(res))
                .catch(err => console.log(err))
        },
        upload() {
            axios.post('/contract_modules', { })
                .then(res => console.log(res))
                .catch(err => console.log(err))
        }
    }
}
</script>

<style scoped>

</style>
