

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
              <span class="relative inline-flex size-2.5 rounded-full bg-blue-500"></span>
        </span>
    </div>


    <teleport to="body">
        <CalendarSettingsModal
            v-if="showCalendarSettingsModal"
            @close="showCalendarSettingsModal = false"
            :is-planning="isPlanning"
            :in-shift-plan="isInShiftPlan"
            :is-daily-view="isDailyView"
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
    }
})

const showCalendarSettingsModal = ref(false);

const CalendarSettingsModal = defineAsyncComponent({
    loader: () => import('@/Artwork/Modals/CalendarSettingsModal.vue'),
    delay: 200,
    timeout: 3000
})

const activeSettings = computed(() => {
    if (props.isDailyView) {
        return usePage().props.daily_view_calendar_settings ?? usePage().props.auth.user.calendar_settings;
    }
    return usePage().props.auth.user.calendar_settings;
});

const checkIfAnySettingIsActive = computed(() => {
    const settings = activeSettings.value;
    if (!settings) return false;

    const settingsInShiftPlan = [
        'high_contrast', 'work_shifts', 'expand_days', 'display_project_groups', 'show_qualifications', 'shift_notes',
        'hide_unoccupied_days', 'hide_unoccupied_rooms', 'show_shift_group_tag', 'show_only_not_fully_staffed_shifts'
    ]

    if (props.isInShiftPlan) {
        return settingsInShiftPlan.some(setting => settings[setting]);
    }

    return Object.values(settings).some(value => value);
});
</script>

<style scoped>

</style>
