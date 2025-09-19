

<template>
    <div class="relative">
        <ToolTipComponent
            direction="bottom"
            :tooltip-text="$t('Display Settings')"
            :icon="IconSettings"
            icon-size="h-7 w-7"
            @click="showCalendarSettingsModal = true"
        />

        <span class="absolute flex size-2.5 top-0 right-0 pointer-events-none" v-if="checkIfAnySettingIsActive">
              <span class="absolute inline-flex h-full w-full motion-safe:animate-ping rounded-full bg-blue-400 opacity-75"></span>
              <span class="relative inline-flex size-2.5 rounded-full bg-blue-500"></span>
        </span>
    </div>


    <teleport to="body">
        <CalendarSettingsModal
            v-if="showCalendarSettingsModal"
            @close="showCalendarSettingsModal = false"
            :is-planning="isPlanning"
            :in-shift-plan="isInShiftPlan"
        />
    </teleport>
</template>


<script setup>

import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {computed, defineAsyncComponent, ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import {IconSettings} from "@tabler/icons-vue";

const props = defineProps({
    isPlanning: {
        type: Boolean,
        default: false
    },
    isInShiftPlan: {
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

const checkIfAnySettingIsActive = computed(() => {

    const settingsInShiftPlan = [
        'high_contrast', 'work_shifts', 'expand_days', 'display_project_groups', 'show_qualifications', 'shift_notes'
    ]

    if (props.isInShiftPlan) {
        return settingsInShiftPlan.some(setting => usePage().props.auth.user.calendar_settings[setting]);
    }

    if (usePage().props.auth.user.calendar_settings) {
        const userCalendarSettings = usePage().props.auth.user.calendar_settings;
        return Object.values(userCalendarSettings).some(value => value);
    }
});
</script>

<style scoped>

</style>
