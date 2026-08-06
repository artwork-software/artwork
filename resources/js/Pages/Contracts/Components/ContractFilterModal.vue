<template>
    <ArtworkBaseModal
        modal-size="max-w-3xl"
        :title="$t('Contract Filter')"
        :description="$t('Filter contracts by various criteria to find the relevant information quickly.')"
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
                    <BaseUIButton @click="applyFilter" type="button" label="Apply" use-translation is-add-button icon="IconCircleCheck"/>
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import {IconX} from "@tabler/icons-vue";
import { ref, computed, onMounted, watch } from 'vue';
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
    currentFilters: {
        type: Object,
        default: () => ({
            kskLiable: false,
            foreignTax: false,
            dateFrom: null,
            dateTo: null,
            legalForms: [],
            contractTypes: []
        })
    }
});

const emit = defineEmits(['close', 'apply']);

const filterComponentRef = ref(null);

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

const applyFilter = () => {
    emit('apply', { ...localFilterState.value });
    emit('close');
};

const initializeFromCurrentFilters = () => {
    // Initialize legalForms from companyTypes
    localFilterState.value.legalForms = props.companyTypes.map(type => ({
        id: type.id,
        name: type.name,
        checked: props.currentFilters.legalForms?.some(f => f.id === type.id && f.checked) || false
    }));

    // Initialize contractTypes
    localFilterState.value.contractTypes = props.contractTypes.map(type => ({
        id: type.id,
        name: type.name,
        checked: props.currentFilters.contractTypes?.some(f => f.id === type.id && f.checked) || false
    }));

    // Initialize boolean and date filters
    localFilterState.value.kskLiable = props.currentFilters.kskLiable || false;
    localFilterState.value.foreignTax = props.currentFilters.foreignTax || false;
    localFilterState.value.dateFrom = props.currentFilters.dateFrom || null;
    localFilterState.value.dateTo = props.currentFilters.dateTo || null;
};

onMounted(() => {
    initializeFromCurrentFilters();
});

watch(() => props.currentFilters, () => {
    initializeFromCurrentFilters();
}, { deep: true });
</script>
