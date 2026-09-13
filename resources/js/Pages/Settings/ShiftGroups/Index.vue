<template>
    <ShiftSettingsHeader :title="$t('shift groups')" :description="$t('Manage shift groups used to categorize shifts.')">

        <SettingsGuideBanner
            storage-key="settings-guide.shift.shift-groups"
            title="How shift groups work"
            :paragraphs="[
                'Shift groups categorize shifts and are the prerequisite for the rule type \'Rest time between shift groups\' in the \'Shift warnings – rules\' tab — without maintained groups this rule cannot take effect.',
                'The warning switch additionally reports when a person is assigned to shifts of more than one group on the same day.'
            ]"
        />

        <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5 mt-10">
            <div class="flex items-center justify-between">
                <BasePageTitle class=""
                               :title="$t('Waring on multiple shift assignments')"
                               :description="$t('Warning if person is assigned to more than one shift group on one day')"
                />

                <SwitchIconTooltip
                    v-model="warn_multiple_assignments"
                    @update:modelValue="updateWarnMultipleAssignments"
                    :tooltip-text="warn_multiple_assignments ? $t('No') : $t('Yes')"
                    size="md"
                    :icon="!warn_multiple_assignments ? IconX : IconCheck"
                />

            </div>
        </div>

        <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5 mt-10">
            <div class="flex items-center justify-between">
                <BasePageTitle class=""
                               :title="$t('shift groups')"
                               :description="$t('Manage shift groups used to categorize shifts.')"
                />
                <BaseUIButton @click="showCreateOrUpdateShiftGroupModal = true" label="New shift group" use-translation is-add-button />
            </div>
            <div class="mt-5">
                <div v-if="shiftGroups.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                    <IconStack2 class="h-10 w-10 text-text-subtle mb-3" stroke-width="1.5" />
                    <p class="text-sm font-medium text-text">{{ $t('No shift groups found.') }}</p>
                    <p class="mt-1 text-xs text-text-subtle max-w-md">
                        {{ $t('Shift groups categorise shifts, e.g. for filtering and evaluations. Create the first group to get started.') }}
                    </p>
                    <BaseUIButton class="mt-4" @click="showCreateOrUpdateShiftGroupModal = true" label="New shift group" use-translation is-add-button />
                </div>
                <ul v-else role="list" class="w-full">
                    <li v-for="(shiftGroup) in shiftGroups"
                        :key="shiftGroup.id"

                        class="cursor-pointer py-4 pr-4 flex justify-between items-center border-b border-border-subtle"
                    >
                        <SingleShiftGroup :shift-group="shiftGroup" />

                    </li>
                </ul>
            </div>
        </div>

        <CreateOrUpdateShiftGroupModal
            v-if="showCreateOrUpdateShiftGroupModal"
            @close="showCreateOrUpdateShiftGroupModal = false"
        />
    </ShiftSettingsHeader>
</template>

<script setup>

import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import SingleShiftGroup from "@/Pages/Settings/ShiftGroups/Components/SingleShiftGroup.vue";
import {ref} from "vue";
import CreateOrUpdateShiftGroupModal from "@/Pages/Settings/ShiftGroups/Components/CreateOrUpdateShiftGroupModal.vue";
import {IconCheck, IconStack2, IconX} from "@tabler/icons-vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import {router, usePage} from "@inertiajs/vue3";

const props = defineProps({
    shiftGroups: {
        type: Object,
        required: true,
        default: () => []
    }
})

const showCreateOrUpdateShiftGroupModal = ref(false);

const warn_multiple_assignments = ref(usePage().props.warn_multiple_assignments)

const updateWarnMultipleAssignments = () => {
   router.patch(route('shift-settings.update-warn-multiple-assignments'), {
       warn_multiple_assignments: warn_multiple_assignments.value
   })
}
</script>
<style scoped>

</style>
