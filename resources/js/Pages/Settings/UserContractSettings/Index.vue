<template>
    <ShiftSettingsHeader :title="$t('User Contracts')" :description="$t('Manage contract templates with employment-law parameters.')">
        <template #actions>
            <BaseUIButton variant="primary" hide-icon @click="showCreateOrUpdateUserContractModal = true">
                <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                {{ $t('Add User Contracts') }}
            </BaseUIButton>
        </template>

        <SettingsGuideBanner
            storage-key="settings-guide.shift.user-contracts"
            title="How user contracts work"
            :paragraphs="[
                'Contracts bundle the employment-law parameters of a person: free days, target-hour rules, season entitlements and overtime deadlines. You assign a contract to people in their user profile.',
                'Contracts take effect in the hour accounts, on the user info pages and in the shift rule check.'
            ]"
            footnote="Important: people without an assigned contract are not covered by the rule check at all."
        />

            <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5 mt-5">
                <ul role="list" class="divide-y divide-border-subtle" v-if="contracts.length > 0">
                    <li v-for="contract in contracts" :key="contract.id" class="flex justify-between gap-x-6 py-5">
                        <SingleUserContractTemplate :contract="contract" />
                    </li>
                </ul>
                <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                    <IconFileDescription class="h-10 w-10 text-text-subtle mb-3" stroke-width="1.5" />
                    <p class="text-sm font-medium text-text">{{ $t('No contract templates yet') }}</p>
                    <p class="mt-1 text-xs text-text-subtle max-w-md">
                        {{ $t('Contract templates bundle free days, target-hour rules and overtime deadlines. People without a contract are not covered by the rule check.') }}
                    </p>
                    <BaseUIButton class="mt-4" :label="$t('Add User Contracts')" is-add-button @click="showCreateOrUpdateUserContractModal = true" />
                </div>
            </div>

        <CreateOrUpdateUserContractModal
            v-if="showCreateOrUpdateUserContractModal"
            @close="showCreateOrUpdateUserContractModal = false"
        />
    </ShiftSettingsHeader>
</template>

<script setup>

import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue";
import {ref} from "vue";
import CreateOrUpdateWorkTimePatternModal
    from "@/Pages/Settings/WorkTimePattern/Components/CreateOrUpdateWorkTimePatternModal.vue";
import CreateOrUpdateUserContractModal
    from "@/Pages/Settings/UserContractSettings/Components/CreateOrUpdateUserContractModal.vue";
import SingleUserContractTemplate
    from "@/Pages/Settings/UserContractSettings/Components/SingleUserContractTemplate.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import {IconCirclePlus, IconFileDescription} from "@tabler/icons-vue";

const props = defineProps({
    contracts: {
        type: Object,
        default: () => ([])
    }
})

const showCreateOrUpdateUserContractModal = ref(false)
</script>

<style scoped>

</style>
