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
                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Filter')"
                        :icon="IconFilter"
                        icon-size="h-5 w-5"
                        @click="openContractFilterModal"
                        classesButton="ui-button"
                    >
                        <template #badge v-if="activeFilterCount > 0">
                            <span class="absolute top-3 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                {{ activeFilterCount }}
                            </span>
                        </template>
                    </ToolTipComponent>

                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Export')"
                        :icon="IconFileSpreadsheet"
                        icon-size="h-5 w-5"
                        @click="openContractExportModal"
                        classesButton="ui-button"
                    />

                    <button class="ui-button-add" @click="openContractUploadModal">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('New contract') }}
                    </button>
                </template>
            </ToolbarHeader>

            <!-- Active filter tags -->
            <div class="flex flex-wrap gap-2 mt-1" v-if="hasActiveFilters">
                <!-- KSK-liable tag -->
                <BaseFilterTag v-if="filter.kskLiable" :filter="{ name: $t('KSK-liable'), type: 'kskLiable' }" @remove-filter="removeKskLiableFilter" />
                <!-- Foreign tax tag -->
                <BaseFilterTag v-if="filter.foreignTax" :filter="{ name: $t('Foreign tax'), type: 'foreignTax' }" @remove-filter="removeForeignTaxFilter" />
                <!-- Date range tag -->
                <BaseFilterTag v-if="filter.dateFrom || filter.dateTo" :filter="{ name: `${$t('Deadline')}: ${formatDateDisplay(filter.dateFrom) || '...'} - ${formatDateDisplay(filter.dateTo) || '...'}`, type: 'dateRange' }" @remove-filter="clearDateRange" />
                <!-- Legal form tags -->
                <div v-for="filterItem in filter.legalForms" :key="'tag-company-' + filterItem.id">
                    <BaseFilterTag v-if="filterItem.checked" :filter="filterItem" @remove-filter="removeLegalFormFilter(filterItem)" />
                </div>
                <!-- Contract type tags -->
                <div v-for="filterItem in filter.contractTypes" :key="'tag-contract-' + filterItem.id">
                    <BaseFilterTag v-if="filterItem.checked" :filter="filterItem" @remove-filter="removeContractTypeFilter(filterItem)" />
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

                <!-- Access Users & Departments -->
                <template #cell-accessUsers="{ row }">
                    <div class="flex items-center">
                        <div class="flex -space-x-2">
                            <img
                                v-for="user in row.accessibleUsers?.slice(0, 3)"
                                :key="'user-' + user.id"
                                :src="user.profile_photo_url"
                                :alt="user.first_name + ' ' + user.last_name"
                                class="size-8 rounded-full ring-2 ring-white object-cover"
                                v-tooltip.top="{ value: user.first_name + ' ' + user.last_name, appendTo: 'body', class: 'aw-tooltip' }"
                            />
                            <div
                                v-for="department in row.accessibleDepartments?.slice(0, Math.max(0, 3 - (row.accessibleUsers?.length || 0)))"
                                :key="'dept-' + department.id"
                                class="size-8 rounded-full ring-2 ring-white bg-gray-100 flex items-center justify-center"
                                v-tooltip.top="{ value: department.name, appendTo: 'body', class: 'aw-tooltip' }"
                            >
                                <TeamIconCollection :iconName="department.svg_name" class="size-6" />
                            </div>
                        </div>
                        <BaseMenu
                            v-if="(row.accessibleUsers?.length || 0) + (row.accessibleDepartments?.length || 0) > 3"
                            :show-icon="false"
                            :show-menu-button-text="true"
                            :menu-button-text="'+' + ((row.accessibleUsers?.length || 0) + (row.accessibleDepartments?.length || 0) - 3)"
                            classes="ml-2 cursor-pointer"
                            classes-button="text-xs text-gray-500 hover:text-gray-700 cursor-pointer"
                            white-menu-background
                        >
                            <div class="p-2 min-w-48">
                                <div v-for="user in row.accessibleUsers" :key="'menu-user-' + user.id" class="flex items-center py-1.5 px-2 hover:bg-gray-50 rounded">
                                    <img :src="user.profile_photo_url" :alt="user.first_name + ' ' + user.last_name" class="size-6 rounded-full object-cover mr-2" />
                                    <span class="text-sm text-gray-700">{{ user.first_name }} {{ user.last_name }}</span>
                                </div>
                                <div v-for="department in row.accessibleDepartments" :key="'menu-dept-' + department.id" class="flex items-center py-1.5 px-2 hover:bg-gray-50 rounded">
                                    <TeamIconCollection :iconName="department.svg_name" class="size-6 mr-2" />
                                    <span class="text-sm text-gray-700">{{ department.name }}</span>
                                </div>
                            </div>
                        </BaseMenu>
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
            @closeModal="closeContractEditModal"
            :contract="selectedContract"
        />

        <!-- Contract Delete Modal -->
        <ContractDeleteModal
            v-if="showContractDeleteModal"
            :show="showContractDeleteModal !== null"
            :close-modal="closeContractDeleteModal"
            :contract="selectedContract"
        />

        <!-- Contract Filter Modal -->
        <ContractFilterModal
            v-if="showContractFilterModal"
            :company-types="company_types"
            :contract-types="contract_types"
            :current-filters="filter"
            @close="closeContractFilterModal"
            @apply="applyContractFilter"
        />

        <!-- Contract Export Modal -->
        <ContractExportModal
            v-if="showContractExportModal"
            :company-types="company_types"
            :contract-types="contract_types"
            :initial-filter="filter"
            @close="closeContractExportModal"
        />
    </app-layout>
