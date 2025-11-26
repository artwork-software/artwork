<template>
    <div class="flex flex-col rounded-2xl border border-gray-200 bg-white shadow-sm" :class="rejectActive ? 'border-rose-300' : ''">
        <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3">
            <div class="flex items-center gap-3 flex-1">
                <div v-if="row.type !== 'unassigned'" class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-[11px] font-semibold text-gray-700 overflow-hidden">
                    <img v-if="row.avatar" :src="row.avatar" alt="" class="h-8 w-8 rounded-full object-cover" />
                    <span v-else class="uppercase">{{ row.name.slice(0, 2) }}</span>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ row.name }}</div>
                    <div class="text-[11px] text-gray-500 flex items-center gap-1">
                        <span v-if="row.typeLabel">{{ $t(row.typeLabel) }}</span>
                        <span v-if="row.totals.total_shifts > 0" class="inline-flex items-center gap-1">
                            ·
                            <IconCalendarTime class="h-3 w-3" />
                            {{ row.totals.total_shifts }} {{ row.totals.total_shifts > 1 ? $t('Shifts') : $t('Shift') }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="text-[11px] text-gray-400"></div>
        </div>
        <div class="px-3 py-3">
            <div class="grid gap-2" :style="gridStyle">
                <ShiftDayCell
                    v-for="day in days"
                    :key="day.date"
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
const props = defineProps({ row: { type: Object, required: true }, days: { type: Array, required: true }, gridStyle: { type: Object, required: true }, rejectActive: { type: Boolean, default: false }, selectedDays: { type: Object, required: true }, shiftSelections: { type: Object, required: true } });
const emits = defineEmits(['open-history']);
</script>
