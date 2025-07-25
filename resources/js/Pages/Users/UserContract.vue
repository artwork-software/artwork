<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="userToEdit">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
            <div>
                <TinyPageHeadline
                    :title="$t('User Contract')"
                    :description="$t('Here you can manage the user contract settings.')"
                />

                <div v-if="selectedContract" class="mt-1">
                    <p class="text-sm font-lexend text-gray-500">
                        {{ $t('The employment contract “{0}” is currently selected. This means that the contractually agreed rules cannot be edited. Remove the employment contract to enter your own rules.', [selectedContract.name]) }}
                    </p>
                    <div class="mt-2 cursor-pointer text-artwork-buttons-create hover:text-artwork-buttons-default flex items-center gap-x-1 text-sm font-lexend" @click="showConfirmRemoveContractModal = true">
                        <component is="IconRepeat" class="size-5 text-gray-500"/>
                        {{ $t('Click here to remove the current employment contract.') }}
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2">
                <GlassyIconButton text="Select employment contract" icon="IconFileSearch" @click.stop="showSelectUserContractModal = true"/>
            </div>
        </div>

        <VisualFeedback :show-save-success="showVisualFeedback" />

        <div class="mt-5" v-if="!isContractSelected">
            <form @submit.prevent="submit">
                <div class="space-y-4">
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

                    <div>
                        <BaseInput
                            v-model="userContractForm.free_sundays_per_season"
                            label="Free Sundays Per Season"
                            type="number"
                            id="free_sundays_per_season" />
                        <p v-if="userContractForm.errors.free_sundays_per_season" class="text-red-500 mt-0.5 text-xs"></p>
                    </div>

                    <div>
                        <BaseInput
                            v-model="userContractForm.days_off_first_26_weeks"
                            label="Days Off First 26 Weeks"
                            type="number"
                            id="days_off_first_26_weeks" :step="0.01" />
                        <p v-if="userContractForm.errors.days_off_first_26_weeks" class="text-red-500 mt-0.5 text-xs"></p>
                    </div>

                </div>


                <div>
                    <ArtworkBaseModalButton
                        type="submit"
                        class="mt-4"
                        :disabled="userContractForm.processing">
                        {{ $t('Save') }}
                    </ArtworkBaseModalButton>
                </div>
            </form>
        </div>

        <div v-else class="mt-5">
            <div>
                <p class="text-sm font-lexend text-gray-500">
                    {{ $t('The employment contract “{0}” is currently selected. This means that employment contract rules cannot be edited.', [selectedContract?.name]) }}
                </p>
            </div>

            <div class="mt-5">

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 text-sm text-gray-700">
                    <div><b>{{ $t('Free Full Days Per Week') }}:</b> {{ contract?.free_full_days_per_week }}</div>
                    <div><b>{{ $t('Free Half Days Per Week') }}:</b> {{ contract?.free_half_days_per_week }}</div>
                    <div><b>{{ $t('Special Day Rule Active') }}:</b> {{ contract?.special_day_rule_active ? $t('Yes') : $t('No') }}</div>
                    <div><b>{{ $t('Compensation Period (in days)') }}:</b> {{ contract?.compensation_period }}</div>
                    <div><b>{{ $t('Free Sundays Per Season') }}:</b> {{ contract?.free_sundays_per_season }}</div>
                    <div><b>{{ $t('Days Off First 26 Weeks') }}:</b> {{ contract?.days_off_first_26_weeks.toFixed(2) }}</div>
                </div>
            </div>

        </div>


        <SelectUserContractModal
            :contracts="userContracts"
            v-if="showSelectUserContractModal"
            @close="showSelectUserContractModal = false"
            @select-contract="selectUserContract"
        />

        <ConfirmDeleteModal
            v-if="showConfirmRemoveContractModal"
            :title="$t('Remove employment contract')"
            :description="$t('Are you sure you want to remove the current employment contract? This action cannot be undone.')"
            @delete="removePattern"
            @closed="showConfirmRemoveContractModal = false"
        />


    </UserEditHeader>
</template>

<script setup>

import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {computed, ref} from "vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import SelectUserContractModal from "@/Pages/Users/Components/SelectUserContractModal.vue";
import VisualFeedback from "@/Components/Feedback/VisualFeedback.vue";

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

const removePattern = () => {
    userContractForm.user_contract_id = null;
    userContractForm.free_full_days_per_week = 0;
    userContractForm.free_half_days_per_week = 0;
    userContractForm.special_day_rule_active = false;
    userContractForm.compensation_period = 0;
    userContractForm.free_sundays_per_season = 0;
    userContractForm.days_off_first_26_weeks = 0.00;
    showConfirmRemoveContractModal.value = false;

    submit();
}

const userContractForm = useForm({
    user_id: props.userToEdit?.id,
    user_contract_id: props.contract?.user_contract_id,
    free_full_days_per_week: props.contract?.free_full_days_per_week,
    free_half_days_per_week: props.contract?.free_half_days_per_week,
    special_day_rule_active: props.contract?.special_day_rule_active,
    compensation_period: props.contract?.compensation_period,
    free_sundays_per_season: props.contract?.free_sundays_per_season,
    days_off_first_26_weeks: props.contract?.days_off_first_26_weeks
})

const selectUserContract = (contract) => {
    userContractForm.user_contract_id = contract.id;
    userContractForm.free_full_days_per_week = contract.free_full_days_per_week;
    userContractForm.free_half_days_per_week = contract.free_half_days_per_week;
    userContractForm.special_day_rule_active = contract.special_day_rule_active;
    userContractForm.compensation_period = contract.compensation_period;
    userContractForm.free_sundays_per_season = contract.free_sundays_per_season;
    userContractForm.days_off_first_26_weeks = contract.days_off_first_26_weeks;
    showSelectUserContractModal.value = false;

    submit();
}

const isContractSelected = computed(() => {
    return userContractForm.user_contract_id !== null;
});

const selectedContract = computed(() => {
    return props.userContracts.find(contract => contract.id === userContractForm.user_contract_id);
});


const submit = () => {
    userContractForm.patch(route('user-contract-settings.update-user', usePage().props.auth.user), {
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
