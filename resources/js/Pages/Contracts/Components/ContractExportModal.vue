<template>
    <ArtworkBaseModal
        modal-size="max-w-3xl"
        :title="$t('Export contracts')"
        :description="$t('Filter contracts and export them to Excel.')"
        @close="$emit('close')"
        full-modal
    >
        <div>
            <!-- Active Filters Section -->
            <div class="mb-4 pb-4 border-b-2 border-dashed border-border">
                <div class="flex items-start justify-between mb-3">
                    <BasePageTitle
                        :title="$t('Active filters')"
                        :description="$t('Your active filters. Click on a filter to remove it.')"
                    />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- KSK-liable filter tag -->
                    <div v-if="localFilterState.kskLiable" class="group block cursor-pointer shrink-0 bg-accent-50 w-fit px-2 py-1.5 rounded-full border border-accent-200">
                        <div class="flex items-center">
                            <div class="mx-2">
                                <p class="text-accent-600 text-xs group-hover:text-accent-600">{{ $t('KSK-liable') }}</p>
                            </div>
                            <button type="button" @click="localFilterState.kskLiable = false">
                                <IconX class="size-4 text-accent-600 hover:text-danger" />
                            </button>
                        </div>
                    </div>
                    <!-- Foreign tax filter tag -->
                    <div v-if="localFilterState.foreignTax" class="group block cursor-pointer shrink-0 bg-accent-50 w-fit px-2 py-1.5 rounded-full border border-accent-200">
                        <div class="flex items-center">
                            <div class="mx-2">
                                <p class="text-accent-600 text-xs group-hover:text-accent-600">{{ $t('Foreign tax') }}</p>
                            </div>
                            <button type="button" @click="localFilterState.foreignTax = false">
                                <IconX class="size-4 text-accent-600 hover:text-danger" />
                            </button>
                        </div>
                    </div>
                    <!-- Date range filter tag -->
                    <div v-if="localFilterState.dateFrom || localFilterState.dateTo" class="group block cursor-pointer shrink-0 bg-accent-50 w-fit px-2 py-1.5 rounded-full border border-accent-200">
                        <div class="flex items-center">
                            <div class="mx-2">
                                <p class="text-accent-600 text-xs group-hover:text-accent-600">
                                    {{ $t('Deadline') }}: {{ localFilterState.dateFrom || '...' }} - {{ localFilterState.dateTo || '...' }}
                                </p>
                            </div>
                            <button type="button" @click="clearDateRange">
                                <IconX class="size-4 text-accent-600 hover:text-danger" />
                            </button>
                        </div>
                    </div>
                    <!-- Legal form filter tags -->
                    <div v-for="legalForm in selectedLegalForms" :key="'tag-lf-' + legalForm.id" class="group block cursor-pointer shrink-0 bg-accent-50 w-fit px-2 py-1.5 rounded-full border border-accent-200">
                        <div class="flex items-center">
                            <div class="mx-2">
                                <p class="text-accent-600 text-xs group-hover:text-accent-600">{{ legalForm.name }}</p>
                            </div>
                            <button type="button" @click="legalForm.checked = false">
                                <IconX class="size-4 text-accent-600 hover:text-danger" />
                            </button>
                        </div>
                    </div>
                    <!-- Contract type filter tags -->
                    <div v-for="contractType in selectedContractTypes" :key="'tag-ct-' + contractType.id" class="group block cursor-pointer shrink-0 bg-accent-50 w-fit px-2 py-1.5 rounded-full border border-accent-200">
                        <div class="flex items-center">
                            <div class="mx-2">
                                <p class="text-accent-600 text-xs group-hover:text-accent-600">{{ contractType.name }}</p>
                            </div>
                            <button type="button" @click="contractType.checked = false">
                                <IconX class="size-4 text-accent-600 hover:text-danger" />
                            </button>
                        </div>
                    </div>
                    <!-- No active filters message -->
                    <p v-if="!hasActiveFilters" class="text-text-subtle text-sm">{{ $t('No active filters') }}</p>
                </div>
            </div>

            <!-- Filter Component -->
            <ContractFilterComponent
                ref="filterComponentRef"
                :company-types="companyTypes"
                :contract-types="contractTypes"
                v-model="localFilterState"
            />
        </div>

        <!-- Action Buttons -->
        <div class="py-4">
            <div class="flex items-center justify-between">
                <div>
                    <BaseUIButton @click="resetFilter" type="button" label="Reset" use-translation icon="IconRestore"/>
                </div>
                <div class="flex items-center gap-4">
                    <BaseUIButton @click="exportContracts" type="button" label="Export" use-translation is-add-button icon="IconDownload" :disabled="isExporting"/>
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import {IconX} from "@tabler/icons-vue";
import { ref, computed, onMounted } from 'vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BasePageTitle from '@/Artwork/Titles/BasePageTitle.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import ContractFilterComponent from './ContractFilterComponent.vue';

