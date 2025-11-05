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

                <div v-if="showFilters">
                    <section class="">
                        <div class="mb-4 pb-4 border-b-2 border-dashed border-gray-300">
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                <div
                                    v-for="(filter, index) in activeFilters"
                                    :key="`${filter.id}-${filter.value ?? ''}-${index}`"
                                    class="group block cursor-pointer shrink-0 bg-blue-50 w-fit px-2 py-1.5 rounded-full border border-blue-200"
                                >
                                    <div class="flex items-center">
                                        <div class="mx-2">
                                            <p class="text-blue-500 text-xs group-hover:text-blue-600">
                                                <span v-if="filter.id === 'adjoiningNoAudience' || filter.id === 'adjoiningNotLoud'">{{ $t(filter?.name)}}</span>
                                                <span v-else>{{ filter?.name }}</span>
                                            </p>
                                        </div>
                                        <div class="flex items-center">
                                            <button type="button" @click="removeActiveFilter(filter)">
                                                <component :is="IconX" class="size-4 text-blue-500 hover:text-error" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div v-for="(filterMainCategory, mainKey) in filteredOptionsByCategories" :key="mainKey" class="py-1">
                                <div class="text-white bg-gray-900 rounded-lg px-4 py-2 font-lexend shadow text-sm">
                                    {{ $t(mainKey) }}
                                </div>

                                <div class="space-y-2 mt-2">
                                    <div v-for="(filterSubCategory, subKey) in filterMainCategory" :key="subKey">
                                        <div class="card white px-4 ">
                                            <div class="flex items-center select-none justify-between duration-200 ease-in-out cursor-pointer py-3" @click="toggleOpen(mainKey, subKey)">
                                                <div class="text-sm text-gray-900">
                                                    {{ $t(subKey) }}
                                                </div>
                                                <div class="flex items-center gap-5">
                                            <span
                                                class="inline-flex items-center rounded-lg bg-green-50 px-2 py-1 text-xs/4 text-green-600 ring-1 ring-inset ring-green-500/10"
                                                :class="filterSubCategory.filter(filter => filter.checked).length > 0 ? 'visible' : 'invisible'"
                                            >
                                                {{ filterSubCategory.filter(filter => filter.checked).length }} {{ $t('selected') }}
                                            </span>
                                                    <component :is="IconChevronDown" class="w-4 h-4 text-gray-400" :class="isOpen(mainKey, subKey) ? 'rotate-180' : ''" />
                                                </div>
                                            </div>

                                            <div v-if="isOpen(mainKey, subKey)">
                                                <div class="grid gird-cols-1 md:grid-cols-4 gap-4 my-3">
                                                    <div v-for="(filter, index) in filterSubCategory" :key="index">
                                                        <div class="flex items-center gap-x-2">
                                                            <div class="flex h-6 shrink-0 items-center">
                                                                <div class="group grid size-4 grid-cols-1">
                                                                    <input
                                                                        v-model="filter.checked"
                                                                        :id="removeSpaceFromKey(filter.name)"
                                                                        :aria-describedby="removeSpaceFromKey(filter.name) + '-description'"
                                                                        :name="removeSpaceFromKey(filter.name)"
                                                                        type="checkbox"
                                                                        class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                                                    />
                                                                    <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                            <div class="text-sm flex items-center gap-x-1">
                                                                <div v-if="filter.icon" class="flex items-center gap-2">
                                                                    <component :is="filter.icon" class="size-4" stroke-width="1.5"/>
                                                                </div>
                                                                <label :for="removeSpaceFromKey(filter.name)" class="text-gray-900">
                                                                    {{ filter.name }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div> <!-- card -->
                                    </div> <!-- each sub -->
                                </div>
                            </div> <!-- each main -->
                        </div>
                    </section>
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
import {useForm, usePage} from "@inertiajs/vue3";
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
import {IconChevronDown, IconX} from "@tabler/icons-vue";

// Local open/close state per subcategory to avoid mutating computed arrays
const openState = ref({});
const keyFor = (mainKey, subKey) => `${mainKey}::${subKey}`;
const isOpen = (mainKey, subKey) => !!openState.value[keyFor(mainKey, subKey)];
const toggleOpen = (mainKey, subKey) => {
    const k = keyFor(mainKey, subKey);
    openState.value[k] = !openState.value[k];
};


const activeFilters = computed(() => {
    const list = [];
    const cats = filteredOptionsByCategories.value;
    Object.keys(cats).forEach(category => {
        Object.keys(cats[category]).forEach(subCategory => {
            list.push(...cats[category][subCategory].filter((f) => f.checked));
        })
    })
    return list;
})

const filteredOptionsByCategories = computed(() => {
    const roomFilters = Object.keys(usePage().props.filterOptions).filter((key) => key.includes('room'));
    const eventFilters = Object.keys(usePage().props.filterOptions).filter((key) => key.includes('event'));
    const areaFilters = Object.keys(usePage().props.filterOptions).filter((key) => key.includes('area'));

    const filteredOptions = {
        roomFilters: {},
        areaFilters: {},
        eventFilters: {},
    }

    // Areas unverändert
    areaFilters.forEach((filter) => {
        filteredOptions.areaFilters[filter] = usePage().props.filterOptions[filter] || [];
    })

    // Rooms: nur tatsächliche Raumliste filtern
    roomFilters.forEach((filter) => {
        const list = usePage().props.filterOptions[filter] || [];
        if (filter === 'rooms' || filter === 'room_ids') {
            filteredOptions.roomFilters[filter] = list.filter((item) => {
                const rel = item?.relevant_for_disposition;
                return !(rel === false || rel === 0 || rel === '0');
            });
        } else {
            filteredOptions.roomFilters[filter] = list;
        }
    })

    // Events unverändert
    eventFilters.forEach((filter) => {
        filteredOptions.eventFilters[filter] = usePage().props.filterOptions[filter] || [];
    })

    return filteredOptions;
})

const extractCheckedIds = (filterGroup) => {
    const result = {};
    Object.entries(filteredOptionsByCategories.value[filterGroup]).forEach(([key, list]) => {
        const checked = list.filter(item => item.checked).map(item => item.id);
        result[key] = checked.length > 0 ? checked : [];
    });
    return result;
};

const removeSpaceFromKey = (key) => key.replace(/\s/g, '')

// **Fix:** keine gruppenübergreifende Suche – direkt das referenzierte Objekt deaktivieren
const removeActiveFilter = (filterToRemove) => {
    filterToRemove.checked = false;
};

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
        filter: {},
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
            exportForm.conditional.dateStart = conditionalDateStart.value;
            exportForm.conditional.dateEnd = conditionalDateEnd.value;

            delete exportForm.conditional.projects;
        } else {
            exportForm.conditional.projects = conditionalProjects.value.map((project) => project.id);

            delete exportForm.conditional.dateStart;
            delete exportForm.conditional.dateEnd;
        }

        if (!isExcelEventListExport()) {
            delete exportForm.desiredColumns;
        }


        const data = {};

        Object.assign(data, extractCheckedIds('roomFilters'));
        Object.assign(data, extractCheckedIds('areaFilters'));
        Object.assign(data, extractCheckedIds('eventFilters'));

        // Normalize: accept room_ids alias by merging into rooms and removing alias
        if (Array.isArray(data.room_ids)) {
            const existingRooms = Array.isArray(data.rooms) ? data.rooms : [];
            data.rooms = Array.from(new Set([...existingRooms, ...data.room_ids]));
            delete data.room_ids;
        } else {
            // Ensure rooms key exists as an array for backend expectations
            data.rooms = Array.isArray(data.rooms) ? data.rooms : [];
        }

        // Map alias *_ids keys coming from FilterService to backend-expected keys
        const aliasMap = {
            room_ids: 'rooms',
            area_ids: 'areas',
            room_category_ids: 'roomCategories',
            room_attribute_ids: 'roomAttributes',
            event_type_ids: 'eventTypes',
            event_property_ids: 'eventProperties',
        };
        Object.entries(aliasMap).forEach(([alias, target]) => {
            if (Array.isArray(data[alias])) {
                const existing = Array.isArray(data[target]) ? data[target] : [];
                data[target] = Array.from(new Set([...(existing), ...data[alias]]));
                delete data[alias];
            }
        });

        // Ensure expected filter keys exist as arrays to avoid undefined indexes on backend
        ['rooms', 'areas', 'roomCategories', 'roomAttributes', 'eventTypes', 'eventProperties'].forEach((k) => {
            if (!Array.isArray(data[k])) data[k] = [];
        });

        exportForm.filter = data;

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
