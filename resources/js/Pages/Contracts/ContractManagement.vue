<template>
    <app-layout>
        <div class="py-4">
            <div class="max-w-screen-2xl mb-40 my-12 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex flex-wrap w-full">
                            <div class="flex flex-wrap w-full">
                                <div class="justify-center flex w-full mb-6">
                                    <h2 class="headline1">Verträge</h2>
                                    <ContractFilter class="ml-auto" @filter="filterContracts" />
                                </div>
                                <div class="flex w-full mb-4">
                                    <div v-for="filter in filters.costsFilter">
                                        <BaseFilterTag :filter="filter" :remove-filter="removeFilter" />
                                    </div>
                                    <div v-for="filter in filters.legalFormsFilter">
                                        <BaseFilterTag :filter="filter" :remove-filter="removeFilter" />
                                    </div>
                                    <div v-for="filter in filters.contractTypesFilter">
                                        <BaseFilterTag :filter="filter" :remove-filter="removeFilter" />
                                    </div>
                                </div>
                                <div v-for="contract in contractsCopy.data" class="mt-6 w-full">
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
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag";

export default {
    name: "ContractManagement",
    components: {
        BaseFilterTag,
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
            contractsCopy: this.contracts,
            filters: {}
        }
    },
    methods: {
        async filterContracts(filters) {
            if(filters.costsFilter ||
                filters.legalFormsFilter ||
                filters.contractTypesFilter) {
                this.filters = filters
            }
            await axios.get('/contracts/', { params: {
                costsFilter: { array: this.filters.costsFilter },
                legalFormsFilter: { array: this.filters.legalFormsFilter },
                contractTypesFilter: { array: this.filters.contractTypesFilter },
            }})
            .then(res => {
                this.contractsCopy.data = res.data.contracts
            })
        },
        removeFilter(filter) {
            if(this.filters.costsFilter.includes(filter)) {
                this.filters.costsFilter = this.filters.costsFilter
                    .filter(filterItem => filterItem !== filter)
            }
            if(this.filters.legalFormsFilter.includes(filter)) {
                this.filters.legalFormsFilter = this.filters.legalFormsFilter
                    .filter(filterItem => filterItem !== filter)
            }
            if(this.filters.contractTypesFilter.includes(filter)) {
                this.filters.contractTypesFilter = this.filters.contractTypesFilter
                    .filter(filterItem => filterItem !== filter)
            }
            this.filterContracts({})
        }
    }
}
</script>

<style scoped>

</style>
