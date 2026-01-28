<template>
    <div class="space-y-4">
        <!-- Boolean Filters Section -->
        <div class="py-2">
            <div class="text-white bg-gray-900 rounded-lg px-4 py-2 font-lexend shadow text-sm">
                {{ $t('Contract options') }}
            </div>
            <div class="card white px-4 mt-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-3">
                    <!-- KSK-pflichtig -->
                    <div class="flex items-center gap-x-2">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="localFilters.kskLiable"
                                    id="filter-ksk-liable"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <label for="filter-ksk-liable" class="text-sm text-gray-900">
                            {{ $t('KSK-liable') }}
                        </label>
                    </div>
                    <!-- Auslandsteuer / Foreign Tax -->
                    <div class="flex items-center gap-x-2">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="localFilters.foreignTax"
                                    id="filter-foreign-tax"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <label for="filter-foreign-tax" class="text-sm text-gray-900">
                            {{ $t('Foreign tax') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Range Filter Section -->
        <div class="py-2">
            <div class="text-white bg-gray-900 rounded-lg px-4 py-2 font-lexend shadow text-sm">
                {{ $t('Deadline period') }}
            </div>
            <div class="card white px-4 mt-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-3">
                    <div>
                        <BaseInput
                            type="date"
                            id="filter-date-from"
                            v-model="localFilters.dateFrom"
                            :label="$t('From')"
                        />
                    </div>
                    <div>
                        <BaseInput
                            type="date"
                            id="filter-date-to"
                            v-model="localFilters.dateTo"
                            :label="$t('To')"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Legal Forms Filter Section -->
        <div class="py-2">
            <div class="text-white bg-gray-900 rounded-lg px-4 py-2 font-lexend shadow text-sm flex items-center justify-between cursor-pointer" @click="toggleSection('legalForms')">
                <span>{{ $t('Legal form') }}</span>
                <div class="flex items-center gap-5">
                    <span v-if="selectedLegalFormsCount > 0" class="inline-flex items-center rounded-lg bg-green-50 px-2 py-1 text-xs/4 text-green-600 ring-1 ring-inset ring-green-500/10">
                        {{ selectedLegalFormsCount }} {{ $t('selected') }}
                    </span>
                    <component :is="IconChevronDown" class="w-4 h-4 text-gray-400" :class="openSections.legalForms ? 'rotate-180' : ''" />
                </div>
            </div>
            <div v-if="openSections.legalForms" class="card white px-4 mt-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-3">
                    <div v-for="legalForm in localFilters.legalForms" :key="legalForm.id" class="flex items-center gap-x-2">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="legalForm.checked"
                                    :id="'filter-legal-form-' + legalForm.id"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <label :for="'filter-legal-form-' + legalForm.id" class="text-sm text-gray-900">
                            {{ legalForm.name }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contract Types Filter Section -->
        <div class="py-2">
            <div class="text-white bg-gray-900 rounded-lg px-4 py-2 font-lexend shadow text-sm flex items-center justify-between cursor-pointer" @click="toggleSection('contractTypes')">
                <span>{{ $t('Contract type') }}</span>
                <div class="flex items-center gap-5">
                    <span v-if="selectedContractTypesCount > 0" class="inline-flex items-center rounded-lg bg-green-50 px-2 py-1 text-xs/4 text-green-600 ring-1 ring-inset ring-green-500/10">
                        {{ selectedContractTypesCount }} {{ $t('selected') }}
                    </span>
                    <component :is="IconChevronDown" class="w-4 h-4 text-gray-400" :class="openSections.contractTypes ? 'rotate-180' : ''" />
                </div>
            </div>
            <div v-if="openSections.contractTypes" class="card white px-4 mt-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-3">
                    <div v-for="contractType in localFilters.contractTypes" :key="contractType.id" class="flex items-center gap-x-2">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="contractType.checked"
                                    :id="'filter-contract-type-' + contractType.id"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <label :for="'filter-contract-type-' + contractType.id" class="text-sm text-gray-900">
                            {{ contractType.name }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { IconChevronDown } from '@tabler/icons-vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';

const props = defineProps({
    companyTypes: {
        type: Array,
        default: () => []
    },
    contractTypes: {
        type: Array,
        default: () => []
    },
    modelValue: {
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

const emit = defineEmits(['update:modelValue']);

const openSections = ref({
    legalForms: false,
    contractTypes: false
});

const localFilters = ref({
    kskLiable: false,
    foreignTax: false,
    dateFrom: null,
    dateTo: null,
    legalForms: [],
    contractTypes: []
});

const selectedLegalFormsCount = computed(() => {
    return localFilters.value.legalForms.filter(f => f.checked).length;
});

const selectedContractTypesCount = computed(() => {
    return localFilters.value.contractTypes.filter(f => f.checked).length;
});

const toggleSection = (section) => {
    openSections.value[section] = !openSections.value[section];
};

const initializeFilters = () => {
    localFilters.value.legalForms = props.companyTypes.map(type => ({
        id: type.id,
        name: type.name,
        checked: props.modelValue.legalForms?.some(f => f.id === type.id && f.checked) || false
    }));

    localFilters.value.contractTypes = props.contractTypes.map(type => ({
        id: type.id,
        name: type.name,
        checked: props.modelValue.contractTypes?.some(f => f.id === type.id && f.checked) || false
    }));

    localFilters.value.kskLiable = props.modelValue.kskLiable || false;
    localFilters.value.foreignTax = props.modelValue.foreignTax || false;
    localFilters.value.dateFrom = props.modelValue.dateFrom || null;
    localFilters.value.dateTo = props.modelValue.dateTo || null;
};

// Watch for changes and emit to parent
watch(localFilters, (newVal) => {
    emit('update:modelValue', { ...newVal });
}, { deep: true });

// Initialize on mount
onMounted(() => {
    initializeFilters();
});

// Re-initialize when props change
watch(() => [props.companyTypes, props.contractTypes], () => {
    initializeFilters();
}, { deep: true });

// Watch for modelValue changes (e.g., when parent resets filters)
watch(() => props.modelValue, (newVal) => {
    localFilters.value.kskLiable = newVal.kskLiable || false;
    localFilters.value.foreignTax = newVal.foreignTax || false;
    localFilters.value.dateFrom = newVal.dateFrom || null;
    localFilters.value.dateTo = newVal.dateTo || null;
    localFilters.value.legalForms.forEach(f => {
        f.checked = newVal.legalForms?.some(nf => nf.id === f.id && nf.checked) || false;
    });
    localFilters.value.contractTypes.forEach(f => {
        f.checked = newVal.contractTypes?.some(nf => nf.id === f.id && nf.checked) || false;
    });
}, { deep: true });

// Expose reset method for parent components
const resetFilters = () => {
    localFilters.value.kskLiable = false;
    localFilters.value.foreignTax = false;
    localFilters.value.dateFrom = null;
    localFilters.value.dateTo = null;
    localFilters.value.legalForms.forEach(f => f.checked = false);
    localFilters.value.contractTypes.forEach(f => f.checked = false);
};

defineExpose({
    resetFilters,
    localFilters
});
</script>
