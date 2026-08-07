

<template>
    <div class="relative">
        <ToolTipComponent
            direction="bottom"
            :tooltip-text="$t('Display Settings')"
            icon="IconSettings"
            icon-size="h-5 w-5"
            @click="showCalendarSettingsModal = true"
            classesButton="ui-button"
        />

        <span class="absolute flex size-2.5 top-0 right-0 pointer-events-none" v-if="checkIfAnySettingIsActive">
              <span class="relative inline-flex size-2.5 rounded-full bg-accent-600"></span>
        </span>
    </div>


    <teleport to="body">
        <CalendarSettingsModal
            v-if="showCalendarSettingsModal"
            @close="showCalendarSettingsModal = false"
            :is-planning="isPlanning"
            :in-shift-plan="isInShiftPlan"
            :is-daily-view="isDailyView"
            :is-list-view="isListView"
            :is-in-project-view="isInProjectView"
        />
    </teleport>
</template>


<script setup>

import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {computed, defineAsyncComponent, ref} from "vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    isPlanning: {
        type: Boolean,
        default: false
    },
    isInShiftPlan: {
        type: Boolean,
        default: false
    },
    isDailyView: {
        type: Boolean,
        default: false
    },
    isListView: {
        type: Boolean,
        default: false
    },
    isInProjectView: {
        type: Boolean,
        default: false
    }
})

const showCalendarSettingsModal = ref(false);

const CalendarSettingsModal = defineAsyncComponent({
    loader: () => import('@/Artwork/Modals/CalendarSettingsModal.vue'),
    delay: 200,
    timeout: 3000
})

const activeSettings = computed(() => {
    if (props.isListView) {
        return usePage().props.listViewSettings;
    }
    if (props.isInShiftPlan) {
        if (props.isDailyView) {
            return usePage().props.shift_plan_daily_settings ?? usePage().props.shift_plan_settings ?? usePage().props.auth.user.calendar_settings;
        }
        return usePage().props.shift_plan_settings ?? usePage().props.auth.user.calendar_settings;
    }
    if (props.isDailyView) {
        return usePage().props.daily_view_calendar_settings ?? usePage().props.auth.user.calendar_settings;
    }
    return usePage().props.auth.user.calendar_settings;
});

const checkIfAnySettingIsActive = computed(() => {
    const settings = activeSettings.value;
    if (!settings) return false;

    if (props.isListView) {
        const listViewKeys = [
            'detailed_shift_overview',
            'show_fully_staffed_shifts',
            'show_appointments',
            'group_by_shift_groups',
            'hide_shift_row',
            'shift_notes',
        ];
        return listViewKeys.some(setting => settings[setting]);
    }

    const settingsInShiftPlan = [
        'high_contrast', 'work_shifts', 'expand_days', 'display_project_groups', 'show_qualifications', 'shift_notes',
        'hide_unoccupied_days', 'hide_unoccupied_rooms', 'show_shift_group_tag', 'show_only_not_fully_staffed_shifts',
        'project_artists', 'project_status', 'project_management'
    ]

    // Projektfremde Termine/Schichten gibt es nur im Projekt-Schichten-Tab
    if (props.isInProjectView) {
        settingsInShiftPlan.push('show_unrelated_events', 'show_unrelated_shifts')
    }

    if (props.isInShiftPlan) {
        return settingsInShiftPlan.some(setting => settings[setting]);
    }

    return Object.values(settings).some(value => value);
});
</script>

<style scoped>

</style>
