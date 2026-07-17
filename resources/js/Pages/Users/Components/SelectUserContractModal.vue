<template>
    <ArtworkBaseModal
        title="Select User contract"
        description="Choose a contract for the user."
        modal-size="sm:max-w-3xl"
        @close="$emit('close')">

        <div class="space-y-4">
            <BaseInput
                v-model="searchInput"
                type="text"
                label="Search contracts..."
                is-small
                id="searchInput"/>

            <div v-if="selectedContractId" class="rounded-md bg-blue-50/60 border border-blue-200 px-3 py-2 text-xs text-blue-800">
                {{ $t('Selecting a different contract replaces the current values for this user.') }}
            </div>

            <div v-if="filteredContracts.length === 0" class="py-8 text-center text-sm text-gray-500">
                {{ $t('No contracts found.') }}
            </div>

            <ul v-else role="list" class="space-y-3 max-h-[55vh] overflow-y-auto pr-1">
                <li v-for="contract in filteredContracts"
                    :key="contract.id"
                    @click="$emit('selectContract', contract)"
                    class="group cursor-pointer rounded-lg border p-4 transition duration-150 ease-in-out"
                    :class="contract.id === selectedContractId
                        ? 'border-blue-300 bg-blue-50/50'
                        : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'">

                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 font-lexend flex items-center gap-2">
                                {{ contract.name }}
                                <span v-if="contract.id === selectedContractId"
                                      class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700">
                                    <component :is="IconCheck" class="size-3" stroke-width="2" />
                                    {{ $t('Currently selected') }}
                                </span>
                            </p>
                            <p v-if="contract.description" class="text-xs text-gray-500 mt-0.5">
                                {{ contract.description }}
                            </p>
                        </div>
                        <span class="shrink-0 text-xs font-medium transition"
                              :class="contract.id === selectedContractId
                                ? 'text-blue-400'
                                : 'text-blue-600 opacity-0 group-hover:opacity-100'">
                            {{ contract.id === selectedContractId ? '' : $t('Select') }} &rarr;
                        </span>
                    </div>

                    <dl class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-2">
                        <div>
                            <dt class="text-[11px] text-gray-500">{{ $t('Free Full Days Per Week') }}</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ contract.free_full_days_per_week }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-gray-500">{{ $t('Free Half Days Per Week') }}</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ contract.free_half_days_per_week }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-gray-500">{{ $t('Compensation Period (in days)') }}</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ contract.compensation_period }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-gray-500">{{ $t('Special Day Rule Active') }}</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ contract.special_day_rule_active ? $t('Yes') : $t('No') }}</dd>
                        </div>
                    </dl>

                    <!-- Season-related info data + overtime as compact badges -->
                    <div v-if="contractBadges(contract).length > 0" class="mt-3 flex flex-wrap gap-1.5">
                        <span v-for="badge in contractBadges(contract)" :key="badge"
                              class="inline-flex items-center rounded-full bg-gray-100 group-hover:bg-white px-2 py-0.5 text-[11px] text-gray-700 border border-gray-200">
                            {{ badge }}
                        </span>
                    </div>
                </li>
            </ul>

        </div>
    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {computed, ref} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {IconCheck} from "@tabler/icons-vue";
import {useTranslation} from "@/Composeables/Translation.js";

const $t = useTranslation();

const props = defineProps({
    contracts: {
        type: Object,
        required: true
    },
    selectedContractId: {
        type: [Number, null],
        required: false,
        default: null
    },
})

const emit = defineEmits(['close', 'selectContract']);

const searchInput = ref('');

const filteredContracts = computed(() => {
    const search = searchInput.value.toLowerCase();
    return props.contracts.filter(contract =>
        contract.name.toLowerCase().includes(search) ||
        (contract.description ?? '').toLowerCase().includes(search)
    );
});

// Spielzeitbezogene Infodaten (DP-18) als kompakte Badges
const seasonInfoParams = [
    { key: 'free_sundays_per_season', activeKey: 'free_sundays_per_season_active', label: 'Free Sundays per season' },
    { key: 'days_off_first_26_weeks', activeKey: 'days_off_first_26_weeks_active', label: 'Days off in the first 26 weeks', decimals: 2 },
    { key: 'free_sundays_sat_mon_per_half', activeKey: 'free_sundays_sat_mon_per_half_active', label: 'Free Sundays connected with Saturday/Monday per season half' },
    { key: 'free_sundays_and_saturdays_per_season', activeKey: 'free_sundays_and_saturdays_per_season_active', label: 'Free Sundays + Saturdays per season' },
    { key: 'free_sundays_per_calendar_year', activeKey: 'free_sundays_per_calendar_year_active', label: 'Free Sundays per calendar year' },
    { key: 'one_and_half_day_combinations', activeKey: 'one_and_half_day_combinations_active', label: '1.5-day combinations' },
];

const contractBadges = (contract) => {
    const badges = seasonInfoParams
        .filter(param => contract[param.activeKey])
        .map(param => {
            const value = param.decimals
                ? Number(contract[param.key] ?? 0).toFixed(param.decimals)
                : (contract[param.key] ?? 0);
            return `${$t(param.label)}: ${value}`;
        });

    if (Number(contract.annual_vacation_days) > 0) {
        badges.push(`${$t('Annual vacation days (per calendar year)')}: ${contract.annual_vacation_days}`);
    }

    if (contract.overtime_rule_active) {
        badges.push(`${$t('Overtime rule active')}: ${contract.overtime_compensation_period ?? '-'} ${$t('days')}`);
    }

    return badges;
};

</script>

<style scoped>

</style>
