<template>
    <div class="min-h-[3.25rem] rounded-xl border border-dashed p-1.5 flex flex-col gap-1"
         :class="[
             rejectActive && isSelectedDay ? 'ring-2 ring-rose-400' : '',
             day.is_rejected ? 'border-red-500 bg-red-50/20 shadow-none' : 'border-gray-200 bg-gray-50/60'
         ]">
        <div v-if="day.rejection_reason" class="text-[10px] text-red-500 font-bold leading-tight mb-1 px-1 border-b border-red-100 pb-1">
            {{ day.rejection_reason }}
        </div>
        <template v-if="entries && entries.length">
            <div v-for="entry in entries"
                 :key="entry.unique_key"
                 class="rounded-lg px-2 py-1.5 flex flex-col gap-0.5 text-[11px] transition relative"
                 :class="entry.is_removed_ghost
                     ? 'border border-dashed border-red-300 bg-red-50/40'
                     : (entry.is_individual_time ? 'bg-blue-50 border border-blue-200' : [entryCardClass(entry), 'cursor-pointer'])"
                 @click="(!entry.is_individual_time && !entry.is_removed_ghost) && $emit('open-history', entry.shift_id)">

                <!-- Removed / Deleted Ghost Entry -->
                <template v-if="entry.is_removed_ghost">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-red-400 line-through">{{ entry.start_time }} – {{ entry.end_time }}</span>
                        <IconTrash class="h-3 w-3 text-red-400 shrink-0" />
                    </div>
                    <span class="inline-flex w-fit items-center gap-1 rounded-full bg-red-100 px-1.5 py-0.5 text-[9px] font-semibold text-red-600">
                        {{ entry.reason === 'shift_deleted' ? $t('Subsequently deleted') : $t('Removed from shift') }}
                    </span>
                    <div v-if="entry.qualification" class="text-[10px] text-red-400/80 line-through truncate">{{ entry.qualification }}</div>
                </template>

                <!-- Shift Entry -->
                <template v-else-if="!entry.is_individual_time">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-gray-900">{{ entry.start_time }} – {{ entry.end_time }}</span>
                        <div class="flex items-center gap-1">
                            <span v-if="entry.is_subsequently_added" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-semibold text-amber-700">
                                {{ $t('Subsequently added') }}
                            </span>
                            <span v-if="entry.is_committed" class="inline-flex items-center gap-1 text-[10px] text-gray-500">
                                <IconLock class="h-3 w-3" />
                                {{ $t('Committed') }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] text-gray-500 truncate">{{ entry.qualification || $t('Shift') }}</span>
                        <span v-if="entry.has_changes_after_commit" class="inline-flex items-center gap-1 text-[10px] text-red-500">
                            <IconAlertTriangle class="h-3 w-3" />
                            {{ $t('Changed') }}
                        </span>
                        <span v-if="entry.has_changes_after_workflow" class="inline-flex items-center gap-1 text-[10px] text-orange-500">
                            <IconAlertTriangle class="h-3 w-3" />
                            {{ $t('Change requested') }}
                        </span>
                    </div>
                    <div v-if="entry.short_description" class="text-[10px] text-gray-400 truncate">{{ entry.short_description }}</div>
                    <div v-if="entry.workflow_rejection_reason" class="text-[10px] text-red-500 font-medium leading-tight mt-0.5">
                        {{ entry.workflow_rejection_reason }}
                    </div>
                </template>

                <!-- Individual Time Entry -->
                <template v-else>
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-gray-900">
                            {{ entry.full_day ? $t('Full day') : `${entry.start_time} – ${entry.end_time}` }}
                        </span>
                        <IconClock class="h-3 w-3 text-blue-400" />
                    </div>
                    <span class="text-[10px] text-blue-500 truncate">{{ entry.title || $t('Individual time') }}</span>
                </template>
            </div>
        </template>
        <template v-else>
            <div class="flex h-full items-center justify-center text-[11px] text-gray-300">
                {{ $t('No shift') }}
            </div>
        </template>
    </div>
</template>
<script setup>
import { IconAlertTriangle, IconClock, IconLock, IconTrash } from '@tabler/icons-vue';
import {computed} from 'vue';
const props = defineProps({
    entries: { type: Array, default: () => [] },
    rejectActive: { type: Boolean, default: false },
    dayDate: { type: String, required: true },
    day: { type: Object, default: () => ({}) },
    selectedDays: { type: Object, required: true },
    shiftSelections: { type: Object, required: true }
});
const isSelectedDay = computed(() => !!props.selectedDays[props.dayDate]);
const entryCardClass = (entry) => {
    let base = entry.has_changes_after_commit ? 'border border-red-300 bg-red-50/70 shadow-none hover:border-red-400' : 'bg-white shadow-sm hover:ring-1 hover:ring-indigo-200';

    if (entry.is_rejected || entry.workflow_rejection_reason) {
        base = 'border border-red-500 bg-red-50/30 shadow-none hover:border-red-600';
    }

    if (props.rejectActive && props.shiftSelections[entry.unique_key]) {
        return base + ' ring-2 ring-rose-400';
    }
    return base;
};
</script>