</template>

<script>
import { ref, computed } from 'vue'
import {IconFileText, IconCirclePlus, IconEdit, IconTrash, IconDownload, IconFilter, IconFileSpreadsheet} from '@tabler/icons-vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue"
import ContractModuleSidenav from "@/Layouts/Components/ContractModuleSidenav.vue"
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue"
import Permissions from "@/Mixins/Permissions.vue"
import ContractUploadModal from "@/Layouts/Components/ContractUploadModal.vue"
import ContractDeleteModal from "@/Layouts/Components/ContractDeleteModal.vue"
import ContractEditModal from "@/Layouts/Components/ContractEditModal.vue"
import ContractFilterModal from "@/Pages/Contracts/Components/ContractFilterModal.vue"
import ContractExportModal from "@/Pages/Contracts/Components/ContractExportModal.vue"
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue"
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue"
import BaseTable from '@/Artwork/Table/BaseTable.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue"
import axios from 'axios'

export default {
    mixins: [Permissions],
    name: "ContractManagement",
    components: {
        ContractFilterModal,
        ContractExportModal,
        IconDownload,
        BaseMenuItem,
        BaseMenu,
        BaseTable,
        ToolbarHeader,
        ContractEditModal,
        ContractDeleteModal,
        ContractUploadModal,
        BaseFilterTag,
        ToolTipComponent,
        ContractModuleSidenav,
        BaseSidenav,
        AppLayout,
        TeamIconCollection,
    },
    props: [
        'contracts',
        'contract_modules',
        'company_types',
        'contract_types',
        'currencies',
        'first_project_tab_id',
        'first_project_calendar_tab_id',
        'saved_filter'
    ],
    setup() {
        return {
            IconFileText,
            IconCirclePlus,
            IconEdit,
            IconTrash,
            IconDownload,
            IconFilter,
            IconFileSpreadsheet,
        }
    },
    data() {
        return {
            page: 1,
            showContractUploadModal: false,
            showContractDeleteModal: null,
            showContractEditModal: null,
            showContractFilterModal: false,
            showContractExportModal: false,
            selectedContract: null,
            filter: {
                kskLiable: false,
                foreignTax: false,
                dateFrom: null,
                dateTo: null,
                legalForms: [],
                contractTypes: []
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
        // Initialize legalForms with saved filter state
        const savedLegalFormIds = this.saved_filter?.legalFormIds || [];
        this.filter.legalForms = this.company_types.map((companyType) => {
            return {
                id: companyType.id,
                name: companyType.name,
                checked: savedLegalFormIds.includes(companyType.id),
                type: 'legal_form'
            }
        });

        // Initialize contractTypes with saved filter state
        const savedContractTypeIds = this.saved_filter?.contractTypeIds || [];
        this.filter.contractTypes = this.contract_types.map((contractType) => {
            return {
                id: contractType.id,
                name: contractType.name,
                checked: savedContractTypeIds.includes(contractType.id),
                type: 'contract_type'
            }
        });

        // Load other saved filter values
        if (this.saved_filter) {
            this.filter.kskLiable = this.saved_filter.kskLiable || false;
            this.filter.foreignTax = this.saved_filter.foreignTax || false;
            this.filter.dateFrom = this.saved_filter.dateFrom || null;
            this.filter.dateTo = this.saved_filter.dateTo || null;
        }
    },
    computed: {
        hasActiveFilters() {
            return this.filter.kskLiable ||
                   this.filter.foreignTax ||
                   this.filter.dateFrom ||
                   this.filter.dateTo ||
                   this.filter.legalForms.some(f => f.checked) ||
                   this.filter.contractTypes.some(f => f.checked);
        },
        activeFilterCount() {
            let count = 0;
            if (this.filter.kskLiable) count++;
            if (this.filter.foreignTax) count++;
            if (this.filter.dateFrom || this.filter.dateTo) count++;
            count += this.filter.legalForms.filter(f => f.checked).length;
            count += this.filter.contractTypes.filter(f => f.checked).length;
            return count;
        },
        filteredContracts() {
            let filteredContracts = this.contracts;

            // Filter by KSK-liable
            if (this.filter.kskLiable) {
                filteredContracts = filteredContracts.filter((contract) => {
                    return contract.ksk_liable;
                });
            }

            // Filter by foreign tax
            if (this.filter.foreignTax) {
                filteredContracts = filteredContracts.filter((contract) => {
                    return contract.has_foreign_tax;
                });
            }

            // Filter by date range (deadline_date within the range, inclusive)
            if (this.filter.dateFrom || this.filter.dateTo) {
                filteredContracts = filteredContracts.filter((contract) => {
                    if (!contract.deadline_date) return false;
                    // Extract only the date part (YYYY-MM-DD) for comparison in case deadline_date includes time
                    const deadlineDate = contract.deadline_date.substring(0, 10);
                    // Inclusive comparison: deadline on start or end date should be included
                    if (this.filter.dateFrom && deadlineDate < this.filter.dateFrom) return false;
                    if (this.filter.dateTo && deadlineDate > this.filter.dateTo) return false;
                    return true;
                });
            }

            // Filter by legal form (company type)
            const selectedLegalForms = this.filter.legalForms.filter(f => f.checked);
            if (selectedLegalForms.length > 0) {
                filteredContracts = filteredContracts.filter((contract) => {
                    return selectedLegalForms.some(lf => contract?.company_type?.id === lf.id);
                });
            }

            // Filter by contract type
            const selectedContractTypes = this.filter.contractTypes.filter(f => f.checked);
            if (selectedContractTypes.length > 0) {
                filteredContracts = filteredContracts.filter((contract) => {
                    return selectedContractTypes.some(ct => contract?.contract_type?.id === ct.id);
                });
            }

            return filteredContracts;
        },
    },
    methods: {
        openContractFilterModal() {
            this.showContractFilterModal = true;
        },
        closeContractFilterModal() {
            this.showContractFilterModal = false;
        },
        applyContractFilter(newFilters) {
            this.filter.kskLiable = newFilters.kskLiable;
            this.filter.foreignTax = newFilters.foreignTax;
            this.filter.dateFrom = newFilters.dateFrom;
            this.filter.dateTo = newFilters.dateTo;

            // Update legalForms checked state
            if (newFilters.legalForms) {
                this.filter.legalForms.forEach(lf => {
                    const matchingFilter = newFilters.legalForms.find(f => f.id === lf.id);
                    lf.checked = matchingFilter ? matchingFilter.checked : false;
                });
            }

            // Update contractTypes checked state
            if (newFilters.contractTypes) {
                this.filter.contractTypes.forEach(ct => {
                    const matchingFilter = newFilters.contractTypes.find(f => f.id === ct.id);
                    ct.checked = matchingFilter ? matchingFilter.checked : false;
                });
            }

            // Save filters to backend
            this.saveFilter();
        },
        saveFilter() {
            const filterData = {
                kskLiable: this.filter.kskLiable,
                foreignTax: this.filter.foreignTax,
                dateFrom: this.filter.dateFrom,
                dateTo: this.filter.dateTo,
                legalFormIds: this.filter.legalForms.filter(f => f.checked).map(f => f.id),
                contractTypeIds: this.filter.contractTypes.filter(f => f.checked).map(f => f.id),
            };

            axios.post(route('contracts.filter.save'), filterData).catch(error => {
                console.error('Failed to save contract filter:', error);
            });
        },
        clearDateRange() {
            this.filter.dateFrom = null;
            this.filter.dateTo = null;
            this.saveFilter();
        },
        removeKskLiableFilter() {
            this.filter.kskLiable = false;
            this.saveFilter();
        },
        removeForeignTaxFilter() {
            this.filter.foreignTax = false;
            this.saveFilter();
        },
        removeLegalFormFilter(filterItem) {
            filterItem.checked = false;
            this.saveFilter();
        },
        removeContractTypeFilter(filterItem) {
            filterItem.checked = false;
            this.saveFilter();
        },
        formatDateDisplay(dateString) {
            if (!dateString) return null;
            const [year, month, day] = dateString.split('-');
            return `${day}.${month}.${year}`;
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
        openContractExportModal() {
            this.showContractExportModal = true;
        },
        closeContractExportModal() {
            this.showContractExportModal = false;
        }
    }
}
</script>
