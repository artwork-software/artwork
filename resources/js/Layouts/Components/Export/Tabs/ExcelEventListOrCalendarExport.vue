<template>
    <div class="mx-auto w-full max-w-5xl">
        <div class="flex flex-col space-y-6">

            <!-- Kopfbereich -->
            <section class="">
                <h1 class="text-lg font-semibold text-zinc-900">
                    {{ $t(exportTabEnum) }}
                </h1>
                <p class="mt-1 text-sm text-zinc-600">
                    {{ $t('All Events are exported by given settings') }}
                </p>
            </section>

            <!-- Export-Typ (Zeitraum / Projekte) -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <ExportTypeSlider @on-update="(bool) => exportForm.desiresTimespanExport = bool" />

                <!-- Projekte -->
                <div v-if="!exportForm.desiresTimespanExport" class="space-y-3">
                    <ProjectSearch
                        class="-mt-2"
                        @project-selected="addConditionalProject"
                    />
                    <div class="flex flex-row flex-wrap gap-1.5">
                        <TagComponent
                            v-for="conditionalProject in conditionalProjects"
                            :key="conditionalProject.id"
                            :method="removeConditionalProject"
                            :property="conditionalProject.id"
                            :displayed-text="conditionalProject.name"
                        />
                    </div>

                    <div class="pt-1">
                        <LastedProjects :limit="10" @select="addConditionalProject" />
                    </div>
                </div>

                <!-- Zeitraum -->
                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput
                        type="date"
                        id="startDate"
                        v-model="conditionalDateStart"
                        :label="$t('Start date')"
                    />
                    <BaseInput
                        type="date"
                        id="endDate"
                        v-model="conditionalDateEnd"
                        :label="$t('End date')"
                    />
                    <p v-if="datesInvalid()" class="col-span-full text-xs text-red-600">
                        {{ $t('Start date must not be after the end date!') }}
                    </p>
                </div>
            </section>

            <!-- Filter -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-zinc-900">{{ $t('Filter') }}</h2>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 text-sm text-zinc-600 hover:text-zinc-900"
                        @click="showFilters = !showFilters"
                    >
                        <span v-if="!showFilters">{{ $t('Show') }}</span>
                        <span v-else>{{ $t('Hide') }}</span>
                        <ChevronDownIcon v-if="!showFilters" class="h-5 w-5" />
                        <ChevronUpIcon v-else class="h-5 w-5" />
                    </button>
                </div>

                <div v-if="showFilters" class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <!-- Room Categories -->
                    <div v-if="receivedFilters['roomCategories']" class="space-y-2">
                        <h3 class="text-sm font-medium text-zinc-900">{{ $t('Room categories') }}</h3>
                        <div class="space-y-1.5">
                            <template v-for="filter in receivedFilters['roomCategories']" :key="filter.id">
                                <label class="flex items-center gap-2 text-xs text-zinc-700 cursor-pointer">
                                    <input
                                        class="input-checklist"
                                        type="checkbox"
                                        :id="`cb-roomCategories-${filter.id}`"
                                        v-model="exportForm.filter.roomCategories"
                                        :value="filter.id"
                                    />
                                    <span>{{ filter.name }}</span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Room Attributes -->
                    <div v-if="receivedFilters['roomAttributes']" class="space-y-2">
                        <h3 class="text-sm font-medium text-zinc-900">{{ $t('Room properties') }}</h3>
                        <div class="space-y-1.5">
                            <template v-for="filter in receivedFilters['roomAttributes']" :key="filter.id">
                                <label class="flex items-center gap-2 text-xs text-zinc-700 cursor-pointer">
                                    <input
                                        class="input-checklist"
                                        type="checkbox"
                                        :id="`cb-roomAttributes-${filter.id}`"
                                        v-model="exportForm.filter.roomAttributes"
                                        :value="filter.id"
                                    />
                                    <span>{{ filter.name }}</span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Event Types + Event Attributes -->
                    <div v-if="receivedFilters['eventTypes']" class="space-y-2">
                        <h3 class="text-sm font-medium text-zinc-900">{{ $t('Event Types') }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 sm:gap-x-3">
                            <template v-for="filter in receivedFilters['eventTypes']" :key="filter.id">
                                <label class="flex items-center gap-2 text-xs text-zinc-700 cursor-pointer">
                                    <input
                                        class="input-checklist"
                                        type="checkbox"
                                        :id="`cb-eventTypes-${filter.id}`"
                                        v-model="exportForm.filter.eventTypes"
                                        :value="filter.id"
                                    />
                                    <span>{{ filter.name }}</span>
                                </label>
                            </template>

                            <template v-for="(translationKey, key) in receivedFilters['eventAttributes']" :key="key">
                                <label class="flex items-center gap-2 text-xs text-zinc-700 cursor-pointer">
                                    <input
                                        class="input-checklist"
                                        type="checkbox"
                                        :id="`cb-${translationKey}`"
                                        v-model="exportForm.filter.eventAttributes"
                                        :value="key"
                                    />
                                    <span>{{ $t(translationKey) }}</span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Areas -->
                    <div v-if="receivedFilters['areas']" class="space-y-2">
                        <h3 class="text-sm font-medium text-zinc-900">{{ $t('Areas') }}</h3>
                        <div class="space-y-1.5">
                            <template v-for="filter in receivedFilters['areas']" :key="filter.id">
                                <label class="flex items-center gap-2 text-xs text-zinc-700 cursor-pointer">
                                    <input
                                        class="input-checklist"
                                        type="checkbox"
                                        :id="`cb-areas-${filter.id}`"
                                        v-model="exportForm.filter.areas"
                                        :value="filter.id"
                                    />
                                    <span>{{ filter.name }}</span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Rooms -->
                    <div v-if="receivedFilters['rooms']" class="space-y-2">
                        <h3 class="text-sm font-medium text-zinc-900">{{ $t('Rooms') }}</h3>
                        <div class="space-y-1.5">
                            <template v-for="filter in receivedFilters['rooms']" :key="filter.id">
                                <label class="flex items-center gap-2 text-xs text-zinc-700 cursor-pointer">
                                    <input
                                        class="input-checklist"
                                        type="checkbox"
                                        :id="`cb-rooms-${filter.id}`"
                                        v-model="exportForm.filter.rooms"
                                        :value="filter.id"
                                    />
                                    <span>{{ filter.name }}</span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Spalten (nur Event-List-Export) -->
            <section v-if="isExcelEventListExport()" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-zinc-900">{{ $t('Columns') }}</h2>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 text-sm text-zinc-600 hover:text-zinc-900"
                        @click="showColumns = !showColumns"
                    >
                        <span v-if="!showColumns">{{ $t('Show') }}</span>
                        <span v-else>{{ $t('Hide') }}</span>
                        <ChevronDownIcon v-if="!showColumns" class="h-5 w-5" />
                        <ChevronUpIcon v-else class="h-5 w-5" />
                    </button>
                </div>

                <div v-if="showColumns" class="grid grid-cols-1 sm:grid-cols-2 gap-y-2">
                    <template v-for="(translationKey, column) in availableColumns" :key="column">
                        <div v-if="column !== 'artists' || showArtists" class="flex items-center gap-2">
                            <input
                                :id="`cb-${column}`"
                                v-model="exportForm.desiredColumns"
                                :value="column"
                                type="checkbox"
                                class="input-checklist"
                            />
                            <label
                                :for="`cb-${column}`"
                                class="text-xs text-zinc-700 cursor-pointer hover:text-green-600"
                            >
                                {{ $t(translationKey) }}
                            </label>
                        </div>
                    </template>
                </div>
            </section>

            <section class="flex items-center justify-end">
                <BaseUIButton
                    @click="initializeDownload()"
                    :label="$t('Export')"
                    icon="IconFileExport"
                    :disabled="computedValidation"
                    is-add-button
                />
            </section>

        </div>
    </div>
</template>

<script setup>
import ExportTypeSlider from "@/Layouts/Components/Export/Components/ExportTypeSlider.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import {useForm} from "@inertiajs/vue3";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import {ChevronDownIcon, ChevronUpIcon, DocumentReportIcon} from "@heroicons/vue/outline";
import {computed, ref} from "vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import Input from "@/Jetstream/Input.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import {useExportTabEnums} from "@/Layouts/Components/Export/Enums/ExportTabEnum.js";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const receivedFilters = ref([]);
axios.get(route('calendar.filters')).then((response) => receivedFilters.value = response.data);

const props = defineProps({
        projectPreselect: {
            type: Object,
            default: null,
            required: false
        },
        exportTabEnum: {
            type: String,
            required: true
        },
        showArtists: {
            type: Boolean,
            default: false,
        },
    }),
    exportTabEnums = useExportTabEnums(),
    $t = useTranslation(),
    emits = defineEmits(['close']),
    availableColumns = ref({
        event_id: 'Event ID',
        project_name: 'Project name',
        artists: 'Artists',
        start_date: 'Start date',
        end_date: 'End date',
        start_time: 'Start time',
        end_time: 'End time',
        event_type: 'Event type',
        event_name: 'Event name',
        event_description: 'Event description',
        event_status: 'Event Status',
        room: 'Room',
        project_team: 'Project team',
        project_properties: 'Project properties'
    }),
    isExcelEventListExport = () => {
        return props.exportTabEnum === exportTabEnums.EXCEL_EVENT_LIST_EXPORT;
    },
    exportForm = useForm({
        desiresTimespanExport: false,
        conditional: {},
        filter: {
            roomCategories: [],
            roomAttributes: [],
            eventTypes: [],
            eventAttributes: [],
            areas: [],
            rooms: []
        },
        desiresEventListExport: isExcelEventListExport(),
        desiredColumns: Object.keys(availableColumns.value).filter(
            (column) => column !== 'artists' || props.showArtists
        )
    }),
    conditionalDateStart = ref(''),
    conditionalDateEnd = ref(''),
    conditionalProjects = ref(
        props.projectPreselect ?
            [
                {
                    id: props.projectPreselect.id,
                    name: props.projectPreselect.name
                }
            ] :
            []
    ),
    showFilters = ref(false),
    showColumns = ref(false),
    computedValidation = computed(() => {
        if (exportForm.desiresTimespanExport) {
            return conditionalDateStart.value.length !== 10 ||
                conditionalDateEnd.value.length !== 10 ||
                datesInvalid();
        } else {
            return conditionalProjects.value.length === 0;
        }
    }),
    addConditionalProject = (project) => {
        if (conditionalProjects.value.findIndex((conditionalProject) => conditionalProject.id === project.id) > -1) {
            return;
        }

        conditionalProjects.value.push(project);
    },
    removeConditionalProject = (projectId) => {
        conditionalProjects.value.splice(
            conditionalProjects.value.findIndex((project) => project.id === projectId),
            1
        );
    },
    datesInvalid = () => {
        return exportForm.desiresTimespanExport &&
            conditionalDateStart.value.length > 0 &&
            conditionalDateEnd.value.length > 0 &&
            (
                (conditionalDateStart.value.length < 10 || conditionalDateEnd.value.length < 10) ||
                Date.parse(conditionalDateStart.value) > Date.parse(conditionalDateEnd.value)
            );
    },
    initializeDownload = () => {
        if (exportForm.desiresTimespanExport) {
            exportForm.conditional.dateStart = conditionalDateStart;
            exportForm.conditional.dateEnd = conditionalDateEnd;

            delete exportForm.conditional.projects;
        } else {
            exportForm.conditional.projects = conditionalProjects.value.map((project) => project.id);

            delete exportForm.conditional.dateStart;
            delete exportForm.conditional.dateEnd;
        }

        if (!isExcelEventListExport()) {
            delete exportForm.desiredColumns;
        }

        axios.post(route('export.cache-filter'), exportForm.data())
            .then((response) => {
                window.open(
                    route(
                        isExcelEventListExport() ?
                            'export.download-event-list-xlsx' :
                            'export.download-calendar-xlsx',
                        {
                            cacheToken: response.data
                        }
                    ),
                    '_blank',
                    'noopener'
                );
            }).catch(() => console.error($t('Export could not be created. Please try again.')));
};
</script>
