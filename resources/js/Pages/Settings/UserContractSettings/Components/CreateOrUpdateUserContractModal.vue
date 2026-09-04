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
                    <p v-if="userContractForm.errors.name" class="text-danger mt-0.5 text-xs"></p>
                </div>

                <div>
                    <BaseTextarea
                        v-model="userContractForm.description"
                        label="Description"
                        id="description" />
                    <p v-if="userContractForm.errors.description" class="text-danger mt-0.5 text-xs"></p>
                </div>

                <SettingsGuideBanner
                    variant="static"
                    title="Free days"
                    :paragraphs="[
                        'Full and half free days per week define how many days off a person is entitled to each week. If the special-day rule is active, different free-day rules apply on special days.',
                        'The compensation period is the deadline in days within which a missed free day must be granted as a substitute day off.'
                    ]"
                />

                <div>
                    <BaseInput
                        v-model="userContractForm.free_full_days_per_week"
                        label="Free Full Days Per Week"
                        type="number"
                        id="free_full_days_per_week" />
                    <p v-if="userContractForm.errors.free_full_days_per_week" class="text-danger mt-0.5 text-xs"></p>
                </div>
                <div>
                    <BaseInput
                        v-model="userContractForm.free_half_days_per_week"
                        label="Free Half Days Per Week"
                        type="number"
                        id="free_half_days_per_week" />
                    <p v-if="userContractForm.errors.free_half_days_per_week" class="text-danger mt-0.5 text-xs"></p>
                </div>
                <div>
                    <div class="flex gap-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input id="candidates" aria-describedby="candidates-description" v-model="userContractForm.special_day_rule_active" name="candidates" type="checkbox" class="input-checklist" />
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="candidates" class="font-medium text-text">{{ $t('Special Day Rule Active')}}</label>
                            <p id="candidates-description" class="text-text-subtle">
                                {{ $t('Active: special days reduce the daily target of this person. Inactive: special days do not count, every day has the normal daily target (e.g. contracts where public holidays do not matter).') }}
                            </p>
                        </div>
                    </div>
                    <p v-if="userContractForm.errors.special_day_rule_active" class="text-danger mt-0.5 text-xs"></p>
                </div>
                <div>
                    <BaseInput
                        v-model="userContractForm.compensation_period"
                        label="Compensation Period (in days)"
                        type="number"
                        id="compensation_period" />
                    <p v-if="userContractForm.errors.compensation_period" class="text-danger mt-0.5 text-xs"></p>
                </div>

                <div class="rounded-xl border border-accent-200 bg-accent-50/60 p-4">
                    <label class="flex cursor-pointer items-start gap-3" for="use_three_month_average_for_target_reduction">
                        <input
                            id="use_three_month_average_for_target_reduction"
                            v-model="userContractForm.use_three_month_average_for_target_reduction"
                            type="checkbox"
                            class="input-checklist mt-0.5"
                        >
                        <span>
                            <span class="block text-sm font-semibold text-text">{{ $t('Use three-month average for target-hour reductions') }}</span>
                            <span class="mt-1 block text-xs leading-5 text-text-muted">
                                {{ $t('For weekly holidays and holiday-related compensation days without work, the target is reduced by the average actual daily working time from the three completed calendar months before the affected day. Only days with recorded work are counted; sickness, non-worked holidays and days off are excluded. Confirmed individual working times are included through the daily working-time booking. If no eligible working day exists, the contractual daily target is used.') }}
                            </span>
                        </span>
                    </label>
                </div>

                <!-- Spielzeitbezogene Infodaten (DP-18) -->
                <div class="mt-6 border-t border-border-subtle pt-4">
                    <h3 class="text-sm font-semibold text-text">{{ $t('Season-related info data') }}</h3>
                    <p class="text-xs text-text-subtle mb-3">
                        {{ $t('Activate the parameters relevant for this contract and define the target value (X). The season is configured in the tool settings under "Communication & Legal".') }}
                    </p>

                    <SettingsGuideBanner
                        variant="static"
                        title="Season parameters"
                        class="mb-3"
                        :paragraphs="[
                            'These parameters define minimum entitlements per season — the value X is always the minimum that should be reached (e.g. at least X free Sundays per season).',
                            'The season itself is configured in the tool settings under \'Communication & Legal\'. The evaluation appears on the user info pages.'
                        ]"
                    />

                    <div class="space-y-3">
                        <div v-for="param in seasonInfoParams" :key="param.key"
                             class="rounded-md border border-border-subtle p-3">
                            <div class="flex items-start gap-3">
                                <div class="flex h-6 shrink-0 items-center">
                                    <input :id="`param_${param.key}`" type="checkbox" class="input-checklist"
                                           v-model="userContractForm[param.activeKey]" />
                                </div>
                                <div class="flex-1 text-sm/6">
                                    <label :for="`param_${param.key}`" class="font-medium text-text">
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
                        <div class="rounded-md border border-border-subtle p-3">
                            <BaseInput
                                v-model="userContractForm.annual_vacation_days"
                                :label="$t('Annual vacation days (per calendar year)')"
                                type="number"
                                id="annual_vacation_days" />
                        </div>
                    </div>
                </div>

                <!-- Überstunden (DP-18 Stufe 2) -->
                <div class="mt-6 border-t border-border-subtle pt-4">
                    <h3 class="text-sm font-semibold text-text">{{ $t('Overtime') }}</h3>

                    <SettingsGuideBanner
                        variant="static"
                        title="Overtime deadline"
                        class="mt-3"
                        :paragraphs="[
                            'After the deadline has expired, overtime that has not been reduced is considered due for financial compensation — it is then no longer expected to be taken as time off.'
                        ]"
                    />

                    <div class="mt-3 rounded-md border border-border-subtle p-3">
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <input id="overtime_rule_active" type="checkbox" class="input-checklist"
                                       v-model="userContractForm.overtime_rule_active" />
                            </div>
                            <div class="flex-1 text-sm/6">
                                <label for="overtime_rule_active" class="font-medium text-text">
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
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
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
            special_day_rule_active: true,
            compensation_period: 0,
            use_three_month_average_for_target_reduction: false,
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
    use_three_month_average_for_target_reduction: props.userContract.use_three_month_average_for_target_reduction ?? false,
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
