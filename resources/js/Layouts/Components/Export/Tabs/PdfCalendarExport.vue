<template>
    <div class="mx-auto w-full max-w-4xl">
        <div class="flex flex-col space-y-6">
            <!-- Titel + Hinweis -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <BaseInput id="title" v-model="pdf.title" :label="$t('Heading')" />

                <div
                    v-if="showModalInformation"
                    class="rounded-xl border border-blue-200 bg-blue-50/70 p-4"
                >
                    <div class="flex items-start gap-x-3">
                        <PropertyIcon name="IconExclamationCircle" class="size-5 min-h-5 min-w-5 text-blue-500"/>
                        <p class="text-sm text-blue-500">
                            {{
                                $t(
                                    'If a project is specified, the project period is used for the export. If no project is specified, you can either specify the start and end date yourself or your current calendar start and end date will be used automatically.'
                                )
                            }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="mt-2 block w-fit text-xs text-red-500 underline"
                        @click="showModalInformation = false"
                    >
                        {{ $t('Close note') }}
                    </button>
                </div>
            </section>

            <!-- Projektwahl / Zeitraum -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4" :class="pdfSelectedProject ? 'pt-3' : 'pt-8'">
                    <ProjectSearch
                        v-if="!pdfSelectedProject"
                        @project-selected="addProjectToPdf"
                        no-project-groups
                        get-first-last-event
                    />
                    <div v-else class="flex flex-col gap-2">
                        <h3 class="text-base font-semibold text-zinc-900">
                            {{ pdfSelectedProject.name }}
                        </h3>
                        <p
                            class="text-sm text-zinc-700"
                            v-if="pdfSelectedProject?.first_event && pdfSelectedProject?.last_event"
                        >
                            {{ $t('Project period') }}:
                            <span class="text-blue-700" v-if="pdfSelectedProject.first_event.start_time">
                              {{ pdfSelectedProject.first_event.start_time }}
                            </span>
                            –
                            <span class="text-blue-700" v-if="pdfSelectedProject.last_event.end_time">
                              {{ pdfSelectedProject.last_event.end_time }}
                            </span>
                        </p>
                        <p v-else class="text-sm text-zinc-700">
                            {{ $t('Project period') }}:
                            <span class="text-blue-700" v-if="pdfSelectedProject.firstEventStart">
                              {{ pdfSelectedProject.firstEventStart }}
                            </span>
                            –
                            <span class="text-blue-700" v-if="pdfSelectedProject.lastEventEnd">
                              {{ pdfSelectedProject.lastEventEnd }}
                            </span>
                        </p>
                        <button
                            type="button"
                            class="w-fit text-left text-xs text-blue-700 underline"
                            @click="pdfSelectedProject = null"
                        >
                            {{ $t('Cancel project selection') }}
                        </button>
                    </div>

                    <div class="mt-4">
                        <LastedProjects :limit="10" @select="addProjectToPdf" />
                    </div>
                </div>

                <!-- Zeitraum nur wenn kein Projekt -->
                <div v-if="!pdfSelectedProject" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput type="date" v-model="pdf.start" :label="$t('Start date')" id="start" />
                    <BaseInput type="date" v-model="pdf.end" :label="$t('End date')" id="end" />
                </div>
            </section>

            <!-- Filter -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
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
                                        <div class="flex items-center gap-3">
                                            <div class="hidden md:flex items-center gap-2 mr-2">
                                                <button
                                                    type="button"
                                                    class="text-[11px] text-artwork-buttons-create hover:text-artwork-buttons-hover cursor-pointer"
                                                    @click.stop="selectAllInSubcategory(mainKey, subKey)"
                                                >
                                                    {{ $t('Select all') }}
                                                </button>
                                                <span class="text-zinc-300 text-xs">•</span>
                                                <button
                                                    type="button"
                                                    class="text-[11px] text-artwork-buttons-create hover:text-artwork-buttons-hover cursor-pointer"
                                                    @click.stop="deselectAllInSubcategory(mainKey, subKey)"
                                                >
                                                    {{ $t('Deselect all') }}
                                                </button>
                                            </div>
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

            <!-- Papierformat + Orientierung + DPI -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Papierformat -->
                    <div class="space-y-2">
                        <Listbox as="div" v-model="selectedPaperSize">
                            <ListboxLabel class="block text-sm font-medium text-zinc-700">
                                {{ $t('Paper size') }}
                            </ListboxLabel>
                            <div class="relative mt-1">
                                <ListboxButton
                                    class="relative w-full cursor-pointer rounded-xl border border-zinc-200 bg-white px-4 py-3 text-left text-sm hover:bg-zinc-50"
                                >
                                    <div class="block truncate">{{ selectedPaperSize.name }}</div>
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <svg class="h-5 w-5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                        </svg>
                                    </span>
                                </ListboxButton>
                                <transition
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <ListboxOptions
                                        class="absolute z-10 mt-2 max-h-60 w-full overflow-auto rounded-xl bg-white py-2 text-sm shadow-lg ring-1 ring-black/10 focus:outline-none"
                                    >
                                        <ListboxOption
                                            v-for="paperSize in paperSizes"
                                            :key="paperSize.id"
                                            :value="paperSize"
                                            v-slot="{ active, selected }"
                                            as="template"
                                        >
                                            <li
                                                :class="[
                                                    'relative cursor-pointer select-none py-2 pl-3 pr-9',
                                                    active ? 'bg-zinc-900 text-white' : 'text-zinc-900'
                                                ]"
                                            >
                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                                    {{ paperSize.name }}
                                                </span>
                                                <span
                                                    v-if="selected"
                                                    :class="[
                                                        active ? 'text-white' : 'text-zinc-900',
                                                        'absolute inset-y-0 right-0 flex items-center pr-4'
                                                    ]"
                                                >
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                              d="M4.5 12.75l6 6 9-13.5" />
                                                    </svg>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                    </div>

                    <!-- Orientierung -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-zinc-700">
                            {{ $t('Paper orientation') }}
                        </label>
                        <fieldset>
                            <legend class="sr-only">Paper orientation</legend>
                            <div class="flex gap-3">
                                <div
                                    v-for="paperOrientation in paperOrientations"
                                    :key="paperOrientation.id"
                                    class="relative flex-1"
                                >
                                    <input
                                        :id="paperOrientation.id"
                                        name="notification-method"
                                        type="radio"
                                        :checked="paperOrientation.id === checkedOrientation"
                                        class="peer absolute inset-0 h-0 w-0 opacity-0"
                                        :disabled="orientationDisabled"
                                        @change="changePaperOrientation(paperOrientation)"
                                    />
                                    <label
                                        :for="paperOrientation.id"
                                        class="block cursor-pointer rounded-xl border px-4 py-3 text-sm transition
                                        peer-checked:border-zinc-900 peer-checked:bg-zinc-900 peer-checked:text-white
                                        border-zinc-200 bg-white text-zinc-800 hover:bg-zinc-50"
                                        :class="orientationDisabled ? 'opacity-60 cursor-not-allowed' : ''"
                                    >
                                        {{ paperOrientation.title }}
                                    </label>
                                </div>
                            </div>
                            <span class="mt-2 block text-xs text-red-500" v-if="orientationDisabled">
                                {{ $t('The A6 format is only possible in landscape format.') }}
                            </span>
                        </fieldset>
                    </div>

                    <!-- DPI / weitere Optionen -->
                    <div>
                        <BaseInput
                            id="dpi"
                            v-model="pdf.dpi"
                            :label="$t('Resolution (DPI) (Standard: 72) (Maximum: 300)')"
                        />
                    </div>
                    <div>
                        <BaseInput
                            id="daysPerPage"
                            v-model="pdf.daysPerPage"
                            :label="$t('Tage pro Seite (Standard: 5)')"
                        />
                    </div>
                    <div>
                        <BaseInput
                            id="roomsPerPage"
                            v-model="pdf.roomsPerPage"
                            :label="$t('Räume pro Seite (Standard: 8)')"
                        />
                    </div>
                </div>
            </section>

            <!-- Export -->
            <section class="flex items-center justify-end">
                <BaseUIButton
                    @click="createPdf()"
                    :label="$t('Export PDF')"
                    icon="IconFileExport"
                    is-add-button
                />
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import {computed, ref, watch} from 'vue'
import {useForm, usePage} from '@inertiajs/vue3'
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import NumberInputComponent from '@/Components/Inputs/NumberInputComponent.vue'
import ProjectSearch from '@/Components/SearchBars/ProjectSearch.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import LastedProjects from '@/Artwork/LastedProjects.vue'
import { useTranslation } from '@/Composeables/Translation.js'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import {IconChevronDown, IconX} from "@tabler/icons-vue";

