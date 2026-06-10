<template>
    <ArtworkBaseModal :title="userContract.id ? 'Edit User Contract' : 'Create User Contract'"
                      :description="userContract.id ? 'Edit the user contract details.' : 'Create a new user contract.'"
                      @close="$emit('close')">


        <div v-if="userContract.id">
            <BaseAlertComponent
                message="This is an existing user contract. Editing it will update the contract for all users associated with it."
                use-translation
                type="warning"
            />
        </div>

        <form @submit.prevent="submit">
            <div class="space-y-4">
                <div>
                    <BaseInput
                        v-model="userContractForm.name"
                        label="Name"
                        required
                        id="name" />
                    <p v-if="userContractForm.errors.name" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <div>
                    <BaseTextarea
                        v-model="userContractForm.description"
                        label="Description"
                        id="description" />
                    <p v-if="userContractForm.errors.description" class="text-red-500 mt-0.5 text-xs"></p>
                </div>
                <div>
                    <BaseInput
                        v-model="userContractForm.free_full_days_per_week"
                        label="Free Full Days Per Week"
                        type="number"
                        id="free_full_days_per_week" />
                    <p v-if="userContractForm.errors.free_full_days_per_week" class="text-red-500 mt-0.5 text-xs"></p>
                </div>
                <div>
                    <BaseInput
                        v-model="userContractForm.free_half_days_per_week"
                        label="Free Half Days Per Week"
                        type="number"
                        id="free_half_days_per_week" />
                    <p v-if="userContractForm.errors.free_half_days_per_week" class="text-red-500 mt-0.5 text-xs"></p>
                </div>
                <div>
                    <div class="flex gap-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input id="candidates" aria-describedby="candidates-description" v-model="userContractForm.special_day_rule_active" name="candidates" type="checkbox" class="input-checklist" />
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="candidates" class="font-medium text-gray-900">{{ $t('Special Day Rule Active')}}</label>
                            <p id="candidates-description" class="text-gray-500">
                                {{ $t('If this is active, the special day rule will be applied to this contract. This means that on special days, the rules for free days may differ from the regular rules.') }}
                            </p>
                        </div>
                    </div>
                    <p v-if="userContractForm.errors.special_day_rule_active" class="text-red-500 mt-0.5 text-xs"></p>
                </div>
                <div>
                    <BaseInput
                        v-model="userContractForm.compensation_period"
                        label="Compensation Period (in days)"
                        type="number"
                        id="compensation_period" />
                    <p v-if="userContractForm.errors.compensation_period" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <!-- Spielzeitbezogene Infodaten (DP-18) -->
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <h3 class="text-sm font-semibold text-gray-900">{{ $t('Season-related info data') }}</h3>
                    <p class="text-xs text-gray-500 mb-3">
                        {{ $t('Activate the parameters relevant for this contract and define the target value (X). The season is configured in the tool settings under "Communication & Legal".') }}
                    </p>

                    <div class="space-y-3">
                        <div v-for="param in seasonInfoParams" :key="param.key"
                             class="rounded-md border border-gray-100 p-3">
                            <div class="flex items-start gap-3">
                                <div class="flex h-6 shrink-0 items-center">
                                    <input :id="`param_${param.key}`" type="checkbox" class="input-checklist"
                                           v-model="userContractForm[param.activeKey]" />
                                </div>
                                <div class="flex-1 text-sm/6">
                                    <label :for="`param_${param.key}`" class="font-medium text-gray-900">
                                        {{ $t(param.label) }}
                                    </label>
                                </div>
                            </div>
                            <div v-if="userContractForm[param.activeKey]" class="mt-2 pl-7">
                                <BaseInput
                                    v-model="userContractForm[param.key]"
                                    :label="$t('Minimum value (X)')"
                                    type="number"
                                    :step="param.step || '1'"
                                    :id="param.key" />
                            </div>
                        </div>

                        <!-- Urlaubsanspruch pro Kalenderjahr -->
                        <div class="rounded-md border border-gray-100 p-3">
                            <BaseInput
                                v-model="userContractForm.annual_vacation_days"
                                :label="$t('Annual vacation days (per calendar year)')"
                                type="number"
                                id="annual_vacation_days" />
                        </div>
                    </div>
                </div>

                <!-- Überstunden (DP-18 Stufe 2) -->
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <h3 class="text-sm font-semibold text-gray-900">{{ $t('Overtime') }}</h3>
                    <div class="mt-3 rounded-md border border-gray-100 p-3">
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <input id="overtime_rule_active" type="checkbox" class="input-checklist"
                                       v-model="userContractForm.overtime_rule_active" />
                            </div>
                            <div class="flex-1 text-sm/6">
                                <label for="overtime_rule_active" class="font-medium text-gray-900">
                                    {{ $t('Non-reduced overtime within the deadline leads to financial compensation') }}
                                </label>
                            </div>
                        </div>
                        <div v-if="userContractForm.overtime_rule_active" class="mt-2 pl-7">
                            <BaseInput
                                v-model="userContractForm.overtime_compensation_period"
                                :label="$t('Period within which overtime must be reduced (days)')"
                                type="number"
                                id="overtime_compensation_period" />
                        </div>
                    </div>
                </div>

            </div>

            <div class="flex items-center justify-between mt-10">
                <BaseUIButton
                    type="button"
                    @click="$emit('close')"
                    :label="$t('Cancel')"
                    is-cancel-button/>


                <BaseUIButton
                    type="submit"
                    is-add-button
                    :label="$t('Save')"
                />
            </div>
        </form>


    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import {useForm} from "@inertiajs/vue3";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    userContract: {
        type: Object,
        default: () => ({
            id: null,
            name: '',
            description: '',
            free_full_days_per_week: 0,
            free_half_days_per_week: 0,
            special_day_rule_active: false,
            compensation_period: 0,
            free_sundays_per_season: 0,
            days_off_first_26_weeks: 0.00,
            free_sundays_per_season_active: false,
            days_off_first_26_weeks_active: false,
            free_sundays_sat_mon_per_half: 0,
            free_sundays_sat_mon_per_half_active: false,
            free_sundays_and_saturdays_per_season: 0,
            free_sundays_and_saturdays_per_season_active: false,
            free_sundays_per_calendar_year: 0,
            free_sundays_per_calendar_year_active: false,
            one_and_half_day_combinations: 0,
            one_and_half_day_combinations_active: false,
            annual_vacation_days: 0,
            overtime_rule_active: false,
            overtime_compensation_period: null,
        })
    },
})

