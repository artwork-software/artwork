<template>
    <app-layout>
        <div class="">
            <div class="max-w-screen-2xl mb-40 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto">
                        <div class="flex flex-wrap w-full">
                            <div class="flex flex-wrap w-full">
                                <div class="flex justify-between w-full mb-6">
                                    <h2 class="headline1">Verträge</h2>
                                    <div class="flex">
                                        <ContractFilter :company-types="company_types" :contract-types="contract_types" class="ml-auto" @filter="setFilters" />
                                    </div>
                                    <div>
                                        <AddButton @click="openContractUploadModal" text="Neu" mode="page"/>
                                    </div>

                                </div>
                                <div class="flex w-full mb-4" >
                                    <div v-for="filter in filters.costsFilter">
                                        <BaseFilterTag :filter="filter" @remove-filter="removeFilter(filter)" />
                                    </div>
                                    <div v-for="filter in filters.companyTypesFilter">
                                        <BaseFilterTag :filter="filter" @remove-filter="removeFilter(filter)" />
                                    </div>
                                    <div v-for="filter in filters.contractTypesFilter">
                                        <BaseFilterTag :filter="filter" @remove-filter="removeFilter(filter)" />
                                    </div>
                                </div>
                                <div v-for="contract in filteredContracts" class="mt-6 w-full">
                                    <ContractListItem @open-delete-contract-modal="openContractDeleteModal" @open-edit-contract-modal="openContractEditModal" :contract="contract" class="mb-6"></ContractListItem>
                                    <ContractDeleteModal :show="showContractDeleteModal === contract?.id"
                                                         :close-modal="closeContractDeleteModal" :contract="contract"/>
                                    <ContractEditModal :contract-types="contract_types" :currencies="currencies" :company-types="company_types" :show="showContractEditModal === contract?.id"
                                                       :close-modal="closeContractEditModal" :contract="contract"/>
                                    <hr class="text-secondary">
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

        <ContractUploadModal
            :show="showContractUploadModal"
            @close-modal="closeContractUploadModal"
            :company-types="company_types"
            :contract-types="contract_types"
            :currencies="currencies"
        />
    </app-layout>

</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseSidenav from "@/Layouts/Components/BaseSidenav";
import ContractListItem from "@/Layouts/Components/ContractListItem";
import ContractModuleSidenav from "@/Layouts/Components/ContractModuleSidenav";
import ContractFilter from "@/Layouts/Components/ContractFilter";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag";
import Permissions from "@/mixins/Permissions.vue";
import ContractUploadModal from "@/Layouts/Components/ContractUploadModal.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import ContractDeleteModal from "@/Layouts/Components/ContractDeleteModal.vue";
import ContractEditModal from "@/Layouts/Components/ContractEditModal.vue";
import {Inertia} from "@inertiajs/inertia";

export default {
    mixins: [Permissions],
    name: "ContractManagement",
    components: {
        ContractEditModal,
        ContractDeleteModal,
        AddButton,
        ContractUploadModal,
        BaseFilterTag,
        ContractFilter,
        ContractModuleSidenav,
        ContractListItem,
        BaseSidenav,
        AppLayout
    },
    props: [
        'contracts',
        'contract_modules',
        'company_types',
        'contract_types',
        'currencies'
    ],
    data() {
        return {
            show: false,
            filters: {},
            costNames: [],
            companyTypeNames: [],
            contractTypeNames: [],
            showContractUploadModal: false,
            showContractDeleteModal: null,
            showContractEditModal: null,
        }
    },
    computed: {
        filteredContracts(){
            let filteredContracts = this.contracts;
            if(this.filters.costsFilter?.length > 0) {
                filteredContracts = filteredContracts.filter((contract) => {
                    let costsFilter = this.filters.costsFilter.filter((costFilter) => costFilter.id === 'KSK-pflichtig');
                    if(costsFilter !== null && costsFilter.length > 0) {
                        if(costsFilter[0].checked && contract.ksk_liable) {
                            return contract;
                        }
                    }
                    costsFilter = this.filters.costsFilter.filter((costFilter) => costFilter.name === 'Im Ausland ansässig');
                    if(costsFilter !== null && costsFilter.length > 0) {
                        if(costsFilter[0].checked && contract.resident_abroad) {
                            return contract;
                        }
                    }
                })
            }
            if(this.filters.companyTypesFilter?.length > 0) {
                filteredContracts = filteredContracts.filter((contract) => {
                    let companyTypeFilter = this.filters.companyTypesFilter.filter((companyTypeFilter) => companyTypeFilter.name === contract.company_type?.name);
                    if(companyTypeFilter !== null && companyTypeFilter.length > 0) {
                        if(companyTypeFilter[0].checked) {
                            return contract;
                        }
                    }
                })
            }

            if(this.filters.contractTypesFilter?.length > 0) {
                filteredContracts = filteredContracts.filter((contract) => {
                    let contractTypeFilter = this.filters.contractTypesFilter.filter((contractTypeFilter) => contractTypeFilter.name === contract.contract_type?.name);
                    if(contractTypeFilter !== null && contractTypeFilter.length > 0) {
                        if(contractTypeFilter[0].checked) {
                            return contract;
                        }
                    }
                })
            }

            return filteredContracts;
        }
    },
    methods: {
        setFilters(filter){
            this.filters = filter
        },
        openContractEditModal(contract) {
            this.showContractEditModal = contract.id
        },
        closeContractEditModal() {
            this.showContractEditModal = null;
        },
        openContractDeleteModal(contract) {
            this.showContractDeleteModal = contract.id
        },
        closeContractDeleteModal() {
            this.showContractDeleteModal = null;
        },
        openContractUploadModal() {
            this.showContractUploadModal = true
        },
        closeContractUploadModal() {
            this.showContractUploadModal = false
        },
        removeFilter(filter) {
            // check if filter is in costsFilter or companyTypesFilter or contractTypesFilter and remove the filter from the array
            if(this.filters.costsFilter?.length > 0) {
                this.filters.costsFilter = this.filters.costsFilter.filter((costFilter) => costFilter.id !== filter.id);
            }
            if(this.filters.companyTypesFilter?.length > 0) {
                this.filters.companyTypesFilter = this.filters.companyTypesFilter.filter((companyTypeFilter) => companyTypeFilter.name !== filter.name);
            }
            if(this.filters.contractTypesFilter?.length > 0) {
                this.filters.contractTypesFilter = this.filters.contractTypesFilter.filter((contractTypeFilter) => contractTypeFilter.name !== filter.name);
            }
        }
    }
}
</script>

<style scoped>

</style>
