<template>
    <ShiftSettingsHeader :title="$t('Work Time Pattern')" :description="$t('Define weekly target hours that can be assigned to users.')">
        <template #actions>
            <button class="ui-button-add" @click="showCreateOrUpdateWorkTimePatternModal = true">
                <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                {{ $t('Add Work Time Pattern') }}
            </button>
        </template>

        <SettingsGuideBanner
            storage-key="settings-guide.shift.work-time-patterns"
            title="How work time patterns work"
            :paragraphs="[
                'Create a pattern here and then assign it to people in their user profile. There it takes effect in the hour account: it defines the daily target hours from which overtime and undertime are calculated.',
                'The fields per weekday are daily target hours, not times of day — 8:00 therefore means a target of eight hours on that day.'
            ]"
        />

            <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5 mt-5">
                <ul role="list" class="divide-y divide-border-subtle" v-if="workTimePatterns.length > 0">
                    <li v-for="workTime in workTimePatterns" :key="workTime.id" class="flex justify-between gap-x-6 py-5">
                        <SingleWorkTimePattern :work-time-pattern="workTime" />
                    </li>
                </ul>
                <div v-else>
                    <BaseAlertComponent message="No work time patterns found. Please create a new one." type="info" use-translation />
                </div>
            </div>

        <CreateOrUpdateWorkTimePatternModal
            v-if="showCreateOrUpdateWorkTimePatternModal"
            @close="showCreateOrUpdateWorkTimePatternModal = false"
        />
    </ShiftSettingsHeader>
</template>

<script setup>

import TabComponent from "@/Components/Tabs/TabComponent.vue";
import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue";
import {ref} from "vue";
import CreateOrUpdateWorkTimePatternModal
    from "@/Pages/Settings/WorkTimePattern/Components/CreateOrUpdateWorkTimePatternModal.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import SingleWorkTimePattern from "@/Pages/Settings/WorkTimePattern/Components/SingleWorkTimePattern.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import {IconCirclePlus} from "@tabler/icons-vue";

const props = defineProps({
    workTimePatterns: {
        type: Object,
        default: () => ([])
    }
})

const showCreateOrUpdateWorkTimePatternModal = ref(false)
</script>

<style scoped>

</style>
