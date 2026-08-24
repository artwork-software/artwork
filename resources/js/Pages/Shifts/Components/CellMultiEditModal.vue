<template>
    <ArtworkBaseModal
        title="Mehrfacheintrag"
        description=""
        modal-size="sm:max-w-xl"
        @close="$emit('close', { saved: false })"
    >
        <div class="space-y-6 text-sm">
            <!-- Save-Hinweis -->
            <div
                v-show="showSaveSuccess"
                class="my-1.5 inline-flex items-center gap-2 rounded-lg bg-success-surface px-3 py-1.5 text-xs text-success border border-success-border"
            >
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-success"></span>
                <span>{{ $t('Saved. The changes have been successfully applied.') }}</span>
            </div>

            <!-- Verfügbarkeit -->
            <section class="space-y-3 rounded-xl border border-border-subtle bg-surface-sunken/80 px-3.5 py-3">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                            {{ $t('Availability') }}
                        </h3>
                        <p class="text-[11px] text-text-subtle mt-0.5">
                            {{ $t('Set a unified availability status for all selected cells.') }}
                        </p>
                    </div>
                </div>

                <Listbox as="div" v-model="multiEditCellForm.vacation_type" class="w-full relative mt-2">
                    <ListboxButton class="menu-button flex items-center justify-between">
                        <div class="flex items-center gap-2 truncate">
                            <span
                                class="inline-flex h-1.5 w-1.5 rounded-full"
                                :class="currentVacationDotClass"
                            ></span>
                            <span class="truncate">
                                {{ multiEditCellForm.vacation_type.name }}
                            </span>
                        </div>
                        <PropertyIcon name="IconChevronDown" class="h-5 w-5 text-text" aria-hidden="true" />
                    </ListboxButton>
                    <ListboxOptions
                        class="absolute mt-1 w-full z-10 bg-surface-inverse shadow-lg rounded-md max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm"
                    >
                        <ListboxOption
                            v-for="type in vacationTypes"
                            :key="type.type"
                            :value="type"
                            v-slot="{ selected }"
                            class="cursor-pointer rounded-md p-2 mb-0.5 flex justify-between items-center text-text-subtle"
                        >
                            <div class="flex items-center gap-2 truncate">
                                <span
                                    class="inline-flex h-1.5 w-1.5 rounded-full"
                                    :class="dotClassForVacationType(type)"
                                ></span>
                                <span :class="[selected ? 'text-sm/5 font-bold text-white' : 'text-sm/5 font-bold text-text-subtle', 'truncate']">
                                    {{ type.name }}
                                </span>
                            </div>
                            <PropertyIcon name="CheckIcon"
                                v-if="selected"
                                class="h-5 w-5 text-success"
                                aria-hidden="true"
                            />
                        </ListboxOption>
                    </ListboxOptions>
                </Listbox>

                <!-- Hinweis zu "Keine Änderung" -->
                <p class="text-[11px] text-text-subtle mt-1">
                    {{ $t('If you do not select a status, the availability will not be changed.') }}
                </p>
            </section>

            <!-- Individuelle Zeiten -->
            <section class="space-y-3 rounded-xl border border-border-subtle bg-white px-3.5 py-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                            {{ $t('Individual time') }}
                        </h4>
                        <p class="text-[11px] text-text-subtle mt-0.5">
                            {{ $t('Optional: define custom times that will be applied to all selected cells.') }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="hidden sm:inline-flex items-center gap-1 rounded-full border border-border-subtle bg-white px-2.5 py-1 text-[11px] text-text-muted hover:border-accent-700 hover:text-accent-700 transition-colors"
                        @click="addIndividualTime"
                    >
                        <PropertyIcon name="IconCirclePlus" class="h-3.5 w-3.5" stroke-width="2" />
                        <span>{{ $t('Add time') }}</span>
                    </button>
                </div>

                <div v-if="multiEditCellForm.individual_times.length" class="text-sm mt-2 text-sm/5 font-bold text-text-subtle">
                    <!-- Kopfzeile -->
                    <div class="hidden md:block text-[11px] text-text-subtle">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-1 px-1">
                            <span>{{ $t('Title') }}</span>
                            <span class="col-span-2">{{ $t('Period') }}</span>
                            <span class="text-right">{{ $t('Actions') }}</span>
                        </div>
                    </div>

                    <!-- Einträge -->
                    <div
                        v-for="(individual_time, index) in multiEditCellForm.individual_times"
                        :key="index"
                        class="mb-2 rounded-lg border border-border-subtle bg-surface-sunken px-3 py-3 group"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-1 items-center">
                            <BaseInput
                                id="title"
                                v-model="individual_time.title"
                                :label="$t('Title')"
                                :show-label="false"
                                no-margin-top
                            />
                            <div class="flex items-center col-span-2 gap-1">
                                <BaseInput
                                    type="time"
                                    id="start_time"
                                    v-model="individual_time.start_time"
                                    classes="rounded-r-none"
                                    :label="$t('Start time')"
                                    :show-label="false"
                                    no-margin-top
                                />
                                <BaseInput
                                    type="time"
                                    id="end_time"
                                    v-model="individual_time.end_time"
                                    classes="border-l-0 rounded-l-none"
                                    :label="$t('End time')"
                                    :show-label="false"
                                    no-margin-top
                                />
                            </div>
                            <div class="flex items-center justify-end">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-md p-1.5 text-text-subtle hover:text-danger hover:bg-danger-surface transition-colors"
                                    @click="deleteIndividualTimeByIndex(index)"
                                >
                                    <PropertyIcon name="IconTrash" class="h-4 w-4" stroke-width="1.5" />
                                </button>
                            </div>
                        </div>
                        <div v-if="individual_time.error" class="text-xs text-danger mt-1">
                            {{ individual_time.error }}
                        </div>
                    </div>

                    <!-- Mobile "Zeit hinzufügen" -->
                    <div class="mt-1 sm:hidden">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-xs text-sm/5 font-bold text-text-subtle hover:text-accent-700 transition-colors"
                            @click="addIndividualTime"
                        >
                            <PropertyIcon name="IconCirclePlus" class="h-5 w-5" stroke-width="2" />
                            <span>{{ $t('Add time') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Noch keine Zeiten -->
                <div
                    v-else
                    class="cursor-pointer mt-2"
                    @click="addIndividualTime"
                >
                    <div class="w-full px-3 py-4 bg-accent-500/8 hover:bg-accent-500/16 border border-dashed border-accent-200/70 rounded-lg transition-colors">
                        <AlertComponent
                            text="Es wurden noch keine Zeiten festgelegt. Klicke hier um Zeiten zu erstellen"
                            show-icon
                            icon-size="h-4 w-4"
                        />
                    </div>
                </div>
            </section>

            <!-- Projekt zuordnen -->
            <section class="space-y-3 rounded-xl border border-border-subtle bg-white px-3.5 py-3">
                <div>
                    <h4 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                        {{ $t('Assign project') }}
                    </h4>
                    <p class="text-[11px] text-text-subtle mt-0.5">
                        {{ $t('Optional: bindingly assign all selected persons to a project on the selected days. Only projects covering all selected days can be chosen.') }}
                    </p>
                </div>

                <div v-if="selectedAssignmentProject" class="flex items-center justify-between gap-2 rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: colorForProjectId(selectedAssignmentProject.id) }"></span>
                        <span class="truncate text-text">{{ selectedAssignmentProject.name }}</span>
                        <span v-if="selectedAssignmentProject.period_start" class="text-[11px] text-text-subtle shrink-0">
                            {{ formatAssignmentDate(selectedAssignmentProject.period_start) }} - {{ formatAssignmentDate(selectedAssignmentProject.period_end) }}
                        </span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md p-1.5 text-text-subtle hover:text-danger hover:bg-danger-surface transition-colors"
                        @click="selectAssignmentProject(null)"
                    >
                        <PropertyIcon name="IconX" class="h-4 w-4" stroke-width="1.5" />
                    </button>
                </div>

                <template v-else>
                    <BaseInput
                        id="cell-multi-edit-project-search"
                        v-model="assignmentProjectSearch"
                        :label="$t('Search project')"
                        :show-label="false"
                        :placeholder="$t('Search project')"
                        no-margin-top
                    />
                    <div class="max-h-44 overflow-y-auto rounded-lg border border-border-subtle divide-y divide-border-subtle">
                        <div v-if="loadingAssignmentProjects" class="px-3 py-2.5 text-xs text-text-subtle">
                            {{ $t('Loading...') }}
                        </div>
                        <div v-else-if="!assignmentProjectOptions.length" class="px-3 py-2.5 text-xs text-text-subtle">
                            {{ $t('No projects found') }}
                        </div>
                        <button
                            v-for="project in assignmentProjectOptions"
                            :key="project.id"
                            type="button"
                            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left transition-colors"
                            :class="project.covers_all_days ? 'hover:bg-surface-sunken' : 'text-text-subtle cursor-not-allowed'"
                            :disabled="!project.covers_all_days"
                            @click="selectAssignmentProject(project)"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: colorForProjectId(project.id) }"></span>
                                <span class="truncate text-text text-xs">{{ project.name }}</span>
                            </div>
                            <span class="shrink-0 text-[10px]" :class="project.covers_all_days ? 'text-text-subtle' : 'text-warning'">
                                <template v-if="project.covers_all_days">
                                    <template v-if="project.period_start">
                                        {{ formatAssignmentDate(project.period_start) }} - {{ formatAssignmentDate(project.period_end) }}
                                    </template>
                                </template>
                                <template v-else>
                                    {{ $t('covers {0} of {1} days', [coveredDayCount(project), selectedDayCount]) }}
                                </template>
                            </span>
                        </button>
                    </div>
                </template>
            </section>

            <!-- Kommentar -->
            <section class="space-y-3 rounded-xl border border-border-subtle bg-white px-3.5 py-3">
                <div>
                    <h4 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                        {{ $t('Comment') }}
                    </h4>
                    <p class="text-[11px] text-text-subtle mt-0.5">
                        {{ $t('Optional note that will be saved for all affected cells.') }}
                    </p>
                </div>
                <BaseTextarea
                    id="shift_comment"
                    v-model="multiEditCellForm.comment"
                    :show-label="false"
                    no-margin-top
                    label="Comment"
                />
            </section>

            <!-- Footer -->
            <div class="flex justify-end pt-2 border-t border-border-subtle">
                <BaseUIButton
                    :label="$t('Save')"
                    is-add-button
                    :disabled="multiEditCellForm.processing || checkingVacationImpact"
                    @click="submitForm"
                />
            </div>
        </div>

        <!-- Rückfrage: der neue Status löst Projektzuordnungen/-wünsche auf -->
        <ArtworkBaseModal
            v-if="showVacationImpactModal"
            :title="$t('Change availability status')"
            :description="$t('The new status dissolves project assignments of this person. Do you want to continue?')"
            @close="showVacationImpactModal = false"
        >
            <ul class="mt-4 space-y-1.5">
                <li
                    v-for="(entry, index) in vacationImpactAffected"
                    :key="`impact-${index}`"
                    class="flex items-center gap-2 rounded-lg border border-border-subtle bg-surface-sunken/70 px-3 py-2 text-xs text-text-muted"
                >
                    <span
                        class="inline-flex h-1.5 w-1.5 rounded-full"
                        :class="entry.type === 'binding' ? 'bg-danger' : 'bg-warning'"
                    ></span>
                    <span class="font-medium truncate">{{ entry.worker_name }}</span>
                    <span class="text-text-subtle truncate">· {{ entry.project_name }}</span>
                    <span class="text-text-subtle">
                        · {{ entry.type === 'binding' ? $t('Binding assignment') : $t('Project wish') }}
                    </span>
                    <span class="text-text-subtle">· {{ entry.dates.join(', ') }}</span>
                </li>
            </ul>
            <div class="flex justify-end gap-2 mt-6">
                <BaseUIButton type="button" variant="secondary" hide-icon @click="showVacationImpactModal = false">
                    {{ $t('Cancel') }}
                </BaseUIButton>
                <BaseUIButton
                    type="button"
                    variant="primary"
                    hide-icon
                    :disabled="multiEditCellForm.processing"
                    @click="confirmVacationImpactAndSubmit"
                >
                    {{ $t('Change status anyway') }}
                </BaseUIButton>
            </div>
        </ArtworkBaseModal>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { colorForProjectId, formatAssignmentDate } from '@/Composeables/UseProjectDayAssignments.js';

import AlertComponent from '@/Components/Alerts/AlertComponent.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const props = defineProps({
    multiEditCellByDayAndUser: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

const vacationTypes = ref([
    { name: 'Verfügbar', type: 'AVAILABLE' },
    { name: 'Arbeitsfreier Tag', type: 'OFF_WORK' },
    { name: 'Nicht Verfügbar', type: 'NOT_AVAILABLE' },
    { name: 'Frei', type: 'FREE_WORK' },
]);

const showSaveSuccess = ref(false);

const multiEditCellForm = useForm({
    comment: '',
    vacation_type: {
        name: 'Keine Änderung',
        type: null,
    },
    entities: props.multiEditCellByDayAndUser,
    individual_times: [],
    project_id: null,
});

// --- Projektzuordnung: nur Projekte wählbar, deren Zeitraum ALLE selektierten Tage abdeckt ---
const selectedDays = computed(() => {
    const days = Object.values(props.multiEditCellByDayAndUser ?? {}).flatMap(e => e.days ?? []);
    return [...new Set(days)].sort();
});
const selectedDayCount = computed(() => selectedDays.value.length);

const assignmentProjectSearch = ref('');
const assignmentProjectOptions = ref([]);
const loadingAssignmentProjects = ref(false);
const selectedAssignmentProject = ref(null);

let assignmentProjectDebounce = null;
let assignmentProjectRequest = null;
let assignmentProjectRequestSequence = 0;

async function loadAssignmentProjects() {
    if (!selectedDays.value.length) {
        assignmentProjectOptions.value = [];
        return;
    }
    const requestSequence = ++assignmentProjectRequestSequence;
    assignmentProjectRequest?.abort();
    assignmentProjectRequest = new AbortController();
    loadingAssignmentProjects.value = true;
    try {
        const { data } = await axios.get(route('project-day-assignments.projects'), {
            signal: assignmentProjectRequest.signal,
            params: {
                days: selectedDays.value,
                search: assignmentProjectSearch.value || undefined,
            },
        });
        if (requestSequence === assignmentProjectRequestSequence) {
            assignmentProjectOptions.value = data.projects ?? [];
        }
    } catch (error) {
        if (error?.code !== 'ERR_CANCELED' && requestSequence === assignmentProjectRequestSequence) {
            assignmentProjectOptions.value = [];
        }
    } finally {
        if (requestSequence === assignmentProjectRequestSequence) {
            loadingAssignmentProjects.value = false;
        }
    }
}

watch([assignmentProjectSearch, () => selectedDays.value.join(',')], () => {
    clearTimeout(assignmentProjectDebounce);
    assignmentProjectDebounce = setTimeout(loadAssignmentProjects, 300);
});

onMounted(loadAssignmentProjects);
onUnmounted(() => {
    clearTimeout(assignmentProjectDebounce);
    assignmentProjectRequest?.abort();
});

function selectAssignmentProject(project) {
    selectedAssignmentProject.value = project;
    multiEditCellForm.project_id = project?.id ?? null;
}

/** Wie viele der selektierten Tage im Projektzeitraum liegen (für den Disabled-Hinweis) */
function coveredDayCount(project) {
    if (!project.period_start || !project.period_end) return 0;
    return selectedDays.value.filter(d => d >= project.period_start && d <= project.period_end).length;
}

// Farbpunkt für aktuellen Vacation-Typ
const currentVacationDotClass = computed(() => {
    const type = multiEditCellForm.vacation_type?.type;
    if (type === 'AVAILABLE') return 'bg-success';
    if (type === 'OFF_WORK') return 'bg-warning';
    if (type === 'NOT_AVAILABLE') return 'bg-danger';
    return 'bg-border';
});

function dotClassForVacationType(type) {
    if (type.type === 'AVAILABLE') return 'bg-success';
    if (type.type === 'OFF_WORK') return 'bg-warning';
    if (type.type === 'NOT_AVAILABLE') return 'bg-danger';
    return 'bg-border';
}

const addIndividualTime = () => {
    multiEditCellForm.individual_times.push({
        title: '',
        start_time: '',
        end_time: '',
    });
};

const deleteIndividualTimeByIndex = (index) => {
    multiEditCellForm.individual_times.splice(index, 1);
};

// --- Rückfrage vor dem Speichern: löst der neue Status Projektzuordnungen auf? ---
const showVacationImpactModal = ref(false);
const vacationImpactAffected = ref([]);
const checkingVacationImpact = ref(false);
const DISSOLVING_VACATION_TYPES = ['FREE_WORK', 'OFF_WORK', 'NOT_AVAILABLE'];

const submitForm = async () => {
    const vacationType = multiEditCellForm.vacation_type?.type;

    if (vacationType && DISSOLVING_VACATION_TYPES.includes(vacationType) && !checkingVacationImpact.value) {
        checkingVacationImpact.value = true;
        try {
            // Dienstleister (type 2) ausnehmen: der Speicherpfad (updateUserCell)
            // schreibt für sie keine Verfügbarkeiten und löst nichts auf — sie
            // hier mitzuprüfen würde eine Auflösung ankündigen, die nie passiert.
            const workers = Object.values(props.multiEditCellByDayAndUser ?? {})
                .filter((entry) => Number(entry.type) !== 2)
                .map((entry) => ({
                    type: entry.type,
                    id: entry.id,
                    dates: entry.days ?? [],
                }));
            if (workers.length) {
                const { data } = await axios.post(route('project-day-assignments.vacation-impact'), {
                    workers,
                    vacation_type: vacationType,
                });

                if ((data.affected ?? []).length) {
                    vacationImpactAffected.value = data.affected;
                    showVacationImpactModal.value = true;
                    return;
                }
            }
        } catch (error) {
            // Precheck fehlgeschlagen: Speichern nicht blockieren
        } finally {
            checkingVacationImpact.value = false;
        }
    }

    doSubmit();
};

function confirmVacationImpactAndSubmit() {
    showVacationImpactModal.value = false;
    doSubmit();
}

const doSubmit = () => {
    multiEditCellForm.post(route('shift.plan.user.cell.update'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showSaveSuccess.value = true;
            emit('close', { saved: true });
        },
    });
};
</script>

<style scoped>
/* Optional: Zusätzliche Feintuning-Styles */
</style>
