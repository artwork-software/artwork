<template>
    <div class="relative">
        <ToolTipComponent
            direction="bottom"
            :tooltip-text="$t('Filter')"
            icon="IconFilter"
            icon-size="h-5 w-5"
            @click="showCalendarFilterModal = true"
            classesButton="ui-button"
        />

        <span class="absolute flex size-2.5 top-0 right-0 pointer-events-none" v-if="checkIfAnyFilterIsActive || staffingFilterActive">
              <span class="relative inline-flex size-2.5 rounded-full bg-accent-600"></span>
        </span>
    </div>

    <teleport to="body">
        <CalendarFilterModal
            v-if="showCalendarFilterModal"
            @close="showCalendarFilterModal = false"
            :filter-options="filterOptions"
            :personal-filters="personalFilters"
            :user_filters="user_filters"
            :crafts="crafts"
            :filter-type="filterType"
        />
    </teleport>
</template>

<script setup>

import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {computed, defineAsyncComponent, ref} from "vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    filterOptions: {
        type: Object,
        required: true
    },
    personalFilters: {
        type: Object,
        required: true
    },
    user_filters: {
        type: Object,
        required: true
    },
    crafts: {
        type: Object,
        required: false
    },
    inShiftPlan: {
        type: Boolean,
        default: false
    },
    filterType: {
        type: String,
        default: 'calendar_filter'
    }
});


const showCalendarFilterModal = ref(false);

const checkIfAnyFilterIsActive = computed(() => {
    const ignoredKeys = ['start_date', 'end_date', 'created_at', 'updated_at', 'id', 'user_id', 'filter_type'];

    // Guard against undefined/null user_filters
    const filters = props.user_filters ?? {};

    return Object.entries(filters).some(([key, value]) => {
        if (ignoredKeys.includes(key)) {
            return false;
        }

        if (Array.isArray(value)) {
            return value.length > 0;
        }

        // Boolesche Filter (z. B. show_only_users_with_open_violations): nur "an" zählt als aktiv
        if (typeof value === 'boolean' || value === 0 || value === 1) {
            return !!value;
        }

        return value !== null && value !== '';
    });
});

// Besetzungsfilter (Schichtplan-Setting, kein user_filters-Eintrag) soll den
// Aktiv-Punkt am Filter-Icon ebenfalls anzeigen
const staffingFilterActive = computed(() => {
    const pageProps = usePage().props;
    if (props.filterType === 'shift_daily_filter') {
        return !!(pageProps.shift_plan_daily_settings ?? pageProps.shift_plan_settings ?? pageProps.auth.user.calendar_settings)?.show_only_not_fully_staffed_shifts;
    }
    if (props.filterType === 'shift_filter') {
        return !!(pageProps.shift_plan_settings ?? pageProps.auth.user.calendar_settings)?.show_only_not_fully_staffed_shifts;
    }
    return false;
});

const CalendarFilterModal = defineAsyncComponent({
    loader: () => import('@/Pages/Calendar/Components/CalendarFilterModal.vue'),
    delay: 200,
    timeout: 5000
})
</script>

<style scoped>

</style>
