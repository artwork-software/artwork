<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="userToEdit">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
            <div>
                <TinyPageHeadline
                    :title="$t('Work Time Pattern')"
                    :description="$t('Select a work time pattern for the user or enter custom working times.')"
                />
            </div>
            <div class="flex items-center justify-end gap-2">
                <BaseUIButton
                    v-if="isPatternSelected"
                    :label="$t('Remove Work Time Pattern')"
                    :icon="IconTrash"
                    @click.stop="showConfirmRemovePatternModal = true"
                />
                <BaseUIButton
                    :label="isPatternSelected ? $t('Switch work time pattern') : $t('Select Work Time Pattern')"
                    is-add-button
                    :icon="IconClockSearch"
                    @click.stop="showSelectWorkTimePatternModal = true"/>
            </div>
        </div>

        <VisualFeedback :show-save-success="showVisualFeedback" />

        <NextWorkTimeCountdown :next-work-time="nextWorkTime" v-if="nextWorkTime" />

        <!-- Pattern selected: read-only overview -->
        <div v-if="isPatternSelected" class="mt-5 space-y-4">
            <div class="rounded-lg border border-accent-200 bg-accent-50/60 px-4 py-3">
                <div class="flex items-start gap-3">
                    <component :is="IconClockCheck" class="size-5 shrink-0 text-accent-600 mt-0.5" stroke-width="1.5" />
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-accent-700 font-lexend">
                            {{ $t('Active work time pattern: {0}', [selectedWorkTimePattern?.name ?? '-']) }}
                        </p>
                        <p v-if="selectedWorkTimePattern?.description" class="text-xs text-accent-700/80 mt-0.5">
                            {{ selectedWorkTimePattern.description }}
                        </p>
                        <p class="text-xs text-accent-700/80 mt-1">
                            {{ $t('Working times are defined by the pattern and cannot be edited. Remove the pattern to enter custom times.') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <h3 class="text-sm font-semibold text-text font-lexend flex items-center gap-2">
                        <component :is="IconCalendarWeek" class="size-4 text-text-subtle" stroke-width="1.5" />
                        {{ $t('Weekly working hours') }}
                    </h3>
                    <p v-if="currentWorkTime?.valid_from" class="text-xs text-text-subtle">
                        {{ $t('Valid from') }}: {{ formatDate(currentWorkTime.valid_from) }}
                        <template v-if="currentWorkTime?.valid_until"> &ndash; {{ formatDate(currentWorkTime.valid_until) }}</template>
                    </p>
                </div>
                <p class="text-xs text-text-subtle mt-1">
                    {{ $t('The values show the working hours per day. They are used as the daily target, e.g. for the hours account and overtime calculation.') }}
                </p>

                <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
                    <div v-for="day in weekDays" :key="day.key"
                         class="rounded-md border border-border-subtle bg-surface-sunken/60 px-3 py-2.5 text-center">
                        <p class="text-xs text-text-subtle">{{ $t(day.label) }}</p>
                        <p class="mt-0.5 text-sm font-semibold text-text">
                            {{ formatHours(currentWorkTime?.[`${day.key}_hours`]) }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2 border-t border-border-subtle pt-3">
                    <span class="text-xs text-text-subtle">{{ $t('Total hours') }}:</span>
                    <span class="text-sm font-semibold text-text">
                        {{ formatHours(currentWorkTime?.full_work_time_in_hours) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- No pattern: custom working times form -->
        <div class="mt-5" v-else>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <h3 class="text-sm font-semibold text-text font-lexend flex items-center gap-2">
                            <component :is="IconCalendarWeek" class="size-4 text-text-subtle" stroke-width="1.5" />
                            {{ $t('Weekly working hours') }}
                        </h3>
                        <p class="text-xs text-text-subtle">
                            {{ $t('Total hours') }}:
                            <span class="font-semibold text-text">{{ weeklyTotalFormatted }}</span>
                        </p>
                    </div>

                    <div class="mt-3 rounded-md border border-accent-200 bg-accent-50/60 px-3 py-2 flex items-start gap-2">
                        <component :is="IconInfoCircle" class="size-4 shrink-0 text-accent-600 mt-0.5" stroke-width="1.5" />
                        <p class="text-xs text-accent-700">
                            {{ $t('Enter the number of working hours per day (e.g. 08:00 = 8 hours) – not a start time.') }}
                            {{ $t('These hours are the daily target and are used, for example, for the hours account and overtime calculation.') }}
                        </p>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div v-for="day in weekDays" :key="day.key">
                            <BaseInput
                                v-model="workTimeForm[day.key]"
                                :label="day.label"
                                type="time"
                                :id="day.key" />
                            <p v-if="workTimeForm.errors[day.key]" class="text-danger mt-0.5 text-xs">{{ workTimeForm.errors[day.key] }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
                    <h3 class="text-sm font-semibold text-text font-lexend flex items-center gap-2">
                        <component :is="IconCalendarDue" class="size-4 text-text-subtle" stroke-width="1.5" />
                        {{ $t('Validity period') }}
                    </h3>
                    <p class="text-xs text-text-subtle mt-1">
                        {{ $t('If no dates are set, the hours apply from today indefinitely as the daily target for all future days.') }}
                    </p>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-xl">
                        <div>
                            <BaseInput
                                v-model="workTimeForm.valid_from"
                                :label="$t('Valid from')"
                                without-translation
                                type="date"
                                id="valid_from" />
                            <p v-if="workTimeForm.errors.valid_from" class="text-danger mt-0.5 text-xs">{{ workTimeForm.errors.valid_from }}</p>
                        </div>
                        <div>
                            <BaseInput
                                v-model="workTimeForm.valid_until"
                                :label="$t('Valid until')"
                                without-translation
                                type="date"
                                id="valid_until" />
                            <p v-if="workTimeForm.errors.valid_until" class="text-danger mt-0.5 text-xs">{{ workTimeForm.errors.valid_until }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <BaseUIButton
                        type="submit"
                        :label="!workTimeForm.processing ? $t('Save') : $t('Saving...')"
                        is-add-button
                        :disabled="workTimeForm.processing" />
                </div>
            </form>
        </div>

        <SelectWorkTimePatternModal
            :work-time-patterns="workTimePatterns"
            :selected-pattern-id="workTimeForm.work_time_pattern_id"
            v-if="showSelectWorkTimePatternModal"
            @close="showSelectWorkTimePatternModal = false"
            @select-pattern="selectPattern"
        />


        <ConfirmDeleteModal
            :title="$t('Remove Work Time Pattern')"
            :description="$t('Are you sure you want to remove the current work time pattern? This will allow you to enter custom working times.')"
            v-if="showConfirmRemovePatternModal"
            @closed="showConfirmRemovePatternModal = false"
            @delete="removePattern"
            />
    </UserEditHeader>
</template>

<script setup>

import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import {useForm} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import SelectWorkTimePatternModal from "@/Pages/Users/Components/SelectWorkTimePatternModal.vue";
import {computed, ref} from "vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import NextWorkTimeCountdown from "@/Pages/Users/Components/NextWorkTimeCountdown.vue";
import VisualFeedback from "@/Components/Feedback/VisualFeedback.vue";
import {IconClockSearch, IconTrash, IconClockCheck, IconCalendarWeek, IconCalendarDue, IconInfoCircle} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import dayjs from "dayjs";

const props = defineProps({
    userToEdit: {
        type: Object,
        required: true
    },
    workTimes: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            work_time_pattern_id: null,
            monday: null,
            tuesday: null,
            wednesday: null,
            thursday: null,
            friday: null,
            saturday: null,
            sunday: null,
            valid_from: '',
            valid_until: ''
        })
    },
    currentWorkTime: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            work_time_pattern_id: null,
            monday: '00:00',
            tuesday: '00:00',
            wednesday: '00:00',
            thursday: '00:00',
            friday: '00:00',
            saturday: '00:00',
            sunday: '00:00',
            valid_from: '',
            valid_until: ''
        })
    },
    nextWorkTime: {
        type: [Object, null],
        required: false,
        default: () => null
    },
    currentTab: {
        type: String,
        required: true
    },
    workTimePatterns: {
        type: Object,
        required: true
    },
})

const weekDays = [
    { key: 'monday', label: 'Monday' },
    { key: 'tuesday', label: 'Tuesday' },
    { key: 'wednesday', label: 'Wednesday' },
    { key: 'thursday', label: 'Thursday' },
    { key: 'friday', label: 'Friday' },
    { key: 'saturday', label: 'Saturday' },
    { key: 'sunday', label: 'Sunday' },
];

const workTimeForm = useForm({
    id: props.currentWorkTime?.id || null,
    work_time_pattern_id: props.currentWorkTime?.work_time_pattern_id || null,
    monday: props.currentWorkTime?.monday || '00:00',
    tuesday: props.currentWorkTime?.tuesday || '00:00',
    wednesday: props.currentWorkTime?.wednesday || '00:00',
    thursday: props.currentWorkTime?.thursday || '00:00',
    friday: props.currentWorkTime?.friday || '00:00',
    saturday: props.currentWorkTime?.saturday || '00:00',
    sunday: props.currentWorkTime?.sunday || '00:00',
    valid_from: props.currentWorkTime?.valid_from || '',
    valid_until: props.currentWorkTime?.valid_until || ''
})

const showSelectWorkTimePatternModal = ref(false)
const showConfirmRemovePatternModal = ref(false)
const showVisualFeedback = ref(false)

const selectPattern = (data) => {

    workTimeForm.work_time_pattern_id = data.workTimePattern.id;
    workTimeForm.valid_from = data.valid_from;
    workTimeForm.valid_until = data.valid_until;
    workTimeForm.monday = data.workTimePattern.monday;
    workTimeForm.tuesday = data.workTimePattern.tuesday;
    workTimeForm.wednesday = data.workTimePattern.wednesday;
    workTimeForm.thursday = data.workTimePattern.thursday;
    workTimeForm.friday = data.workTimePattern.friday;
    workTimeForm.saturday = data.workTimePattern.saturday;
    workTimeForm.sunday = data.workTimePattern.sunday;

    showSelectWorkTimePatternModal.value = false;

    submit();
}

const removePattern = () => {
    workTimeForm.work_time_pattern_id = null;
    showConfirmRemovePatternModal.value = false;

    submit();
}

const selectedWorkTimePattern = computed(() => {
    return props.workTimePatterns.find(pattern => pattern.id === workTimeForm.work_time_pattern_id);
});

const isPatternSelected = computed(() => {
    return workTimeForm.work_time_pattern_id !== null;
});

const parseTimeToMinutes = (value) => {
    if (!value || typeof value !== 'string' || !value.includes(':')) {
        return 0;
    }
    const [hours, minutes] = value.split(':');
    return (parseInt(hours, 10) || 0) * 60 + (parseInt(minutes, 10) || 0);
};

const weeklyTotalFormatted = computed(() => {
    const totalMinutes = weekDays.reduce((sum, day) => sum + parseTimeToMinutes(workTimeForm[day.key]), 0);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return `${hours}:${String(minutes).padStart(2, '0')} Std.`;
});

const formatHours = (value) => {
    if (value === null || value === undefined || value === '') {
        return '0 Std.';
    }
    return `${value} Std.`;
};

const formatDate = (value) => {
    if (!value) {
        return '';
    }
    return dayjs(value).format('DD.MM.YYYY');
};

const submit = () => {
    workTimeForm.patch(route('shift.work-time-pattern.update-user', props.userToEdit.id), {
        preserveScroll: true,
        onSuccess: () => {
            showVisualFeedback.value = true;
            setTimeout(() => {
                showVisualFeedback.value = false;
            }, 3000);
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
}
</script>

<style scoped>

</style>