const emit = defineEmits(['close']);

const userContractForm = useForm({
    id: props.userContract.id,
    name: props.userContract.name,
    description: props.userContract.description,
    free_full_days_per_week: props.userContract.free_full_days_per_week,
    free_half_days_per_week: props.userContract.free_half_days_per_week,
    special_day_rule_active: props.userContract.special_day_rule_active,
    compensation_period: props.userContract.compensation_period,
    free_sundays_per_season: props.userContract.free_sundays_per_season,
    days_off_first_26_weeks: props.userContract.days_off_first_26_weeks,
    // Spielzeitbezogene Infodaten (DP-18)
    free_sundays_per_season_active: props.userContract.free_sundays_per_season_active ?? false,
    days_off_first_26_weeks_active: props.userContract.days_off_first_26_weeks_active ?? false,
    free_sundays_sat_mon_per_half: props.userContract.free_sundays_sat_mon_per_half ?? 0,
    free_sundays_sat_mon_per_half_active: props.userContract.free_sundays_sat_mon_per_half_active ?? false,
    free_sundays_and_saturdays_per_season: props.userContract.free_sundays_and_saturdays_per_season ?? 0,
    free_sundays_and_saturdays_per_season_active:
        props.userContract.free_sundays_and_saturdays_per_season_active ?? false,
    free_sundays_per_calendar_year: props.userContract.free_sundays_per_calendar_year ?? 0,
    free_sundays_per_calendar_year_active: props.userContract.free_sundays_per_calendar_year_active ?? false,
    one_and_half_day_combinations: props.userContract.one_and_half_day_combinations ?? 0,
    one_and_half_day_combinations_active: props.userContract.one_and_half_day_combinations_active ?? false,
    annual_vacation_days: props.userContract.annual_vacation_days ?? 0,
    // Überstunden (DP-18 Stufe 2)
    overtime_rule_active: props.userContract.overtime_rule_active ?? false,
    overtime_compensation_period: props.userContract.overtime_compensation_period ?? null,
})

const seasonInfoParams = [
    { key: 'free_sundays_per_season', activeKey: 'free_sundays_per_season_active', label: 'Free Sundays per season' },
    { key: 'days_off_first_26_weeks', activeKey: 'days_off_first_26_weeks_active', label: 'Days off in the first 26 weeks', step: '0.5' },
    { key: 'free_sundays_sat_mon_per_half', activeKey: 'free_sundays_sat_mon_per_half_active', label: 'Free Sundays connected with Saturday/Monday per season half' },
    { key: 'free_sundays_and_saturdays_per_season', activeKey: 'free_sundays_and_saturdays_per_season_active', label: 'Free Sundays + Saturdays per season' },
    { key: 'free_sundays_per_calendar_year', activeKey: 'free_sundays_per_calendar_year_active', label: 'Free Sundays per calendar year' },
    { key: 'one_and_half_day_combinations', activeKey: 'one_and_half_day_combinations_active', label: '1.5-day combinations' },
];

const submit = () => {
    if(userContractForm.id) {
        userContractForm.patch(route('user-contract-settings.update', userContractForm.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            }
        });
    } else {
        userContractForm.post(route('user-contract-settings.store'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            }
        });
    }
}

</script>

<style scoped>

</style>
