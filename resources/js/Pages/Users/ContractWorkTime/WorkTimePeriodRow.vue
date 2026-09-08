<template>
    <div class="px-4 py-3 border-t border-border-subtle">
        <div class="flex items-start gap-3">
            <component :is="IconClockHour10" class="size-5 shrink-0 text-text-subtle mt-0.5" stroke-width="1.5" />

            <!-- Kein Arbeitszeit-Satz in diesem Zeitraum -->
            <div v-if="!workTime" class="min-w-0 flex-1 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <p class="text-xs text-text-subtle">{{ $t('Work Time Pattern') }}</p>
                    <p class="text-sm font-medium text-text">{{ $t('No working hours in this period') }}</p>
                    <p class="text-[11px] text-text-subtle mt-0.5">
                        {{ $t('Without working hours there is no daily target for the hours account and overtime calculation.') }}
                    </p>
                </div>
                <BaseUIButton
                    :label="$t('Set working hours from here')"
                    :icon="IconClockSearch"
                    is-small
                    @click.stop="$emit('planFrom')"
                />
            </div>

            <div v-else class="min-w-0 flex-1">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-xs text-text-subtle">{{ $t('Work Time Pattern') }}</p>
                        <p class="text-sm font-semibold text-text font-lexend">
                            {{ workTime.pattern_name ?? $t('Custom weekly hours (no pattern)') }}
                        </p>
                        <p class="text-[11px] text-text-subtle mt-0.5">
                            {{ $t('Valid') }}: {{ formatPeriod(workTime.valid_from, workTime.valid_until, $t) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <BaseUIButton
                            :label="expanded ? $t('Close') : $t('Edit')"
                            :icon="expanded ? IconChevronUp : IconPencil"
                            is-small
                            @click.stop="toggle"
                        />
                        <BaseUIButton
                            :label="$t('Delete period')"
                            :icon="IconTrash"
                            is-small
                            variant="ghost"
                            @click.stop="showConfirmDelete = true"
                        />
                    </div>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <span v-for="day in weekDays" :key="day.key"
                          class="inline-flex flex-col items-center rounded-md border border-border-subtle bg-surface-sunken/60 px-2 py-1 min-w-[3.25rem]">
                        <span class="text-[10px] text-text-subtle">{{ $t(day.short) }}</span>
                        <span class="text-xs font-semibold text-text tabular-nums">{{ normalizeTime(workTime[day.key]) }}</span>
                    </span>
                    <span class="inline-flex flex-col items-center rounded-md border border-accent-200 bg-accent-50 px-2 py-1 min-w-[4.5rem]">
                        <span class="text-[10px] text-accent-600">&Sigma; / {{ $t('week') }}</span>
                        <span class="text-xs font-semibold text-accent-700 tabular-nums">{{ weeklyTotal }}</span>
                    </span>
                </div>

                <form v-if="expanded" @submit.prevent="submit" class="mt-4 space-y-4 border-t border-border-subtle pt-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-xl">
                        <div>
                            <BaseInput
                                v-model="form.valid_from"
                                :label="$t('Valid from')"
                                without-translation
                                type="date"
                                :id="`worktime_${workTime.id}_valid_from`" />
                            <p v-if="form.errors.valid_from" class="text-danger mt-0.5 text-xs">{{ form.errors.valid_from }}</p>
                        </div>
                        <div>
                            <BaseInput
                                v-model="form.valid_until"
                                :label="$t('Valid until')"
                                without-translation
                                type="date"
                                :id="`worktime_${workTime.id}_valid_until`" />
                            <p class="text-[11px] text-text-subtle mt-0.5">{{ $t('Empty = open-ended') }}</p>
                            <p v-if="form.errors.valid_until" class="text-danger mt-0.5 text-xs">{{ form.errors.valid_until }}</p>
                        </div>
                    </div>

                    <WorkTimeFieldsForm
                        :form="form"
                        :errors="form.errors"
                        :work-time-patterns="workTimePatterns"
                        :id-prefix="`worktime_${workTime.id}`"
                    />

                    <div class="flex justify-end gap-2">
                        <BaseUIButton
                            type="button"
                            :label="$t('Cancel')"
                            is-cancel-button
                            @click.stop="toggle"
                        />
                        <BaseUIButton
                            type="submit"
                            :label="!form.processing ? $t('Save') : $t('Saving...')"
                            is-add-button
                            :disabled="form.processing"/>
                    </div>
                </form>
            </div>
        </div>

        <ConfirmDeleteModal
            v-if="showConfirmDelete"
            :title="$t('Delete working hours period')"
            :description="$t('Do you really want to delete this working hours period?')"
            @delete="destroy"
            @closed="showConfirmDelete = false"
        />
    </div>
</template>

<script setup>
import {computed, ref} from "vue";
import {router, useForm} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import WorkTimeFieldsForm from "@/Pages/Users/ContractWorkTime/WorkTimeFieldsForm.vue";
import {IconChevronUp, IconClockHour10, IconClockSearch, IconPencil, IconTrash} from "@tabler/icons-vue";
import {useTranslation} from "@/Composeables/Translation.js";
import {
    formatMinutesAsHours,
    formatPeriod,
    normalizeTime,
    weekDays,
    weeklyMinutes,
    workTimeValuesFrom
} from "@/Pages/Users/ContractWorkTime/contractWorkTimeFields.js";

const $t = useTranslation();

const props = defineProps({
    workTime: { type: [Object, null], default: null },
    userId: { type: Number, required: true },
    workTimePatterns: { type: Array, default: () => [] },
});

const emit = defineEmits(['planFrom', 'saved']);

const expanded = ref(false);
const showConfirmDelete = ref(false);

const form = useForm({
    id: props.workTime?.id ?? null,
    valid_from: props.workTime?.valid_from ?? '',
    valid_until: props.workTime?.valid_until ?? '',
    ...workTimeValuesFrom(props.workTime, props.workTime?.work_time_pattern_id ?? null),
});

const toggle = () => {
    if (!expanded.value && props.workTime) {
        form.clearErrors();
        Object.assign(form, {
            id: props.workTime.id,
            valid_from: props.workTime.valid_from ?? '',
            valid_until: props.workTime.valid_until ?? '',
            ...workTimeValuesFrom(props.workTime, props.workTime.work_time_pattern_id ?? null),
        });
    }
    expanded.value = !expanded.value;
};

const weeklyTotal = computed(() => formatMinutesAsHours(weeklyMinutes(props.workTime)));

const submit = () => {
    form.transform(data => ({
        ...data,
        valid_from: data.valid_from || null,
        valid_until: data.valid_until || null,
    })).patch(route('shift.work-time-pattern.update-user', props.userId), {
        preserveScroll: true,
        onSuccess: () => {
            expanded.value = false;
            emit('saved');
        },
    });
};

const destroy = () => {
    router.delete(route('shift.work-time-pattern.work-time.destroy', { user: props.userId, workTime: props.workTime.id }), {
        preserveScroll: true,
        onFinish: () => {
            showConfirmDelete.value = false;
        },
    });
};
</script>
