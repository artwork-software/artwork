<template>
    <app-layout :title="$t('Contracts')">
        <div class="artwork-container-fluid">
            <ToolbarHeader
                band
                :icon="IconFileText"
                :title="$t('Contracts')"
                :description="filteredContracts.length ? `${filteredContracts.length} ${filteredContracts.length === 1 ? $t('Contract') : $t('Contracts')}` : ''"
                v-model="searchTerm"
                :search-enabled="true"
                :search-label="$t('Search contracts')"
                :search-tooltip="$t('Search')"
            >
                <template #actions>
                    <div class="relative inline-flex">
                        <ToolTipComponent
                            direction="bottom"
                            :tooltip-text="$t('Filter')"
                            :icon="IconFilter"
                            icon-size="h-5 w-5"
                            white-icon
                            @click="openContractFilterModal"
                            classesButton="select-none size-[30px] min-h-0 p-0 inline-flex items-center justify-center rounded-md bg-white/8 hover:bg-white/16 cursor-pointer transition-[background-color] duration-150 ease-out"
                        />
                        <span
                            v-if="activeFilterCount > 0"
                            class="absolute -top-1 -right-1 flex items-center justify-center size-4 rounded-full bg-accent-600 text-white text-[10px] font-bold pointer-events-none"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </div>

                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Export')"
                        :icon="IconFileSpreadsheet"
                        icon-size="h-5 w-5"
                        white-icon
                        @click="openContractExportModal"
                        classesButton="select-none size-[30px] min-h-0 p-0 inline-flex items-center justify-center rounded-md bg-white/8 hover:bg-white/16 cursor-pointer transition-[background-color] duration-150 ease-out"
                    />

                    <BaseUIButton variant="primary" on-band hide-icon @click="openContractUploadModal">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('New contract') }}
                    </BaseUIButton>
                </template>
            </ToolbarHeader>

            <!-- Active filter tags -->
            <div class="flex flex-wrap gap-2 mt-1" v-if="hasActiveFilters || searchTerm.trim() !== ''">
                <BaseFilterTag v-if="searchTerm.trim() !== ''" :filter="{ name: `${$t('Search')}: ${searchTerm.trim()}`, type: 'search' }" @remove-filter="searchTerm = ''" />
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

            <!-- Schnellfilter: Stichtage, offene Aufgaben, Status -->
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <label v-if="filteredContracts.length > 0" class="mr-2 inline-flex cursor-pointer items-center gap-2 text-xs text-text-muted">
                    <input type="checkbox"
                           class="h-4 w-4 cursor-pointer rounded border-border text-accent-600 focus:ring-accent-600"
                           :checked="allFilteredSelected"
                           :indeterminate.prop="selectedCount > 0 && !allFilteredSelected"
                           @change="toggleSelectAll($event.target.checked)"/>
                    {{ $t('Select all') }}
                </label>
                <button v-for="option in deadlineFilterOptions" :key="option.value ?? 'all'"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs transition"
                        :class="deadlineFilter === option.value ? 'border-accent-600 bg-accent-50 font-semibold text-accent-700' : 'border-border-subtle text-text-muted hover:bg-surface-sunken'"
                        :aria-pressed="deadlineFilter === option.value"
                        @click="deadlineFilter = option.value">
                    {{ $t(option.label) }}
                    <span v-if="option.value" class="ml-1 tabular-nums text-text-subtle">{{ deadlineCounts[option.value] }}</span>
                </button>
                <span class="mx-1 h-4 w-px bg-border" aria-hidden="true"/>
                <button type="button"
                        class="rounded-full border px-3 py-1 text-xs transition"
                        :class="openTasksOnly ? 'border-accent-600 bg-accent-50 font-semibold text-accent-700' : 'border-border-subtle text-text-muted hover:bg-surface-sunken'"
                        :aria-pressed="openTasksOnly"
                        @click="openTasksOnly = !openTasksOnly">
                    {{ $t('With open tasks') }}
                </button>
                <template v-if="contractStates.length > 0">
                    <span class="mx-1 h-4 w-px bg-border" aria-hidden="true"/>
                    <button v-for="state in contractStates" :key="state"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs transition"
                            :class="stateFilter === state ? 'border-accent-600 bg-accent-50 font-semibold text-accent-700' : 'border-border-subtle text-text-muted hover:bg-surface-sunken'"
                            :aria-pressed="stateFilter === state"
                            @click="stateFilter = stateFilter === state ? null : state">
                        {{ state }}
                    </button>
                </template>
            </div>

            <!-- Contracts Table -->
            <BaseTable
                :rows="filteredContracts"
                :columns="cols"
                row-key="id"
                v-model:page="page"
                :page-size="filteredContracts.length > 25 ? 25 : null"
                :empty-title="isNarrowed ? 'No contracts match your search or filters.' : 'No contracts available'"
                :empty-message="isNarrowed ? '' : 'No contracts have been uploaded yet.'"
                :show-reset-button="isNarrowed"
                @reset="resetSearchAndFilters"
                @sort-change="page = 1"
            >
                <!-- Auswahl -->
                <template #cell-select="{ row }">
                    <input type="checkbox"
                           class="h-4 w-4 cursor-pointer rounded border-border text-accent-600 focus:ring-accent-600"
                           :checked="selectedIds[row.id] === true"
                           :aria-label="$t('Select')"
                           @change="toggleSelected(row.id, $event.target.checked)"/>
                </template>

                <!-- Contract Partner -->
                <template #cell-partner="{ row }">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="font-medium text-text">{{ row.partner || '-' }}</span>
                        <span v-if="row.ksk_liable" class="rounded border border-border-subtle px-1.5 py-0.5 text-[10px] text-text-muted">{{ $t('KSK-liable') }}</span>
                        <span v-if="row.foreign_tax" class="rounded border border-border-subtle px-1.5 py-0.5 text-[10px] text-text-muted">{{ $t('Foreign tax') }}</span>
                    </div>
                </template>

                <!-- Project -->
                <template #cell-project="{ row }">
                    <Link v-if="row.project" :href="route('projects.tab', {project: row.project.id, projectTab: first_project_tab_id})" class="text-accent-600 hover:text-accent-700">
                        {{ row.project.name }}
                    </Link>
                    <span v-else class="text-text-subtle">-</span>
                </template>

                <!-- Contract type -->
                <template #cell-contractType="{ row }">
                    <span v-if="row.contract_type" class="inline-flex items-center gap-1.5 text-text-muted">
                        <span v-if="row.contract_type.color" class="size-2 shrink-0 rounded-full" :style="{ backgroundColor: row.contract_type.color }" aria-hidden="true"/>
                        {{ row.contract_type.name }}
                    </span>
                    <span v-else class="text-text-subtle">-</span>
                </template>

                <!-- Amount -->
                <template #cell-amount="{ row }">
                    <span v-if="row.amount !== null && row.amount !== ''" class="tabular-nums">
                        {{ formatAmount(row.amount) }} {{ row.currency?.name ?? '' }}
                    </span>
                    <span v-else class="text-text-subtle">-</span>
                </template>

                <!-- Status (Freitext aus dem Vertrag) -->
                <template #cell-status="{ row }">
                    <span v-if="row.contract_state"
                          class="inline-flex max-w-40 items-center truncate rounded-md border border-border-subtle bg-surface-sunken px-2 py-0.5 text-xs text-text"
                          v-tooltip.top="row.contract_state_comment ? { value: row.contract_state_comment, appendTo: 'body', class: 'aw-tooltip' } : null">
                        {{ row.contract_state }}
                    </span>
                    <span v-else class="text-text-subtle">-</span>
                </template>

                <!-- Offene Aufgaben -->
                <template #cell-openTasks="{ row }">
                    <span v-if="row.open_tasks_count > 0" class="font-medium tabular-nums text-text">{{ row.open_tasks_count }}</span>
                    <span v-else class="text-text-subtle">-</span>
                </template>

                <!-- Deadline -->
                <template #cell-deadline="{ row }">
                    <span v-if="row.deadline_date" class="tabular-nums" :class="isOverdue(row) ? 'font-medium text-danger' : 'text-text-muted'"
                          :title="isOverdue(row) ? $t('Overdue') : null">
                        {{ formatDateDisplay(row.deadline_date) }}
                    </span>
                    <span v-else class="text-text-subtle">-</span>
                </template>

                <!-- Access Users & Departments -->
                <template #cell-accessUsers="{ row }">
                    <div class="flex items-center">
                        <div class="flex -space-x-2">
                            <img
                                v-for="user in displayedAccessUsersOf(row)?.slice(0, 3)"
                                :key="'user-' + user.id"
                                :src="user.profile_photo_url"
                                :alt="user.first_name + ' ' + user.last_name"
                                class="size-8 rounded-full ring-2 ring-white object-cover"
                                v-tooltip.top="{ value: user.first_name + ' ' + user.last_name, appendTo: 'body', class: 'aw-tooltip' }"
                            />
                            <div
                                v-for="department in row.accessibleDepartments?.slice(0, Math.max(0, 3 - (displayedAccessUsersOf(row)?.length || 0)))"
                                :key="'dept-' + department.id"
                                class="size-8 rounded-full ring-2 ring-white bg-surface-sunken flex items-center justify-center"
                                v-tooltip.top="{ value: department.name, appendTo: 'body', class: 'aw-tooltip' }"
                            >
                                <TeamIconCollection :iconName="department.svg_name" class="size-6" />
                            </div>
                        </div>
                        <BaseMenu
                            v-if="(displayedAccessUsersOf(row)?.length || 0) + (row.accessibleDepartments?.length || 0) > 3"
                            :show-icon="false"
                            :show-menu-button-text="true"
                            :menu-button-text="'+' + ((displayedAccessUsersOf(row)?.length || 0) + (row.accessibleDepartments?.length || 0) - 3)"
                            classes="ml-2 cursor-pointer"
                            classes-button="text-xs text-text-subtle hover:text-text-muted cursor-pointer"
                            white-menu-background
                        >
                            <div class="p-2 min-w-48">
                                <div v-for="user in displayedAccessUsersOf(row)" :key="'menu-user-' + user.id" class="flex items-center py-1.5 px-2 hover:bg-surface-sunken rounded">
                                    <img :src="user.profile_photo_url" :alt="user.first_name + ' ' + user.last_name" class="size-6 rounded-full object-cover mr-2" />
                                    <span class="text-sm text-text-muted">{{ user.first_name }} {{ user.last_name }}</span>
                                </div>
                                <div v-for="department in row.accessibleDepartments" :key="'menu-dept-' + department.id" class="flex items-center py-1.5 px-2 hover:bg-surface-sunken rounded">
                                    <TeamIconCollection :iconName="department.svg_name" class="size-6 mr-2" />
                                    <span class="text-sm text-text-muted">{{ department.name }}</span>
                                </div>
                            </div>
                        </BaseMenu>
                    </div>
                </template>

                <!-- File Name (Download) -->
                <template #cell-filename="{ row }">
                    <div class="flex items-center gap-2">
                        <button v-if="row.is_previewable"
                                type="button"
                                class="rounded p-1 text-text-muted hover:bg-surface-sunken hover:text-text"
                                :title="$t('Preview')" :aria-label="$t('Preview')"
                                @click="previewContract = row">
                            <IconEye class="size-4" />
                        </button>
                        <a :href="route('contracts.download', row.id)" class="text-accent-600 hover:text-accent-700 flex items-center">
                            <IconDownload class="size-4 mr-2" />
                            {{ row.name }}
                        </a>
                    </div>
                </template>

                <!-- Actions -->
                <template #row-actions="{ row }">
                    <BaseMenu has-no-offset white-menu-background>
                        <BaseMenuItem v-if="row.is_previewable" :icon="IconEye" :title="$t('Preview')" white-menu-background @click="previewContract = row" />
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

        <!-- Vorschau -->
        <ContractPreviewModal
            v-if="previewContract"
            :contract="previewContract"
            @close="previewContract = null"
        />

        <!-- Sammel-Löschen -->
        <ConfirmDeleteModal
            v-if="showBulkDeleteModal"
            :title="$t('Delete contracts')"
            :description="selectedCount === 1 ? $t('Are you sure you want to delete this contract from the system?') : $t('Do you really want to delete the {count} selected contracts?', { count: selectedCount })"
            @closed="showBulkDeleteModal = false"
            @delete="bulkDelete"
        />

        <!-- Leiste für die Mehrfachauswahl -->
        <div v-if="selectedCount > 0" class="pointer-events-none fixed inset-x-0 bottom-6 z-40 flex justify-center px-4">
            <div class="pointer-events-auto flex items-center gap-x-3 rounded-xl bg-surface-inverse py-2.5 pl-4 pr-2 text-sm text-white shadow-lg">
                <span class="text-white/70">{{ $t('{count} selected', { count: selectedCount }) }}</span>
                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1 font-semibold hover:bg-white/25" @click="exportSelected">
                    <IconFileSpreadsheet class="size-4" />
                    {{ $t('Export') }}
                </button>
                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-danger px-3 py-1 font-semibold hover:opacity-90 disabled:opacity-50"
                        :disabled="bulkDeleting" @click="showBulkDeleteModal = true">
                    <IconTrash class="size-4" />
                    {{ $t('Delete') }}
                </button>
                <button type="button" class="rounded-lg p-1.5 hover:bg-white/15" :title="$t('Clear selection')" :aria-label="$t('Clear selection')" @click="selectedIds = {}">
                    <component :is="IconX" class="size-4" />
                </button>
            </div>
        </div>

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
import { Link, router } from '@inertiajs/vue3'
import { createInstanceFormatter } from "@/Helper/instanceFormat.js"
import {
    contractSortAccessors,
    distinctContractStates,
    matchesContractSearch,
    matchesDeadlineFilter,
    todayDateString
} from "@/Pages/Contracts/contractListTools.js"
import ContractPreviewModal from "@/Pages/Contracts/Components/ContractPreviewModal.vue"
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue"
import {IconFileText, IconCirclePlus, IconEdit, IconTrash, IconDownload, IconFilter, IconFileSpreadsheet, IconEye, IconX} from '@tabler/icons-vue'
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
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue"
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue"
import BaseTable from '@/Artwork/Table/BaseTable.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue"
import axios from 'axios'
import { displayedAccessUsersOf } from '@/Helper/sharedAccess.js'

