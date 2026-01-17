<template>
    <ArtworkBaseModal
        :title="`${day.dayString} ${day.fullDay}`"
        description=""
        modal-size="max-w-4xl"
        @close="closeModal"
    >
        <div class="space-y-7 text-sm">
            <!-- Kopfbereich: User-Info -->
            <section class="flex items-center justify-between gap-4 border-b border-zinc-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <img
                            :src="user.element.profile_photo_url"
                            class="object-cover h-16 w-16 rounded-full ring-2 ring-zinc-100"
                            alt=""
                        />

                        <span
                            class="absolute -bottom-3 left-1/2 -translate-x-1/2 inline-flex items-center justify-center rounded-full border px-1.5 py-0.5 text-[10px] font-medium first-letter:capitalize"
                            :class="badgeClassForType"
                        >
                            <span v-if="user.element.type === 'service_provider'">
                                {{ t('Service provider') }}
                            </span>
                            <span v-else-if="user.element.type === 'freelancer'">
                                {{ t('external') }}
                            </span>
                            <span v-else>
                                {{ t('internal') }}
                            </span>
                        </span>
                    </div>


                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-base font-semibold text-zinc-900">
                                <span v-if="user.element.type === 'service_provider'">
                                    {{ user.element.provider_name }}
                                </span>
                                <span v-else>
                                    {{ user.element.first_name }} {{ user.element.last_name }}
                                </span>
                            </p>
                        </div>
                        <p class="mt-0.5 text-xs text-zinc-500">
                            {{ t('Overview for this day') }}
                        </p>
                    </div>
                </div>

                <div class="hidden sm:flex flex-col items-end gap-1 text-xs">
                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-50 px-2 py-1 text-zinc-600 border border-zinc-100">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        {{ day.dayString }}
                    </span>
                    <span class="text-[11px] text-zinc-400">
                        {{ day.fullDay }}
                    </span>
                </div>
            </section>

            <div class="">
                <div class="space-y-6">
                    <!-- Schichten an diesem Tag -->
                    <section class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                                    {{ t('Assigned shifts') }}
                                </h3>
                                <p class="text-[11px] text-zinc-400 mt-0.5">
                                    {{ t('All shifts assigned to this person on the selected day.') }}
                                </p>
                            </div>
                        </div>

                        <div v-if="shiftsForDay.length" class="rounded-xl border border-zinc-100 bg-zinc-50/70 px-3 py-2">
                            <template v-for="shift in shiftsForDay" :key="shift.id">
                                <div class="flex items-center justify-between group border-b last:border-b-0 border-dashed border-zinc-200 py-2" :id="'shift-' + shift.id">
                                    <SingleShiftInShiftOverviewUser
                                        :user="user"
                                        :shift="shift"
                                        @shiftDeleted="handleShiftDeleted"
                                    />
                                </div>
                            </template>
                        </div>
                        <div
                            v-else
                            class="flex items-center gap-2 rounded-xl border border-dashed border-zinc-200 bg-zinc-50/60 px-3 py-3 text-xs text-zinc-500"
                        >
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-zinc-300"></span>
                            <span>{{ t('No shifts assigned for this day.') }}</span>
                        </div>
                    </section>

                    <!-- Individuelle Zeiten -->
                    <section class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                                    {{ t('Individual time') }}
                                </h3>
                                <p class="text-[11px] text-zinc-400 mt-0.5">
                                    {{ t('Define or adjust custom times for this person on this day.') }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="hidden sm:inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-white px-2.5 py-1 text-[11px] text-zinc-600 hover:border-artwork-buttons-hover hover:text-artwork-buttons-hover transition-colors"
                                @click="addIndividualTime"
                            >
                                <PropertyIcon name="IconCirclePlus" class="h-3.5 w-3.5" stroke-width="2" />
                                <span>{{ t('Add individual time') }}</span>
                            </button>
                        </div>

                        <div v-if="individualTimesByDate.length > 0">
                            <!-- Kopfzeile -->
                            <div class="hidden md:block text-[11px] text-zinc-500">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 px-1">
                                    <div>
                                        {{ t('Title') }}
                                    </div>
                                    <div class="col-span-2">
                                        {{ t('Period') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Einträge -->
                            <div
                                v-for="(individual_time, index) in individualTimesByDate"
                                :key="individual_time.id ?? index"
                                class="rounded-lg border border-zinc-100 bg-white px-3 py-3 mt-2 shadow-sm/10 group"
                            >
                                <!-- Nur Einträge für diesen Tag -->
                                <div
                                    v-if="individual_time?.days_of_individual_time?.includes(day.withoutFormat)"
                                    class="grid grid-cols-1 md:grid-cols-4 gap-2 items-start"
                                >
                                    <!-- FALL 1: Einfache individuelle Zeit (ohne Serie) -->
                                    <template v-if="!individual_time.series_uuid">
                                        <BaseInput
                                            id="title"
                                            v-model="individual_time.title"
                                            :label="t('Title')"
                                            :show-label="false"
                                            no-margin-top
                                        />
                                        <div class="flex items-center justify-center col-span-2 gap-1">
                                            <BaseInput
                                                type="time"
                                                id="start_time"
                                                classes="rounded-r-none"
                                                v-model="individual_time.start_time"
                                                :label="t('Start time')"
                                                :show-label="false"
                                                no-margin-top
                                            />
                                            <BaseInput
                                                type="time"
                                                id="end_time"
                                                v-model="individual_time.end_time"
                                                classes="border-l-0 rounded-l-none"
                                                :label="t('End time')"
                                                :show-label="false"
                                                no-margin-top
                                            />
                                        </div>
                                        <div
                                            v-if="individual_time.id"
                                            class="flex items-center justify-end md:justify-center"
                                        >

                                            <BaseUIButton
                                                label="Delete"
                                                :use-translation="true"
                                                is-delete-button
                                                @click="deleteIndividualTimeById(individual_time)"
                                                icon="IconTrash"
                                            />
                                        </div>

                                        <!-- Break Minutes -->
                                        <div class="col-span-full mt-3 pt-3 border-t border-zinc-100">
                                            <BaseInput
                                                type="number"
                                                :id="'break_minutes_' + index"
                                                v-model.number="individual_time.break_minutes"
                                                :label="t('Break time (minutes)')"
                                                :show-label="false"
                                                :min="0"
                                                :step="1"
                                                no-margin-top
                                                class="max-w-xs"
                                                @input="markBreakAsManuallyEdited(individual_time)"
                                            />
                                            <p class="text-[11px] text-zinc-500 mt-1.5 leading-snug">
                                                {{ t('This time will be deducted from the working hours when calculating the daily working time.') }}
                                            </p>
                                        </div>
                                    </template>

                                    <!-- FALL 3: Zeit gehört zu einer Serie -->
                                    <template v-else>
                                        <div class="space-y-1.5">
                                            <BaseInput
                                                id="title"
                                                v-model="individual_time.title"
                                                :label="t('Title')"
                                                :show-label="false"
                                                no-margin-top
                                            />
                                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-medium text-indigo-600 border border-indigo-100">
                                                • {{ t('Series entry') }}
                                            </span>
                                        </div>
                                        <div class="col-span-2">
                                            <div class="flex items-center justify-center gap-1">
                                                <BaseInput
                                                    type="time"
                                                    id="start_time"
                                                    classes="rounded-r-none"
                                                    v-model="individual_time.start_time"
                                                    :label="t('Start time')"
                                                    :show-label="false"
                                                    no-margin-top
                                                />
                                                <BaseInput
                                                    type="time"
                                                    id="end_time"
                                                    v-model="individual_time.end_time"
                                                    classes="border-l-0 rounded-l-none"
                                                    :label="t('End time')"
                                                    :show-label="false"
                                                    no-margin-top
                                                />
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end gap-2 col-span-1">
                                            <BaseUIButton
                                                is-small
                                                label="Edit series"
                                                :use-translation="true"
                                                @click="openSeriesModal(individual_time)"
                                            />
                                            <BaseUIButton
                                                is-small
                                                label="Delete"
                                                :use-translation="true"
                                                is-delete-button
                                                @click="deleteIndividualTimeById(individual_time)"
                                                icon="IconTrash"
                                            />
                                        </div>
                                        <div class="text-[11px] text-gray-500 col-span-full mt-1">
                                            {{ t('This time belongs to a series. If you change it, it will be detached from the series.') }}
                                        </div>

                                        <!-- Break Minutes -->
                                        <div class="col-span-full mt-3 pt-3 border-t border-zinc-100">
                                            <label class="block text-[11px] font-medium text-zinc-500 mb-1.5">
                                                {{ t('Break time (minutes)') }}
                                            </label>
                                            <BaseInput
                                                type="number"
                                                :id="'break_minutes_series_' + index"
                                                v-model.number="individual_time.break_minutes"
                                                :label="t('Break time (minutes)')"
                                                :show-label="false"
                                                :min="0"
                                                :step="1"
                                                no-margin-top
                                                class="max-w-xs"
                                                @input="markBreakAsManuallyEdited(individual_time)"
                                            />
                                            <p class="text-[11px] text-zinc-500 mt-1.5 leading-snug">
                                                {{ t('This time will be deducted from the working hours when calculating the daily working time.') }}
                                            </p>
                                        </div>
                                    </template>
                                </div>

                                <div
                                    v-if="individual_time.error"
                                    class="text-[11px] text-red-500 mt-2"
                                >
                                    {{ individual_time.error }}
                                </div>
                            </div>

                            <!-- Weitere Zeit hinzufügen (mobile) -->
                            <div class="mt-3 sm:hidden">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-xs xsLight hover:text-artwork-buttons-hover transition-colors"
                                    @click="addIndividualTime"
                                >
                                    <PropertyIcon name="IconCirclePlus"
                                        class="h-5 w-5"
                                        stroke-width="2"
                                    />
                                    <span>{{ t('Add individual time') }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Noch keine Zeiten -->
                        <div
                            v-else
                            class="cursor-pointer"
                            @click="addIndividualTime"
                        >
                            <div class="w-full px-3 py-4 bg-blue-400/8 hover:bg-blue-400/16 border border-dashed border-blue-200/70 rounded-lg mt-1 transition-colors">
                                <AlertComponent
                                    text="Es wurden noch keine Zeiten festgelegt. Klicke hier um Zeiten zu erstellen"
                                    show-icon
                                    icon-size="h-4 w-4"
                                />
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Rechte Spalte: Availability + Kommentar -->
                <div class="space-y-6 mt-5">
                    <!-- Verfügbarkeits-Typ (nur intern & extern) -->
                    <section
                        v-if="user.type === 0 || user.type === 1"
                        class="space-y-3 rounded-xl border border-zinc-100 bg-zinc-50/80 px-3.5 py-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <h3 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                                    {{ t('Availability status for this day') }}
                                </h3>
                                <p class="text-[11px] text-zinc-400 mt-0.5">
                                    {{ t('Define whether this person is available, off work or not available.') }}
                                </p>
                            </div>
                        </div>

                        <Listbox as="div" v-model="checked" class="relative mt-2 w-full">
                            <ListboxButton class="menu-button flex items-center justify-between">
                                <div class="flex items-center gap-2 truncate">
                                    <span
                                        class="inline-flex h-1.5 w-1.5 rounded-full"
                                        :class="dotClassForChecked"
                                    ></span>
                                    <span class="truncate">
                                        {{ checked?.name }}
                                    </span>
                                </div>
                                <PropertyIcon name="IconChevronDown"
                                    class="h-5 w-5 text-primary"
                                    aria-hidden="true"
                                />
                            </ListboxButton>
                            <ListboxOptions
                                class="absolute mt-1 w-full z-10 bg-artwork-navigation-background shadow-lg rounded-md max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm"
                            >
                                <ListboxOption
                                    v-for="type in vacationTypes"
                                    :key="type.type"
                                    :value="type"
                                    v-slot="{ selected }"
                                    class="text-secondary cursor-pointer rounded-md p-2 mb-0.5 flex justify-between items-center"
                                >
                                    <div class="flex items-center gap-2 truncate">
                                        <span
                                            class="inline-flex h-1.5 w-1.5 rounded-full"
                                            :class="dotClassForType(type)"
                                        ></span>
                                        <span
                                            :class="[
                                                selected ? 'xsWhiteBold' : 'xsLight',
                                                'truncate'
                                            ]"
                                        >
                                            {{ type.name }}
                                        </span>
                                    </div>
                                    <PropertyIcon name="IconCheck"
                                        v-if="selected"
                                        class="h-5 w-5 text-success"
                                        aria-hidden="true"
                                    />
                                </ListboxOption>
                            </ListboxOptions>
                        </Listbox>
                    </section>

                    <!-- Kommentar -->
                    <section class="space-y-3 rounded-xl border border-zinc-100 bg-white px-3.5 py-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                                    {{ t('Comment') }}
                                </h3>
                                <p class="text-[11px] text-zinc-400 mt-0.5">
                                    {{ t('Optional note for this day (visible in shift planning).') }}
                                </p>
                            </div>
                        </div>
                        <BaseTextarea
                            id="shift_comment"
                            v-model="shiftPlanComment.comment"
                            :label="t('Comment')"
                            :show-label="false"
                            no-margin-top
                        />
                    </section>

                    <!-- Registrierte Verfügbarkeiten -->
                    <section
                        v-if="user.availabilities"
                        class="space-y-3 rounded-xl border border-zinc-100 bg-zinc-50/70 px-3.5 py-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <h3 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                                    {{ t('Registered availabilities') }}
                                </h3>
                                <p class="text-[11px] text-zinc-400 mt-0.5">
                                    {{ t('Availabilities that this person has registered in the system.') }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="user.availabilities[day.fullDay]?.length"
                            class="space-y-2"
                        >
                            <div
                                v-for="availability in user.availabilities[day.fullDay]"
                                :key="availability.id ?? availability.date_casted + availability.start_time + availability.end_time"
                                class="rounded-lg border border-zinc-100 bg-white px-3 py-2"
                            >
                                <div class="flex items-center justify-between text-xs text-zinc-700">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        <span>{{ availability.date_casted }}</span>
                                        <span v-if="!availability.full_day">
                                            · {{ availability.start_time }} – {{ availability.end_time }}
                                        </span>
                                        <span v-else class="text-[11px] text-emerald-600">
                                            ({{ t('Full day') }})
                                        </span>
                                    </div>
                                </div>
                                <p
                                    v-if="availability.comment"
                                    class="mt-1 text-[11px] text-zinc-500 italic"
                                >
                                    &bdquo;{{ availability.comment }}&rdquo;
                                </p>
                            </div>
                        </div>
                        <div
                            v-else
                            class="text-[11px] text-zinc-500 italic"
                        >
                            {{ t('No availabilities are registered for this day.') }}
                        </div>
                    </section>
                </div>
            </div>

            <!-- Footer: Speichern -->
            <div class="flex justify-between pt-2 border-t border-zinc-100 mt-2">
                <BaseUIButton
                    :label="t('Cancel')"
                    is-cancel-button
                    @click="closeModal"
                />
                <BaseUIButton
                    :label="t('Save')"
                    is-add-button
                    @click="checkVacation"
                />
            </div>
        </div>

        <!-- Modale -->
        <ConfirmDeleteModal
            v-if="showConfirmDeleteModal"
            :title="t('Delete user from shift')"
            :description="t('Are you sure you want to delete the user from this shift?')"
            @closed="closeConfirmDeleteModal"
            @delete="submitDeleteUserFromShift"
        />

        <RequestWorkTimeChangeModal
            v-if="showRequestWorkTimeChangeModal"
            :user="user.element"
            :shift="selectedShift"
            @close="showRequestWorkTimeChangeModal = false"
        />

        <IndividualTimeSeriesModal
            v-if="showSeriesModal"
            :is-in-shift-plan="true"
            :series-uuid="activeSeriesUuid"
            :initial-subject="activeSeriesSubject || currentSeriesSubject"
            @close="closeSeriesModal"
            @created="handleSeriesUpdated"
            @updated="handleSeriesUpdated"
        />
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

import AlertComponent from '@/Components/Alerts/AlertComponent.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import RequestWorkTimeChangeModal from '@/Pages/Shifts/Components/RequestWorkTimeChangeModal.vue';
import SingleShiftInShiftOverviewUser from '@/Pages/Shifts/Components/SingleShiftInShiftOverviewUser.vue';
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import IndividualTimeSeriesModal from '@/Pages/Shifts/Components/IndividualTimeSeriesModal.vue';
import { IconCirclePlus, IconTrash } from '@tabler/icons-vue';
import { useLegalBreak } from '@/Composeables/useLegalBreak';
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

defineOptions({
    name: 'ShowUserShiftsModal',
});

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    day: {
        type: Object,
        required: true,
    },
    shiftQualifications: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['closed', 'delete', 'desiresReload']);

const { t } = useI18n();
const page = usePage(); // aktuell nicht genutzt, aber da, falls später benötigt

// Verfügbarkeits-Typen
const vacationTypes = ref([
    { name: 'Verfügbar', type: 'AVAILABLE' },
    { name: 'Arbeitsfreier Tag', type: 'OFF_WORK' },
    { name: 'Nicht Verfügbar', type: 'NOT_AVAILABLE' },
    { name: 'Frei', type: 'FREE_WORK' },
]);

const checked = ref(null);
const vacationTypeBeforeUpdate = ref(null);

// Kopie des ursprünglichen Zustands der individuellen Zeiten (zum Zurücksetzen bei Close)
const originalIndividualTimes = ref(
    props.user.individual_times ? JSON.parse(JSON.stringify(props.user.individual_times)) : [],
);

// Modale & Zustände
const showConfirmDeleteModal = ref(false);
const wantedShiftId = ref(null);
const wantedUserId = ref(null);

const showRequestWorkTimeChangeModal = ref(false);
const selectedShift = ref(null);

const showSeriesModal = ref(false);
const activeSeriesUuid = ref(null);
const activeSeriesSubject = ref(null);

// Edit-Modus-Flag (bisher nicht verwendet, aber Logik bleibt erhalten)
const editMode = ref(false);

// Kommentar
const shiftPlanComment = ref(
    props.user.shift_comments?.[props.day.withoutFormat]?.[0] ?? {
        comment: '',
        date: props.day.withoutFormat,
    },
);

// Track manual edits for break_minutes per individual time
const manualBreakEdits = ref(new Map());

// Function to mark break time as manually edited
function markBreakAsManuallyEdited(individualTime) {
    const timeKey = individualTime.id || `${individualTime.start_date}-${individualTime.start_time}-${individualTime.end_time}`;
    manualBreakEdits.value.set(timeKey, true);
}

// IndividualTimes gefiltert nach Tag
const individualTimesByDate = computed(() => {
    return (props.user.individual_times || []).filter((individual_time) =>
        individual_time.days_of_individual_time?.includes(props.day.withoutFormat),
    );
});

// Watch each individual time for start/end time changes to auto-calculate break
watch(
    () => props.user.individual_times?.map(it => ({
        id: it.id,
        start_time: it.start_time,
        end_time: it.end_time,
        start_date: it.start_date
    })),
    (newTimes, oldTimes) => {
        if (!newTimes || !props.user.individual_times) return;

        newTimes.forEach((newTime, index) => {
            const oldTime = oldTimes?.[index];
            const individualTime = props.user.individual_times[index];

            if (!individualTime) return;

            const timeKey = individualTime.id || `${individualTime.start_date}-${individualTime.start_time}-${individualTime.end_time}`;

            // Check if start_time or end_time actually changed
            const timesChanged = oldTime && (
                newTime.start_time !== oldTime.start_time ||
                newTime.end_time !== oldTime.end_time
            );

            // Only recalculate break_minutes when times actually changed
            if (timesChanged) {
                // Reset manual edit flag when times change
                manualBreakEdits.value.delete(timeKey);

                // Auto-calculate break_minutes if times are set
                if (individualTime.start_time && individualTime.end_time) {
                    const startRef = computed(() => individualTime.start_time);
                    const endRef = computed(() => individualTime.end_time);
                    const { breakMinutes } = useLegalBreak(startRef, endRef);

                    if (breakMinutes.value !== individualTime.break_minutes) {
                        individualTime.break_minutes = breakMinutes.value;
                    }
                }
            }
        });
    },
    { deep: true }
);

// Subject für Serien-Modal (Person)
const currentSeriesSubject = computed(() => {
    const element = props.user.element;
    return {
        id: element.id,
        type: element.type,
        display_name: element.provider_name
            ? element.provider_name
            : `${element.first_name} ${element.last_name}`,
    };
});

// Badge-Klasse für Typ
const badgeClassForType = computed(() => {
    if (props.user.element.type === 'service_provider') {
        return 'bg-purple-50 text-purple-700 border-purple-100';
    }
    if (props.user.element.type === 'freelancer') {
        return 'bg-amber-50 text-amber-700 border-amber-100';
    }
    return 'bg-emerald-50 text-emerald-700 border-emerald-100';
});

// Dot-Farbe für aktuellen Availability-Status
const dotClassForChecked = computed(() => {
    if (!checked.value) return 'bg-zinc-300';
    if (checked.value.type === 'AVAILABLE') return 'bg-emerald-500';
    if (checked.value.type === 'OFF_WORK') return 'bg-amber-500';
    if (checked.value.type === 'NOT_AVAILABLE') return 'bg-rose-500';
    return 'bg-zinc-300';
});

function dotClassForType(type) {
    if (type.type === 'AVAILABLE') return 'bg-emerald-500';
    if (type.type === 'OFF_WORK') return 'bg-amber-500';
    if (type.type === 'NOT_AVAILABLE') return 'bg-rose-500';
    return 'bg-zinc-300';
}

// Initialer Vacation-Type
onMounted(() => {
    const vacation = props.user.vacations?.find(
        (v) => v.date === props.day.withoutFormat,
    );

    if (vacation) {
        const vacationType = vacationTypes.value.find(
            (type) => type.type === vacation.type,
        );
        checked.value = vacationType ?? vacationTypes.value[0];
        vacationTypeBeforeUpdate.value = vacationType ?? vacationTypes.value[0];
    } else {
        checked.value = vacationTypes.value[0];
        vacationTypeBeforeUpdate.value = vacationTypes.value[0];
    }
});

// Methoden
function openSeriesModal(individualTime) {
    activeSeriesUuid.value = individualTime.series_uuid;
    activeSeriesSubject.value = currentSeriesSubject.value;
    showSeriesModal.value = true;
}

function closeSeriesModal() {
    showSeriesModal.value = false;
    activeSeriesUuid.value = null;
    activeSeriesSubject.value = null;

    closeModal();
}

function handleSeriesUpdated() {
    router.reload({
        only: ['usersForShifts', 'freelancersForShifts', 'serviceProvidersForShifts'],
    });
    closeSeriesModal();
}

function deleteIndividualTimeById(individualTime) {
    if (individualTime.id) {
        router.delete(
            route('delete.individualTimes', { individualTime }),
            {
                preserveScroll: true,
                preserveState: false,
            },
        );
    }
}

const shiftsForDay = computed(() => {
    return (props.user.element.shifts || []).filter((shift) =>
        shift.days_of_shift?.includes(props.day.fullDay),
    );
});

function addIndividualTime() {
    if (!props.user.individual_times) {
        props.user.individual_times = [];
    }

    props.user.individual_times.push({
        id: null,
        title: '',
        start_time: '',
        end_time: '',
        start_date: props.day.withoutFormat,
        break_minutes: 0,
        days_of_individual_time: [props.day.withoutFormat],
    });
}

function closeModal(bool) {
    // Zustand auf ursprüngliche IndividualTimes zurücksetzen
    props.user.individual_times = JSON.parse(JSON.stringify(originalIndividualTimes.value));
    emit('closed', bool);
}

function removeUserFromShift(shiftId, usersPivotId) {
    router.delete(
        route('shift.removeUserByType', {
            usersPivotId,
            userType: props.user.type,
        }),
        {
            data: {
                removeFromSingleShift: true,
            },
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                document.getElementById('shift-' + shiftId)?.remove();
            },
            onFinish: () => {
                document.getElementById('shift-' + shiftId)?.remove();
            },
        },
    );
}

function submitDeleteUserFromShift() {
    removeUserFromShift(wantedShiftId.value, wantedUserId.value);
    closeConfirmDeleteModal();
}

function openConfirmDeleteModal(shiftId, usersPivotId) {
    wantedShiftId.value = shiftId;
    wantedUserId.value = usersPivotId;
    showConfirmDeleteModal.value = true;
}

function closeConfirmDeleteModal() {
    showConfirmDeleteModal.value = false;
}

function handleShiftDeleted(deletedShiftId) {
    // Lokale Shifts des Users aktualisieren, um stale Pivots zu vermeiden
    props.user.element.shifts = (props.user.element.shifts || []).filter(
        (shift) => shift.id !== deletedShiftId,
    );
}

function sendIndividualTimes() {

    // nur zeiten die wirklich verändert wurden senden
    const individualTimesWhereAreEdited = (props.user.individual_times || []).filter(
        (individualTime) => {
            const original = originalIndividualTimes.value.find(
                (orig) => orig.id === individualTime.id,
            );
            return JSON.stringify(original) !== JSON.stringify(individualTime);
        },
    );


    axios
        .post(route('add.update.individualTimesAndShiftPlanComment'), {
            modelId: props.user.element.id,
            modelType: props.user.type,
            individualTimes: individualTimesWhereAreEdited,
            shift_comment: shiftPlanComment.value,
        })
        .then((response) => {
            if (response.data.individual_times) {
                originalIndividualTimes.value = response.data.individual_times;
                props.user.individual_times = response.data.individual_times;
            }

            if (response.data.shift_comment) {
                shiftPlanComment.value = response.data.shift_comment;
            }

            sendCheckVacation();
        })
        .catch(() => {
            // Fehler-Fall ggf. später behandeln
        });
}

function sendCheckVacation() {
    if (props.user.type === 0) {
        router.patch(
            route('user.check.vacation', { user: props.user.element.id }),
            {
                checked: checked.value,
                day: props.day.fullDay,
                vacationTypeBeforeUpdate: vacationTypeBeforeUpdate.value,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    closeModal(true);
                },
            },
        );
    } else if (props.user.type === 2) {
        router.patch(
            route('service_provider.check.vacation', {
                service_provider: props.user.element.id,
            }),
            {
                checked: checked.value,
                day: props.day.fullDay,
                vacationTypeBeforeUpdate: vacationTypeBeforeUpdate.value,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    closeModal(true);
                },
            },
        );
    } else {
        router.patch(
            route('freelancer.check.vacation', {
                freelancer: props.user.element.id,
            }),
            {
                checked: checked.value,
                day: props.day.fullDay,
                vacationTypeBeforeUpdate: vacationTypeBeforeUpdate.value,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    closeModal(true);
                },
            },
        );
    }
}

function checkVacation() {
    // Bisherige Errors leeren
    for (const individualTime of props.user.individual_times || []) {
        delete individualTime.error;
    }

    // Serien-Detach nur bei EditMode (aktuell false, Verhaltens-Änderung vermeiden)
    for (const individualTime of props.user.individual_times || []) {
        if (individualTime.series_uuid && editMode.value) {
            individualTime.series_uuid = null;
        }
        delete individualTime.error;
    }

    // Validierung
    for (const individualTime of props.user.individual_times || []) {
        if (individualTime.start_time && !individualTime.end_time) {
            individualTime.error = t('Please also enter an end time here.');
            return;
        }
        if (!individualTime.start_time && individualTime.end_time) {
            individualTime.error = t('Please also enter a start time here.');
            return;
        }
    }

    sendIndividualTimes();
}

function openRequestWorkTimeChangeModal(shift) {
    selectedShift.value = shift;
    showRequestWorkTimeChangeModal.value = true;
}
</script>
