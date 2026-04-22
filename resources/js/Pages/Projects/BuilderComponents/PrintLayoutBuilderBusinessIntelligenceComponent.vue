<template>
    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-secondary mb-2">{{ $t('Business Intelligence') }}</h3>
        <div v-if="biData">
            <div class="mb-4">
                <h4 class="text-[10px] font-semibold text-gray-700 mb-1">{{ $t('Production data') }}</h4>
                <div class="grid grid-cols-4 gap-2 text-xs">
                    <div>{{ $t('New production') }}: {{ biData.is_new_production ? $t('Yes') : $t('No') }}</div>
                    <div>{{ $t('Co-production') }}: {{ biData.is_co_production ? $t('Yes') : $t('No') }}</div>
                    <div>{{ $t('Own production') }}: {{ biData.is_own_production ? $t('Yes') : $t('No') }}</div>
                    <div>{{ $t('Germany premiere') }}: {{ biData.is_germany_premiere ? $t('Yes') : $t('No') }}</div>
                </div>
                <div v-if="biData.premiere_date" class="text-xs mt-1">
                    {{ $t('Premiere date') }}: {{ formatDate(biData.premiere_date) }}
                </div>
            </div>

            <div class="mb-4" v-if="biData.visitors_total || biData.sold_tickets_total || biData.revenue_total">
                <h4 class="text-[10px] font-semibold text-gray-700 mb-1">{{ $t('Key figures') }}</h4>
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <div v-if="biData.visitors_total">{{ $t('Visitors') }}: {{ biData.visitors_total }}</div>
                    <div v-if="biData.sold_tickets_total">{{ $t('Sold tickets') }}: {{ biData.sold_tickets_total }}</div>
                    <div v-if="biData.revenue_total">{{ $t('Revenue') }}: {{ biData.revenue_total }}</div>
                </div>
            </div>
        </div>
        <div v-else class="text-sm text-secondary">
            {{ $t('No BI data available') }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    project: { type: Object, required: true },
    component: { type: Object, required: false },
});

const biData = computed(() => props.project?.bi_data || null);

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
};
</script>
