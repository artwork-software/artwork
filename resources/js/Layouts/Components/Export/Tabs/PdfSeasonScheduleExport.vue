<template>
    <div class="mx-auto w-full max-w-4xl">
        <div class="flex flex-col space-y-6">
            <!-- Titel + Zeitraum -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
                <div class="rounded-xl border border-info-border bg-info-surface/70 p-4">
                    <div class="flex items-start gap-x-3">
                        <PropertyIcon name="IconExclamationCircle" class="size-5 min-h-5 min-w-5 text-info"/>
                        <p class="text-sm text-info">
                            {{
                                $t(
                                    'This export shows you when which project takes place in the selected period. If required, you can filter the projects by specific event types - projects will then only appear on days on which they have an event of one of the selected event types.'
                                )
                            }}
                        </p>
                    </div>
                </div>

                <BaseInput id="season-title" v-model="pdf.title" :label="$t('Heading')" :placeholder="defaultTitle" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput type="date" v-model="pdf.startDate" id="season-startDate" :label="$t('Start date')" />
                    <BaseInput type="date" v-model="pdf.endDate" id="season-endDate" :label="$t('End date')" />
                </div>
                <p class="text-xs text-text-muted">
                    {{ $t('Up to 6 months are displayed per page (A3 landscape).') }}
                </p>
            </section>

            <!-- Terminarten-Filter (prominent) -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-semibold text-text">
                        {{ $t('Only show days with event type') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="text-[11px] text-accent-600 hover:text-accent-700 cursor-pointer"
                            @click="setAllEventTypes(true)"
                        >
                            {{ $t('Select all') }}
                        </button>
                        <span class="text-text-subtle text-xs">•</span>
                        <button
                            type="button"
                            class="text-[11px] text-accent-600 hover:text-accent-700 cursor-pointer"
                            @click="setAllEventTypes(false)"
                        >
                            {{ $t('Deselect all') }}
                        </button>
                    </div>
                </div>
                <p class="text-xs text-text-muted">
                    {{ $t('If active, a project only appears on days on which it has at least one event of the selected event types. Without a selection, all events count.') }}
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div v-for="eventType in eventTypeFilterList" :key="eventType.id" class="flex items-center gap-x-2">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="eventType.checked"
                                    :id="'season-event-type-' + eventType.id"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-border bg-surface checked:border-accent-600 checked:bg-accent-600 indeterminate:border-accent-600 indeterminate:bg-accent-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <label :for="'season-event-type-' + eventType.id" class="text-sm text-text">
                            {{ eventType.name }}
                        </label>
                    </div>
                </div>
            </section>

            <!-- Weitere Filter -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm">
                <!-- Gespeicherte Filter Presets -->
                <div v-if="savedFilterPresets.length > 0" class="mb-4 pb-4 border-b-2 border-dashed border-border">
                    <label class="block text-sm font-medium text-text-muted mb-2">
                        {{ $t('Saved filter presets') }}
                    </label>
                    <div class="flex flex-wrap items-center gap-2">
                        <div
                            v-for="preset in savedFilterPresets"
                            :key="preset.id"
                            class="group flex items-center bg-success-surface px-3 py-1.5 rounded-full border border-success-border cursor-pointer hover:bg-success-border/40 transition-colors"
                            @click="applyFilterPreset(preset)"
                        >
                            <span class="text-success text-xs font-medium">{{ preset.name }}</span>
                            <button
                                type="button"
                                class="ml-2 text-success hover:text-danger transition-colors"
                                @click.stop="confirmDeletePreset(preset)"
                            >
                                <component :is="IconX" class="size-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Aktive Filter + Speichern Button -->
                <div class="mb-4 pb-4 border-b-2 border-dashed border-border">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-text-muted">
                            {{ $t('Active filters') }}
                        </label>
                        <button
                            v-if="activeFilters.length > 0"
                            type="button"
                            class="text-xs text-accent-600 hover:text-accent-700 font-medium"
                            @click="showSavePresetModal = true"
                        >
                            {{ $t('Save current filters as preset') }}
                        </button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div
                            v-for="(filter, index) in activeFilters"
                            :key="`${filter.id}-${filter.value ?? ''}-${index}`"
                            class="group block cursor-pointer shrink-0 bg-accent-50 w-fit px-2 py-1.5 rounded-full border border-accent-200"
                        >
                            <div class="flex items-center">
                                <div class="mx-2">
                                    <p class="text-accent-600 text-xs group-hover:text-accent-700">
                                        {{ filter?.name }}
                                    </p>
                                </div>
                                <div class="flex items-center">
                                    <button type="button" @click="removeActiveFilter(filter)">
                                        <component :is="IconX" class="size-4 text-accent-600 hover:text-danger" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <div v-for="(filterMainCategory, mainKey) in accordionFilterCategories" :key="mainKey" class="py-1" v-show="hasNonEmptySubcategory(filterMainCategory)">
                        <div class="text-text-inverse bg-surface-inverse rounded-lg px-4 py-2 font-lexend shadow text-sm">
                            {{ $t(mainKey) }}
                        </div>

                        <div class="space-y-2 mt-2">
                            <div v-for="(filterSubCategory, subKey) in filterMainCategory" :key="subKey" v-show="filterSubCategory.length > 0">
                                <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised px-4 ">
                                    <div class="flex items-center select-none justify-between duration-200 ease-in-out cursor-pointer py-3" @click="toggleOpen(mainKey, subKey)">
                                        <div class="text-sm text-text">
                                            {{ $t(subKey) }}
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div class="hidden md:flex items-center gap-2 mr-2">
                                                <button
                                                    type="button"
                                                    class="text-[11px] text-accent-600 hover:text-accent-700 cursor-pointer"
                                                    @click.stop="selectAllInSubcategory(mainKey, subKey)"
                                                >
                                                    {{ $t('Select all') }}
                                                </button>
                                                <span class="text-text-subtle text-xs">•</span>
                                                <button
                                                    type="button"
                                                    class="text-[11px] text-accent-600 hover:text-accent-700 cursor-pointer"
                                                    @click.stop="deselectAllInSubcategory(mainKey, subKey)"
                                                >
                                                    {{ $t('Deselect all') }}
                                                </button>
                                            </div>
                                            <span
                                                class="inline-flex items-center rounded-lg bg-success-surface px-2 py-1 text-xs/4 text-success ring-1 ring-inset ring-success-border"
                                                :class="filterSubCategory.filter(filter => filter.checked).length > 0 ? 'visible' : 'invisible'"
                                            >
                                                {{ filterSubCategory.filter(filter => filter.checked).length }} {{ $t('selected') }}
                                            </span>
                                            <component :is="IconChevronDown" class="w-4 h-4 text-text-subtle" :class="isOpen(mainKey, subKey) ? 'rotate-180' : ''" />
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
                                                                class="col-start-1 row-start-1 appearance-none rounded-sm border border-border bg-surface checked:border-accent-600 checked:bg-accent-600 indeterminate:border-accent-600 indeterminate:bg-accent-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 disabled:border-border disabled:bg-surface-sunken disabled:checked:bg-surface-sunken forced-colors:appearance-auto"
                                                            />
                                                            <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-border-strong" viewBox="0 0 14 14" fill="none">
                                                                <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="text-sm flex items-center gap-x-1">
                                                        <div v-if="filter.icon" class="flex items-center gap-2">
                                                            <component :is="filter.icon" class="size-4" stroke-width="1.5"/>
                                                        </div>
                                                        <label :for="removeSpaceFromKey(filter.name)" class="text-text">
                                                            {{ filter.name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Anzeigeeinstellungen (Farbquelle + Künstler:innen statt Titel; vorbelegt aus dem Kalender) -->
            <ExportDisplaySettings v-model="displaySettings" compact id-prefix="season" />

            <!-- Darstellungs-Optionen -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-3">
                <label class="block text-sm font-semibold text-text">
                    {{ $t('Display options') }}
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div v-for="option in displayOptions" :key="option.key" class="flex items-center gap-x-2">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="pdf[option.key]"
                                    :id="'season-option-' + option.key"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-border bg-surface checked:border-accent-600 checked:bg-accent-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <label :for="'season-option-' + option.key" class="text-sm text-text">
                            {{ option.label }}
                        </label>
                    </div>
                </div>
            </section>

            <!-- Papierformat + DPI -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 items-start">
                    <div>
                        <Listbox as="div" v-model="selectedPaperSize">
                            <!-- Label- und Feldmaße wie BaseInput, damit Papiergröße und DPI bündig sind -->
                            <ListboxLabel class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">
                                {{ $t('Paper size') }}
                            </ListboxLabel>
                            <div class="relative">
                                <ListboxButton
                                    class="relative block h-8 w-full cursor-pointer rounded-md border border-border bg-surface px-3 text-left text-sm text-text hover:bg-surface-sunken"
                                >
                                    <div class="block truncate">{{ selectedPaperSize.name }}</div>
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <svg class="h-5 w-5 text-text-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor">
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
                                        class="absolute z-10 mt-2 max-h-60 w-full overflow-auto rounded-xl bg-white py-2 text-sm shadow-lg ring-1 ring-black/10"
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
                                                    active ? 'bg-surface-inverse text-text-inverse' : 'text-text'
                                                ]"
                                            >
                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                                    {{ paperSize.name }}
                                                </span>
                                                <span
                                                    v-if="selected"
                                                    :class="[
                                                        active ? 'text-text-inverse' : 'text-text',
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
                        <p class="mt-1 text-xs text-text-muted">
                            {{ $t('The season schedule is always exported in landscape format.') }}
                        </p>
                    </div>

                    <div>
                        <BaseInput
                            id="season-dpi"
                            v-model="pdf.dpi"
                            :label="$t('Resolution (DPI) (Standard: 72) (Maximum: 300)')"
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

    <!-- Save Filter Preset Modal -->
    <SaveFilterPresetModal
        v-if="showSavePresetModal"
        :active-filters="activeFilters"
        :filter-data="getCurrentFilterData()"
        @close="showSavePresetModal = false"
        @saved="onPresetSaved"
    />

    <!-- Confirm Delete Preset Modal -->
    <ConfirmDeleteModal
        v-if="showDeletePresetModal"
        :title="$t('Delete filter preset')"
        :description="$t('Are you sure you want to delete this filter preset?')"
        @closed="showDeletePresetModal = false"
        @delete="deletePreset"
    />
</template>

<script setup lang="ts">
import {computed, onMounted, ref, watch} from 'vue'
import {useForm, usePage} from '@inertiajs/vue3'
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import { useTranslation } from '@/Composeables/Translation.js'
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import {IconChevronDown, IconX} from "@tabler/icons-vue";
import SaveFilterPresetModal from '@/Layouts/Components/Export/Modals/SaveFilterPresetModal.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import ExportDisplaySettings from '@/Layouts/Components/Export/Components/ExportDisplaySettings.vue'

const $t = useTranslation()
const emits = defineEmits<{ (e: 'closed', value: boolean): void }>()
const props = defineProps<{
    preselectedFilters?: Record<string, number[] | null> | null;
    preselectedDateRange?: string[] | null;
}>()

const toDateInputValue = (date: Date): string => {
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${date.getFullYear()}-${month}-${day}`
}

// Default: 6 volle Monate ab dem 01. des aktuellen Monats
const defaultStart = new Date()
defaultStart.setDate(1)
const defaultEnd = new Date(defaultStart)
defaultEnd.setMonth(defaultEnd.getMonth() + 6)
defaultEnd.setDate(0) // letzter Tag des 6. Monats

const paperSizes = [
    { id: 'a3', name: 'A3 (Standard)' },
    { id: 'a4', name: 'A4' }
]

const openState = ref<Record<string, boolean>>({});
const keyFor = (mainKey: string, subKey: string) => `${mainKey}::${subKey}`;
const isOpen = (mainKey: string, subKey: string) => !!openState.value[keyFor(mainKey, subKey)];
const toggleOpen = (mainKey: string, subKey: string) => {
    const k = keyFor(mainKey, subKey);
    openState.value[k] = !openState.value[k];
};

const pdf = useForm({
    title: '',
    startDate: toDateInputValue(defaultStart) as string | null,
    endDate: toDateInputValue(defaultEnd) as string | null,
    paperSize: null as string | null,
    dpi: 72,
    filter: {} as Record<string, number[] | null>,
    displaySettings: null as Record<string, boolean> | null,
    showHolidays: true,
    showWeekNumbers: true,
    highlightWeekends: true,
    showColorDots: true,
    showEventsWithoutProject: false,
    showRoomAbbreviations: false,
    splitMonths: false,
})

// Anzeigeeinstellungen (Farbquelle + Künstler:innen statt Titel)
const displaySettings = ref<Record<string, boolean> | null>(null)

// Zeitraum: aktuell sichtbarer Kalenderzeitraum, auf volle Monate gerundet
// (das Raster zeigt ohnehin immer ganze Monate)
const applyPreselectedDateRange = () => {
    const range = props.preselectedDateRange
    if (!Array.isArray(range) || !range[0] || !range[1]) return
    const start = new Date(String(range[0]).slice(0, 10))
    const end = new Date(String(range[1]).slice(0, 10))
    if (isNaN(start.getTime()) || isNaN(end.getTime())) return
    start.setDate(1)
    end.setMonth(end.getMonth() + 1)
    end.setDate(0) // letzter Tag des Endmonats
    pdf.startDate = toDateInputValue(start)
    pdf.endDate = toDateInputValue(end)
}

const defaultTitle = computed(() => {
    const startYear = pdf.startDate ? new Date(pdf.startDate).getFullYear() : new Date().getFullYear()
    const endYear = pdf.endDate ? new Date(pdf.endDate).getFullYear() : startYear
    return startYear === endYear ? `Spielplan ${startYear}` : `Spielplan ${startYear}/${endYear}`
})

const displayOptions = computed(() => [
    { key: 'showHolidays', label: $t('Show holidays') },
    { key: 'showWeekNumbers', label: $t('Show calendar weeks') },
    { key: 'highlightWeekends', label: $t('Highlight weekends') },
    { key: 'showColorDots', label: $t('Color dot per event type') },
    { key: 'showEventsWithoutProject', label: $t('Show events without project') },
    { key: 'showRoomAbbreviations', label: $t('Show room abbreviations') },
    { key: 'splitMonths', label: $t('Half months per page (double row height)') },
])

const selectedPaperSize = ref<{ id: string; name: string }>({ id: 'a3', name: 'A3 (Standard)' })

const closeModal = (bool: boolean) => emits('closed', bool)

// Filter Preset State
const savedFilterPresets = ref<any[]>([])
const showSavePresetModal = ref(false)
const showDeletePresetModal = ref(false)
const presetToDelete = ref<any>(null)

const loadFilterPresets = async () => {
    try {
        const response = await axios.get(route('pdf-export-user-filters.index'))
        savedFilterPresets.value = response.data
    } catch (error) {
        console.error('Failed to load filter presets:', error)
    }
}

// Aktive Kalender-Filter des Users als Vorauswahl übernehmen (im Modal weiterhin anpassbar).
// Setzt checked für ALLE Einträge (true/false), damit keine veralteten Häkchen aus den
// geteilten filterOptions-Referenzen (CalendarFilterModal) übrig bleiben.
const applyActiveUserFilters = () => {
    const source = props.preselectedFilters ?? (usePage().props.user_filters as Record<string, any> | undefined) ?? null
    if (!source) return
    const cats = filteredOptionsByCategories.value
    Object.keys(cats).forEach(category => {
        Object.keys(cats[category]).forEach(subKey => {
            const activeIds = Array.isArray(source[subKey]) ? source[subKey] : []
            cats[category][subKey].forEach((f: any) => {
                f.checked = activeIds.includes(f.id)
            })
        })
    })
}

onMounted(() => {
    loadFilterPresets()
    applyActiveUserFilters()
    applyPreselectedDateRange()
})

const getCurrentFilterData = () => {
    const data: Record<string, number[] | null> = {}
    Object.assign(data, extractCheckedIds('roomFilters'))
    Object.assign(data, extractCheckedIds('areaFilters'))
    Object.assign(data, extractCheckedIds('eventFilters'))
    return data
}

const applyFilterPreset = (preset: any) => {
    const cats = filteredOptionsByCategories.value
    Object.keys(cats).forEach(category => {
        Object.keys(cats[category]).forEach(subCategory => {
            cats[category][subCategory].forEach((f: any) => {
                f.checked = false
            })
        })
    })

    if (preset.filters) {
        Object.entries(preset.filters).forEach(([filterKey, ids]) => {
            if (!ids || !Array.isArray(ids)) return

            Object.keys(cats).forEach(category => {
                if (cats[category][filterKey]) {
                    cats[category][filterKey].forEach((f: any) => {
                        if (ids.includes(f.id)) {
                            f.checked = true
                        }
                    })
                }
            })
        })
    }
}

const onPresetSaved = (newPreset: any) => {
    savedFilterPresets.value.push(newPreset)
    savedFilterPresets.value.sort((a, b) => a.name.localeCompare(b.name))
}

const confirmDeletePreset = (preset: any) => {
    presetToDelete.value = preset
    showDeletePresetModal.value = true
}

const deletePreset = async () => {
    if (!presetToDelete.value) return

    try {
        await axios.delete(route('pdf-export-user-filters.destroy', presetToDelete.value.id))
        savedFilterPresets.value = savedFilterPresets.value.filter(p => p.id !== presetToDelete.value.id)
    } catch (error) {
        console.error('Failed to delete preset:', error)
    } finally {
        showDeletePresetModal.value = false
        presetToDelete.value = null
    }
}

const createPdf = () => {
    pdf.paperSize = selectedPaperSize.value.id
    if (!pdf.title) {
        pdf.title = defaultTitle.value
    }

    // Auf YYYY-MM-DD normalisieren – Browser ohne date-Input-Support liefern auch
    // Freitext wie "15.10.2025"
    const toIsoDate = (value: string | null): string | null => {
        if (!value) return null
        const iso = value.match(/^(\d{4})-(\d{2})-(\d{2})/)
        if (iso) return `${iso[1]}-${iso[2]}-${iso[3]}`
        const dayMonthYear = value.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/)
        if (dayMonthYear) {
            return `${dayMonthYear[3]}-${dayMonthYear[2].padStart(2, '0')}-${dayMonthYear[1].padStart(2, '0')}`
        }
        return null
    }
    pdf.startDate = toIsoDate(pdf.startDate)
    pdf.endDate = toIsoDate(pdf.endDate)

    const data: Record<string, number[] | null> = {};

    Object.assign(data, extractCheckedIds('roomFilters'));
    Object.assign(data, extractCheckedIds('areaFilters'));
    Object.assign(data, extractCheckedIds('eventFilters'));

    pdf.filter = data;
    pdf.displaySettings = displaySettings.value;

    pdf.post(route('calendar.export.season-schedule-pdf'), { preserveScroll: true })
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
    const eventFilters = Object.keys(usePage().props.filterOptions).filter((key: string) => key.includes('event') || key === 'project_state_ids'); // Projektstatus-Filter gehoert zur Termin-Gruppe
    const areaFilters = Object.keys(usePage().props.filterOptions).filter((key: string) => key.includes('area'));

    const filteredOptions: Record<string, Record<string, any[]>> = {
        roomFilters: {},
        areaFilters: {},
        eventFilters: {},
    }

    areaFilters.forEach((filter: string) => {
        filteredOptions.areaFilters[filter] = usePage().props.filterOptions[filter] || [];
    })

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

    eventFilters.forEach((filter: string) => {
        filteredOptions.eventFilters[filter] = usePage().props.filterOptions[filter] || [];
    })

    return filteredOptions;
})

// Terminarten kommen prominent nach oben, deshalb im generischen Akkordeon ausblenden
const eventTypeFilterList = computed(() => filteredOptionsByCategories.value.eventFilters['event_type_ids'] ?? [])
const accordionFilterCategories = computed(() => {
    const cats = filteredOptionsByCategories.value
    const eventFiltersWithoutTypes: Record<string, any[]> = {}
    Object.keys(cats.eventFilters).forEach((subKey) => {
        if (subKey !== 'event_type_ids') {
            eventFiltersWithoutTypes[subKey] = cats.eventFilters[subKey]
        }
    })
    return {
        roomFilters: cats.roomFilters,
        areaFilters: cats.areaFilters,
        eventFilters: eventFiltersWithoutTypes,
    }
})

const setAllEventTypes = (value: boolean) => {
    eventTypeFilterList.value.forEach((eventType: any) => {
        eventType.checked = value
    })
}

const extractCheckedIds = (filterGroup: 'roomFilters' | 'areaFilters' | 'eventFilters') => {
    const result: Record<string, number[] | null> = {};
    Object.entries(filteredOptionsByCategories.value[filterGroup]).forEach(([key, list]) => {
        const checked = (list as any[]).filter(item => item.checked).map(item => item.id);
        result[key] = checked.length > 0 ? checked : null;
    });
    return result;
};

const removeSpaceFromKey = (key: string) => key.replace(/\s/g, '')

const hasNonEmptySubcategory = (mainCategory: Record<string, any[]>) => {
    return Object.values(mainCategory).some((list) => Array.isArray(list) && list.length > 0)
}

const removeActiveFilter = (filterToRemove: any) => {
    filterToRemove.checked = false;
};

const mutateSubcategory = (mainKey: string, subKey: string, value: boolean) => {
    const group = filteredOptionsByCategories.value as Record<string, Record<string, any[]>>;
    const sub = group?.[mainKey]?.[subKey];
    if (!Array.isArray(sub)) return;
    sub.forEach((item: any) => {
        item.checked = value;
    });
};

const selectAllInSubcategory = (mainKey: string, subKey: string) => mutateSubcategory(mainKey, subKey, true);
const deselectAllInSubcategory = (mainKey: string, subKey: string) => mutateSubcategory(mainKey, subKey, false);

// When start date changes, keep end date at or after it
watch(
    () => pdf.startDate,
    (newVal) => {
        if (newVal && (!pdf.endDate || pdf.endDate < newVal)) {
            pdf.endDate = newVal
        }
    }
)
</script>
