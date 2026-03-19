<template>
    <div v-if="loading" class="flex items-center justify-center py-4">
        <div class="h-4 w-4 animate-spin rounded-full border-2 border-zinc-300 border-t-zinc-600"></div>
    </div>
    <div v-else-if="schedule" class="rounded-xl border border-zinc-200 px-4 py-3">
        <h4 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase mb-2">
            {{ $t('Schedule for this week') }} ({{ $t('Calendar week') }} {{ schedule.calendar_week }})
        </h4>
        <div class="grid grid-cols-7 gap-1">
            <div
                v-for="day in schedule.days"
                :key="day.date"
                class="rounded-lg border px-2 py-2 text-center text-[11px] transition-all"
                :class="day.is_selected
                    ? 'ring-2 ring-artwork-buttons-hover bg-blue-50 border-blue-300'
                    : 'border-zinc-200 bg-white'"
            >
                <div class="font-semibold text-zinc-700">{{ day.day_name }}</div>
                <div class="text-zinc-400 text-[10px]">{{ day.day_short }}</div>

                <div v-if="day.vacation_type" class="mt-1.5 text-[10px] text-orange-500 font-medium">
                    {{ day.vacation_type }}
                </div>

                <template v-else-if="day.shifts.length || day.individual_times.length">
                    <div v-for="(shift, si) in day.shifts" :key="'s'+si" class="mt-1 text-[10px] text-zinc-600 leading-tight">
                        {{ shift.start }}-{{ shift.end }}
                    </div>
                    <div v-for="(it, ii) in day.individual_times" :key="'i'+ii" class="mt-1 text-[10px] text-teal-600 leading-tight">
                        {{ it.start_time }}-{{ it.end_time }}
                        <span v-if="it.title" class="block text-zinc-400 truncate">{{ it.title }}</span>
                    </div>
                </template>

                <div v-else class="mt-1.5 text-[10px] text-zinc-300 italic">
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
