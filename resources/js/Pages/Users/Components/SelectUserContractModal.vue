<template>
    <ArtworkBaseModal
        title="Select Work Time Pattern"
        description="Choose a work time pattern for the user."
        @close="$emit('close')">

        <div class="p-4 space-y-4">
            <BaseInput
                v-model="searchInput"
                type="text"
                label="Search patterns..."
                id="searchInput"/>


            <div class="mt-5">
                <ul role="list" class="divide-y divide-gray-100">
                    <li v-for="contract in filteredContracts"
                        :key="contract.id"
                        @click="$emit('selectContract', contract)"
                        class="flex flex-col gap-y-2 px-4 py-5 cursor-pointer hover:bg-gray-50 transition duration-150 ease-in-out rounded-md">

                        <div>
                            <p class="text-base font-semibold text-gray-900">
                                {{ contract.name }}
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ contract.description }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 text-sm text-gray-700">
                            <div><b>{{ $t('Free Full Days Per Week') }}:</b> {{ contract.free_full_days_per_week }}</div>
                            <div><b>{{ $t('Free Half Days Per Week') }}:</b> {{ contract.free_half_days_per_week }}</div>
                            <div><b>{{ $t('Special Day Rule Active') }}:</b> {{ contract.special_day_rule_active ? $t('Yes') : $t('No') }}</div>
                            <div><b>{{ $t('Compensation Period (in days)') }}:</b> {{ contract.compensation_period }}</div>
                            <div><b>{{ $t('Free Sundays Per Season') }}:</b> {{ contract.free_sundays_per_season }}</div>
                            <div><b>{{ $t('Days Off First 26 Weeks') }}:</b> {{ contract.days_off_first_26_weeks.toFixed(2) }}</div>
                        </div>
                    </li>
                </ul>
            </div>

        </div>
    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {computed, ref} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
    contracts: {
        type: Object,
        required: true
    },
})

const emit = defineEmits(['close', 'selectContract']);

const searchInput = ref('');

const filteredContracts = computed(() => {
    return props.contracts.filter(pattern =>
        pattern.name.toLowerCase().includes(searchInput.value.toLowerCase())
    );
});

</script>

<style scoped>

</style>