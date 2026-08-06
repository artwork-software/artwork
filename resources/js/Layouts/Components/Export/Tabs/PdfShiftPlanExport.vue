<template>
    <div class="mx-auto w-full max-w-4xl">
        <div class="flex flex-col space-y-6">
            <!-- What this export does -->
            <section>
                <h1 class="text-lg font-semibold text-text">
                    {{ $t('PDF_SHIFT_PLAN_EXPORT') }}
                </h1>
                <p class="mt-1 text-sm text-text-muted">
                    {{ $t('Exports the shift plan as a PDF without requiring a project – for a freely selectable period, filterable by calendar week and craft.') }}
                </p>
            </section>

            <!-- Title + mode + period -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
                <BaseInput id="shiftPlanTitle" v-model="pdf.title" :label="$t('Heading')" />

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <button
                        v-for="mode in exportModes"
                        :key="mode.id"
                        type="button"
                        class="rounded-xl border p-4 text-left transition"
                        :class="pdf.exportMode === mode.id
                            ? 'border-surface-inverse bg-surface-inverse text-white'
                            : 'border-border-subtle bg-white text-text hover:bg-surface-sunken'"
                        @click="selectExportMode(mode.id)"
                    >
                        <span class="block text-sm font-semibold">{{ mode.title }}</span>
                        <span class="mt-1 block text-xs opacity-75">{{ mode.description }}</span>
                    </button>
                </div>

                <!-- Period: by date or by calendar week -->
                <div>
                    <div class="mb-2 text-sm font-medium text-text-muted">{{ $t('Time period') }}</div>
                    <div class="flex gap-2">
                        <button
                            v-for="mode in periodModes"
                            :key="mode.id"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs transition-colors"
                            :class="periodMode === mode.id
                                ? 'border-accent-200 bg-accent-50 text-accent-700'
                                : 'border-border-subtle text-text-muted hover:bg-surface-sunken hover:text-text'"
                            @click="periodMode = mode.id"
                        >
                            {{ mode.title }}
                        </button>
                    </div>
                </div>

                <div v-if="periodMode === 'date'" class="space-y-3">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <BaseInput id="shiftPlanExportStart" type="date" v-model="pdf.start" :label="$t('Start date')" />
                        <BaseInput id="shiftPlanExportEnd" type="date" v-model="pdf.end" :label="$t('End date')" />
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="preset in datePresets"
                            :key="preset.label"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs transition-colors"
                            :class="isActiveDatePreset(preset)
                                ? 'border-accent-200 bg-accent-50 text-accent-700'
                                : 'border-border-subtle text-text-muted hover:bg-surface-sunken hover:text-text'"
                            @click="applyDatePreset(preset)"
                        >
                            {{ preset.label }}
                        </button>
                    </div>
                    <p v-if="dateRangeError" class="text-xs text-danger">{{ dateRangeError }}</p>
                    <p v-else class="text-xs text-text-subtle">{{ $t('A maximum of six months can be exported in one PDF.') }}</p>
                </div>

                <div v-else class="space-y-2">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <BaseInput
                            id="shiftPlanExportKwYear"
                            type="number"
                            v-model="kwYear"
                            :label="$t('Year')"
                        />
                        <BaseInput
                            id="shiftPlanExportKwFrom"
                            type="number"
                            v-model="kwFrom"
                            :label="$t('From calendar week')"
                        />
                        <BaseInput
                            id="shiftPlanExportKwTo"
                            type="number"
                            v-model="kwTo"
                            :label="$t('To calendar week')"
                        />
                    </div>
                    <p v-if="kwSelectionInvalid" class="text-xs text-danger">
                        {{ $t('Please select a valid calendar week range.') }} (KW 1–{{ weeksInSelectedYear }})
                    </p>
                    <p v-else-if="dateRangeError" class="text-xs text-danger">
                        {{ dateRangeError }}
                    </p>
                    <p v-else class="text-xs text-text-subtle">
                        {{ $t('Period') }}: {{ formatDate(pdf.start) }} – {{ formatDate(pdf.end) }}
                    </p>
                </div>

                <p v-if="workerMatrixRangeTooLong" class="text-xs text-danger">
                    {{ $t('Days are shown as columns and workers as rows. The maximum export period is 31 days.') }}
                </p>
            </section>

            <section class="rounded-2xl border border-accent-200 bg-accent-50/60 p-5">
                <h2 class="text-sm font-semibold text-text">{{ $t('This is how the PDF is structured') }}</h2>
                <div class="mt-4 overflow-hidden rounded-lg border border-border bg-white text-[11px] text-text-muted shadow-sm">
                    <div class="border-b border-border-subtle bg-surface-sunken px-3 py-2 font-semibold text-text">
                        {{ pdf.title || $t('Shift plan') }} · {{ formatDate(pdf.start) }} – {{ formatDate(pdf.end) }}
                    </div>
                    <div class="grid grid-cols-[7rem_repeat(3,minmax(0,1fr))]">
                        <div class="border-r border-border-subtle bg-surface-sunken p-2 font-semibold">
                            {{ pdf.exportMode === 'rooms' ? $t('Room / area') : $t('Person') }}
                        </div>
                        <div v-for="day in previewDays" :key="day" class="border-r border-border-subtle p-2 text-center font-semibold last:border-r-0">
                            {{ day }}
                        </div>
                        <template v-for="row in previewRows" :key="row.label">
                            <div class="border-r border-t border-border-subtle bg-surface-sunken p-2 font-medium">{{ row.label }}</div>
                            <div v-for="(cell, cellIndex) in row.cells" :key="cellIndex" class="border-r border-t border-border-subtle p-2 last:border-r-0">
                                {{ cell }}
                            </div>
                        </template>
                    </div>
                </div>
                <ul class="mt-4 space-y-1 text-xs text-text-muted">
                    <li><span class="font-semibold">{{ $t('Rows') }}:</span> {{ exportSummary.rows }}</li>
                    <li><span class="font-semibold">{{ $t('Columns') }}:</span> {{ exportSummary.columns }}</li>
                    <li><span class="font-semibold">{{ $t('Cells contain') }}:</span> {{ exportSummary.contents }}</li>
                </ul>
            </section>

            <!-- Filters (room export): rooms, areas, event types, crafts -->
            <section
                v-if="pdf.exportMode === 'rooms'"
                class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-5"
            >
                <div>
                    <h2 class="text-sm font-semibold text-text">{{ $t('Filter') }}</h2>
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('No selection in a group means it is not filtered by it.') }}
                    </p>
                </div>

                <div v-for="group in filterGroups" :key="group.key" class="space-y-2">
                    <div class="flex items-center justify-between border-b border-border-subtle pb-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-text-muted">
                            {{ group.label }}
                            <span v-if="selections[group.key].length > 0" class="ml-1 rounded-full bg-accent-50 px-2 py-0.5 text-[10px] font-semibold text-accent-700">
                                {{ selections[group.key].length }}
                            </span>
                        </span>
                        <button
                            v-if="group.options.length > 0"
                            type="button"
                            class="text-xs text-text-subtle hover:text-text"
                            @click="toggleGroup(group.key)"
                        >
                            {{ selections[group.key].length === group.options.length ? $t('Deselect all') : $t('Select all') }}
                        </button>
                    </div>
                    <div v-if="group.options.length > 0" class="grid max-h-40 grid-cols-1 gap-y-1.5 overflow-y-auto sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="option in group.options"
                            :key="option.id"
                            class="flex cursor-pointer items-center gap-2 pr-2 text-xs text-text-muted"
                        >
                            <input
                                type="checkbox"
                                :value="option.id"
                                v-model="selections[group.key]"
                                class="input-checklist"
                            />
                            <span class="truncate">{{ option.name }}</span>
                        </label>
                    </div>
                    <p v-else class="text-xs text-text-subtle">–</p>
                </div>

                <label class="flex cursor-pointer items-start gap-3 border-t border-border-subtle pt-4">
                    <input type="checkbox" v-model="pdf.hideEmptyRooms" class="input-checklist mt-0.5" />
                    <span>
                        <span class="block text-sm font-medium text-text">{{ $t('Hide empty rooms') }}</span>
                        <span class="block text-xs text-text-subtle">
                            {{ $t('Rooms without events or shifts in the selected period are omitted from the PDF.') }}
                        </span>
                    </span>
                </label>
            </section>

            <!-- Craft filter (personnel export) -->
            <section
                v-if="pdf.exportMode === 'worker_matrix' && crafts.length > 0"
                class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-text">{{ $t('Which shifts should be included?') }}</h2>
                        <p class="mt-1 text-xs text-text-subtle">
                            {{ $t('This filters the shifts printed for each person. Individual times and day services remain visible when enabled below.') }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="text-sm text-text-muted hover:text-text"
                        @click="toggleAllCrafts"
                    >
                        {{ allCraftsSelected ? $t('Deselect all') : $t('Select all') }}
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2">
                    <label
                        v-for="craft in crafts"
                        :key="craft.id"
                        class="flex items-center gap-2 text-xs text-text-muted cursor-pointer"
                    >
                        <input
                            type="checkbox"
                            :value="craft.id"
                            v-model="selectedCraftIds"
                            class="input-checklist"
                        />
                        {{ craft.name }}
                    </label>
                </div>
                <p v-if="selectedCraftIds.length === 0" class="text-xs text-danger">
                    {{ $t('Please select at least one craft.') }}
                </p>
            </section>

            <section
                v-if="pdf.exportMode === 'worker_matrix'"
                class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-5"
            >
                <div>
                    <h3 class="text-sm font-semibold text-text">{{ $t('Personnel overview') }}</h3>
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('Days are shown as columns and workers as rows. The maximum export period is 31 days.') }}
                    </p>
                </div>

                <div>
                    <div class="mb-2 text-sm font-medium text-text-muted">{{ $t('Worker types') }}</div>
                    <p class="mb-3 text-xs text-text-subtle">
                        {{ $t('This selection filters people: only the selected personnel groups become rows in the PDF.') }}
                    </p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <label v-for="workerType in workerTypes" :key="workerType.id" class="flex items-center gap-2 text-sm text-text-muted">
                            <input v-model="pdf.worker_types" :value="workerType.id" type="checkbox" class="input-checklist" />
                            {{ workerType.label }}
                        </label>
                    </div>
                </div>

                <div>
                    <div class="mb-2 text-sm font-medium text-text-muted">{{ $t('Contents') }}</div>
                    <p class="mb-3 text-xs text-text-subtle">
                        {{ $t('These options control which kinds of entries are printed in each person/day cell.') }}
                    </p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <label class="flex items-center gap-2 text-sm text-text-muted">
                            <input v-model="pdf.show_shifts" type="checkbox" class="input-checklist" />
                            {{ $t('Shifts with room and individual time') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm text-text-muted">
                            <input v-model="pdf.show_individual_times" type="checkbox" class="input-checklist" />
                            {{ $t('Individual times') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm text-text-muted">
                            <input v-model="pdf.show_day_services" type="checkbox" class="input-checklist" />
                            {{ $t('Day Services') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm text-text-muted">
                            <input v-model="pdf.include_empty_workers" type="checkbox" class="input-checklist" />
                            {{ $t('Show workers without entries') }}
                        </label>
                    </div>
                </div>
            </section>

            <div class="rounded-xl border border-accent-200 bg-accent-50/70 p-4">
                <div class="flex items-start gap-x-3">
                    <PropertyIcon name="IconExclamationCircle" class="size-5 min-h-5 min-w-5 text-accent-600"/>
                    <p class="text-sm text-accent-600">
                        {{ pdf.exportMode === 'rooms'
                            ? $t('The export is prefilled with your current shift plan view. Time period and filters can be adjusted below before exporting. Each calendar week is placed on its own page.')
                            : $t('Personnel export creates one row per selected person. Craft filters affect shifts, worker types affect people, and the content options affect the entries shown in the cells.') }}
                    </p>
                </div>
            </div>

            <!-- Paper format + orientation + DPI -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Paper size -->
                    <div class="space-y-2">
                        <Listbox as="div" v-model="selectedPaperSize">
                            <ListboxLabel class="block text-sm font-medium text-text-muted">
                                {{ $t('Paper size') }}
                            </ListboxLabel>
                            <div class="relative mt-1">
                                <ListboxButton
                                    class="relative w-full cursor-pointer rounded-xl border border-border-subtle bg-white px-4 py-3 text-left text-sm hover:bg-surface-sunken">
                                    <div class="block truncate">{{ selectedPaperSize.name }}</div>
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <component :is="IconSelector" class="h-5 w-5 text-text-subtle" />
                                    </span>
                                </ListboxButton>
                                <transition
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute z-10 mt-2 max-h-60 w-full overflow-auto rounded-xl bg-white py-2 text-sm shadow-lg ring-1 ring-black/10 focus:outline-none">
                                        <ListboxOption
                                            v-for="paperSize in paperSizes"
                                            :key="paperSize.id"
                                            :value="paperSize"
                                            v-slot="{ active, selected }"
                                            as="template">
                                            <li :class="['relative cursor-pointer select-none py-2 pl-3 pr-9', active ? 'bg-surface-inverse text-white' : 'text-text']">
                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                                    {{ paperSize.name }}
                                                </span>
                                                <span v-if="selected" :class="[active ? 'text-white' : 'text-text', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                    <component :is="IconCheck" class="h-5 w-5" />
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                    </div>

                    <!-- Orientation -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-text-muted">
                            {{ $t('Paper orientation') }}
                        </label>
                        <fieldset>
                            <div class="flex gap-3">
                                <div v-for="paperOrientation in paperOrientations" :key="paperOrientation.id" class="relative flex-1">
                                    <input
                                        :id="`shiftplan-${paperOrientation.id}`"
                                        name="shiftplan-orientation"
                                        type="radio"
                                        :checked="paperOrientation.id === selectedPaperOrientation.id"
                                        class="peer absolute inset-0 h-0 w-0 opacity-0"
                                        @change="selectedPaperOrientation = paperOrientation"
                                    />
                                    <label
                                        :for="`shiftplan-${paperOrientation.id}`"
                                        class="block cursor-pointer rounded-xl border px-4 py-3 text-sm transition
                                        peer-checked:border-surface-inverse peer-checked:bg-surface-inverse peer-checked:text-white
                                        border-border-subtle bg-white text-text hover:bg-surface-sunken hover:text-text">
                                        {{ paperOrientation.title }}
                                    </label>
                                </div>
                            </div>
                            <span class="mt-2 block text-xs text-text-subtle">
                                {{ $t('Landscape format is recommended so a whole calendar week fits on one page.') }}
                            </span>
                        </fieldset>
                    </div>

                    <!-- DPI -->
                    <div>
                        <BaseInput
                            id="shiftPlanDpi"
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
                    :disabled="exportDisabled"
                    is-add-button
                />
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { IconCheck, IconSelector } from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()
const emits = defineEmits<{ (e: 'close'): void }>()
const props = defineProps<{ configuration?: Record<string, any> }>()

const config = props.configuration ?? {}

const paperSizes = [
    { id: 'a3', name: 'A3' },
    { id: 'a4', name: 'A4 (Standard)' },
    { id: 'a5', name: 'A5' },
]
const paperOrientations = [
    { id: 'landscape', title: $t('Landscape format') },
    { id: 'portrait', title: $t('Portrait format') },
]
const exportModes = [
    {
        id: 'rooms',
        title: $t('Rooms by day'),
        description: $t('Rooms are shown as rows with events and shifts.'),
    },
    {
        id: 'worker_matrix',
        title: $t('Personnel overview'),
        description: $t('Workers are shown as rows with rooms, individual times and day services.'),
    },
]
const periodModes = [
    { id: 'date', title: $t('By date') },
    { id: 'calendar_week', title: $t('By calendar week') },
]
const workerTypes = [
    { id: 'user', label: $t('Internal workers') },
    { id: 'freelancer', label: $t('Freelancer') },
    { id: 'service_provider', label: $t('Service provider') },
]

const selectedPaperSize = ref(paperSizes[1])
const selectedPaperOrientation = ref(paperOrientations[0])

// The configuration is provided in two shapes depending on the caller:
// filterOptions/activeFilters (weekly shift plan) or crafts/craftIds (daily view).
const crafts = ((config.crafts ?? config.filterOptions?.crafts ?? []) as Array<{ id: number, name: string }>)
const activeCraftIds = ((config.activeFilters?.craft_ids ?? config.craftIds ?? []) as number[])

// Filter selections for the room export, prefilled with the filters currently
// active in the shift plan. An empty selection means "do not filter by this dimension".
const selections = reactive({
    room_ids: [...(config.activeFilters?.room_ids ?? [])],
    area_ids: [...(config.activeFilters?.area_ids ?? [])],
    event_type_ids: [...(config.activeFilters?.event_type_ids ?? [])],
    craft_ids: [...activeCraftIds],
})

const filterGroups = computed(() => [
    { key: 'room_ids', label: $t('Rooms'), options: config.filterOptions?.rooms ?? [] },
    { key: 'area_ids', label: $t('Areas'), options: config.filterOptions?.areas ?? [] },
    { key: 'event_type_ids', label: $t('Event types'), options: config.filterOptions?.eventTypes ?? [] },
    { key: 'craft_ids', label: $t('Crafts'), options: config.filterOptions?.crafts ?? crafts },
])

const toggleGroup = (key: keyof typeof selections) => {
    const group = filterGroups.value.find((g) => g.key === key)
    if (!group) return
    selections[key] = selections[key].length === group.options.length
        ? []
        : group.options.map((option: { id: number }) => option.id)
}

const pdf = useForm({
    exportMode: 'rooms',
    title: config.projectName || $t('Shift plan'),
    start: config.startDate ?? null,
    end: config.endDate ?? null,
    projectId: config.projectId ?? null,
    isInProjectView: !!config.isInProjectView,
    isDailyView: !!config.isDailyView,
    highlightProjectId: config.highlightProjectId ?? null,
    hideEmptyRooms: true,
    room_ids: [] as number[],
    area_ids: [] as number[],
    event_type_ids: [] as number[],
    craft_ids: [] as number[],
    worker_types: ['user', 'freelancer', 'service_provider'],
    include_empty_workers: false,
    show_shifts: true,
    show_individual_times: true,
    show_day_services: true,
    paperSize: 'a4',
    paperOrientation: 'landscape',
    dpi: 96,
})

// --- Crafts filter (personnel export) ------------------------------------
const preselectedCraftIds = activeCraftIds.filter(
    (id: number) => crafts.some((craft) => craft.id === id)
)
const selectedCraftIds = ref<number[]>(
    preselectedCraftIds.length > 0 ? [...preselectedCraftIds] : crafts.map((craft) => craft.id)
)
const allCraftsSelected = computed(
    () => crafts.length > 0 && selectedCraftIds.value.length === crafts.length
)
const toggleAllCrafts = () => {
    selectedCraftIds.value = allCraftsSelected.value ? [] : crafts.map((craft) => craft.id)
}

// --- Period selection (date / calendar week) -----------------------------
const periodMode = ref('date')

const isoWeekOf = (date: Date): { week: number, year: number } => {
    const target = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()))
    const dayNumber = (target.getUTCDay() + 6) % 7
    target.setUTCDate(target.getUTCDate() - dayNumber + 3)
    const isoYear = target.getUTCFullYear()
    const firstThursday = new Date(Date.UTC(isoYear, 0, 4))
    const firstDayNumber = (firstThursday.getUTCDay() + 6) % 7
    firstThursday.setUTCDate(firstThursday.getUTCDate() - firstDayNumber + 3)
    const week = 1 + Math.round((target.getTime() - firstThursday.getTime()) / (7 * 24 * 3600 * 1000))
    return { week, year: isoYear }
}
const isoWeeksInYear = (year: number) => isoWeekOf(new Date(year, 11, 28)).week
const isoWeekMonday = (year: number, week: number): Date => {
    const jan4 = new Date(year, 0, 4)
    const monday = new Date(jan4)
    monday.setDate(jan4.getDate() - ((jan4.getDay() + 6) % 7) + (week - 1) * 7)
    return monday
}
const toDateString = (date: Date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`

const initialStart = config.startDate ? new Date(config.startDate) : new Date()
const initialEnd = config.endDate ? new Date(config.endDate) : initialStart
const initialStartWeek = isoWeekOf(isNaN(initialStart.getTime()) ? new Date() : initialStart)
const initialEndWeek = isoWeekOf(isNaN(initialEnd.getTime()) ? new Date() : initialEnd)

const kwYear = ref<number>(initialStartWeek.year)
const kwFrom = ref<number>(initialStartWeek.week)
const kwTo = ref<number>(
    initialEndWeek.year === initialStartWeek.year ? initialEndWeek.week : isoWeeksInYear(initialStartWeek.year)
)

const weeksInSelectedYear = computed(() => {
    const year = Number(kwYear.value)
    return (Number.isInteger(year) && year >= 1970 && year <= 2200) ? isoWeeksInYear(year) : 53
})
const kwSelectionInvalid = computed(() => {
    const year = Number(kwYear.value)
    const from = Number(kwFrom.value)
    const to = Number(kwTo.value)
    return !Number.isInteger(year) || year < 1970 || year > 2200 ||
        !Number.isInteger(from) || from < 1 || from > weeksInSelectedYear.value ||
        !Number.isInteger(to) || to < from || to > weeksInSelectedYear.value
})

// In KW mode the calendar week selection drives the exported date range.
watch([periodMode, kwYear, kwFrom, kwTo], () => {
    if (periodMode.value !== 'calendar_week' || kwSelectionInvalid.value) {
        return
    }
    const start = isoWeekMonday(Number(kwYear.value), Number(kwFrom.value))
    const end = isoWeekMonday(Number(kwYear.value), Number(kwTo.value))
    end.setDate(end.getDate() + 6)
    pdf.start = toDateString(start)
    pdf.end = toDateString(end)
})

const rangeDays = computed(() => {
    if (!pdf.start || !pdf.end || pdf.start > pdf.end) return 0
    const start = new Date(`${pdf.start}T00:00:00`)
    const end = new Date(`${pdf.end}T00:00:00`)
    return Math.round((end.getTime() - start.getTime()) / (24 * 3600 * 1000)) + 1
})
const workerMatrixRangeTooLong = computed(
    () => pdf.exportMode === 'worker_matrix' && rangeDays.value > 31
)

// Quick presets for the exported period.
const datePresets = computed(() => {
    const today = new Date()
    const monday = new Date(today)
    monday.setDate(today.getDate() - ((today.getDay() + 6) % 7))
    const sunday = new Date(monday)
    sunday.setDate(monday.getDate() + 6)
    const presets = [
        {
            label: $t('This week'),
            start: toDateString(monday),
            end: toDateString(sunday),
        },
        {
            label: $t('This month'),
            start: toDateString(new Date(today.getFullYear(), today.getMonth(), 1)),
            end: toDateString(new Date(today.getFullYear(), today.getMonth() + 1, 0)),
        },
        {
            label: $t('Next 4 weeks'),
            start: toDateString(monday),
            end: toDateString(new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + 27)),
        },
    ]
    if (config.startDate && config.endDate) {
        presets.unshift({
            label: $t('Current view'),
            start: config.startDate,
            end: config.endDate,
        })
    }
    return presets
})
const applyDatePreset = (preset: { start: string, end: string }) => {
    pdf.start = preset.start
    pdf.end = preset.end
}
const isActiveDatePreset = (preset: { start: string, end: string }) =>
    pdf.start === preset.start && pdf.end === preset.end

const MAX_RANGE_DAYS = 183
const dateRangeError = computed(() => {
    if (!pdf.start || !pdf.end) {
        return $t('Please select a start and end date.')
    }
    const start = new Date(pdf.start)
    const end = new Date(pdf.end)
    if (isNaN(start.getTime()) || isNaN(end.getTime()) || end < start) {
        return $t('The end date must be after the start date.')
    }
    if ((end.getTime() - start.getTime()) / 86400000 > MAX_RANGE_DAYS) {
        return $t('A maximum of six months can be exported in one PDF.')
    }
    return null
})

const previewDays = computed(() => [$t('Day 1'), $t('Day 2'), $t('Day 3')])
const previewRows = computed(() => pdf.exportMode === 'rooms'
    ? [
        { label: $t('Main hall'), cells: [$t('Event + shifts'), '—', $t('Event + shifts')] },
        { label: $t('Studio'), cells: ['—', $t('Event + shifts'), $t('Shifts')] },
    ]
    : [
        { label: $t('Person A'), cells: [$t('Shift · room'), $t('Day service'), '—'] },
        { label: $t('Person B'), cells: [$t('Individual time'), $t('Shift · room'), $t('Shift · room')] },
    ]
)
const exportSummary = computed(() => pdf.exportMode === 'rooms'
    ? {
        rows: $t('Rooms and areas from the current shift plan view'),
        columns: $t('Days in the selected period'),
        contents: $t('Events and shifts; the craft selection filters shifts only'),
    }
    : {
        rows: $t('People from the selected worker types'),
        columns: $t('Days in the selected period (maximum 31)'),
        contents: $t('Enabled shifts, individual times and day services'),
    }
)

const exportDisabled = computed(() =>
    !!dateRangeError.value ||
    (periodMode.value === 'calendar_week' && kwSelectionInvalid.value) ||
    workerMatrixRangeTooLong.value ||
    (pdf.exportMode === 'worker_matrix' && crafts.length > 0 && selectedCraftIds.value.length === 0) ||
    (pdf.exportMode === 'worker_matrix' && pdf.worker_types.length === 0) ||
    pdf.processing
)

const selectExportMode = (mode: string) => {
    pdf.exportMode = mode
    if (mode === 'worker_matrix') {
        selectedPaperSize.value = paperSizes[0]
    }
}

const formatDate = (value: string | null) => {
    if (!value) return '-'
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    return `${day}.${month}.${date.getFullYear()}`
}

const createPdf = () => {
    if (exportDisabled.value) {
        return
    }

    pdf.paperSize = selectedPaperSize.value.id
    pdf.paperOrientation = selectedPaperOrientation.value.id

    if (pdf.exportMode === 'worker_matrix') {
        // Empty array = no craft restriction (all crafts selected).
        pdf.craft_ids = allCraftsSelected.value ? [] : [...selectedCraftIds.value]
    } else {
        pdf.room_ids = [...selections.room_ids]
        pdf.area_ids = [...selections.area_ids]
        pdf.event_type_ids = [...selections.event_type_ids]
        pdf.craft_ids = [...selections.craft_ids]
    }

    const routeName = pdf.exportMode === 'worker_matrix'
        ? 'shift.plan.export.worker-matrix.pdf'
        : 'shift.plan.export.pdf'

    pdf.transform((data: Record<string, unknown>) => {
        // Tagesansicht liefert keine Raum-/Areal-/Terminart-Optionen: Diese Keys
        // dann gar nicht mitsenden — der Controller interpretiert vorhandene
        // leere Arrays als "Dimension löschen" und würde den gespeicherten
        // Daily-Filter des Users für den Export nullen.
        if (data.exportMode !== 'worker_matrix' && !config.filterOptions) {
            const { room_ids, area_ids, event_type_ids, ...rest } = data
            return rest
        }
        return data
    })

    pdf.post(route(routeName), { preserveScroll: true })
    emits('close')
}
</script>
