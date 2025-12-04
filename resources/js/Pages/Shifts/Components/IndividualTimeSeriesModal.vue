<!-- resources/js/Components/IndividualTimes/IndividualTimeSeriesModal.vue -->
<template>

    <ArtworkBaseModal
        :modal-size="'sm:max-w-5xl'"
        :title="$t('Recurring individual times')"
        :description="$t('Define recurring individual times for selected people.')"
        :is-in-shift-plan="isInShiftPlan"
        @close="handleClose"
    >

        <div class="space-y-6">
            <!-- Top Info -->
            <div class="flex flex-col gap-3 rounded-xl border border-zinc-100 bg-zinc-50/80 px-4 py-3 text-xs text-zinc-600">
                <div class="flex items-center gap-2 font-medium text-zinc-800">
                    <PropertyIcon name="IconRepeat" class="h-4 w-4" />
                    <span>{{ $t('Series configuration') }}</span>
                </div>
                <p class="leading-snug">
                    {{ $t('Define date range, recurrence and weekdays. Then select all people for whom the times should be created automatically.') }}
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- LEFT: Time & Recurrence -->
                <div class="space-y-5">
                    <!-- Series title -->
                    <div class="space-y-1.5">
                        <label class="flex items-center gap-2 text-xs font-medium text-zinc-700">
                            <PropertyIcon name="IconCalendar" class="h-4 w-4" />
                            <span>{{ $t('Series title (optional)') }}</span>
                        </label>
                        <BaseInput
                            id="series_title"
                            v-model="form.title"
                            :label="'Series title (optional)'"
                            :placeholder="'e.g. Office time, Homeoffice, Training'"
                            :without-translation="false"
                            :is-small="true"
                        />
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="flex items-center gap-2 text-xs font-medium text-zinc-700">
                                <PropertyIcon name="IconCalendar" class="h-4 w-4" />
                                <span>{{ $t('Start date') }}</span>
                            </label>
                            <BaseInput
                                id="series_start_date"
                                v-model="form.start_date"
                                type="date"
                                :label="'Start date'"
                                :is-small="true"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="flex items-center gap-2 text-xs font-medium text-zinc-700">
                                <PropertyIcon name="IconCalendar" class="h-4 w-4" />
                                <span>{{ $t('End date') }}</span>
                            </label>
                            <BaseInput
                                id="series_end_date"
                                v-model="form.end_date"
                                type="date"
                                :label="'End date'"
                                :is-small="true"
                            />
                        </div>
                    </div>

                    <!-- Times -->
                    <div class="space-y-2 rounded-xl border border-zinc-100 bg-white/80 p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-xs font-medium text-zinc-700">
                                <PropertyIcon name="IconClock" class="h-4 w-4" />
                                <span>{{ $t('Time') }}</span>
                            </div>
                            <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-zinc-600">
                                <input
                                    v-model="form.full_day"
                                    type="checkbox"
                                    class="h-3.5 w-3.5 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span>{{ $t('Full day') }}</span>
                            </label>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">
                                    {{ $t('Start') }}
                                </label>
                                <BaseInput
                                    id="series_start_time"
                                    v-model="form.start_time"
                                    type="time"
                                    :label="'Start'"
                                    :is-small="true"
                                    :disabled="form.full_day"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">
                                    {{ $t('End') }}
                                </label>
                                <BaseInput
                                    id="series_end_time"
                                    v-model="form.end_time"
                                    type="time"
                                    :label="'End'"
                                    :is-small="true"
                                    :disabled="form.full_day"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Recurrence -->
                    <div class="space-y-3 rounded-xl border border-zinc-100 bg-white/80 p-4 shadow-sm">
                        <div class="flex items-center gap-2 text-xs font-medium text-zinc-700">
                            <PropertyIcon name="IconRepeat" class="h-4 w-4" />
                            <span>{{ $t('Recurrence') }}</span>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">
                                    {{ $t('Frequency') }}
                                </label>
                                <select
                                    v-model="form.frequency"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 shadow-sm text-sm outline-none
                                           focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                >
                                    <option value="weekly">{{ $t('Weekly') }}</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">
                                    {{ $t('Interval') }}
                                </label>
                                <div class="flex items-center gap-2">
                                    <BaseInput
                                        id="series_interval"
                                        v-model="form.interval"
                                        type="number"
                                        :label="'Interval'"
                                        :is-small="true"
                                        :step="1"
                                    />
                                    <span class="text-xs text-zinc-500">
                                        {{ $t('e.g. 1 = every week, 2 = every 2 weeks') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Weekdays -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">
                                    {{ $t('Weekdays') }}
                                </span>
                                <button
                                    type="button"
                                    class="text-[11px] font-medium text-indigo-600 hover:text-indigo-700"
                                    @click="selectAllWeekdays"
                                >
                                    {{ $t('Select all') }}
                                </button>
                            </div>
                            <div class="grid grid-cols-7 gap-1.5">
                                <button
                                    v-for="day in weekdayOptions"
                                    :key="day.value"
                                    type="button"
                                    class="flex h-8 items-center justify-center rounded-lg border text-xs font-medium transition
                                           hover:border-indigo-300 hover:text-indigo-600"
                                    :class="form.weekdays.includes(day.value)
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700 shadow-[0_0_0_1px_rgba(79,70,229,0.25)]'
                                        : 'border-zinc-200 bg-white text-zinc-600'"
                                    @click="toggleWeekday(day.value)"
                                >
                                    {{ $t(day.label) }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Subjects / Search -->
                <div class="space-y-5">
                    <!-- Search -->
                    <div class="space-y-2 rounded-xl border border-zinc-100 bg-white/80 p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-xs font-medium text-zinc-700">
                                <PropertyIcon name="IconUserSearch" class="h-4 w-4" />
                                <span>{{ $t('Search people') }}</span>
                            </div>
                            <span class="text-[11px] text-zinc-500">
                                {{ $t('Users, freelancers or service providers') }}
                            </span>
                        </div>

                        <div class="mt-2">
                            <BaseInput
                                id="series_subject_search"
                                v-model="search"
                                :label="'Search people'"
                                :placeholder="'Type a name to search...'"
                                :is-small="true"
                                :show-loading="isSearching"
                            />
                        </div>

                        <!-- Search results -->
                        <div class="mt-3 max-h-56 space-y-1 overflow-y-auto rounded-lg bg-zinc-50/60 p-2">
                            <div
                                v-if="isSearching"
                                class="flex items-center gap-2 rounded-md bg-white/80 px-3 py-2 text-xs text-zinc-500"
                            >
                                <span class="h-2 w-2 animate-pulse rounded-full bg-indigo-500"></span>
                                <span>{{ $t('Searching...') }}</span>
                            </div>
                            <div
                                v-else-if="searchResults.length === 0 && search.length > 0"
                                class="rounded-md bg-white/80 px-3 py-2 text-xs text-zinc-500"
                            >
                                {{ $t('No results found for your search.') }}
                            </div>
                            <template v-else>
                                <button
                                    v-for="result in searchResults"
                                    :key="`${result.type}-${result.id}`"
                                    type="button"
                                    class="group flex w-full items-center justify-between gap-3 rounded-md bg-white/80 px-3 py-2 text-left text-xs
                                           text-zinc-700 shadow-sm transition hover:-translate-y-[1px] hover:bg-indigo-50/80 hover:shadow-md"
                                    @click="addSubject(result)"
                                >
                                    <div class="flex items-center gap-2">
                                        <img
                                            :src="result.profile_photo_url"
                                            class="h-6 w-6 rounded-full object-cover ring-1 ring-zinc-200"
                                            alt=""
                                        />
                                        <div>
                                            <div class="font-medium text-zinc-800">
                                                {{ result.display_name }}
                                            </div>
                                            <div class="text-[11px] text-zinc-500">
                                                {{ subjectTypeLabel(result.type) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            v-if="isSelected(result)"
                                            class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700"
                                        >
                                            <PropertyIcon name="IconCheck" class="h-3 w-3" />
                                            {{ $t('Added') }}
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-medium text-indigo-700 group-hover:bg-indigo-100"
                                        >
                                            <PropertyIcon name="IconPlus" class="h-3 w-3" />
                                            {{ $t('Add') }}
                                        </span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Selection -->
                    <div class="space-y-2 rounded-xl border border-zinc-100 bg-white/80 p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-xs font-medium text-zinc-700">
                                <PropertyIcon name="IconUsers" class="h-4 w-4" />
                                <span>{{ $t('Selected people') }}</span>
                            </div>
                            <span class="text-[11px] text-zinc-500">
                                {{ $t('{count} selected', { count: subjects.length }) }}
                            </span>
                        </div>

                        <div
                            v-if="subjects.length === 0"
                            class="mt-2 rounded-lg border border-dashed border-zinc-200 bg-zinc-50/80 px-3 py-2 text-xs text-zinc-500"
                        >
                            {{ $t('No people selected yet. Use the search above to find users, freelancers or service providers.') }}
                        </div>

                        <div
                            v-else
                            class="mt-2 flex flex-wrap gap-1.5"
                        >
                            <div
                                v-for="subject in subjects"
                                :key="`${subject.type}-${subject.id}`"
                                class="inline-flex items-center rounded-full bg-zinc-100 text-[11px] text-zinc-700 shadow-sm"
                            >
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-zinc-200 text-[10px] font-semibold text-zinc-700">
                                    {{ subjectInitials(subject.display_name) }}
                                </span>
                                <div class="inline-flex items-center gap-1.5 py-1 px-2">
                                    <span class="font-medium">
                                        {{ subject.display_name }}
                                    </span>
                                    <span class="rounded-full bg-white/80 px-1.5 py-0.5 text-[9px] uppercase tracking-wide text-zinc-500">
                                        {{ subjectTypeLabel(subject.type) }}
                                    </span>
                                    <button
                                        type="button"
                                        class="ml-0.5 rounded-full p-0.5 text-zinc-400 hover:bg-zinc-200/80 hover:text-zinc-700"
                                        @click="removeSubject(subject)"
                                    >
                                        <PropertyIcon name="IconX" class="h-3 w-3" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="grid grid-cols-2 border-t border-zinc-100 pt-4 gap-3">
                <div class="flex items-center gap-2 text-[11px] text-zinc-500">
                    <PropertyIcon name="IconInfoCircle" class="h-3.5 w-3.5 min-h-3.5 min-w-3.5" />
                    <span>
                        {{ $t('All times will be stored as individual times per selected person and date. They can later be found via the series (UUID).') }}
                    </span>
                </div>
                <div class="flex justify-end gap-2">
                    <BaseUIButton
                        :label="$t('Cancel')"
                        is-cancel-button
                        type="button"
                        @click="handleClose"
                    />
                    <BaseUIButton
                        :disabled="isSubmitting || !canSubmit"
                        type="button"
                        :is-add-button="true"
                        @click="submit"
                    >
                        <span v-if="isSubmitting" class="mr-2 h-3 w-3 animate-spin rounded-full border border-white border-b-transparent"></span>
                        {{ seriesUuid ? $t('Update series') : $t('Create series') }}
                    </BaseUIButton>
                    <BaseUIButton
                        v-if="seriesUuid"
                        is-delete-button
                        type="button"
                        @click="showDeleteModal = true"
                        :label="$t('Delete series')"
                    />
                </div>
            </div>
        </div>
        <ArtworkBaseDeleteModal
            v-if="showDeleteModal"
            :title="$t('Delete series')"
            :description="$t('When deleted, all associated individual times are irrevocably removed.')"
            @close="showDeleteModal = false"
            @delete="deleteSeries"
        />
    </ArtworkBaseModal>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from 'vue-i18n';

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import ArtworkBaseDeleteModal from '@/Artwork/Modals/ArtworkBaseDeleteModal.vue';
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

type SubjectType = 'user' | 'freelancer' | 'service_provider';

interface Subject {
    id: number;
    type: SubjectType;
    display_name: string;
}

const props = defineProps<{
    isInShiftPlan?: boolean;
    seriesUuid?: string | null;
    initialSubject?: Subject | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'created'): void;
}>();

const { t } = useI18n();

const form = useForm({
    title: '',
    start_date: '',
    end_date: '',
    start_time: '',
    end_time: '',
    full_day: false,
    frequency: 'weekly',
    interval: 1 as number | string,
    weekdays: [1, 2, 3, 4, 5] as number[], // default Mon–Fri
    subjects: [] as Subject[],
});

const search = ref('');
const searchResults = ref<any[]>([]);
const isSearching = ref(false);
const searchTimeout = ref<number | null>(null);
const isSubmitting = ref(false);
const showDeleteModal = ref(false);

const weekdayOptions = [
    { value: 1, label: 'Mo' },
    { value: 2, label: 'Tu' },
    { value: 3, label: 'We' },
    { value: 4, label: 'Th' },
    { value: 5, label: 'Fr' },
    { value: 6, label: 'Sa' },
    { value: 7, label: 'Su' },
];

const subjects = computed<Subject[]>({
    get: () => form.subjects,
    set: (val) => {
        form.subjects = val;
    },
});

const canSubmit = computed(() => {
    return (
        !!form.start_date &&
        !!form.end_date &&
        form.weekdays.length > 0 &&
        subjects.value.length > 0 &&
        (!form.full_day ? !!form.start_time && !!form.end_time : true)
    );
});

function handleClose() {
    emit('close');
}

function toggleWeekday(value: number) {
    const index = form.weekdays.indexOf(value);
    if (index === -1) {
        form.weekdays.push(value);
    } else {
        form.weekdays.splice(index, 1);
    }
}

function selectAllWeekdays() {
    form.weekdays = weekdayOptions.map((d) => d.value);
}

function subjectTypeLabel(type: SubjectType) {
    switch (type) {
        case 'user':
            return t('Employee');
        case 'freelancer':
            return t('Freelancer');
        case 'service_provider':
            return t('Service provider');
        default:
            return type;
    }
}

function subjectInitials(name: string) {
    if (!name) return '';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
}

function isSelected(result: any) {
    return subjects.value.some((s) => s.id === result.id && s.type === result.type);
}

function addSubject(result: any) {
    if (isSelected(result)) return;
    subjects.value = [
        ...subjects.value,
        {
            id: result.id,
            type: result.type,
            display_name: result.display_name,
        },
    ];
}

function removeSubject(subject: { id: number; type: string }) {
    subjects.value = subjects.value.filter(
        (s) => !(s.id === subject.id && s.type === subject.type),
    );
}

async function fetchSearchResults(query: string) {
    if (!query || query.length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    try {
        const response = await axios.get(route('api.individual-times.subjects'), {
            params: {
                q: query,
                limit: 15,
            },
        });
        searchResults.value = response.data?.data ?? [];
    } catch {
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
}

watch(
    () => search.value,
    (value) => {
        if (searchTimeout.value !== null) {
            clearTimeout(searchTimeout.value);
        }
        if (!value || value.length < 2) {
            searchResults.value = [];
            return;
        }
        // simple debounce
        searchTimeout.value = window.setTimeout(() => {
            fetchSearchResults(value);
        }, 250) as unknown as number;
    },
);

/**
 * Optional: vorhandene Serie laden, wenn seriesUuid gesetzt ist.
 * Passe Route & Mapping an deine API an.
 */
async function loadSeries(uuid: string) {
    try {
        const response = await axios.get(route('individual-time-series.show', {
            series: uuid,
            subject_type: props.initialSubject?.type,
            subject_id: props.initialSubject?.id,
        }));

        const data = response.data?.data ?? response.data;

        if (!data) return;

        form.title = data.title ?? '';
        form.start_date = data.start_date ?? '';
        form.end_date = data.end_date ?? '';
        form.start_time = data.start_time ?? '';
        form.end_time = data.end_time ?? '';
        form.full_day = Boolean(data.full_day);
        form.frequency = data.frequency ?? 'weekly';
        form.interval = data.interval ?? 1;
        form.weekdays = Array.isArray(data.weekdays) ? data.weekdays : [1, 2, 3, 4, 5];
    } catch (e) {
        console.error('Failed to load series', e);
    }
}

onMounted(() => {
    // initialen Subject setzen (z. B. der aktuelle User/Freelancer/Provider)
    if (props.initialSubject) {
        form.subjects = [props.initialSubject];
    }

    // existierende Serie laden (Edit-Modus)
    if (props.seriesUuid) {
        loadSeries(props.seriesUuid);
    }
});

async function submit() {
    if (!canSubmit.value || isSubmitting.value) return;

    isSubmitting.value = true;

    const isEdit = Boolean(props.seriesUuid);

    // Payload vorbereiten
    const payload = {
        ...form.data(),
        interval: typeof form.interval === 'string' ? Number(form.interval) : form.interval,
        subjects: subjects.value.map((s) => ({
            id: s.id,
            type: s.type,
        })),
        series_uuid: props.seriesUuid ?? null,
    };

    try {
        if (isEdit) {
            await form
                .transform(() => payload)
                .put(
                    route('individual-time-series.update', { series: props.seriesUuid }),
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            emit('updated');
                            handleClose();
                        },
                    }
                );
        } else {
            await form
                .transform(() => payload)
                .post(
                    route('individual-time-series.store'),
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            emit('created');
                            handleClose();
                        },
                    }
                );
        }
    } finally {
        isSubmitting.value = false;
    }
}

async function deleteSeries() {
    if (!props.seriesUuid ) return;
    isSubmitting.value = true;
    try {
        await axios.delete(route('individual-time-series.destroy', { series: props.seriesUuid  }));
        emit('updated');
        showDeleteModal.value = false;
        handleClose();
    } finally {
        isSubmitting.value = false;
    }
}

</script>