const $t = useTranslation()
const emits = defineEmits<{ (e: 'closed', value: boolean): void }>()
const props = defineProps<{ pdfTitle?: string; project?: any }>()

const showModalInformation = ref(true)

const paperSizes = [
    { id: 'a4', name: 'A4 (Standard)' },
    { id: 'a3', name: 'A3' },
    { id: 'a5', name: 'A5' },
    { id: 'a6', name: 'A6' }
]
const paperOrientations = [
    { id: 'portrait', title: $t('Portrait format') },
    { id: 'landscape', title: $t('Landscape format') }
]

// Local open/close state per subcategory to avoid mutating computed arrays
const openState = ref<Record<string, boolean>>({});
const keyFor = (mainKey: string, subKey: string) => `${mainKey}::${subKey}`;
const isOpen = (mainKey: string, subKey: string) => !!openState.value[keyFor(mainKey, subKey)];
const toggleOpen = (mainKey: string, subKey: string) => {
    const k = keyFor(mainKey, subKey);
    openState.value[k] = !openState.value[k];
};

const pdf = useForm({
    title: props.project ? props.project.name : $t('Room assignment'),
    start: null as string | null,
    end: null as string | null,
    paperSize: null as string | null,
    paperOrientation: null as string | null,
    project: null as number | null,
    dpi: 72,
    daysPerPage: 5,
    roomsPerPage: 8,
    filter: {} as Record<string, number[] | null>
})

