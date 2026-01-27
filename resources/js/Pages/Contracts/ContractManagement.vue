<template>
    <app-layout :title="$t('Contracts')">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconFileText"
                :title="$t('Contracts')"
                icon-bg-class="bg-emerald-600/10 text-emerald-700"
                :description="filteredContracts.length ? `${filteredContracts.length} ${$t('Contracts')}` : ''"
            >
                <template #actions>
                    <BaseMenu show-sort-icon dots-size="size-5" has-no-offset dots-color="!text-zinc-900" menu-width="w-72" classes="ui-button" :menu-button-text="$t('Filter')">
                        <div class="px-4 py-2">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-zinc-700">{{ $t('Additional costs') }}</span>
                                <button @click="resetContractFilter" class="text-xs text-zinc-500 hover:text-zinc-700">
                                    {{ $t('Reset') }}
                                </button>
                            </div>
                            <div v-for="(filterItem, index) in filter.costsFilter" :key="'cost-' + index" class="flex items-center py-1">
                                <input v-model="filterItem.checked" :id="'costs-' + index" type="checkbox" class="input-checklist" />
                                <label :for="'costs-' + index" class="ml-2 text-sm text-zinc-600">{{ filterItem.name }}</label>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="px-4 py-2">
                            <span class="text-sm font-medium text-zinc-700">{{ $t('Legal form') }}</span>
                            <div v-for="(filterItem, index) in filter.companyTypesFilter" :key="'company-' + index" class="flex items-center py-1">
                                <input v-model="filterItem.checked" :id="'company-' + index" type="checkbox" class="input-checklist" />
                                <label :for="'company-' + index" class="ml-2 text-sm text-zinc-600">{{ filterItem.name }}</label>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="px-4 py-2">
                            <span class="text-sm font-medium text-zinc-700">{{ $t('Contract type') }}</span>
                            <div v-for="(filterItem, index) in filter.contractTypesFilter" :key="'contract-' + index" class="flex items-center py-1">
                                <input v-model="filterItem.checked" :id="'contract-' + index" type="checkbox" class="input-checklist" />
                                <label :for="'contract-' + index" class="ml-2 text-sm text-zinc-600">{{ filterItem.name }}</label>
                            </div>
                        </div>
                    </BaseMenu>

                    <button class="ui-button-add" @click="openContractUploadModal">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('New contract') }}
                    </button>
                </template>
            </ToolbarHeader>

            <!-- Active filter tags -->
            <div class="flex flex-wrap gap-2 mb-4" v-if="hasActiveFilters">
                <div v-for="filterItem in filter.costsFilter" :key="'tag-cost-' + filterItem.name">
                    <BaseFilterTag v-if="filterItem.checked" :filter="filterItem" @remove-filter="removeFilter(filterItem)" />
                </div>
                <div v-for="filterItem in filter.companyTypesFilter" :key="'tag-company-' + filterItem.name">
                    <BaseFilterTag v-if="filterItem.checked" :filter="filterItem" @remove-filter="removeFilter(filterItem)" />
                </div>
                <div v-for="filterItem in filter.contractTypesFilter" :key="'tag-contract-' + filterItem.name">
                    <BaseFilterTag v-if="filterItem.checked" :filter="filterItem" @remove-filter="removeFilter(filterItem)" />
                </div>
            </div>

            <!-- Contracts Table -->
            <BaseTable
                :rows="filteredContracts"
                :columns="cols"
                row-key="id"
                v-model:page="page"
                :empty-title="$t('No contracts available')"
                :empty-message="$t('No contracts have been uploaded yet.')"
            >
                <!-- Contract Partner -->
                <template #cell-partner="{ row }">
                    <div class="font-medium text-gray-900">{{ row.partner || '-' }}</div>
                </template>

                <!-- Project -->
                <template #cell-project="{ row }">
                    <a v-if="row.project" :href="route('projects.tab', {project: row.project.id, projectTab: first_project_tab_id})" class="text-blue-600 hover:text-blue-800">
                        {{ row.project.name }}
                    </a>
                    <span v-else class="text-gray-400">-</span>
                </template>

                <!-- Access Users -->
                <template #cell-accessUsers="{ row }">
                    <div class="flex items-center">
                        <div class="flex -space-x-2">
                            <img
                                v-for="user in row.accessibleUsers?.slice(0, 3)"
                                :key="user.id"
                                :src="user.profile_photo_url"
                                :alt="user.first_name + ' ' + user.last_name"
                                class="size-8 rounded-full ring-2 ring-white object-cover"
                                v-tooltip.top="{ value: user.first_name + ' ' + user.last_name, appendTo: 'body', class: 'aw-tooltip' }"
                            />
                        </div>
                        <span v-if="row.accessibleUsers?.length > 3" class="ml-2 text-xs text-gray-500">
                            +{{ row.accessibleUsers.length - 3 }}
                        </span>
                    </div>
                </template>

                <!-- File Name (Download) -->
                <template #cell-filename="{ row }">
                    <a :href="route('contracts.download', row.id)" class="text-blue-600 hover:text-blue-800 flex items-center">
                        <IconDownload class="size-4 mr-2" />
                        {{ row.name }}
                    </a>
                </template>

                <!-- Actions -->
                <template #row-actions="{ row }">
                    <BaseMenu has-no-offset white-menu-background>
                        <BaseMenuItem :icon="IconEdit" :title="$t('Edit')" white-menu-background @click="openContractEditModal(row)" />
                        <BaseMenuItem :icon="IconTrash" :title="$t('Delete')" white-menu-background @click="openContractDeleteModal(row)" />
                    </BaseMenu>
                </template>
            </BaseTable>
        </div>

        <BaseSidenav>
            <ContractModuleSidenav :contractModules="contract_modules" />
        </BaseSidenav>

        <!-- Contract Upload Modal -->
        <ContractUploadModal
            :show="showContractUploadModal"
            @close-modal="closeContractUploadModal"
            :company-types="company_types"
            :contract-types="contract_types"
            :currencies="currencies"
            :first_project_calendar_tab_id="first_project_calendar_tab_id"
        />

        <!-- Contract Edit Modal -->
        <ContractEditModal
            v-if="showContractEditModal"
            :contract-types="contract_types"
            :currencies="currencies"
            :company-types="company_types"
            :show="showContractEditModal !== null"
            :close-modal="closeContractEditModal"
            :contract="selectedContract"
        />

        <!-- Contract Delete Modal -->
        <ContractDeleteModal
            v-if="showContractDeleteModal"
            :show="showContractDeleteModal !== null"
            :close-modal="closeContractDeleteModal"
            :contract="selectedContract"
        />
    </app-layout>
