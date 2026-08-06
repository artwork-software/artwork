<template>
    <!--
        Dedicated print-layout renderer for the shift component (ShiftTab).
        The interactive ShiftTab.vue is not printable, so the print layout uses
        this flat list: one line per shift with date/time, craft, room, staffing
        and description.
    -->
    <div class="text-xs text-text">
        <div v-if="shifts.length" class="divide-y divide-border-subtle">
            <div
                v-for="shift in shifts"
                :key="shift.id"
                class="py-0.5 flex flex-wrap items-baseline gap-x-2"
            >
                <span class="font-bold whitespace-nowrap">{{ shift.time_span_label }}</span>
                <span v-if="craftLabel(shift)" class="whitespace-nowrap">· {{ craftLabel(shift) }}</span>
                <span v-if="shift.room?.name" class="whitespace-nowrap">· {{ shift.room.name }}</span>
                <span class="whitespace-nowrap">· {{ assignedCount(shift) }}/{{ shift.max_users ?? 0 }} {{ $t('Employees') }}</span>
                <span v-if="shift.description" class="text-text-subtle truncate print:whitespace-normal">· {{ shift.description }}</span>
            </div>
        </div>
        <div v-else class="text-text-subtle">
            {{ $t('No entries') }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: false,
    },
});

const shifts = computed(() => props.project?.shifts ?? []);

const craftLabel = (shift) => shift.craft?.abbreviation || shift.craft?.name || '';

const assignedCount = (shift) =>
    (shift.users?.length ?? 0) +
    (shift.freelancer?.length ?? 0) +
    (shift.serviceProvider?.length ?? 0);
</script>