const pdfSelectedProject = ref<any | null>(null)
const selectedPaperSize = ref<{ id: string; name: string }>({ id: 'a4', name: 'A4 (Standard)' })
const selectedPaperOrientation = ref<{ id: 'portrait' | 'landscape'; title: string }>({
    id: 'portrait',
    title: $t('Portrait format')
})
const checkedOrientation = ref<'portrait' | 'landscape'>('portrait')
const orientationDisabled = ref(false)

const closeModal = (bool: boolean) => emits('closed', bool)

const createPdf = () => {
    pdf.paperSize = selectedPaperSize.value.id
    pdf.paperOrientation = selectedPaperOrientation.value.id

    if (props.project) {
        pdf.project = props.project.id
    }
    if (pdfSelectedProject.value) {
        pdf.project = pdfSelectedProject.value.id
    }

    const data: Record<string, number[] | null> = {};

    Object.assign(data, extractCheckedIds('roomFilters'));
    Object.assign(data, extractCheckedIds('areaFilters'));
    Object.assign(data, extractCheckedIds('eventFilters'));

    pdf.filter = data;

    pdf.post(route('calendar.export.pdf'), { preserveScroll: true })
    closeModal(true)
}

const activeFilters = computed(() => {
    const list: any[] = [];
    const cats = filteredOptionsByCategories.value;
    Object.keys(cats).forEach(category => {
        Object.keys(cats[category]).forEach(subCategory => {
            list.push(...cats[category][subCategory].filter((f: any) => f.checked));
        })
    })
    return list;
})

const filteredOptionsByCategories = computed(() => {
    const roomFilters = Object.keys(usePage().props.filterOptions).filter((key: string) => key.includes('room'));
    const eventFilters = Object.keys(usePage().props.filterOptions).filter((key: string) => key.includes('event'));
    const areaFilters = Object.keys(usePage().props.filterOptions).filter((key: string) => key.includes('area'));

    const filteredOptions: Record<string, Record<string, any[]>> = {
        roomFilters: {},
        areaFilters: {},
        eventFilters: {},
    }

    // Areas unverändert
    areaFilters.forEach((filter: string) => {
        filteredOptions.areaFilters[filter] = usePage().props.filterOptions[filter] || [];
    })

    // Rooms: nur tatsächliche Raumliste filtern
    roomFilters.forEach((filter: string) => {
        const list = usePage().props.filterOptions[filter] || [];
        if (filter === 'rooms' || filter === 'room_ids') {
            filteredOptions.roomFilters[filter] = list.filter((item: any) => {
                const rel = item?.relevant_for_disposition;
                return !(rel === false || rel === 0 || rel === '0');
            });
        } else {
            filteredOptions.roomFilters[filter] = list;
        }
    })

    // Events unverändert
    eventFilters.forEach((filter: string) => {
        filteredOptions.eventFilters[filter] = usePage().props.filterOptions[filter] || [];
    })

    return filteredOptions;
})

const extractCheckedIds = (filterGroup: 'roomFilters' | 'areaFilters' | 'eventFilters') => {
    const result: Record<string, number[] | null> = {};
    Object.entries(filteredOptionsByCategories.value[filterGroup]).forEach(([key, list]) => {
        const checked = (list as any[]).filter(item => item.checked).map(item => item.id);
        result[key] = checked.length > 0 ? checked : null;
    });
    return result;
};

const removeSpaceFromKey = (key: string) => key.replace(/\s/g, '')

// **Fix:** keine gruppenübergreifende Suche – direkt das referenzierte Objekt deaktivieren
const removeActiveFilter = (filterToRemove: any) => {
    filterToRemove.checked = false;
};

// Alle auswählen/abwählen innerhalb einer Subkategorie
const mutateSubcategory = (mainKey: string, subKey: string, value: boolean) => {
    const group = filteredOptionsByCategories.value as Record<string, Record<string, any[]>>;
    const sub = group?.[mainKey]?.[subKey];
    if (!Array.isArray(sub)) return;
    sub.forEach((item: any) => {
        // Nur boolean toggeln, keine Struktur verändern
        item.checked = value;
    });
};

const selectAllInSubcategory = (mainKey: string, subKey: string) => mutateSubcategory(mainKey, subKey, true);
const deselectAllInSubcategory = (mainKey: string, subKey: string) => mutateSubcategory(mainKey, subKey, false);

const changePaperOrientation = (orientation: { id: 'portrait' | 'landscape'; title: string }) => {
    selectedPaperOrientation.value = orientation
    checkedOrientation.value = orientation.id
}

const addProjectToPdf = (project: any) => {
    pdfSelectedProject.value = project
}

// A6 erzwingt Landscape
watch(
    () => selectedPaperSize.value,
    () => {
        if (selectedPaperSize.value.id === 'a6') {
            checkedOrientation.value = 'landscape'
            orientationDisabled.value = true
            changePaperOrientation({ id: 'landscape', title: $t('Landscape format') })
        } else {
            checkedOrientation.value = 'portrait'
            orientationDisabled.value = false
            changePaperOrientation({ id: 'portrait', title: $t('Portrait format') })
        }
    },
    { immediate: true }
)
</script>
