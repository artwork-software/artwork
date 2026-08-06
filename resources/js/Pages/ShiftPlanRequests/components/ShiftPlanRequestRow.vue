<template>
    <div
        :data-shift-plan-row-key="row.key"
        class="flex flex-col rounded-2xl border border-border-subtle bg-white shadow-sm transition-[border-color,box-shadow] duration-300"
        :class="[
            rejectActive ? 'border-danger-border' : '',
            isComparisonFocus ? 'border-accent-200 ring-2 ring-accent-200 ring-offset-2' : '',
        ]"
    >
        <div class="flex items-center gap-3 border-b border-border-subtle px-4 py-3">
            <div class="flex items-center gap-3 flex-1">
                <div v-if="row.type !== 'unassigned'" class="h-8 w-8 rounded-full bg-surface-sunken flex items-center justify-center text-[11px] font-semibold text-text-muted overflow-hidden">
                    <img v-if="row.avatar" :src="row.avatar" alt="" class="h-8 w-8 rounded-full object-cover" />
                    <span v-else class="uppercase">{{ row.name.slice(0, 2) }}</span>
                </div>
                <div>
                    <div class="text-sm font-medium text-text">{{ row.name }}</div>
                    <div class="text-[11px] text-text-subtle flex items-center gap-1">
                        <span v-if="row.typeLabel">{{ $t(row.typeLabel) }}</span>
                        <span v-if="row.totals.total_shifts > 0" class="inline-flex items-center gap-1">
                            ·
                            <IconCalendarTime class="h-3 w-3" />
                            {{ row.totals.total_shifts }} {{ row.totals.total_shifts > 1 ? $t('Shifts') : $t('Shift') }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="text-[11px] text-text-subtle"></div>
        </div>
        <div class="px-3 py-3">
            <div class="grid gap-2" :style="gridStyle">
                <ShiftDayCell
                    v-for="day in days"
                    :key="day.date"
                    :day="day"
                    :entries="row.days[day.date]"
                    :reject-active="rejectActive"
                    :day-date="day.date"
                    :selected-days="selectedDays"
                    :shift-selections="shiftSelections"
                    @open-history="$emit('open-history', $event)"
                />
            </div>
        </div>
    </div>
</template>
<script setup>
import ShiftDayCell from './ShiftDayCell.vue';
import { IconCalendarTime } from '@tabler/icons-vue';
defineProps({
    row: {type: Object, required: true},
    days: {type: Array, required: true},
    gridStyle: {type: Object, required: true},
    rejectActive: {type: Boolean, default: false},
    selectedDays: {type: Object, required: true},
    shiftSelections: {type: Object, required: true},
    isComparisonFocus: {type: Boolean, default: false},
});
defineEmits(['open-history']);
</script>
