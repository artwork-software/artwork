<template>
    <div class="mx-auto w-full max-w-4xl">
        <div class="flex flex-col space-y-6">
            <!-- Title + hint -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <BaseInput id="shiftPlanTitle" v-model="pdf.title" :label="$t('Heading')" />

                <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-4">
                    <div class="flex items-start gap-x-3">
                        <PropertyIcon name="IconExclamationCircle" class="size-5 min-h-5 min-w-5 text-blue-500"/>
                        <p class="text-sm text-blue-500">
                            {{ $t('The export is prefilled with your current shift plan view. Time period and filters can be adjusted below before exporting. Each calendar week is placed on its own page.') }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Time period -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-zinc-900">{{ $t('Period') }}</h2>
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
                            ? 'border-blue-200 bg-blue-50 text-blue-700'
                            : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900'"
                        @click="applyDatePreset(preset)"
                    >
                        {{ preset.label }}
                    </button>
                </div>
                <p v-if="dateRangeError" class="text-xs text-red-600">{{ dateRangeError }}</p>
                <p v-else class="text-xs text-zinc-500">{{ $t('A maximum of six months can be exported in one PDF.') }}</p>
            </section>

            <!-- Filters -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-5">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-900">{{ $t('Filter') }}</h2>
                    <p class="mt-1 text-xs text-zinc-500">
                        {{ $t('No selection in a group means it is not filtered by it.') }}
                    </p>
                </div>

                <div v-for="group in filterGroups" :key="group.key" class="space-y-2">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-zinc-700">
                            {{ group.label }}
                            <span v-if="selections[group.key].length > 0" class="ml-1 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
                                {{ selections[group.key].length }}
                            </span>
                        </span>
                        <button
                            v-if="group.options.length > 0"
                            type="button"
                            class="text-xs text-zinc-500 hover:text-zinc-900"
                            @click="toggleGroup(group.key)"
                        >
                            {{ selections[group.key].length === group.options.length ? $t('Deselect all') : $t('Select all') }}
                        </button>
                    </div>
                    <div v-if="group.options.length > 0" class="grid max-h-40 grid-cols-1 gap-y-1.5 overflow-y-auto sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="option in group.options"
                            :key="option.id"
                            class="flex cursor-pointer items-center gap-2 pr-2 text-xs text-zinc-700"
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
                    <p v-else class="text-xs text-zinc-400">–</p>
                </div>
            </section>

            <!-- Content options -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" v-model="pdf.hideEmptyRooms" class="input-checklist mt-0.5" />
                    <span>
                        <span class="block text-sm font-medium text-zinc-800">{{ $t('Hide empty rooms') }}</span>
                        <span class="block text-xs text-zinc-500">
                            {{ $t('Rooms without events or shifts in the selected period are omitted from the PDF.') }}
                        </span>
                    </span>
                </label>
            </section>

            <!-- Paper format + orientation + DPI -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Paper size -->
                    <div class="space-y-2">
                        <Listbox as="div" v-model="selectedPaperSize">
                            <ListboxLabel class="block text-sm font-medium text-zinc-700">
                                {{ $t('Paper size') }}
                            </ListboxLabel>
                            <div class="relative mt-1">
                                <ListboxButton
                                    class="relative w-full cursor-pointer rounded-xl border border-zinc-200 bg-white px-4 py-3 text-left text-sm hover:bg-zinc-50">
                                    <div class="block truncate">{{ selectedPaperSize.name }}</div>
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <component :is="IconSelector" class="h-5 w-5 text-zinc-400" />
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
                                            <li :class="['relative cursor-pointer select-none py-2 pl-3 pr-9', active ? 'bg-zinc-900 text-white' : 'text-zinc-900']">
                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                                    {{ paperSize.name }}
                                                </span>
                                                <span v-if="selected" :class="[active ? 'text-white' : 'text-zinc-900', 'absolute inset-y-0 right-0 flex items-center pr-4']">
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
                        <label class="block text-sm font-medium text-zinc-700">
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
                                        peer-checked:border-zinc-900 peer-checked:bg-zinc-900 peer-checked:text-white
                                        border-zinc-200 bg-white text-zinc-800 hover:bg-zinc-50 hover:text-primary">
                                        {{ paperOrientation.title }}
                                    </label>
                                </div>
                            </div>
                            <span class="mt-2 block text-xs text-zinc-500">
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
                    :disabled="!!dateRangeError"
                    is-add-button
                />
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
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

const selectedPaperSize = ref(paperSizes[1])
const selectedPaperOrientation = ref(paperOrientations[0])

// Filter selections, prefilled with the filters currently active in the shift plan.
// An empty selection means "do not filter by this dimension".
const selections = reactive({
    room_ids: [...(config.activeFilters?.room_ids ?? [])],
    area_ids: [...(config.activeFilters?.area_ids ?? [])],
    event_type_ids: [...(config.activeFilters?.event_type_ids ?? [])],
    craft_ids: [...(config.activeFilters?.craft_ids ?? [])],
})

const filterGroups = computed(() => [
    { key: 'room_ids', label: $t('Rooms'), options: config.filterOptions?.rooms ?? [] },
    { key: 'area_ids', label: $t('Areas'), options: config.filterOptions?.areas ?? [] },
    { key: 'event_type_ids', label: $t('Event types'), options: config.filterOptions?.eventTypes ?? [] },
    { key: 'craft_ids', label: $t('Crafts'), options: config.filterOptions?.crafts ?? [] },
])

const toggleGroup = (key: keyof typeof selections) => {
    const group = filterGroups.value.find((g) => g.key === key)
    if (!group) return
    selections[key] = selections[key].length === group.options.length
        ? []
        : group.options.map((option: { id: number }) => option.id)
}

const pdf = useForm({
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
    paperSize: 'a4',
    paperOrientation: 'landscape',
    dpi: 96,
})

// Quick presets for the exported period.
const toDateString = (date: Date) => {
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${date.getFullYear()}-${month}-${day}`
}
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

const createPdf = () => {
    if (dateRangeError.value) {
        return
    }
    pdf.paperSize = selectedPaperSize.value.id
    pdf.paperOrientation = selectedPaperOrientation.value.id
    pdf.room_ids = [...selections.room_ids]
    pdf.area_ids = [...selections.area_ids]
    pdf.event_type_ids = [...selections.event_type_ids]
    pdf.craft_ids = [...selections.craft_ids]

    pdf.post(route('shift.plan.export.pdf'), { preserveScroll: true })
    emits('close')
}
</script>
