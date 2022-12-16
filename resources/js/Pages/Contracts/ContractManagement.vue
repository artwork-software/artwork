<template>
    <app-layout>
        <div class="py-4">
            <div class="max-w-screen-2xl mb-40 my-12 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex flex-wrap w-full">
                            <div class="flex flex-wrap w-full">
                                <div class="justify-center flex w-full">
                                    <h2 class="headline1 mb-4">Verträge</h2>
                                    <ContractFilter class="ml-auto" @filter="filterContracts" />
                                </div>
                                <div v-for="contract in contracts.data" class="mt-6 w-full">
                                    <ContractListItem :contract="contract" class="mb-6"></ContractListItem>
                                    <hr class="text-secondary">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <BaseSidenav :show="show" @change="this.show =! this.show">
            <ContractModuleSidenav :contractModules="contract_modules" @upload="this.show = true" />
        </BaseSidenav>
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseSidenav from "@/Layouts/Components/BaseSidenav";
import ContractListItem from "@/Layouts/Components/ContractListItem";
import ContractModuleSidenav from "@/Layouts/Components/ContractModuleSidenav";
import ContractFilter from "@/Layouts/Components/ContractFilter";
import {Inertia} from "@inertiajs/inertia";

export default {
    name: "ContractManagement",
    components: {
        ContractFilter,
        ContractModuleSidenav,
        ContractListItem,
        BaseSidenav,
        AppLayout
    },
    props: [
        'contracts',
        'contract_modules'
    ],
    data() {
        return {
            show: false,
        }
    },
    methods: {
        async filterContracts(filters) {
            await axios.get('/contracts', {
                costsFilter: filters.costsFilter,
                legalFormsFilter: filters.legalFormsFilter,
                contractTypesFilter: filters.contractTypesFilter,
            })
        }
    }
}
</script>

<style scoped>

</style>
