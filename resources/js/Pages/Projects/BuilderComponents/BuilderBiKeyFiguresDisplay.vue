<template>
    <div class="min-w-0 text-xs text-text-muted leading-tight">
        <div v-if="hasData" class="space-y-0.5">
            <div v-if="figures.visitors !== null && figures.visitors !== undefined">
                <span class="text-text-subtle">{{ $t('Visitors') }}:</span> {{ formatInt(figures.visitors) }}
            </div>
            <div v-if="figures.revenue !== null && figures.revenue !== undefined">
                <span class="text-text-subtle">{{ $t('Revenue') }}:</span> {{ formatCurrency(figures.revenue) }}
            </div>
            <div v-if="figures.occupancy !== null && figures.occupancy !== undefined">
                <span class="text-text-subtle">{{ $t('Occupancy rate') }}:</span> {{ figures.occupancy.toFixed(1) }} %
            </div>
        </div>
        <span v-else class="text-text-subtle">—</span>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useInstanceFormat } from "@/Composeables/InstanceFormat.js";

const instanceFormat = useInstanceFormat();

const props = defineProps({
    project: { type: Object, required: true },
    component: { type: Object, required: false },
});

const figures = computed(() => props.project?.bi_key_figures ?? {});

const hasData = computed(() => {
    const f = figures.value;
    return [f.visitors, f.revenue, f.occupancy].some(v => v !== null && v !== undefined);
});

const numberFmt = new Intl.NumberFormat(instanceFormat.numberLocale);
const currencyFmt = new Intl.NumberFormat(instanceFormat.numberLocale, { style: 'currency', currency: instanceFormat.currency });
const formatInt = (v) => numberFmt.format(v ?? 0);
const formatCurrency = (v) => currencyFmt.format(v ?? 0);
</script>
