<template>
    <ArtworkBaseModal
        :title="$t('Assign persons')"
        :description="$t('Assign persons bindingly to {0}.', [project?.name ?? ''])"
        modal-size="sm:max-w-xl"
        @close="close"
    >
        <div class="space-y-5 text-sm">
            <!-- Personen-Auswahl (Projekt ist fix — Umkehrung des ProjectAssignmentModal) -->
            <section class="space-y-2 rounded-xl border border-border-subtle bg-surface-sunken/80 px-3.5 py-3">
                <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                    {{ $t('Persons') }}
                </h3>

                <div v-if="selectedWorkers.length" class="flex flex-wrap gap-1.5">
                    <span
                        v-for="worker in selectedWorkers"
                        :key="workerKey(worker)"
                        class="inline-flex items-center gap-1.5 rounded-full border border-border-subtle bg-white pl-1 pr-2 py-0.5"
                    >
                        <img
                            v-if="worker.profile_photo_url"
                            :src="worker.profile_photo_url"
                            :alt="worker.name"
                            class="h-5 w-5 rounded-full object-cover"
                        />
                        <span class="text-[11px] text-text">{{ worker.name }}</span>
                        <button type="button" class="text-text-subtle hover:text-danger" @click="toggleWorker(worker)">
                            <PropertyIcon name="IconX" class="h-3 w-3" stroke-width="2" />
                        </button>
                    </span>
                </div>

                <BaseInput
                    id="project-assign-person-search"
                    v-model="searchQuery"
                    :label="$t('Search person')"
                    :show-label="false"
                    :placeholder="$t('Search person')"
                    no-margin-top
                />
                <p class="text-[11px] text-text-subtle">
                    {{ $t('Project team members are suggested first. Use the search to find all persons who can work shifts.') }}
                </p>
                <div class="max-h-56 overflow-y-auto rounded-lg border border-border-subtle bg-white divide-y divide-border-subtle">
                    <div v-if="loadingWorkers" class="px-3 py-3 text-xs text-text-subtle">
                        {{ $t('Loading...') }}
                    </div>
                    <div v-else-if="!workerOptions.length" class="px-3 py-3 text-xs text-text-subtle">
                        {{ $t('No persons found') }}
                    </div>
                    <button
                        v-for="worker in workerOptions"
                        :key="workerKey(worker)"
                        type="button"
                        class="flex w-full items-center gap-2.5 px-3 py-2 text-left hover:bg-surface-sunken transition-colors"
                        @click="toggleWorker(worker)"
                    >
                        <img
                            v-if="worker.profile_photo_url"
                            :src="worker.profile_photo_url"
                            :alt="worker.name"
                            class="h-7 w-7 shrink-0 rounded-full object-cover"
                        />
                        <span v-else class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-sunken text-[10px] text-text-subtle">
                            {{ worker.name?.slice(0, 2) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-text">{{ worker.name }}</span>
                            <span v-if="worker.position" class="block truncate text-[11px] text-text-subtle">{{ worker.position }}</span>
                        </span>
                        <span class="flex shrink-0 items-center gap-1">
                            <span
                                v-if="worker.in_project_team"
                                class="rounded-full bg-success-surface px-2 py-0.5 text-[10px] text-success border border-success-border"
                            >
                                {{ $t('In project team') }}
                            </span>
                            <span
                                v-if="worker.wish_days > 0"
                                class="rounded-full border border-dashed border-success px-2 py-0.5 text-[10px] italic text-success"
                            >
                                {{ $t('Wish') }}
                            </span>
                            <span
                                v-if="worker.has_full_period || worker.binding_days > 0"
                                class="rounded-full bg-surface-sunken px-2 py-0.5 text-[10px] text-text-subtle"
                                :title="worker.has_full_period ? $t('Entire project period') : `${worker.binding_days} ${$t('Days')}`"
                            >
                                {{ $t('Already assigned') }}
                            </span>
                            <PropertyIcon
                                v-if="isSelected(worker)"
                                name="IconCheck"
                                class="h-4 w-4 text-success"
                                stroke-width="2"
                            />
                        </span>
                    </button>
                </div>
            </section>

            <!-- Zeitraum: ganzer Projektzeitraum oder einzelne Tage -->
            <section class="space-y-3 rounded-xl border border-border-subtle bg-white px-3.5 py-3">
                <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                    {{ $t('Period') }}
                </h3>

                <div class="space-y-2">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input
                            type="radio"
                            value="full_period"
                            v-model="periodMode"
                            class="mt-0.5 h-4 w-4 border-border text-accent-600 focus:ring-accent-600"
                        />
                        <span>
                            <span class="text-text">{{ $t('Entire project period') }}</span>
                            <span v-if="periodStart" class="block text-[11px] text-text-subtle">
                                {{ formatAssignmentDate(periodStart) }} - {{ formatAssignmentDate(periodEnd) }}
                                &middot; {{ $t('Moves along if the project is rescheduled.') }}
                            </span>
                        </span>
                    </label>
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input
                            type="radio"
                            value="days"
                            v-model="periodMode"
                            class="mt-0.5 h-4 w-4 border-border text-accent-600 focus:ring-accent-600"
                        />
                        <span class="text-text">{{ $t('Single day(s)') }}</span>
                    </label>
                </div>

                <div v-if="periodMode === 'days'" class="space-y-2 pl-6">
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="day in selectedDays"
                            :key="day"
                            class="inline-flex items-center gap-1 rounded-full bg-surface-sunken px-2 py-0.5 text-[11px] text-text-muted"
                        >
                            {{ formatAssignmentDate(day) }}
                            <button type="button" class="text-text-subtle hover:text-danger" @click="removeDay(day)">
                                <PropertyIcon name="IconX" class="h-3 w-3" stroke-width="2" />
                            </button>
                        </span>
                        <span v-if="!selectedDays.length" class="text-[11px] text-text-subtle">
                            {{ $t('No days selected yet') }}
                        </span>
                    </div>
                    <VueDatePicker
                        v-model="pickerDates"
                        multi-dates
                        inline
                        auto-apply
                        :enable-time-picker="false"
                        :locale="userLanguage"
                        class="assignment-day-picker"
                    />
                </div>
            </section>

            <!-- Warnung/Info (z. B. alle Tage bereits abgedeckt, Fehler pro Person) -->
            <div
                v-if="warningMessage || errorList.length"
                class="rounded-lg border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning space-y-1"
            >
                <p v-if="warningMessage">{{ warningMessage }}</p>
                <p v-for="entry in errorList" :key="entry.key">
                    <span class="font-semibold">{{ entry.name }}:</span> {{ entry.message }}
                </p>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 pt-2 border-t border-border-subtle">
                <BaseUIButton
                    v-if="absenceBlockedWorkers.length"
                    :label="$t('Assign anyway')"
                    :disabled="submitting"
                    @click="submitForce"
                />
                <BaseUIButton
                    :label="$t('Assign')"
                    is-add-button
                    :disabled="!canSubmit || submitting"
                    @click="submit()"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, defineAsyncComponent } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue';
import { formatAssignmentDate } from '@/Composeables/UseProjectDayAssignments.js';
import { useTranslation } from '@/Composeables/Translation.js';
import '@vuepic/vue-datepicker/dist/main.css';

const $t = useTranslation();

const VueDatePicker = defineAsyncComponent({
    loader: () => import('@vuepic/vue-datepicker'),
    delay: 200,
    timeout: 3000,
});

const props = defineProps({
    project: { type: Object, required: true }, // { id, name }
    periodStart: { type: String, default: null }, // Y-m-d, nur Anzeige
    periodEnd: { type: String, default: null },
    initialDays: { type: Array, default: () => [] }, // ['Y-m-d']
});

const emit = defineEmits(['close']);

const searchQuery = ref('');
const workerOptions = ref([]);
const loadingWorkers = ref(false);
const selectedWorkers = ref([]);
const periodMode = ref(props.initialDays.length ? 'days' : 'full_period');
const selectedDays = ref([...props.initialDays]);
const warningMessage = ref('');
const errorList = ref([]); // [{ key, name, message }] — Fehler pro Person benennen
const absenceBlockedWorkers = ref([]); // Personen mit Frei-/Abwesenheitstagen (409) — „Trotzdem zuordnen"
const submitting = ref(false);
// Falls nach Teil-Erfolg offen geblieben: beim Schließen trotzdem neu laden lassen
let anySaved = false;
// Über Teil-Versuche hinweg aufsummiert — fürs Ergebnis-Feedback beim Schließen
let totalCreated = 0;
let totalSkipped = 0;

const userLanguage = usePage().props.auth?.user?.language ?? 'de';

// Lokales Heute-sicheres Y-m-d (kein toISOString — das wäre UTC)
function toLocalDateString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Kalender-Mehrfachauswahl: Date-Objekte des Pickers <-> Y-m-d-Strings
const pickerDates = computed({
    get: () => selectedDays.value.map((day) => new Date(`${day}T12:00:00`)),
    set: (dates) => {
        selectedDays.value = (dates ?? [])
            .map((date) => toLocalDateString(new Date(date)))
            .sort();
    },
});

const canSubmit = computed(() => {
    if (!selectedWorkers.value.length) return false;
    if (periodMode.value === 'days') return selectedDays.value.length > 0;
    return true;
});

const workerKey = (worker) => `${worker.type}_${worker.id}`;
const isSelected = (worker) => selectedWorkers.value.some((w) => workerKey(w) === workerKey(worker));

function toggleWorker(worker) {
    if (isSelected(worker)) {
        selectedWorkers.value = selectedWorkers.value.filter((w) => workerKey(w) !== workerKey(worker));
    } else {
        selectedWorkers.value = [...selectedWorkers.value, worker];
    }
}

let searchDebounce = null;
let workerRequest = null;
let workerRequestSequence = 0;

async function loadWorkers() {
    const requestSequence = ++workerRequestSequence;
    workerRequest?.abort();
    workerRequest = new AbortController();
    loadingWorkers.value = true;
    try {
        const { data } = await axios.get(
            route('projects.day-assignments.worker-options', { project: props.project.id }),
            {
                signal: workerRequest.signal,
                params: { search: searchQuery.value || undefined },
            }
        );
        if (requestSequence === workerRequestSequence) {
            workerOptions.value = data.workers ?? [];
        }
    } catch (error) {
        if (error?.code !== 'ERR_CANCELED' && requestSequence === workerRequestSequence) {
            workerOptions.value = [];
        }
    } finally {
        if (requestSequence === workerRequestSequence) {
            loadingWorkers.value = false;
        }
    }
}

watch(searchQuery, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(loadWorkers, 300);
});

