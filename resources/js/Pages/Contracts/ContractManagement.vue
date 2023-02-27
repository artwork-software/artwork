<template>
    <app-layout>
        <div class="py-4">
            <div class="max-w-screen-2xl mb-40 my-12 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto">
                        <div class="flex flex-wrap w-full">
                            <div class="flex flex-wrap w-full">
                                <div class="flex w-full mb-6">
                                    <h2 class="headline1">Verträge</h2>
                                    <div class="flex ml-60">
                                        <ContractFilter class="ml-auto" @filter="filterContracts" />
                                    </div>
                                </div>

                                <div class="flex w-full mb-4" >
                                    <div v-for="filter in costNames">
                                        <BaseFilterTag :filter="filter" :remove-filter="removeFilter" />
                                    </div>
                                    <div v-for="filter in companyTypeNames">
                                        <BaseFilterTag :filter="filter" :remove-filter="removeFilter" />
                                    </div>
                                    <div v-for="filter in contractTypeNames">
                                        <BaseFilterTag :filter="filter" :remove-filter="removeFilter" />
                                    </div>
                                </div>
                                <div v-for="contract in contractsCopy.data" class="mt-6 w-full" v-if="this.$page.props.is_contract_admin && contractsCopy.data.length !== 0">
                                    <ContractListItem :contract="contract" class="mb-6"></ContractListItem>
                                    <hr class="text-secondary">
                                </div>
                                <div v-else class="text-secondary">
                                    <p>Bisher wurden für dich noch keine Verträge freigegeben.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <BaseSidenav :show="show" @toggle="this.show =! this.show">
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
            show: true,
            contractsCopy: this.contracts,
            filters: {},
            costNames: [],
            companyTypeNames: [],
            contractTypeNames: []
        }
    },
    methods: {
        async filterContracts(filters) {
            if(filters.costsFilter ||
                filters.companyTypesFilter ||
                filters.contractTypesFilter) {
                this.filters = filters
                this.costNames = filters.costsFilter.map(cost => cost.name)
                this.companyTypeNames = filters.companyTypesFilter.map(companyType => companyType.name)
                this.contractTypeNames = filters.contractTypesFilter.map(contractType => contractType.name)
            }
            await axios.get('/contracts/', { params: {
                costsFilter: { array: this.costNames },
                companyTypesFilter: { array: this.getArrayOfIds(this.filters.companyTypesFilter) },
                contractTypesFilter: { array: this.getArrayOfIds(this.filters.contractTypesFilter) },
            }})
            .then(res => {
                this.contractsCopy.data = res.data.contracts
            })
        },
        getArrayOfIds(array) {
            let ids = []
            array.forEach(item => {
                ids.push(item.id)
            })
            return ids
        },
        removeFilter(filter) {
            let costFilter = this.filters.costsFilter.filter((costFilter) => costFilter.name === filter);
            if(costFilter !== null && costFilter.length > 0) {
                this.filters.costsFilter = this.filters.costsFilter
                    .filter(filterItem => filterItem.name !== filter)
                costFilter[0].checked = false;
            }
            let companyTypeFilter = this.filters.companyTypesFilter.filter((companyTypesFilter) => companyTypesFilter.name === filter)

            if(companyTypeFilter !== null && companyTypeFilter.length > 0) {
                this.filters.companyTypesFilter = this.filters.companyTypesFilter
                    .filter(filterItem => filterItem.name !== filter)
                companyTypeFilter[0].checked = false;
            }
            let contractTypeFilter = this.filters.contractTypesFilter.filter((contractTypesFilter) => contractTypesFilter.name === filter)
            if(contractTypeFilter  !== null && contractTypeFilter.length > 0) {
                this.filters.contractTypesFilter = this.filters.contractTypesFilter
                    .filter(filterItem => filterItem.name !== filter)
                contractTypeFilter[0].checked = false;
            }
            this.filterContracts(this.filters)
        }
    }
}
</script>

<style scoped>

</style>
