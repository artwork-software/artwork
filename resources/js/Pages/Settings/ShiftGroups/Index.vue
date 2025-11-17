<template>
    <ShiftSettingsHeader :title="$t('shift groups')" :description="$t('Manage shift groups used to categorize shifts.')">

        <div class="card white p-5 mt-10">
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

        <CreateOrUpdateShiftGroupModal
            v-if="showCreateOrUpdateShiftGroupModal"
            @close="showCreateOrUpdateShiftGroupModal = false"
        />

        <div class="card white p-5 mt-10">
            <div class="flex items-center justify-between">
                <BasePageTitle class=""
                               :title="$t('shift groups')"
                               :description="$t('Manage shift groups used to categorize shifts.')"
                />
                <BaseUIButton @click="showCreateOrUpdateShiftGroupModal = true" label="New shift group" use-translation is-add-button />
            </div>
            <div class="mt-5">
                <div class="mb-5 xsLight" v-if="shiftGroups.length === 0">
                    {{$t('No shift groups found.')}}
                </div>
                <ul v-else role="list" class="w-full">
                    <li v-for="(shiftGroup) in shiftGroups"
                        :key="shiftGroup.id"

                        class="cursor-pointer py-4 pr-4 flex justify-between items-center border-b border-zinc-200"
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
import {IconCheck, IconX} from "@tabler/icons-vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";
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