</template>

<script>
import { ref, computed } from 'vue'
import { IconFileText, IconCirclePlus, IconEdit, IconTrash, IconDownload } from '@tabler/icons-vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue"
import ContractModuleSidenav from "@/Layouts/Components/ContractModuleSidenav.vue"
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue"
import Permissions from "@/Mixins/Permissions.vue"
import ContractUploadModal from "@/Layouts/Components/ContractUploadModal.vue"
import ContractDeleteModal from "@/Layouts/Components/ContractDeleteModal.vue"
import ContractEditModal from "@/Layouts/Components/ContractEditModal.vue"
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue"
import BaseTable from '@/Artwork/Table/BaseTable.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'

export default {
    mixins: [Permissions],
    name: "ContractManagement",
    components: {
        IconDownload,
        BaseMenuItem,
        BaseMenu,
        BaseTable,
        ToolbarHeader,
        ContractEditModal,
        ContractDeleteModal,
        ContractUploadModal,
        BaseFilterTag,
        ContractModuleSidenav,
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
    setup() {
        return {
            IconFileText,
            IconCirclePlus,
            IconEdit,
            IconTrash,
            IconDownload,
        }
    },
    data() {
        return {
            page: 1,
            showContractUploadModal: false,
            showContractDeleteModal: null,
            showContractEditModal: null,
            selectedContract: null,
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
            cols: [
                { key: 'partner', label: 'Contract partner', sortable: false },
                { key: 'project', label: 'Project', sortable: false },
                { key: 'accessUsers', label: 'Access users', sortable: false },
                { key: 'filename', label: 'File name', sortable: false },
            ],
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
        hasActiveFilters() {
            return this.filter.costsFilter.some(f => f.checked) ||
                   this.filter.companyTypesFilter.some(f => f.checked) ||
                   this.filter.contractTypesFilter.some(f => f.checked);
        },
        filteredContracts() {
            let filteredContracts = this.contracts;
            // filter by costs
            this.filter.costsFilter.forEach((cost) => {
                if(cost.checked) {
                    if(cost.name === this.$t('KSK-liable') || cost.name === 'KSK-pflichtig') {
                        filteredContracts = filteredContracts.filter((contract) => {
                            return contract.ksk_liable
                        })
                    }
                    if(cost.name === this.$t('Resident abroad') || cost.name === 'Im Ausland ansässig') {
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
        openContractEditModal(contract) {
            this.selectedContract = contract;
            this.showContractEditModal = contract.id;
        },
        closeContractEditModal() {
            this.showContractEditModal = null;
            this.selectedContract = null;
        },
        openContractDeleteModal(contract) {
            this.selectedContract = contract;
            this.showContractDeleteModal = contract.id;
        },
        closeContractDeleteModal() {
            this.showContractDeleteModal = null;
            this.selectedContract = null;
        },
        openContractUploadModal() {
            this.showContractUploadModal = true;
        },
        closeContractUploadModal() {
            this.showContractUploadModal = false;
        },
        removeFilter(filter) {
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
