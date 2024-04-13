<template>
    <app-layout>
        <div class="">
            <div class="max-w-screen-2xl mb-40 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto">
                        <div class="flex flex-wrap w-full">
                            <div class="flex flex-wrap w-full">
                                <div class="flex justify-between w-full mb-6">
                                    <h2 class="headline1">{{$t('Contracts')}}</h2>
                                    <div class="flex">
                                        <BaseFilter>
                                            <div class="inline-flex border-none justify-end w-full">
                                                <button class="flex" @click="resetContractFilter">
                                                    <XIcon class="w-3 mr-1 mt-0.5"/>
                                                    <label class="text-xs">{{ $t('Reset') }}</label>
                                                </button>
                                            </div>
                                            <div class="mx-auto w-full max-w-md rounded-2xl bg-primary border-none mt-2">
                                                <BaseFilterDisclosure :title="$t('Additional costs')">
                                                    <div v-for="(filter, index) in filter.costsFilter">
                                                        <div class="relative flex items-center">
                                                            <div class="flex items-center">
                                                                <input v-model="filter.checked" :id="'costs-' + index" aria-describedby="candidates-description" name="candidates" type="checkbox" class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none" />
                                                            </div>
                                                            <div class="text-sm leading-6">
                                                                <label :for="'costs-' + index" :class="[filter.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']" class="ml-1.5 subpixel-antialiased align-text-middle">{{ filter.name }}</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </BaseFilterDisclosure>

                                                <hr class="border-gray-500 rounded-full mt-2 mb-2">
                                                <BaseFilterDisclosure :title="$t('Legal form')">
                                                    <div v-for="(filter, index) in filter.companyTypesFilter">
                                                        <div class="relative flex items-center">
                                                            <div class="flex items-center">
                                                                <input v-model="filter.checked" :id="'costs-' + index" aria-describedby="candidates-description" name="candidates" type="checkbox" class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none" />
                                                            </div>
                                                            <div class="text-sm leading-6">
                                                                <label :for="'costs-' + index" :class="[filter.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']" class="ml-1.5 subpixel-antialiased align-text-middle">{{ filter.name }}</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </BaseFilterDisclosure>

                                                <hr class="border-gray-500 rounded-full mt-2 mb-2">
                                                <BaseFilterDisclosure :title="$t('Contract type')">
                                                    <div v-for="(filter, index) in filter.contractTypesFilter">
                                                        <div class="relative flex items-center">
                                                            <div class="flex items-center">
                                                                <input v-model="filter.checked" :id="'costs-' + index" aria-describedby="candidates-description" name="candidates" type="checkbox" class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none" />
                                                            </div>
                                                            <div class="text-sm leading-6">
                                                                <label :for="'costs-' + index" :class="[filter.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']" class="ml-1.5 subpixel-antialiased align-text-middle">{{ filter.name }}</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </BaseFilterDisclosure>

                                            </div>
                                        </BaseFilter>
                                    </div>
                                    <div>
                                        <AddButtonBig
                                            :text="$t('New')"
                                            @click="openContractUploadModal"
                                        />
                                    </div>

                                </div>

                                <!-- filter tags -->
                                <div class="flex w-full mb-4" >
                                    <div v-for="filter in filter.costsFilter">
                                        <BaseFilterTag v-if="filter.checked" :filter="filter" @remove-filter="removeFilter(filter)" />
                                    </div>
                                    <div v-for="filter in filter.companyTypesFilter">
                                        <BaseFilterTag v-if="filter.checked" :filter="filter" @remove-filter="removeFilter(filter)" />
                                    </div>
                                    <div v-for="filter in filter.contractTypesFilter">
                                        <BaseFilterTag v-if="filter.checked" :filter="filter" @remove-filter="removeFilter(filter)" />
                                    </div>
                                </div>

                                <div v-if="filteredContracts.length === 0" class="w-full text-secondary text-center">
                                    <h2 class="text-secondary">{{ $t('No contracts available')}}</h2>
                                </div>

                                <div v-for="contract in filteredContracts" class="mt-6 w-full">
                                    <ContractListItem @open-delete-contract-modal="openContractDeleteModal"
                                                      @open-edit-contract-modal="openContractEditModal"
                                                      :contract="contract"
                                                      :first_project_tab_id="this.first_project_tab_id"
                                                      class="mb-6"
                                    />
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
            :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
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
import ContractDeleteModal from "@/Layouts/Components/ContractDeleteModal.vue";
import ContractEditModal from "@/Layouts/Components/ContractEditModal.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import BaseFilterDisclosure from "@/Layouts/Components/BaseFilterDisclosure.vue";
import {XIcon} from "@heroicons/vue/outline";
import BaseFilterCheckboxList from "@/Layouts/Components/BaseFilterCheckboxList.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import AddButtonBig from "@/Layouts/Components/General/Buttons/AddButtonBig.vue";

