<template>
    <h1 class="headline1 -my-4" style="font-size:18px;">{{ $t(exportTabEnum)}}</h1>
    <h2 class="text-sm text-gray-500 -mb-2">
        {{ $t('All Events are exported by given settings') }}
    </h2>
    <ExportTypeSlider @on-update="(bool) => exportForm.desiresTimespanExport = bool"/>
    <hr class="pt-2"/>
    <div v-if="!exportForm.desiresTimespanExport" class="flex flex-col gap-y-2">
        <ProjectSearch class="-mt-4"
                       @project-selected="addConditionalProject"/>
        <div class="flex flex-row flex-wrap mt-2.5 gap-y-0.5">
            <TagComponent v-for="conditionalProject in conditionalProjects"
                          :method="removeConditionalProject"
                          :property="conditionalProject.id"
                          :displayed-text="conditionalProject.name"/>
        </div>
    </div>
    <div v-else class="-mt-4 flex flex-row gap-x-2">
        <BaseInput type="date" id="startDate" v-model="conditionalDateStart" :label="$t('Start date')" class="-mt-4"/>
        <BaseInput type="date" id="endDate" v-model="conditionalDateEnd" :label="$t('End date')" class="-mt-4"/>
    </div>
    <span v-if="datesInvalid()" class="errorText !text-xs">
        {{ $t('Start date must not be after the end date!') }}
    </span>
    <hr class="pt-2"/>
    <div class="flex flex-row items-center">
        <h2 class="headline2 !text-sm">{{ $t('Filter') }}</h2>
        <ChevronDownIcon v-if="!showFilters" class="w-5 h-5 cursor-pointer" @click="showFilters = true"/>
        <ChevronUpIcon v-if="showFilters" class="w-5 h-5 cursor-pointer" @click="showFilters = false"/>
    </div>
    <div v-if="showFilters" class="grid grid-cols-3 gap-5">
        <template v-for="(filterDefinitions, filterCategory) in receivedFilters">
            <div v-if="filterCategory === 'roomCategories'" class="flex flex-col gap-y-0.5">
                <h3 class="headline3 !text-sm">{{ $t('Room categories') }}</h3>
                <template v-for="filter in filterDefinitions"
                          :key="filter.id">
                    <div class="flex flex-row items-center gap-x-1">
                        <input :id="'cb-' + filterCategory + '-' + filter.id"
                               v-model="exportForm.filter[filterCategory]"
                               :value="filter.id"
                               type="checkbox"
                               class="input-checklist p-0"/>
                        <label :for="'cb-' + filterCategory + '-' + filter.id"
                               :class="[exportForm.filter[filterCategory].includes(filter.id) ? '' : '', 'text-xs']"
                               class="text-secondary cursor-pointer hover:text-green-500">
                            {{ filter.name }}
                        </label>
                    </div>
                </template>
            </div>
            <div v-if="filterCategory === 'roomAttributes'" class="flex flex-col gap-y-0.5">
                <h3 class="headline3 !text-sm">{{ $t('Room properties') }}</h3>
                <template v-for="filter in filterDefinitions"
                          :key="filter.id">
                    <div class="flex flex-row items-center gap-x-1">
                        <input :id="'cb-' + filterCategory + '-' + filter.id"
                               v-model="exportForm.filter[filterCategory]"
                               :value="filter.id"
                               type="checkbox"
                               class="input-checklist p-0"/>
                        <label :for="'cb-' + filterCategory + '-' + filter.id"
                               :class="[exportForm.filter[filterCategory].includes(filter.id) ? '' : '', 'text-xs']"
                               class="text-secondary cursor-pointer hover:text-green-500">
                            {{ filter.name }}
                        </label>
                    </div>
                </template>
            </div>
            <div v-if="filterCategory === 'eventTypes'" class="flex flex-col gap-y-0.5">
                <h3 class="headline3 !text-sm">{{ $t('Event Types') }}</h3>
                <div class="grid grid-cols-2 gap-y-0.5 gap-x-2">
                <template v-for="filter in filterDefinitions"
                          :key="filter.id">
                    <div class="flex flex-row items-center gap-x-1">
                        <input :id="'cb-' + filterCategory + '-' + filter.id"
                               v-model="exportForm.filter[filterCategory]"
                               :value="filter.id"
                               type="checkbox"
                               class="input-checklist p-0"/>
                        <label :for="'cb-' + filterCategory + '-' + filter.id"
                               :class="[exportForm.filter[filterCategory].includes(filter.id) ? '' : '', 'text-xs']"
                               class="text-secondary cursor-pointer hover:text-green-500">
                            {{ filter.name }}
                        </label>
                    </div>
                </template>
                <template v-for="(translationKey, key) in receivedFilters['eventAttributes']">
                    <div class="flex flex-row items-center gap-x-1">
                        <input :id="'cb-' + translationKey"
                               v-model="exportForm.filter['eventAttributes']"
                               :value="key"
                               type="checkbox"
                               class="input-checklist p-0"/>
                        <label :for="'cb-' + translationKey"
                               :class="[exportForm.filter['eventAttributes'].includes(key) ? '' : '', 'text-xs']"
                               class="text-secondary cursor-pointer hover:text-green-500">
                            {{ $t(translationKey) }}
                        </label>
                    </div>
                </template>
                </div>
            </div>
            <div v-if="filterCategory === 'areas'" class="flex flex-col gap-y-0.5">
                <h3 class="headline3 !text-sm">{{ $t('Areas') }}</h3>
                <template v-for="filter in filterDefinitions"
                          :key="filter.id">
                    <div class="flex flex-row items-center gap-x-1">
                        <input :id="'cb-' + filterCategory + '-' + filter.id"
                               v-model="exportForm.filter[filterCategory]"
                               :value="filter.id"
                               type="checkbox"
                               class="input-checklist p-0"/>
                        <label :for="'cb-' + filterCategory + '-' + filter.id"
                               :class="[exportForm.filter[filterCategory].includes(filter.id) ? '' : '', 'text-xs']"
                               class="text-secondary cursor-pointer hover:text-green-500">
                            {{ filter.name }}
                        </label>
                    </div>
                </template>
            </div>
            <div v-if="filterCategory === 'rooms'" class="flex flex-col gap-y-0.5">
                <h3 class="headline3 !text-sm">{{ $t('Rooms') }}</h3>
                <template v-for="filter in filterDefinitions"
                          :key="filter.id">
                    <div class="flex flex-row items-center gap-x-1">
                        <input :id="'cb-' + filterCategory + '-' + filter.id"
                               v-model="exportForm.filter[filterCategory]"
                               :value="filter.id"
                               type="checkbox"
                               class="input-checklist p-0"/>
                        <label :for="'cb-' + filterCategory + '-' + filter.id"
                               :class="[exportForm.filter[filterCategory].includes(filter.id) ? '' : '', 'text-xs']"
                               class="text-secondary cursor-pointer hover:text-green-500">
                            {{ filter.name }}
                        </label>
                    </div>
                </template>
            </div>
        </template>
    </div>
    <template v-if="isExcelEventListExport()">
        <div class="flex flex-row items-center">
            <h2 class="headline2 !text-sm">{{ $t('Columns') }}</h2>
            <ChevronDownIcon v-if="!showColumns" class="w-5 h-5 cursor-pointer" @click="showColumns = true"/>
            <ChevronUpIcon v-if="showColumns" class="w-5 h-5 cursor-pointer" @click="showColumns = false"/>
        </div>
        <div v-if="showColumns" class="flex flex-col gap-y-0.5">
            <template v-for="(translationKey, column) in availableColumns">
                <div v-if="column !== 'artists' || showArtists" class="flex flex-row items-center gap-x-1">
                    <input :id="'cb-' + column"
                           v-model="exportForm.desiredColumns"
                           :value="column"
                           type="checkbox"
                           class="input-checklist p-0"/>
                    <label :for="'cb-' + column"
                           :class="[exportForm.desiredColumns.includes(column) ? '' : '', 'text-xs']"
                           class="text-secondary cursor-pointer hover:text-green-500">
                        {{ $t(translationKey) }}
                    </label>
                </div>
            </template>
        </div>
    </template>
    <BaseButton :disabled="computedValidation"
                class="mt-4 w-40 gap-x-2 self-center justify-center"
                @click="initializeDownload()"
                :text="$t('Export')">
        <DocumentReportIcon class="h-4 w-4"/>
    </BaseButton>
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
