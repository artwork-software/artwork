<template>
    <div class="relative">
        <ToolTipComponent
            direction="bottom"
            :tooltip-text="$t('Filter')"
            :icon="IconFilter"
            icon-size="h-7 w-7"
            @click="showCalendarFilterModal = true"/>

        <span class="absolute flex size-2.5 top-0 right-0 pointer-events-none" v-if="checkIfAnyFilterIsActive">
              <span class="absolute inline-flex h-full w-full motion-safe:animate-ping rounded-full bg-blue-400 opacity-75"></span>
              <span class="relative inline-flex size-2.5 rounded-full bg-blue-500"></span>
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
import {IconFilter} from "@tabler/icons-vue";

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

    return Object.entries(props.user_filters).some(([key, value]) => {
        if (ignoredKeys.includes(key)) {
            return false;
        }

        if (Array.isArray(value)) {
            return value.length > 0;
        }

        return value !== null && value !== '';
    });
});

const CalendarFilterModal = defineAsyncComponent({
    loader: () => import('@/Pages/Calendar/Components/CalendarFilterModal.vue'),
    delay: 200,
    timeout: 5000
})
</script>

<style scoped>

</style>