export default {
    mixins: [Permissions],
    name: "ContractManagement",
    components: {
        Link,
        ContractPreviewModal,
        ConfirmDeleteModal,
        IconEye,
        IconTrash,
        IconFileSpreadsheet,
        ContractFilterModal,
        ContractExportModal,
        IconDownload,
        BaseMenuItem,
        BaseMenu,
        BaseTable,
        ToolbarHeader,
        BaseUIButton,
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
            IconEye,
            IconX,
        }
    },
    data() {
        return {
            page: 1,
            searchTerm: '',
            // Schnellfilter (nur für die aktuelle Sitzung, nicht im Profil gespeichert)
            deadlineFilter: null,
            openTasksOnly: false,
            stateFilter: null,
            deadlineFilterOptions: [
                { value: null, label: 'All' },
                { value: 'overdue', label: 'Overdue' },
                { value: 'week', label: 'This week' },
                { value: 'month', label: 'This month' },
            ],
            selectedIds: {},
            showBulkDeleteModal: false,
            bulkDeleting: false,
            previewContract: null,
            loadingContractId: null,
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
                { key: 'select', label: '', sortable: false, width: '36px' },
                { key: 'partner', label: 'Contract partner', sortable: true, accessor: contractSortAccessors.partner },
                { key: 'project', label: 'Project', sortable: true, accessor: contractSortAccessors.project },
                { key: 'contractType', label: 'Contract type', sortable: true, accessor: contractSortAccessors.contractType },
                { key: 'amount', label: 'Amount', sortable: true, type: 'number', accessor: contractSortAccessors.amount },
                { key: 'deadline', label: 'Deadline', sortable: true, accessor: contractSortAccessors.deadline },
                { key: 'status', label: 'Status', sortable: true, accessor: contractSortAccessors.status },
                { key: 'openTasks', label: 'Open tasks', sortable: true, type: 'number', accessor: contractSortAccessors.openTasks },
                { key: 'accessUsers', label: 'Access users', sortable: false },
                { key: 'filename', label: 'File name', sortable: true, accessor: contractSortAccessors.filename },
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
    watch: {
        // Nach Suche oder Filterwechsel wieder auf Seite 1, sonst steht man auf einer leeren Seite
        searchTerm() {
            this.page = 1;
        },
        deadlineFilter() {
            this.page = 1;
        },
        openTasksOnly() {
            this.page = 1;
        },
        stateFilter() {
            this.page = 1;
        },
        // Nach dem Neuladen (Löschen, Bearbeiten) keine Auswahl auf verschwundene Verträge behalten
        contracts(contracts) {
            const existing = new Set(contracts.map(contract => contract.id));
            this.selectedIds = Object.fromEntries(Object.keys(this.selectedIds).filter(id => existing.has(Number(id))).map(id => [id, true]));
            if (this.stateFilter && !this.contractStates.includes(this.stateFilter)) {
                this.stateFilter = null;
            }
        },
        filter: {
            handler() {
                this.page = 1;
            },
            deep: true,
        },
    },
    computed: {
        isNarrowed() {
            return Boolean(this.hasActiveFilters) || this.searchTerm.trim() !== ''
                || this.deadlineFilter !== null || this.openTasksOnly || this.stateFilter !== null;
        },
        contractStates() {
            return distinctContractStates(this.contracts);
        },
        // Zähler der Stichtags-Knöpfe über alle Verträge, die sonst zu Suche und Filtern passen
        deadlineCounts() {
            const counts = { overdue: 0, week: 0, month: 0 };
            for (const contract of this.contractsBeforeDeadlineFilter) {
                for (const key of Object.keys(counts)) {
                    if (matchesDeadlineFilter(contract, key)) {
                        counts[key]++;
                    }
                }
            }
            return counts;
        },
        filteredContracts() {
            return this.deadlineFilter
                ? this.contractsBeforeDeadlineFilter.filter(contract => matchesDeadlineFilter(contract, this.deadlineFilter))
                : this.contractsBeforeDeadlineFilter;
        },
        selectedCount() {
            return Object.keys(this.selectedIds).length;
        },
        allFilteredSelected() {
            return this.filteredContracts.length > 0
                && this.filteredContracts.every(contract => this.selectedIds[contract.id]);
        },
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
        contractsBeforeDeadlineFilter() {
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
                    // ContractResource liefert foreign_tax (has_foreign_tax gibt es nur im Projekt-Payload)
                    return contract.foreign_tax;
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

            if (this.searchTerm.trim() !== '') {
                filteredContracts = filteredContracts.filter(contract => matchesContractSearch(contract, this.searchTerm));
            }

            if (this.openTasksOnly) {
                filteredContracts = filteredContracts.filter(contract => contract.open_tasks_count > 0);
            }

            if (this.stateFilter !== null) {
                const wanted = this.stateFilter.toLowerCase();
                filteredContracts = filteredContracts.filter(
                    contract => String(contract.contract_state ?? '').trim().toLowerCase() === wanted
                );
            }

            return filteredContracts;
        },
    },
    methods: {
        // Anzeige inkl. Projektleitungen; das Bearbeiten-Modal nutzt nur die echten Freigaben (accessibleUsers)
        displayedAccessUsersOf,
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
        formatAmount(amount) {
            return createInstanceFormatter(this.$page.props.instanceFormat).formatNumber(amount);
        },
        isOverdue(contract) {
            return Boolean(contract.deadline_date) && contract.deadline_date.substring(0, 10) < todayDateString();
        },
        toggleSelected(id, checked) {
            if (checked) {
                this.selectedIds[id] = true;
            } else {
                delete this.selectedIds[id];
            }
        },
        toggleSelectAll(checked) {
            this.selectedIds = checked
                ? Object.fromEntries(this.filteredContracts.map(contract => [contract.id, true]))
                : {};
        },
        exportSelected() {
            window.open(route('contracts.export', { ids: Object.keys(this.selectedIds) }), '_blank', 'noopener');
        },
        bulkDelete() {
            if (this.bulkDeleting) {
                return;
            }
            this.bulkDeleting = true;
            this.showBulkDeleteModal = false;
            axios.post(route('contracts.bulk-delete'), { ids: Object.keys(this.selectedIds).map(Number) })
                .then(() => {
                    this.selectedIds = {};
                    router.reload({ only: ['contracts'] });
                })
                .finally(() => {
                    this.bulkDeleting = false;
                });
        },
        resetSearchAndFilters() {
            this.searchTerm = '';
            this.deadlineFilter = null;
            this.openTasksOnly = false;
            this.stateFilter = null;
            this.filter.kskLiable = false;
            this.filter.foreignTax = false;
            this.filter.dateFrom = null;
            this.filter.dateTo = null;
            this.filter.legalForms.forEach(legalForm => { legalForm.checked = false; });
            this.filter.contractTypes.forEach(contractType => { contractType.checked = false; });
            this.saveFilter();
        },
        formatDateDisplay(dateString) {
            if (!dateString) return null;
            const [year, month, day] = dateString.substring(0, 10).split('-');
            return `${day}.${month}.${year}`;
        },
        // Die Liste trägt nur schlanke Zeilen; den vollständigen Vertrag (Aufgaben, Kommentare,
        // Freigaben) lädt das Bearbeiten-Fenster hier nach.
        openContractEditModal(contract) {
            if (this.loadingContractId !== null) {
                return;
            }
            this.loadingContractId = contract.id;
            axios.get(route('contracts.details', contract.id))
                .then(({ data }) => {
                    this.selectedContract = data.contract;
                    this.showContractEditModal = contract.id;
                })
                .finally(() => {
                    this.loadingContractId = null;
                });
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
