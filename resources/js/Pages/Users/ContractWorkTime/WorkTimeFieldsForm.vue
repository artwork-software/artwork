<template>
    <div class="space-y-3">
        <div v-if="showPatternSelect" class="rounded-md border border-border-subtle bg-surface-sunken/60 p-3">
            <BaseCombobox
                v-model="patternId"
                :items="patternItems"
                option-label="name"
                option-key="id"
                :label="$t('Work Time Pattern')"
                :placeholder="$t('Custom weekly hours (no pattern)')"
                :empty-text="$t('No work time patterns found.')"
                coerce="number"
                @update:model-value="applyPattern"
            />
            <p class="text-[11px] text-text-subtle mt-2">
                {{ $t('Selecting a pattern copies its daily hours. Without a pattern you can enter the hours per weekday yourself.') }}
            </p>
        </div>

        <div class="rounded-md border border-accent-200 bg-accent-50/60 px-3 py-2 flex items-start gap-2">
            <component :is="IconInfoCircle" class="size-4 shrink-0 text-accent-600 mt-0.5" stroke-width="1.5" />
            <p class="text-xs text-accent-700">
                {{ $t('Enter the number of working hours per day (e.g. 08:00 = 8 hours) – not a start time.') }}
                {{ $t('These hours are the daily target and are used, for example, for the hours account and overtime calculation.') }}
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
            <div v-for="day in weekDays" :key="day.key">
                <BaseInput
                    v-model="form[day.key]"
                    :label="day.label"
                    type="time"
                    :disabled="hasPattern"
                    :id="`${idPrefix}_${day.key}`" />
                <p v-if="errors[day.key]" class="text-danger mt-0.5 text-xs">{{ errors[day.key] }}</p>
            </div>
        </div>

        <div class="flex items-center justify-between gap-2 border-t border-border-subtle pt-2">
            <p v-if="hasPattern" class="text-[11px] text-text-subtle">
                {{ $t('Working times are defined by the pattern and cannot be edited. Remove the pattern to enter custom times.') }}
            </p>
            <span v-else></span>
            <p class="text-xs text-text-subtle">
                {{ $t('Total hours') }}:
                <span class="font-semibold text-text">{{ weeklyTotal }}</span> / {{ $t('week') }}
            </p>
        </div>
    </div>
</template>

<script setup>
import {computed, ref, watch} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCombobox from "@/Artwork/Inputs/BaseCombobox.vue";
import {IconInfoCircle} from "@tabler/icons-vue";
import {
    formatMinutesAsHours,
    weekDays,
    weeklyMinutes,
    workTimeValuesFrom
} from "@/Pages/Users/ContractWorkTime/contractWorkTimeFields.js";

const props = defineProps({
    /** Reaktives Formularobjekt mit work_time_pattern_id + monday..sunday – wird direkt beschrieben */
    form: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    workTimePatterns: { type: Array, default: () => [] },
    showPatternSelect: { type: Boolean, default: true },
    idPrefix: { type: String, default: 'worktime' },
});

const patternId = ref(props.form.work_time_pattern_id ?? null);

watch(() => props.form.work_time_pattern_id, (value) => {
    patternId.value = value ?? null;
});

const hasPattern = computed(() => props.form.work_time_pattern_id !== null && props.form.work_time_pattern_id !== undefined);

const patternItems = computed(() => props.workTimePatterns.map(pattern => ({
    id: pattern.id,
    name: `${pattern.name} (${pattern.full_work_time_in_hours ?? 0} h)`,
})));

const applyPattern = (value) => {
    const id = value === '' || value === undefined ? null : value;
    const pattern = props.workTimePatterns.find(item => item.id === id);
    if (pattern) {
        Object.entries(workTimeValuesFrom(pattern, pattern.id)).forEach(([key, fieldValue]) => {
            props.form[key] = fieldValue;
        });
        return;
    }
    props.form.work_time_pattern_id = null;
};

const weeklyTotal = computed(() => formatMinutesAsHours(weeklyMinutes(props.form)));
</script>
