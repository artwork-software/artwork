<template>
    <div class="min-h-[3.25rem] rounded-xl border border-dashed p-1.5 flex flex-col gap-1"
         :class="[ rejectActive && isSelectedDay ? 'ring-2 ring-danger-border' : '',
             day.is_rejected ? 'border-danger bg-danger-surface shadow-none' : 'border-border-subtle bg-surface-sunken'
         ]">
        <div v-if="day.rejection_reason" class="text-[10px] text-danger font-bold leading-tight mb-1 px-1 border-b border-danger-border pb-1">
            {{ day.rejection_reason }}
        </div>
        <template v-if="entries && entries.length">
            <div v-for="entry in entries"
                 :key="entry.unique_key"
                 class="rounded-lg px-2 py-1.5 flex flex-col gap-0.5 text-[11px] transition relative"
                 :class="entry.is_removed_ghost ? 'border border-dashed border-danger-border bg-danger-surface'
                     : (entry.is_individual_time ? 'bg-accent-50 border border-accent-200' : [entryCardClass(entry), 'cursor-pointer'])"
                 @click="(!entry.is_individual_time && !entry.is_removed_ghost) && $emit('open-history', entry.shift_id)">

                <!-- Removed / Deleted Ghost Entry -->
                <template v-if="entry.is_removed_ghost">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-danger line-through">{{ entry.start_time }} – {{ entry.end_time }}</span>
                        <IconTrash class="h-3 w-3 text-danger shrink-0" />
                    </div>
                    <span class="inline-flex w-fit items-center gap-1 rounded-full bg-danger-surface px-1.5 py-0.5 text-[9px] font-semibold text-danger">
                        {{ entry.reason === 'shift_deleted' ? $t('Subsequently deleted') : $t('Removed from shift') }}
                    </span>
                    <div v-if="entry.qualification" class="text-[10px] text-danger line-through truncate">{{ entry.qualification }}</div>
                </template>

                <!-- Shift Entry -->
                <template v-else-if="!entry.is_individual_time">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-text">{{ entry.start_time }} – {{ entry.end_time }}</span>
                        <div class="flex items-center gap-1">
                            <span v-if="entry.is_subsequently_added" class="inline-flex items-center gap-1 rounded-full bg-warning-surface px-1.5 py-0.5 text-[9px] font-semibold text-warning">
                                {{ $t('Subsequently added') }}
                            </span>
                            <span v-if="entry.is_committed" class="inline-flex items-center gap-1 text-[10px] text-text-subtle">
                                <IconLock class="h-3 w-3" />
                                {{ $t('Committed') }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] text-text-subtle truncate">{{ entry.qualification || $t('Shift') }}</span>
                        <span v-if="entry.has_changes_after_commit" class="inline-flex items-center gap-1 text-[10px] text-danger">
                            <IconAlertTriangle class="h-3 w-3" />
                            {{ $t('Changed') }}
                        </span>
                        <span v-if="entry.has_changes_after_workflow" class="inline-flex items-center gap-1 text-[10px] text-orange-500">
                            <IconAlertTriangle class="h-3 w-3" />
                            {{ $t('Change requested') }}
                        </span>
                    </div>
                    <div v-if="entry.room_name" class="flex items-center gap-1 text-[10px] text-text-subtle">
                        <IconMapPin class="h-3 w-3 shrink-0" />
                        <span class="truncate">{{ entry.room_name }}</span>
                    </div>
                    <div v-if="entry.short_description" class="flex items-start gap-1 text-[10px] text-text-subtle">
                        <IconNote class="h-3 w-3 shrink-0 mt-px" />
                        <span class="line-clamp-2">{{ entry.short_description }}</span>
                    </div>
                    <div v-if="entry.workflow_rejection_reason" class="text-[10px] text-danger font-medium leading-tight mt-0.5">
                        {{ entry.workflow_rejection_reason }}
                    </div>
                </template>

                <!-- Individual Time Entry -->
                <template v-else>
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-text">
                            {{ entry.full_day ? $t('Full day') : `${entry.start_time} – ${entry.end_time}` }}
                        </span>
                        <IconClock class="h-3 w-3 text-accent-500" />
                    </div>
                    <span class="text-[10px] text-accent-600 truncate">{{ entry.title || $t('Individual time') }}</span>
                </template>
            </div>
        </template>
        <template v-else>
            <div class="flex h-full items-center justify-center text-[11px] text-text-subtle">
                {{ $t('No shift') }}
            </div>
        </template>
    </div>
</template>
<script setup>
import { IconAlertTriangle, IconClock, IconLock, IconMapPin, IconNote, IconTrash } from '@tabler/icons-vue';
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
    let base = entry.has_changes_after_commit ? 'border border-danger-border bg-danger-surface shadow-none hover:border-danger-border' : 'bg-white shadow-sm hover:ring-1 hover:ring-accent-200';

    if (entry.is_rejected || entry.workflow_rejection_reason) {
        base = 'border border-danger bg-danger-surface shadow-none hover:border-danger';
    }

    if (props.rejectActive && props.shiftSelections[entry.unique_key]) {
        return base + ' ring-2 ring-danger-border';
    }
    return base;
};
</script>