const props = defineProps({
    companyTypes: {
        type: Array,
        default: () => []
    },
    contractTypes: {
        type: Array,
        default: () => []
    },
    initialFilter: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close']);

const filterComponentRef = ref(null);
const isExporting = ref(false);

const localFilterState = ref({
    kskLiable: false,
    foreignTax: false,
    dateFrom: null,
    dateTo: null,
    legalForms: [],
    contractTypes: []
});

const selectedLegalForms = computed(() => {
    return localFilterState.value.legalForms?.filter(f => f.checked) || [];
});

const selectedContractTypes = computed(() => {
    return localFilterState.value.contractTypes?.filter(f => f.checked) || [];
});

const hasActiveFilters = computed(() => {
    return localFilterState.value.kskLiable ||
           localFilterState.value.foreignTax ||
           localFilterState.value.dateFrom ||
           localFilterState.value.dateTo ||
           selectedLegalForms.value.length > 0 ||
           selectedContractTypes.value.length > 0;
});

const clearDateRange = () => {
    localFilterState.value.dateFrom = null;
    localFilterState.value.dateTo = null;
};

const resetFilter = () => {
    localFilterState.value.kskLiable = false;
    localFilterState.value.foreignTax = false;
    localFilterState.value.dateFrom = null;
    localFilterState.value.dateTo = null;
    localFilterState.value.legalForms.forEach(f => f.checked = false);
    localFilterState.value.contractTypes.forEach(f => f.checked = false);
};

const exportContracts = () => {
    isExporting.value = true;

    // Build query parameters
    const params = new URLSearchParams();

    if (localFilterState.value.kskLiable) {
        params.append('kskLiable', '1');
    }
    if (localFilterState.value.foreignTax) {
        params.append('foreignTax', '1');
    }
    if (localFilterState.value.dateFrom) {
        params.append('dateFrom', localFilterState.value.dateFrom);
    }
    if (localFilterState.value.dateTo) {
        params.append('dateTo', localFilterState.value.dateTo);
    }

    // Add selected legal form IDs
    selectedLegalForms.value.forEach(lf => {
        params.append('legalFormIds[]', lf.id);
    });

    // Add selected contract type IDs
    selectedContractTypes.value.forEach(ct => {
        params.append('contractTypeIds[]', ct.id);
    });

    // Create download link
    const url = route('contracts.export') + '?' + params.toString();

    // Trigger download
    const link = document.createElement('a');
    link.href = url;
    link.download = 'contracts_export.xlsx';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    isExporting.value = false;
    emit('close');
};

const initializeFilters = () => {
    // Get initial filter values if provided
    const initialLegalFormIds = props.initialFilter?.legalForms?.filter(f => f.checked).map(f => f.id) || [];
    const initialContractTypeIds = props.initialFilter?.contractTypes?.filter(f => f.checked).map(f => f.id) || [];

    // Initialize legalForms from companyTypes with initial filter state
    localFilterState.value.legalForms = props.companyTypes.map(type => ({
        id: type.id,
        name: type.name,
        checked: initialLegalFormIds.includes(type.id)
    }));

    // Initialize contractTypes with initial filter state
    localFilterState.value.contractTypes = props.contractTypes.map(type => ({
        id: type.id,
        name: type.name,
        checked: initialContractTypeIds.includes(type.id)
    }));

    // Apply other initial filter values
    if (props.initialFilter) {
        localFilterState.value.kskLiable = props.initialFilter.kskLiable || false;
        localFilterState.value.foreignTax = props.initialFilter.foreignTax || false;
        localFilterState.value.dateFrom = props.initialFilter.dateFrom || null;
        localFilterState.value.dateTo = props.initialFilter.dateTo || null;
    }
};

onMounted(() => {
    initializeFilters();
});
</script>