onMounted(loadWorkers);
onUnmounted(() => {
    clearTimeout(searchDebounce);
    workerRequest?.abort();
});

function removeDay(day) {
    selectedDays.value = selectedDays.value.filter((d) => d !== day);
}

function close() {
    emit('close', { saved: anySaved, created: totalCreated, skipped: totalSkipped });
}

async function submit(workersToSubmit = null, force = false) {
    if ((!canSubmit.value && workersToSubmit === null) || submitting.value) return;
    warningMessage.value = '';
    errorList.value = [];
    absenceBlockedWorkers.value = [];
    submitting.value = true;

    let created = 0;
    let skipped = 0;
    const failed = [];
    const absenceBlocked = [];

    // Ein Store-Call pro Person (Backend-API ist Einzelperson-basiert);
    // sequenziell, damit Fehlermeldungen eindeutig zuordenbar bleiben.
    for (const worker of (workersToSubmit ?? selectedWorkers.value)) {
        try {
            const { data } = await axios.post(route('project-day-assignments.store'), {
                project_id: props.project.id,
                worker_type: worker.type,
                worker_id: worker.id,
                type: 'binding',
                full_period: periodMode.value === 'full_period',
                days: periodMode.value === 'days' ? selectedDays.value : [],
                force,
            });
            created += data.created ?? 0;
            skipped += data.skipped ?? 0;
        } catch (error) {
            if (error?.response?.status === 409 && error.response.data?.warning === 'absences') {
                absenceBlocked.push({ worker, message: error.response.data.message });
                continue;
            }

            const errors = error?.response?.data?.errors;
            failed.push({
                key: workerKey(worker),
                name: worker.name,
                message: errors
                    ? Object.values(errors).flat()[0]
                    : (error?.response?.data?.message ?? String(error)),
            });
        }
    }

    submitting.value = false;
    anySaved = anySaved || created > 0;
    totalCreated += created;
    totalSkipped += skipped;

    if (absenceBlocked.length) {
        // Frei-/Abwesenheitstage: pro Person benennen, „Trotzdem zuordnen" anbieten
        absenceBlockedWorkers.value = absenceBlocked.map((entry) => entry.worker);
        errorList.value = absenceBlocked.map((entry) => ({
            key: workerKey(entry.worker),
            name: entry.worker.name,
            message: entry.message,
        }));
        errorList.value.push(...failed);
        return;
    }

    if (failed.length) {
        errorList.value = failed;
        return;
    }

    if (created === 0) {
        // Alles bereits abgedeckt (bestehende Zuordnung oder Schicht desselben Projekts)
        warningMessage.value = $t('All selected days are already covered for the selected persons.');
        return;
    }

    emit('close', { saved: true, created: totalCreated, skipped: totalSkipped });
}

function submitForce() {
    submit(absenceBlockedWorkers.value, true);
}
</script>

<style scoped>
/* Inline-Kalender an die Modalbreite anpassen */
.assignment-day-picker :deep(.dp__menu) {
    width: 100%;
    border-radius: 0.5rem;
}
.assignment-day-picker :deep(.dp__flex_display) {
    display: block;
}
</style>
