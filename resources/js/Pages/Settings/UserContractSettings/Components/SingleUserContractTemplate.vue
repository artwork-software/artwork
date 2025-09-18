<template>
    <div class="flex min-w-0 gap-x-4">
        <div class="min-w-0 flex-auto">
            <p class="text-sm/6 font-semibold text-gray-900">
                {{ contract.name }}
            </p>
            <p class="mt-1 flex text-xs/5 text-gray-500">
                {{ contract.description }}
            </p>
            <p class="mt-1 flex text-xs/5 text-gray-500 space-x-2">
                <span class="pr-2"><b>{{ $t('Free Full Days Per Week') }}</b>: {{ contract.free_full_days_per_week }}</span>
                <span class="px-2"><b>{{ $t('Free Half Days Per Week') }}</b>: {{ contract.free_half_days_per_week }}</span>
                <span class="px-2"><b>{{ $t('Special Day Rule Active') }}</b>: {{ contract.special_day_rule_active ? $t('Yes') : $t('No') }}</span>
                <span class="px-2"><b>{{ $t('Compensation Period (in days)') }}</b>: {{ contract.compensation_period }}</span>
                <span class="px-2"><b>{{ $t('Free Sundays Per Season') }}</b>: {{ contract.free_sundays_per_season }}</span>
                <span class="px-2"><b>{{ $t('Days Off First 26 Weeks') }}</b>: {{ contract.days_off_first_26_weeks.toFixed(2) }}</span>
            </p>
        </div>
    </div>
    <div class="flex shrink-0 items-center gap-x-6">
        <BaseMenu has-no-offset white-menu-background>
            <BaseMenuItem title="Edit" white-menu-background :icon="IconEdit" @click="showCreateOrUpdateUserContractModal = true"/>
            <BaseMenuItem title="Delete" white-menu-background :icon="IconTrash" @click="showDeleteModal = true"/>
        </BaseMenu>
    </div>

    <CreateOrUpdateUserContractModal
        v-if="showCreateOrUpdateUserContractModal"
        @close="showCreateOrUpdateUserContractModal = false"
        :user-contract="contract"
    />

    <ConfirmDeleteModal
        v-if="showDeleteModal"
        @closed="showDeleteModal = false"
        :title="$t('Delete User Contract')"
        :description="$t('Are you sure you want to delete this user contract? This action cannot be undone.')"
        @delete="deleteUserContract"
    />
</template>

<script setup>

import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {defineAsyncComponent, ref} from "vue";
import {router} from "@inertiajs/vue3";
import {IconEdit, IconTrash} from "@tabler/icons-vue";

const props = defineProps({
    contract: {
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

const showCreateOrUpdateUserContractModal = ref(false)
const showDeleteModal = ref(false)

const CreateOrUpdateUserContractModal = defineAsyncComponent({
    loader: () => import('@/Pages/Settings/UserContractSettings/Components/CreateOrUpdateUserContractModal.vue'),
    delay: 200,
})

const ConfirmDeleteModal = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/ConfirmDeleteModal.vue'),
    delay: 200,
})

const deleteUserContract = () => {
    router.delete(route('user-contract-settings.destroy', {userContract: props.userContract.id}), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
        },
        onError: (error) => {
            console.error('Error deleting work time pattern:', error);
        }
    });
}
</script>

<style scoped>

</style>
