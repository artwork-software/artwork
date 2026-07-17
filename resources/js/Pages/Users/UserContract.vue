<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="userToEdit">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
            <div>
                <TinyPageHeadline
                    :title="$t('User Contract')"
                    :description="isContractSelected ? $t('The values below apply to this user. They were copied from the contract template and may be adjusted individually.') : $t('Here you can manage the user contract settings.')"
                />
            </div>
            <div class="flex items-center justify-end gap-2">
                <BaseUIButton
                    v-if="isContractSelected"
                    :label="$t('Remove employment contract')"
                    :icon="IconTrash"
                    @click.stop="showConfirmRemoveContractModal = true"
                />
                <BaseUIButton
                    :label="isContractSelected ? $t('Switch employment contract') : $t('Select employment contract')"
                    is-add-button
                    :icon="IconFileSearch"
                    @click.stop="showSelectUserContractModal = true"
                />
            </div>
        </div>

        <VisualFeedback :show-save-success="showVisualFeedback" />

        <!-- Active contract banner -->
        <div v-if="isContractSelected" class="mt-5 rounded-lg border border-blue-200 bg-blue-50/60 px-4 py-3">
            <div class="flex items-start gap-3">
                <component :is="IconFileCheck" class="size-5 shrink-0 text-blue-600 mt-0.5" stroke-width="1.5" />
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-blue-900 font-lexend">
                        {{ $t('Active contract: {0}', [selectedContract?.name ?? '-']) }}
                    </p>
                    <p v-if="selectedContract?.description" class="text-xs text-blue-800/80 mt-0.5">
                        {{ selectedContract.description }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Read-only overview of the assigned contract values -->
        <div v-if="isContractSelected" class="mt-5 space-y-4">
            <div class="card white p-5">
                <h3 class="text-sm font-semibold text-gray-900 font-lexend flex items-center gap-2">
                    <component :is="IconCalendarOff" class="size-4 text-gray-400" stroke-width="1.5" />
                    {{ $t('Free days & compensation') }}
                </h3>
                <dl class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-4">
                    <div v-for="item in baseValueItems" :key="item.label">
                        <dt class="text-xs text-gray-500">{{ $t(item.label) }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ item.value }}</dd>
                    </div>
                </dl>
            </div>

            <div class="card white p-5">
                <h3 class="text-sm font-semibold text-gray-900 font-lexend flex items-center gap-2">
                    <component :is="IconInfoSquareRounded" class="size-4 text-gray-400" stroke-width="1.5" />
                    {{ $t('Season-related info data') }}
                </h3>
                <template v-if="activeSeasonInfoItems.length > 0 || Number(userContractForm.annual_vacation_days) > 0">
                    <dl class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                        <div v-for="item in activeSeasonInfoItems" :key="item.label">
                            <dt class="text-xs text-gray-500">{{ $t(item.label) }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ item.value }}</dd>
                        </div>
                        <div v-if="Number(userContractForm.annual_vacation_days) > 0">
                            <dt class="text-xs text-gray-500">{{ $t('Annual vacation days (per calendar year)') }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ userContractForm.annual_vacation_days }}</dd>
                        </div>
                    </dl>
                </template>
                <p v-else class="mt-3 text-sm text-gray-500">
                    {{ $t('No season-related parameters are active for this contract.') }}
                </p>
            </div>

            <div class="card white p-5">
                <h3 class="text-sm font-semibold text-gray-900 font-lexend flex items-center gap-2">
                    <component :is="IconClockPlus" class="size-4 text-gray-400" stroke-width="1.5" />
                    {{ $t('Overtime') }}
                </h3>
                <div class="mt-3">
                    <template v-if="userContractForm.overtime_rule_active">
                        <p class="text-sm text-gray-900 font-medium">
                            {{ $t('Overtime rule active') }}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $t('Overtime must be compensated within {0} days, otherwise it becomes overtime to be paid out.', [userContractForm.overtime_compensation_period ?? '-']) }}
                        </p>
                    </template>
                    <p v-else class="text-sm text-gray-500">
                        {{ $t('No overtime rule is active for this contract.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Manual contract data (no template selected) -->
        <div class="mt-5" v-else>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="card white p-5">
                    <h3 class="text-sm font-semibold text-gray-900 font-lexend flex items-center gap-2">
                        <component :is="IconCalendarOff" class="size-4 text-gray-400" stroke-width="1.5" />
                        {{ $t('Free days & compensation') }}
                    </h3>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <BaseInput
                                v-model="userContractForm.free_full_days_per_week"
                                label="Free Full Days Per Week"
                                type="number"
                                id="free_full_days_per_week" />
                            <p v-if="userContractForm.errors.free_full_days_per_week" class="text-red-500 mt-0.5 text-xs">{{ userContractForm.errors.free_full_days_per_week }}</p>
                        </div>
                        <div>
                            <BaseInput
                                v-model="userContractForm.free_half_days_per_week"
                                label="Free Half Days Per Week"
                                type="number"
                                id="free_half_days_per_week" />
                            <p v-if="userContractForm.errors.free_half_days_per_week" class="text-red-500 mt-0.5 text-xs">{{ userContractForm.errors.free_half_days_per_week }}</p>
                        </div>
                        <div>
                            <BaseInput
                                v-model="userContractForm.compensation_period"
                                label="Compensation Period (in days)"
                                type="number"
                                id="compensation_period" />
                            <p v-if="userContractForm.errors.compensation_period" class="text-red-500 mt-0.5 text-xs">{{ userContractForm.errors.compensation_period }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <BaseCheckbox
                            v-model="userContractForm.special_day_rule_active"
                            id="special_day_rule_active"
                            :label="$t('Special Day Rule Active')"
                            :description="$t('If this is active, the special day rule will be applied to this contract. This means that on special days, the rules for free days may differ from the regular rules.')"
                        />
                        <p v-if="userContractForm.errors.special_day_rule_active" class="text-red-500 mt-0.5 text-xs">{{ userContractForm.errors.special_day_rule_active }}</p>
                    </div>
                </div>

                <div class="card white p-5">
                    <h3 class="text-sm font-semibold text-gray-900 font-lexend flex items-center gap-2">
                        <component :is="IconInfoSquareRounded" class="size-4 text-gray-400" stroke-width="1.5" />
                        {{ $t('Season-related info data') }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $t('Activate the parameters relevant for this contract and define the target value (X). The season is configured in the tool settings under "Communication & Legal".') }}
                    </p>
                    <div class="mt-4 space-y-3">
                        <div v-for="param in seasonInfoParams" :key="param.key"
                             class="rounded-md border border-gray-100 p-3">
                            <BaseCheckbox
                                v-model="userContractForm[param.activeKey]"
                                :id="`param_${param.key}`"
                                :label="$t(param.label)"
                            />
                            <div v-if="userContractForm[param.activeKey]" class="mt-2 pl-7">
                                <BaseInput
                                    v-model="userContractForm[param.key]"
                                    :label="$t('Minimum value (X)')"
                                    type="number"
                                    :step="param.step || '1'"
                                    :id="param.key" />
                            </div>
                        </div>
                        <div class="rounded-md border border-gray-100 p-3">
                            <BaseInput
                                v-model="userContractForm.annual_vacation_days"
                                :label="$t('Annual vacation days (per calendar year)')"
                                type="number"
                                id="annual_vacation_days" />
                        </div>
                    </div>
                </div>

                <div class="card white p-5">
                    <h3 class="text-sm font-semibold text-gray-900 font-lexend flex items-center gap-2">
                        <component :is="IconClockPlus" class="size-4 text-gray-400" stroke-width="1.5" />
                        {{ $t('Overtime') }}
                    </h3>
                    <div class="mt-4">
                        <BaseCheckbox
                            v-model="userContractForm.overtime_rule_active"
                            id="overtime_rule_active"
                            :label="$t('Overtime rule active')"
                            :description="$t('If activated, overtime must be compensated within the given number of days; otherwise it is shown in the \'Overtime\' tab as \'overtime to be paid out\' and must be booked out manually.')"
                        />
                        <p v-if="userContractForm.errors.overtime_rule_active" class="text-red-500 mt-0.5 text-xs">{{ userContractForm.errors.overtime_rule_active }}</p>
                        <div v-if="userContractForm.overtime_rule_active" class="mt-3 pl-7 max-w-sm">
                            <BaseInput
                                v-model="userContractForm.overtime_compensation_period"
                                :label="$t('Period within which overtime must be reduced (days)')"
                                type="number"
                                :min="1"
                                id="overtime_compensation_period" />
                            <p v-if="userContractForm.errors.overtime_compensation_period" class="text-red-500 mt-0.5 text-xs">{{ userContractForm.errors.overtime_compensation_period }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <BaseUIButton
                        type="submit"
                        :label="!userContractForm.processing ? $t('Save') : $t('Saving...')"
                        is-add-button
                        :disabled="userContractForm.processing"/>
                </div>
            </form>
        </div>

        <SelectUserContractModal
            :contracts="userContracts"
            :selected-contract-id="userContractForm.user_contract_id"
            v-if="showSelectUserContractModal"
            @close="showSelectUserContractModal = false"
            @select-contract="selectUserContract"
            @selectContract="selectUserContract"
        />

        <ConfirmDeleteModal
            v-if="showConfirmRemoveContractModal"
            :title="$t('Remove employment contract')"
            :description="$t('Are you sure you want to remove the current employment contract? This action cannot be undone.')"
            @delete="removeContract"
            @closed="showConfirmRemoveContractModal = false"
        />


    </UserEditHeader>
</template>

<script setup>

import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import {useForm} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import {computed, ref} from "vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import SelectUserContractModal from "@/Pages/Users/Components/SelectUserContractModal.vue";
import VisualFeedback from "@/Components/Feedback/VisualFeedback.vue";
import {
    IconFileSearch,
    IconTrash,
    IconFileCheck,
    IconCalendarOff,
    IconInfoSquareRounded,
    IconClockPlus
} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import {useTranslation} from "@/Composeables/Translation.js";

const $t = useTranslation();

const props = defineProps({
    userToEdit: {
        type: Object,
        required: true
    },
    contract: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            user_id: null,
            user_contract_id: null,
            free_full_days_per_week: 0,
            free_half_days_per_week: 0,
            special_day_rule_active: false,
            compensation_period: 0,
            free_sundays_per_season: 0,
            days_off_first_26_weeks: 0.00
        })
    },
    currentTab: {
        type: String,
        required: true
    },
    userContracts: {
        type: Object,
        required: true
    },
})

const showConfirmRemoveContractModal = ref(false)
const showSelectUserContractModal = ref(false)
const showVisualFeedback = ref(false)

// Spielzeitbezogene Infodaten (DP-18) – gleiche Definition wie in der Vertragsvorlage
const seasonInfoParams = [
    { key: 'free_sundays_per_season', activeKey: 'free_sundays_per_season_active', label: 'Free Sundays per season' },
    { key: 'days_off_first_26_weeks', activeKey: 'days_off_first_26_weeks_active', label: 'Days off in the first 26 weeks', step: '0.5', decimals: 2 },
    { key: 'free_sundays_sat_mon_per_half', activeKey: 'free_sundays_sat_mon_per_half_active', label: 'Free Sundays connected with Saturday/Monday per season half' },
    { key: 'free_sundays_and_saturdays_per_season', activeKey: 'free_sundays_and_saturdays_per_season_active', label: 'Free Sundays + Saturdays per season' },
    { key: 'free_sundays_per_calendar_year', activeKey: 'free_sundays_per_calendar_year_active', label: 'Free Sundays per calendar year' },
    { key: 'one_and_half_day_combinations', activeKey: 'one_and_half_day_combinations_active', label: '1.5-day combinations' },
];

const userContractForm = useForm({
    user_id: props.userToEdit?.id,
    user_contract_id: props.contract?.user_contract_id,
    free_full_days_per_week: props.contract?.free_full_days_per_week,
    free_half_days_per_week: props.contract?.free_half_days_per_week,
    special_day_rule_active: props.contract?.special_day_rule_active ?? false,
    compensation_period: props.contract?.compensation_period,
    overtime_rule_active: props.contract?.overtime_rule_active ?? false,
    overtime_compensation_period: props.contract?.overtime_compensation_period,
    free_sundays_per_season: props.contract?.free_sundays_per_season,
    days_off_first_26_weeks: props.contract?.days_off_first_26_weeks,
    // Spielzeitbezogene Infodaten (DP-18)
    free_sundays_per_season_active: props.contract?.free_sundays_per_season_active ?? false,
    days_off_first_26_weeks_active: props.contract?.days_off_first_26_weeks_active ?? false,
    free_sundays_sat_mon_per_half: props.contract?.free_sundays_sat_mon_per_half ?? 0,
    free_sundays_sat_mon_per_half_active: props.contract?.free_sundays_sat_mon_per_half_active ?? false,
    free_sundays_and_saturdays_per_season: props.contract?.free_sundays_and_saturdays_per_season ?? 0,
    free_sundays_and_saturdays_per_season_active: props.contract?.free_sundays_and_saturdays_per_season_active ?? false,
    free_sundays_per_calendar_year: props.contract?.free_sundays_per_calendar_year ?? 0,
    free_sundays_per_calendar_year_active: props.contract?.free_sundays_per_calendar_year_active ?? false,
    one_and_half_day_combinations: props.contract?.one_and_half_day_combinations ?? 0,
    one_and_half_day_combinations_active: props.contract?.one_and_half_day_combinations_active ?? false,
    annual_vacation_days: props.contract?.annual_vacation_days ?? 0,
})

const removeContract = () => {
    userContractForm.user_contract_id = null;
    userContractForm.free_full_days_per_week = 0;
    userContractForm.free_half_days_per_week = 0;
    userContractForm.special_day_rule_active = false;
    userContractForm.compensation_period = 0;
    userContractForm.overtime_rule_active = false;
    userContractForm.overtime_compensation_period = null;
    userContractForm.free_sundays_per_season = 0;
    userContractForm.days_off_first_26_weeks = 0.00;
    userContractForm.free_sundays_per_season_active = false;
    userContractForm.days_off_first_26_weeks_active = false;
    userContractForm.free_sundays_sat_mon_per_half = 0;
    userContractForm.free_sundays_sat_mon_per_half_active = false;
    userContractForm.free_sundays_and_saturdays_per_season = 0;
    userContractForm.free_sundays_and_saturdays_per_season_active = false;
    userContractForm.free_sundays_per_calendar_year = 0;
    userContractForm.free_sundays_per_calendar_year_active = false;
    userContractForm.one_and_half_day_combinations = 0;
    userContractForm.one_and_half_day_combinations_active = false;
    userContractForm.annual_vacation_days = 0;
    showConfirmRemoveContractModal.value = false;

    submit();
}

const selectUserContract = (contract) => {
    userContractForm.user_contract_id = contract.id;
    userContractForm.free_full_days_per_week = contract.free_full_days_per_week;
    userContractForm.free_half_days_per_week = contract.free_half_days_per_week;
    userContractForm.special_day_rule_active = contract.special_day_rule_active;
    userContractForm.compensation_period = contract.compensation_period;
    userContractForm.overtime_rule_active = contract.overtime_rule_active ?? false;
    userContractForm.overtime_compensation_period = contract.overtime_compensation_period;
    userContractForm.free_sundays_per_season = contract.free_sundays_per_season;
    userContractForm.days_off_first_26_weeks = contract.days_off_first_26_weeks;
    userContractForm.free_sundays_per_season_active = contract.free_sundays_per_season_active ?? false;
    userContractForm.days_off_first_26_weeks_active = contract.days_off_first_26_weeks_active ?? false;
    userContractForm.free_sundays_sat_mon_per_half = contract.free_sundays_sat_mon_per_half ?? 0;
    userContractForm.free_sundays_sat_mon_per_half_active = contract.free_sundays_sat_mon_per_half_active ?? false;
    userContractForm.free_sundays_and_saturdays_per_season = contract.free_sundays_and_saturdays_per_season ?? 0;
    userContractForm.free_sundays_and_saturdays_per_season_active = contract.free_sundays_and_saturdays_per_season_active ?? false;
    userContractForm.free_sundays_per_calendar_year = contract.free_sundays_per_calendar_year ?? 0;
    userContractForm.free_sundays_per_calendar_year_active = contract.free_sundays_per_calendar_year_active ?? false;
    userContractForm.one_and_half_day_combinations = contract.one_and_half_day_combinations ?? 0;
    userContractForm.one_and_half_day_combinations_active = contract.one_and_half_day_combinations_active ?? false;
    userContractForm.annual_vacation_days = contract.annual_vacation_days ?? 0;
    showSelectUserContractModal.value = false;

    submit();
}

const isContractSelected = computed(() => {
    return userContractForm.user_contract_id !== null;
});

const selectedContract = computed(() => {
    return props.userContracts.find(contract => contract.id === userContractForm.user_contract_id);
});

const baseValueItems = computed(() => [
    { label: 'Free Full Days Per Week', value: userContractForm.free_full_days_per_week ?? 0 },
    { label: 'Free Half Days Per Week', value: userContractForm.free_half_days_per_week ?? 0 },
    { label: 'Compensation Period (in days)', value: userContractForm.compensation_period ?? 0 },
    { label: 'Special Day Rule Active', value: userContractForm.special_day_rule_active ? $t('Yes') : $t('No') },
]);

const activeSeasonInfoItems = computed(() => {
    return seasonInfoParams
        .filter(param => userContractForm[param.activeKey])
        .map(param => ({
            label: param.label,
            value: param.decimals
                ? Number(userContractForm[param.key] ?? 0).toFixed(param.decimals)
                : (userContractForm[param.key] ?? 0),
        }));
});

const submit = () => {
    userContractForm.patch(route('user-contract-settings.update-user', props.userToEdit), {
        preserveScroll: true,
        onSuccess: () => {
            showVisualFeedback.value = true;
            setTimeout(() => {
                showVisualFeedback.value = false;
            }, 3000);
        },
        onError: () => {
        }
    });
}
</script>

<style scoped>

</style>
