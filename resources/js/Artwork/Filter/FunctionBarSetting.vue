

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
import {can, is} from "laravel-permission-to-vuejs";
import {resolveView, activeIndicatorKeys} from "@/Artwork/Modals/CalendarSettingsCatalog.js";

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

    // Dieselbe Liste wie im Modal: nur Einstellungen, die in dieser Ansicht erscheinen und wirken
    const view = resolveView({
        isPlanning: props.isPlanning,
        inShiftPlan: props.isInShiftPlan,
        isDailyView: props.isDailyView,
        isListView: props.isListView,
        isInProjectView: props.isInProjectView,
    });
    const ctx = {
        view,
        page: usePage().props,
        settings,
        dayRemarks: usePage().props.day_remarks ?? { enabled: false, mandatory: false, can_view: false, can_edit: false },
        can,
        is,
    };
    return activeIndicatorKeys(view, ctx).some((key) => !!settings[key]);
});
</script>

<style scoped>

</style>
