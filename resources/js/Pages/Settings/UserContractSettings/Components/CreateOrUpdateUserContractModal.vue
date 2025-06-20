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
                        id="days_off_first_26_weeks" step="0.01" />
                    <p v-if="userContractForm.errors.days_off_first_26_weeks" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

            </div>

            <div class="flex items-center justify-between mt-10">
                <ArtworkBaseModalButton
                    type="button"
                    @click="$emit('close')"
                    variant="danger">
                    {{ $t('Cancel') }}
                </ArtworkBaseModalButton>


                <ArtworkBaseModalButton
                    type="submit"
                    variant="primary">
                    {{ $t('Save') }}
                </ArtworkBaseModalButton>
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
            days_off_first_26_weeks: 0.00
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
    days_off_first_26_weeks: props.userContract.days_off_first_26_weeks
})

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