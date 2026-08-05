<template>
    <ArtworkBaseModal
        :title="mode === 'wish' ? $t('Enter project wish') : $t('Assign project')"
        :description="mode === 'wish'
            ? $t('Enter yourself as a wish for a project. Planners will see it in the shift plan.')
            : $t('Assign {0} bindingly to a project.', [workerName])"
        modal-size="sm:max-w-xl"
        @close="$emit('close', { saved: false })"
    >
        <div class="space-y-5 text-sm">
            <!-- Projekt-Auswahl -->
            <section class="space-y-2 rounded-xl border border-border-subtle bg-surface-sunken/80 px-3.5 py-3">
                <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                    {{ $t('Project') }}
                </h3>

                <div v-if="selectedProject" class="flex items-center justify-between gap-2 rounded-lg border border-border-subtle bg-white px-3 py-2">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: colorForProjectId(selectedProject.id) }"></span>
                            <span class="truncate font-medium text-text">{{ selectedProject.name }}</span>
                        </div>
                        <div class="text-[11px] text-text-subtle mt-0.5">
                            <template v-if="selectedProject.period_start">
                                {{ formatAssignmentDate(selectedProject.period_start) }} - {{ formatAssignmentDate(selectedProject.period_end) }}
                            </template>
                            <template v-else>{{ $t('No events yet') }}</template>
                            <template v-if="selectedProject.artists"> &middot; {{ selectedProject.artists }}</template>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md p-1.5 text-text-subtle hover:text-danger hover:bg-danger-surface transition-colors"
                        @click="selectedProject = null"
                    >
                        <PropertyIcon name="IconX" class="h-4 w-4" stroke-width="1.5" />
                    </button>
                </div>

                <template v-else>
                    <BaseInput
                        id="project-assignment-search"
                        v-model="searchQuery"
                        :label="$t('Search project')"
                        :show-label="false"
                        :placeholder="$t('Search project')"
                        no-margin-top
                    />
                    <p class="text-[11px] text-text-subtle">
                        {{ $t('Projects whose period includes the selected days are suggested first. Use the search to find all projects.') }}
                    </p>
                    <div class="max-h-56 overflow-y-auto rounded-lg border border-border-subtle bg-white divide-y divide-border-subtle">
                        <div v-if="loadingProjects" class="px-3 py-3 text-xs text-text-subtle">
                            {{ $t('Loading...') }}
                        </div>
                        <div v-else-if="!projectOptions.length" class="px-3 py-3 text-xs text-text-subtle">
                            {{ $t('No projects found') }}
                        </div>
                        <button
                            v-for="project in projectOptions"
                            :key="project.id"
                            type="button"
                            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left hover:bg-surface-sunken transition-colors"
                            @click="selectProject(project)"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: colorForProjectId(project.id) }"></span>
                                    <span class="truncate text-text">{{ project.name }}</span>
                                </div>
                                <div class="text-[11px] text-text-subtle mt-0.5">
                                    <template v-if="project.period_start">
                                        {{ formatAssignmentDate(project.period_start) }} - {{ formatAssignmentDate(project.period_end) }}
                                    </template>
                                    <template v-else>{{ $t('No events yet') }}</template>
                                    <template v-if="project.artists"> &middot; {{ project.artists }}</template>
                                </div>
                            </div>
                            <span
                                v-if="project.covers_all_days"
                                class="shrink-0 rounded-full bg-success-surface px-2 py-0.5 text-[10px] text-success border border-success-border"
                            >
                                {{ $t('In period') }}
                            </span>
                        </button>
                    </div>
                </template>
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
                            <span v-if="selectedProject?.period_start" class="block text-[11px] text-text-subtle">
                                {{ formatAssignmentDate(selectedProject.period_start) }} - {{ formatAssignmentDate(selectedProject.period_end) }}
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
                    <div class="flex items-center gap-2">
                        <BaseInput
                            id="project-assignment-add-day"
                            type="date"
                            v-model="dayToAdd"
                            :label="$t('Add day')"
                            :show-label="false"
                            no-margin-top
                        />
                        <BaseUIButton
                            :label="$t('Add')"
                            :disabled="!dayToAdd"
                            @click="addDay"
                        />
                    </div>
                </div>
            </section>

            <!-- Warnung (z. B. Wunsch auf Abwesenheitstag) -->
            <div
                v-if="warningMessage"
                class="rounded-lg border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning"
            >
                {{ warningMessage }}
            </div>

            <!-- Footer -->
            <div class="flex justify-end pt-2 border-t border-border-subtle">
                <BaseUIButton
                    :label="mode === 'wish' ? $t('Enter wish') : $t('Assign')"
                    is-add-button
                    :disabled="!canSubmit || submitting"
                    @click="submit"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue';
import { colorForProjectId, formatAssignmentDate } from '@/Composeables/UseProjectDayAssignments.js';