export default {
    mixins: [Permissions],
    name: "ContractManagement",
    components: {
        AddButtonBig,
        FormButton,
        BaseFilterCheckboxList, XIcon, BaseFilterDisclosure,
        BaseFilter,
        ContractEditModal,
        ContractDeleteModal,
        ContractUploadModal,
        BaseFilterTag,
        ContractFilter,
        ContractModuleSidenav,
        ContractListItem,
        BaseSidenav,
        AppLayout,
    },
    props: [
        'contracts',
        'contract_modules',
        'company_types',
        'contract_types',
        'currencies',
        'first_project_tab_id',
        'first_project_calendar_tab_id'
    ],
    data() {
        return {
            show: false,
            //filters: {},
            costNames: [],
            companyTypeNames: [],
            contractTypeNames: [],
            showContractUploadModal: false,
            showContractDeleteModal: null,
            showContractEditModal: null,
            //filtersToRemove: null,
            filter: {
                costsFilter: [{
                    name: 'KSK-pflichtig',
                    checked: false,
                    type: 'cost'
                }, {
                    name: 'Im Ausland ansässig',
                    checked: false,
                    type: 'cost'
                }],
                companyTypesFilter: [],
                contractTypesFilter: []
            },
        }
    },
    mounted() {
        this.filter.companyTypesFilter = this.company_types.map((companyType) => {
            return {
                id: companyType.id,
                name: companyType.name,
                checked: false,
                type: 'company_type'
            }
        });
        this.filter.contractTypesFilter = this.contract_types.map((contractType) => {
            return {
                id: contractType.id,
                name: contractType.name,
                checked: false,
                type: 'contract_type'
            }
        });
    },
    computed: {
        filteredContracts(){
            let filteredContracts = this.contracts;
            // filter by costs
            this.filter.costsFilter.forEach((cost) => {
                if(cost.checked) {
                    if(cost.name === this.$t('KSK-liable')) {
                        filteredContracts = filteredContracts.filter((contract) => {
                            return contract.ksk_liable
                        })
                    }
                    if(cost.name === this.$t('Resident abroad')) {
                        filteredContracts = filteredContracts.filter((contract) => {
                            return contract.resident_abroad
                        })
                    }
                }
            })
            // filter by company type
            this.filter.companyTypesFilter.forEach((companyType) => {
                if(companyType.checked) {
                    filteredContracts = filteredContracts.filter((contract) => {
                        return contract?.company_type?.id === companyType?.id
                    })
                }
            })
            // filter by contract type
            this.filter.contractTypesFilter.forEach((contractType) => {
                if(contractType.checked) {
                    filteredContracts = filteredContracts.filter((contract) => {
                        return contract?.contract_type?.id === contractType?.id
                    })
                }
            })
            return filteredContracts;
        },
    },
    methods: {
        resetContractFilter() {
            this.filter.costsFilter.forEach((cost) => {
                cost.checked = false;
            })
            this.filter.companyTypesFilter.forEach((companyType) => {
                companyType.checked = false;
            })
            this.filter.contractTypesFilter.forEach((contractType) => {
                contractType.checked = false;
            })
        },
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
            // uncheck filter in filter object
            if(filter.type === 'cost') {
                this.filter.costsFilter.forEach((cost) => {
                    if(cost.name === filter.name) {
                        cost.checked = false;
                    }
                })
            }
            if(filter.type === 'company_type') {
                this.filter.companyTypesFilter.forEach((companyType) => {
                    if(companyType.name === filter.name) {
                        companyType.checked = false;
                    }
                })
            }

            if(filter.type === 'contract_type') {
                this.filter.contractTypesFilter.forEach((contractType) => {
                    if(contractType.name === filter.name) {
                        contractType.checked = false;
                    }
                })
            }

        }
    }
}
</script>

<style scoped>

</style>
