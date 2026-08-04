<template>
    <div v-if="loading" class="flex items-center justify-center py-4">
        <div class="h-4 w-4 animate-spin rounded-full border-2 border-border border-t-zinc-600"></div>
    </div>
    <div v-else-if="schedule" class="rounded-xl border border-border-subtle px-4 py-3">
        <h4 class="text-xs font-semibold tracking-wide text-text-subtle uppercase mb-2">
            {{ $t('Schedule for this week') }} ({{ $t('Calendar week') }} {{ schedule.calendar_week }})
        </h4>
        <div class="grid grid-cols-7 gap-1">
            <div
                v-for="day in schedule.days"
                :key="day.date"
                class="rounded-lg border px-2 py-2 text-center text-[11px] transition-all"
                :class="day.is_selected
                    ? 'ring-2 ring-accent-700 bg-accent-50 border-accent-200'
                    : 'border-border-subtle bg-white'"
            >
                <div class="font-semibold text-text-muted">{{ day.day_name }}</div>
                <div class="text-text-subtle text-[10px]">{{ day.day_short }}</div>

                <div v-if="day.vacation_type" class="mt-1.5 text-[10px] text-orange-500 font-medium">
                    {{ day.vacation_type }}
                </div>

                <template v-else-if="day.shifts.length || day.individual_times.length">
                    <div v-for="(shift, si) in day.shifts" :key="'s'+si" class="mt-1 text-[10px] text-text-muted leading-tight">
                        {{ shift.start }}-{{ shift.end }}
                    </div>
                    <div v-for="(it, ii) in day.individual_times" :key="'i'+ii" class="mt-1 text-[10px] text-teal-600 leading-tight">
                        {{ it.start_time }}-{{ it.end_time }}
                        <span v-if="it.title" class="block text-text-subtle truncate">{{ it.title }}</span>
                    </div>
                </template>

                <div v-else class="mt-1.5 text-[10px] text-text-subtle italic">
                    {{ $t('Free') }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    userId: { type: Number, required: true },
    selectedDate: { type: String, required: true },
});

const loading = ref(false);
const schedule = ref(null);

async function loadSchedule() {
    if (!props.selectedDate) {
        schedule.value = null;
        return;
    }
    loading.value = true;
    try {
        const response = await axios.get(
            route('compensation-day-offs.week-schedule', { user: props.userId }),
            { params: { date: props.selectedDate } }
        );
        schedule.value = response.data;
    } catch (e) {
        schedule.value = null;
    } finally {
        loading.value = false;
    }
}

watch(() => props.selectedDate, loadSchedule, { immediate: true });
</script>
