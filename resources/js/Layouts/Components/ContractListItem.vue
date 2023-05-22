<template>
    <div class="flex">
        <div class="w-full">
            <div class="flex items-center">
                <div class="text-primary text-sm">{{ contract.partner }}</div>
                <hr class="border-l border-l-primary h-5 mx-2">
                <div class="text-buttonBlue text-sm"><a
                    :href="'/projects/' + contract.project.id">{{ contract.project?.name }}</a></div>
                <hr class="border-l border-l-primary h-5 mx-2">
                <div class="text-primary text-sm">{{ contract.amount }}€</div>
            </div>
            <div class="flex items-center mt-1">
                <div class="text-secondary text-xs">{{ contract.company_type?.name }}</div>
                <hr v-if="contract.company_type" class="border-l border-l-secondary h-4 mx-2">
                <div class="text-secondary text-xs">{{ contract.contract_type?.name }}</div>
                <hr class="border-l border-l-secondary h-4 mx-2">
                <div class="text-secondary text-xs">
                    {{ contract.ksk_liable ? 'KSK-pflichtig' : 'Nicht KSK-pflichtig' }}
                </div>
                <hr class="border-l border-l-secondary h-4 mx-2">
                <div class="text-secondary text-xs">
                    {{ contract.resident_abroad ? 'Im Ausland ansässig' : 'Nicht im Ausland ansässig' }}
                </div>
            </div>
            <div class="flex items-center text-secondary text-xs mt-1">
                {{ contract.description }}
            </div>
        </div>
        <div class="ml-auto">
            <DownloadIcon @click="download" class="w-5 h-5 p-1 rounded-full bg-buttonBlue text-white"/>
        </div>
    </div>
</template>

<script>
import {
    DownloadIcon
} from '@heroicons/vue/outline';
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: "ContractListItem",
    mixins: [Permissions],
    props: {
        contract: Object
    },
    components: {
        DownloadIcon
    },
    methods: {
        download() {
            let link = document.createElement('a');
            link.href = route('contracts.download', {contract: this.contract});
            link.target = '_blank';
            link.click();
        }
    }
}
</script>

<style scoped>

</style>