const props = defineProps({
    workerType: { type: Number, required: true }, // 0=User, 1=Freelancer, 2=ServiceProvider
    workerId: { type: Number, required: true },
    workerName: { type: String, default: '' },
    mode: { type: String, default: 'binding' }, // 'binding' | 'wish'
    initialDays: { type: Array, default: () => [] }, // ['Y-m-d']
});

const emit = defineEmits(['close']);

const searchQuery = ref('');
const projectOptions = ref([]);
const loadingProjects = ref(false);
const selectedProject = ref(null);
const periodMode = ref(props.initialDays.length ? 'days' : 'full_period');
const selectedDays = ref([...props.initialDays]);
const dayToAdd = ref('');
const warningMessage = ref('');
const submitting = ref(false);

const daysForSuggestion = computed(() => selectedDays.value.length
    ? selectedDays.value
    : [new Date().toISOString().slice(0, 10)]);

const canSubmit = computed(() => {
    if (!selectedProject.value) return false;
    if (periodMode.value === 'days') return selectedDays.value.length > 0;
    return true;
});

let searchDebounce = null;
let projectRequest = null;
let projectRequestSequence = 0;

// Zuletzt verwendetes Projekt (pro Modus und User) vorauswählen — aber nur, wenn es
// in den Vorschlägen für die gewählten Tage auftaucht, und nur beim ersten Laden:
// wählt die Person es aktiv ab, wird nicht erneut vorausgewählt.
const lastProjectStorageKey = `artwork.projectAssignment.lastProject.${props.mode}.${usePage().props.auth?.user?.id ?? 'anon'}`;
let lastProjectPreselectDone = false;

function preselectLastUsedProject() {
    if (lastProjectPreselectDone || selectedProject.value || searchQuery.value) return;
    lastProjectPreselectDone = true;

    let lastProjectId = null;
    try {
        lastProjectId = Number(localStorage.getItem(lastProjectStorageKey));
    } catch (e) {
        return;
    }
    if (!lastProjectId) return;

    const match = projectOptions.value.find((project) => project.id === lastProjectId);
    if (match) {
        selectProject(match);
    }
}

function rememberLastUsedProject() {
    try {
        localStorage.setItem(lastProjectStorageKey, String(selectedProject.value.id));
    } catch (e) {
        // localStorage nicht verfügbar (z. B. Private Mode) — Vorauswahl ist optional
    }
}

async function loadProjects() {
    const requestSequence = ++projectRequestSequence;
    projectRequest?.abort();
    projectRequest = new AbortController();
    loadingProjects.value = true;
    try {
        const { data } = await axios.get(route('project-day-assignments.projects'), {
            signal: projectRequest.signal,
            params: {
                days: daysForSuggestion.value,
                search: searchQuery.value || undefined,
            },
        });
        if (requestSequence === projectRequestSequence) {
            projectOptions.value = data.projects ?? [];
            preselectLastUsedProject();
        }
    } catch (error) {
        if (error?.code !== 'ERR_CANCELED' && requestSequence === projectRequestSequence) {
            projectOptions.value = [];
        }
    } finally {
        if (requestSequence === projectRequestSequence) {
            loadingProjects.value = false;
        }
    }
}

watch([searchQuery, () => daysForSuggestion.value.join(',')], () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(loadProjects, 300);
});

onMounted(loadProjects);
onUnmounted(() => {
    clearTimeout(searchDebounce);
    projectRequest?.abort();
});

function selectProject(project) {
    selectedProject.value = project;
    warningMessage.value = '';
}

function addDay() {
    if (dayToAdd.value && !selectedDays.value.includes(dayToAdd.value)) {
        selectedDays.value.push(dayToAdd.value);
        selectedDays.value.sort();
    }
    dayToAdd.value = '';
}

function removeDay(day) {
    selectedDays.value = selectedDays.value.filter(d => d !== day);
}

async function submit() {
    if (!canSubmit.value || submitting.value) return;
    warningMessage.value = '';
    submitting.value = true;

    try {
        await axios.post(route('project-day-assignments.store'), {
            project_id: selectedProject.value.id,
            worker_type: props.workerType,
            worker_id: props.workerId,
            type: props.mode,
            full_period: periodMode.value === 'full_period',
            days: periodMode.value === 'days' ? selectedDays.value : [],
        });
        rememberLastUsedProject();
        emit('close', { saved: true });
    } catch (error) {
        // 422 mit verständlicher Meldung (z. B. Wunsch auf Abwesenheitstag) als
        // freundliche Warnung anzeigen statt rohem Fehler.
        const errors = error?.response?.data?.errors;
        warningMessage.value = errors
            ? Object.values(errors).flat()[0]
            : (error?.response?.data?.message ?? String(error));
    } finally {
        submitting.value = false;
    }
}
</script>
