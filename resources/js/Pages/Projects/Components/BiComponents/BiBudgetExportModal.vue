<template>
    <ArtworkBaseModal
        :title="$t('Budget export')"
        :description="options?.sageApiEnabled
            ? $t('All budget rows of the projects with events in the period, including Sage actuals.')
            : $t('All budget rows of the projects with events in the period.')"
        modal-size="sm:max-w-3xl"
        @close="$emit('close')"
    >
        <div class="px-6 pb-6 space-y-5">
            <!-- Zeitraum (Projektzeitraum-Bezug) -->
            <div>
                <p class="text-xs font-medium text-text-subtle mb-1.5">
                    {{ $t('Period (projects with events in this range)') }}
                </p>
                <div class="flex items-end gap-3">
                    <BaseInput type="date" id="bi_budget_from" v-model="dateFrom" :label="$t('From')" class="w-40" />
                    <BaseInput type="date" id="bi_budget_to" v-model="dateTo" :label="$t('To')" class="w-40" />
                </div>
            </div>

            <!-- KTO/KST-Freitextfilter mit Live-Trefferzahl -->
            <div>
                <p class="text-xs font-medium text-text-subtle mb-1.5">
                    {{ $t('Filter by account number (prefix match)') }}
                </p>
                <BaseInput
                    id="bi_budget_prefix"
                    v-model="prefixInput"
                    :label="options ? `${options.ktoColumnName} / ${options.kstColumnName}` : $t('Account number')"
                    class="max-w-xs"
                    @update:model-value="onPrefixInput"
                />
                <div v-if="prefixInput && matchCounts" class="mt-2 flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-md border border-accent-200 bg-accent-50 px-2.5 py-1 text-xs font-medium text-accent-700 hover:bg-accent-100 transition"
                        @click="addChip('kto')"
                    >
                        {{ options.ktoColumnName }}: {{ matchCounts.kto }} {{ $t('rows') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-md border border-accent-200 bg-accent-50 px-2.5 py-1 text-xs font-medium text-accent-700 hover:bg-accent-100 transition"
                        @click="addChip('kst')"
                    >
                        {{ options.kstColumnName }}: {{ matchCounts.kst }} {{ $t('rows') }}
                    </button>
                    <span class="py-1 text-[11px] text-text-subtle">{{ $t('Click to add as filter') }}</span>
                </div>

                <div v-if="chips.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                    <span
                        v-for="(chip, index) in chips"
                        :key="chip.column + chip.prefix"
                        class="inline-flex items-center gap-1.5 rounded-full bg-surface-sunken border border-border-subtle px-2.5 py-0.5 text-xs text-text-muted"
                    >
                        {{ chip.column === 'kto' ? options.ktoColumnName : options.kstColumnName }}
                        {{ $t('starts with') }} {{ chip.prefix }}
                        <button type="button" class="text-text-subtle hover:text-text-muted" @click="chips.splice(index, 1)">
                            <IconX class="size-3" />
                        </button>
                    </span>
                </div>
                <p v-if="chips.length > 1" class="mt-1 text-[11px] text-text-subtle">
                    {{ $t('Chips of the same column are OR-combined, different columns AND-combined.') }}
                </p>
            </div>

            <!-- Kostenträger -->
            <ArtworkBaseListbox
                v-if="options && options.costCenters.length > 0"
                v-model="selectedCostCenters"
                :items="options.costCenters"
                by="id"
                option-label="name"
                :label="$t('Cost bearer')"
                :placeholder="$t('All cost bearers')"
                multiple
                :search-threshold="0"
                :search-placeholder="$t('Search cost bearer')"
                class="max-w-md"
            />

            <!-- Sage-Optionen nur mit aktiver Schnittstelle -->
            <label
                v-if="options?.sageApiEnabled"
                class="flex items-center gap-2 text-sm text-text-muted cursor-pointer select-none"
            >
                <input type="checkbox" class="input-checklist !h-3.5 !w-3.5" v-model="restrictBookings" />
                {{ $t('Restrict Sage bookings additionally to the document date within the period') }}
            </label>

            <p v-if="exportError" class="text-sm text-danger">
                {{ $t('Export failed. Please try again.') }}
            </p>

            <div class="flex justify-end">
                <BaseUIButton
                    :label="isExporting ? $t('Exporting') + '…' : $t('Export')"
                    :disabled="isExporting || !dateFrom || !dateTo"
                    hide-icon
                    @click="doExport"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { IconX } from '@tabler/icons-vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import { useBiExport } from '@/Composeables/BiExport.js';

const props = defineProps({
    defaultDateFrom: { type: String, default: null },
    defaultDateTo: { type: String, default: null },
});

const emit = defineEmits(['close']);

const { isExporting, exportError, runExport } = useBiExport({
    cache: 'bi.budget-export.cache',
    status: 'bi.budget-export.status',
    download: 'bi.budget-export.download',
});

const options = ref(null);
const dateFrom = ref(props.defaultDateFrom ?? '');
const dateTo = ref(props.defaultDateTo ?? '');
const selectedCostCenters = ref([]);
const restrictBookings = ref(false);

const prefixInput = ref('');
const matchCounts = ref(null);
const chips = ref([]);

onMounted(async () => {
    const { data } = await axios.get(route('bi.budget-export.options'));
    options.value = data;
});

let countTimer = null;
const onPrefixInput = () => {
    clearTimeout(countTimer);
    matchCounts.value = null;
    const prefix = prefixInput.value?.trim();
    if (!prefix || !dateFrom.value || !dateTo.value) return;
    countTimer = setTimeout(async () => {
        try {
            const { data } = await axios.get(route('bi.budget-export.match-counts'), {
                params: {
                    prefix,
                    date_from: dateFrom.value,
                    date_to: dateTo.value,
                    cost_center_ids: selectedCostCenters.value.map(c => c.id),
                },
            });
            matchCounts.value = data;
        } catch (e) {
            matchCounts.value = null;
        }
    }, 350);
};

const addChip = (column) => {
    const prefix = prefixInput.value?.trim();
    if (!prefix) return;
    if (!chips.value.some(chip => chip.column === column && chip.prefix === prefix)) {
        chips.value.push({ column, prefix });
    }
    prefixInput.value = '';
    matchCounts.value = null;
};

const doExport = async () => {
    const started = await runExport({
        date_from: dateFrom.value,
        date_to: dateTo.value,
        cost_center_ids: selectedCostCenters.value.map(c => c.id),
        filters: chips.value,
        restrict_bookings_to_range: restrictBookings.value,
    });
    if (started) {
        emit('close');
    }
};
</script>
