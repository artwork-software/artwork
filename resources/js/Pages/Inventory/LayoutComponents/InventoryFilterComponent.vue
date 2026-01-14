<!-- resources/js/Components/Inventory/InventoryFilter.vue -->
<template>
    <div class="select-none border-b border-gray-300" :class="showFilter ? 'pb-4' : ''">
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 cursor-pointer" @click="showFilter = !showFilter">
            <div class="flex items-start gap-x-4 hover:text-artwork-buttons-hover">
                <BasePageTitle :title="$t('Filters')" />
                <component
                    :is="IconChevronDown"
                    class="size-5 mt-0.5 transition-transform duration-150"
                    :class="showFilter ? 'rotate-180 transform' : ''"
                />
            </div>

            <!-- compact summary right -->
            <div class="hidden md:flex items-center gap-2 pt-0.5">
                <span v-if="activeSummaryText" class="text-[11px] text-gray-500">
                    {{ activeSummaryText }}
                </span>

                <span
                    v-if="activePresetName"
                    class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2 py-0.5 text-[11px] text-gray-700"
                >
                    <component :is="IconBookmark" class="size-3.5" />
                    {{ activePresetName }}
                </span>
            </div>
        </div>

        <!-- Body -->
        <div v-if="showFilter" class="mt-3">
            <!-- Presets Bar -->
            <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                        <div class="flex items-center gap-2">
                            <component :is="IconBookmark" class="size-4 text-gray-500" />
                            <span class="font-lexend text-xs text-gray-700">{{ $t("Presets") }}</span>
                        </div>

                        <select
                            v-model="selectedPresetId"
                            class="h-10 w-full sm:w-[280px] rounded-lg border border-gray-200 bg-white px-2 text-xs text-gray-900 outline-0 focus:outline-0 ring-0 focus:ring-0"
                            @change.stop
                        >
                            <option :value="null">{{ $t("No preset") }}</option>
                            <option v-for="p in filterPresets" :key="p.id" :value="p.id">
                                {{ p.is_default ? "★ " : "" }}{{ p.name }}
                            </option>
                        </select>

                        <button
                            type="button"
                            class="h-10 inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="!selectedPresetId || isBusy"
                            @click.stop="applyPreset"
                            :title="$t('Apply preset')"
                        >
                            <component :is="IconSparkles" class="size-4" />
                            {{ $t("Apply preset") }}
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <BaseUIButton
                            type="button"
                            is-add-button
                            :disabled="!selectedPresetId || isBusy"
                            @click.stop="openPresetModalForEdit"
                            icon="IconPencil"
                        >
                            {{ $t("Edit") }}
                        </BaseUIButton>

                        <BaseUIButton
                            icon="IconStar"
                            type="button"
                            :disabled="!selectedPresetId || isBusy"
                            @click.stop="setPresetDefault"
                        >
                            {{ $t("Set as default") }}
                        </BaseUIButton>

                        <BaseUIButton
                            type="button"
                            :disabled="!selectedPresetId || isBusy"
                            @click.stop="deletePreset"
                            is-delete-button
                        >
                            {{ $t("Delete") }}
                        </BaseUIButton>

                        <span v-if="filterSource" class="text-[11px] text-gray-500 ml-1">
                            {{ $t("Source") }}: {{ filterSource }}
                        </span>
                    </div>
                </div>

                <!-- Tiny status line -->
                <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <component :is="IconFilter" class="size-3.5" />
                        {{ activeSummaryText || $t("No active filters") }}
                    </span>

                    <span v-if="activePresetName" class="inline-flex items-center gap-1">
                        · <span class="text-gray-400">{{ $t("Preset") }}:</span> {{ activePresetName }}
                    </span>
                </div>
            </div>

            <!-- Property-Filter -->
            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <component :is="IconAdjustments" class="size-4 text-gray-500" />
                        <span class="font-lexend text-xs text-gray-700">{{ $t("Properties") }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <BaseUIButton
                            type="button"
                            is-add-button
                            :disabled="isBusy"
                            @click="submitFilter"
                            icon="IconCheck"
                        >
                            {{ $t("Apply filters") }}
                        </BaseUIButton>

                        <BaseUIButton
                            is-cancel-button
                            v-if="checkIfAnyFilterIsSet"
                            type="button"
                            :disabled="isBusy"
                            @click="resetFilter"
                            icon="IconRefresh"
                        >
                            {{ $t("Reset") }}
                        </BaseUIButton>
                    </div>
                </div>

                <div class="grid gap-4 mt-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, max-content));">
                    <div v-for="filterProperty in newFilterObject" :key="filterProperty.id">
                        <div class="flex items-center justify-between">
                            <label class="font-lexend text-xs mb-1 whitespace-nowrap text-gray-700">
                                {{ filterProperty.name }}
                            </label>

                            <button
                                v-if="hasValue(filterProperty)"
                                type="button"
                                class="text-[11px] text-gray-400 hover:text-gray-600"
                                @click="clearSingleFilter(filterProperty)"
                                :title="$t('Clear')"
                            >
                                <component :is="IconX" class="size-4" />
                            </button>
                        </div>

                        <!-- Text/Number/Date etc. -->
                        <div
                            class="flex items-center border border-gray-200 rounded-lg bg-white focus-within:ring-2 focus-within:ring-blue-500"
                            v-if="filterProperty.type !== 'selection' && filterProperty.type !== 'checkbox'"
                        >
                            <select
                                v-model="filterProperty.operator"
                                v-if="getAllowedFilters(filterProperty.type).length > 0"
                                class="text-gray-700 min-w-28 text-sm px-2 py-2 border-none rounded-l-lg focus:outline-none focus:ring-0 bg-white"
                            >
                                <option v-for="filter in getAllowedFilters(filterProperty.type)" :key="filter.type" :value="filter.type">
                                    {{ filter.name }}
                                </option>
                            </select>

                            <input
                                v-model="filterProperty.value"
                                :type="filterProperty.type"
                                class="w-full px-3 py-2 xsDark placeholder:xsLight shadow-sm h-10 rounded-lg border-none focus:outline-none focus:ring-0"
                                :placeholder="filterProperty.name"
                                @keydown.enter.prevent="submitFilter"
                            />
                        </div>

                        <!-- Selection -->
                        <div v-if="filterProperty.type === 'selection'" class="w-full">
                            <select
                                @change="submitFilter"
                                v-model="filterProperty.value"
                                class="block shadow-sm w-full h-10 rounded-lg border border-gray-200 bg-white text-xs text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                            >
                                <option value="">{{ $t("Please select") }}</option>
                                <option v-for="value in filterProperty.select_values" :value="value" :key="value">
                                    {{ value }}
                                </option>
                            </select>
                        </div>

                        <!-- Checkbox -->
                        <div v-if="filterProperty.type === 'checkbox'" class="w-full h-full mt-1.5">
                            <div class="flex gap-3 rounded-lg border border-gray-200 bg-white p-2">
                                <div class="flex h-6 shrink-0 items-center">
                                    <div class="group grid size-4 grid-cols-1">
                                        <input
                                            v-model="filterProperty.value"
                                            @change="submitFilter"
                                            :id="`filter-checkbox-${filterProperty.id}`"
                                            :name="`filter-checkbox-${filterProperty.id}`"
                                            type="checkbox"
                                            class="input-checklist"
                                        />
                                    </div>
                                </div>
                                <div class="text-sm/6">
                                    <label :for="`filter-checkbox-${filterProperty.id}`" class="font-medium text-gray-900">
                                        {{ filterProperty.name }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tag-Filter -->
            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm" v-if="tags && tags.length">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <component :is="IconTag" class="size-4 text-gray-500" />
                        <span class="font-lexend text-xs text-gray-700">{{ $t("Tags") }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            v-model="tagSearch"
                            type="text"
                            class="h-9 w-[220px] rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-900 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                            :placeholder="$t('Search tags…')"
                        />

                        <button
                            v-if="selectedTagIds.length"
                            type="button"
                            class="h-9 inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-2 text-[11px] text-gray-700 hover:bg-gray-50"
                            @click="clearTagsOnly"
                        >
                            <component :is="IconX" class="size-4" />
                            {{ $t("Clear tags") }}
                        </button>
                    </div>
                </div>

                <!-- Grouped tags (if tagGroups exists) -->
                <div v-if="tagGroupsSorted.length" class="mt-3 space-y-3">
                    <div
                        v-for="g in tagGroupsSorted"
                        :key="`tg-${g.id}`"
                        class="rounded-lg border border-gray-100 bg-gray-50/40 p-3"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-lexend text-[11px] text-gray-700">
                                    {{ g.name }}
                                </span>
                                <span class="text-[11px] text-gray-400">
                                    ({{ filteredTagsByGroup(g).length }})
                                </span>
                            </div>

                            <button
                                type="button"
                                class="text-[11px] text-gray-400 hover:text-gray-600"
                                @click="toggleGroupCollapsed(g.id)"
                                :title="$t('Collapse / expand')"
                            >
                                <component
                                    :is="IconChevronDown"
                                    class="size-4 transition-transform duration-150"
                                    :class="collapsedGroupIds.includes(g.id) ? '' : 'rotate-180 transform'"
                                />
                            </button>
                        </div>

                        <div v-if="!collapsedGroupIds.includes(g.id)" class="mt-2 flex flex-wrap gap-1.5">
                            <button
                                v-for="tag in filteredTagsByGroup(g)"
                                :key="tag.id"
                                type="button"
                                class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium transition-colors"
                                :class="
                                    selectedTagIds.includes(tag.id)
                                        ? 'bg-indigo-50 border-indigo-200 text-indigo-700'
                                        : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'
                                "
                                @click="toggleTag(tag.id)"
                            >
                                <span
                                    class="inline-block h-2 w-2 rounded-full border border-white/60"
                                    :style="{ backgroundColor: tag.color || '#4f46e5' }"
                                />
                                <span class="truncate max-w-[140px]">
                                    {{ tag.name }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Flat tags fallback -->
                <div v-else class="mt-3 flex flex-wrap gap-1.5">
                    <button
                        v-for="tag in filteredTagsFlat"
                        :key="tag.id"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium transition-colors"
                        :class="
                            selectedTagIds.includes(tag.id)
                                ? 'bg-indigo-50 border-indigo-200 text-indigo-700'
                                : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'
                        "
                        @click="toggleTag(tag.id)"
                    >
                        <span
                            class="inline-block h-2 w-2 rounded-full border border-white/60"
                            :style="{ backgroundColor: tag.color || '#4f46e5' }"
                        />
                        <span class="truncate max-w-[140px]">
                            {{ tag.name }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Footer actions -->
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <BaseUIButton icon="IconCheck" is-add-button type="button" @click="submitFilter" :disabled="isBusy">
                    {{ $t("Apply filters") }}
                </BaseUIButton>

                <BaseUIButton v-if="checkIfAnyFilterIsSet" icon="IconRefresh" is-cancel-button type="button" @click="resetFilter" :disabled="isBusy">
                    {{ $t("Reset") }}
                </BaseUIButton>

                <BaseUIButton
                    v-if="checkIfAnyFilterIsSet"
                    type="button"
                    is-add-button
                    @click="openPresetModalForCreate"
                    icon="IconBookmark"
                >
                    {{ $t("Save as preset") }}
                </BaseUIButton>
            </div>
        </div>

        <!-- Active Filter Chips -->
        <div class="my-3 flex flex-wrap gap-2" v-if="activeChips.length">
            <div
                v-for="chip in activeChips"
                :key="chip.key"
                class="flex items-center rounded-full px-3 py-1 text-sm font-medium border"
                :style="chip.style"
                :class="chip.class"
            >
                <span class="max-w-[260px] truncate">{{ chip.label }}</span>
                <button type="button" class="ml-2 hover:opacity-80" @click="chip.onRemove">
                    <component :is="IconX" class="size-4" />
                </button>
            </div>
        </div>

        <!-- Preset Builder Modal -->
        <ArtworkBaseModal
            v-if="presetModalOpen"
            modalSize="sm:max-w-2xl"
            :title="presetModalMode === 'edit' ? $t('Edit preset') : $t('Create preset')"
            :description="$t('Save your current filters as a preset or overwrite an existing preset.')"
            @close="closePresetModal"
        >
            <div class="space-y-5">
                <!-- Form -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="font-lexend text-xs text-gray-700">{{ $t("Name") }}</label>
                        <input
                            v-model="presetForm.name"
                            type="text"
                            class="mt-1 h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                            :placeholder="$t('e.g. Camera setup, Site A, …')"
                        />
                    </div>

                    <div class="sm:col-span-1">
                        <label class="font-lexend text-xs text-gray-700">{{ $t("Action") }}</label>
                        <div class="mt-1 rounded-lg border border-gray-200 bg-white p-2">
                            <label class="flex items-center gap-2 text-sm text-gray-800">
                                <input
                                    v-model="presetForm.overwriteSelected"
                                    type="checkbox"
                                    class="input-checklist"
                                    :disabled="!selectedPresetId"
                                />
                                <span class="text-xs">
                                    {{ $t("Overwrite selected preset") }}
                                    <span v-if="!selectedPresetId" class="text-gray-400">{{ $t("(none selected)") }}</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="sm:col-span-1">
                        <label class="font-lexend text-xs text-gray-700">{{ $t("Default") }}</label>
                        <div class="mt-1 rounded-lg border border-gray-200 bg-white p-2">
                            <label class="flex items-center gap-2 text-sm text-gray-800">
                                <input v-model="presetForm.isDefault" type="checkbox" class="input-checklist" />
                                <span class="text-xs">{{ $t("Set as default preset") }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="rounded-xl border border-gray-200 bg-gray-50/40 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <component :is="IconEye" class="size-4 text-gray-500" />
                            <span class="font-lexend text-xs text-gray-700">{{ $t("Preview") }}</span>
                        </div>
                        <span class="text-[11px] text-gray-500">
                            {{ $t("Will be saved") }}: {{ previewSummary }}
                        </span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            v-for="p in previewPropertyChips"
                            :key="p.key"
                            class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[12px] font-medium text-blue-700"
                        >
                            {{ p.label }}
                        </span>

                        <span
                            v-for="t in activeTagObjects"
                            :key="`prev-tag-${t.id}`"
                            class="inline-flex items-center rounded-full border px-3 py-1 text-[12px] font-medium"
                            :style="tagChipStyle(t)"
                        >
                            {{ t.name }}
                        </span>

                        <span v-if="!previewPropertyChips.length && !activeTagObjects.length" class="text-[12px] text-gray-500">
                            {{ $t("No filters selected.") }}
                        </span>
                    </div>
                </div>

                <!-- Error -->
                <div v-if="presetError" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ presetError }}
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <button
                        type="button"
                        class="h-10 inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 hover:bg-gray-50"
                        :disabled="isBusy"
                        @click="closePresetModal"
                    >
                        <component :is="IconX" class="size-4" />
                        {{ $t("Cancel") }}
                    </button>

                    <button
                        type="button"
                        class="h-10 inline-flex items-center gap-2 rounded-lg bg-gray-900 px-3 text-sm text-white hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isBusy || !canSavePreset"
                        @click="savePresetFromModal"
                    >
                        <component
                            :is="isBusy ? IconLoader2 : IconDeviceFloppy"
                            class="size-4"
                            :class="isBusy ? 'animate-spin' : ''"
                        />
                        {{ presetModalMode === "edit" ? $t("Save") : $t("Create preset") }}
                    </button>
                </div>
            </div>
        </ArtworkBaseModal>
    </div>
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from "vue"
import axios from "axios"
import SmallFormButton from "@/Components/Buttons/SmallFormButton.vue"
import { router, usePage } from "@inertiajs/vue3"
import { isBool } from "@aesoper/normal-utils"
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue"
import { useTranslation } from "@/Composeables/Translation.js"

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue"

import {
    IconChevronDown,
    IconX,
    IconBookmark,
    IconPlus,
    IconPencil,
    IconTrash,
    IconStar,
    IconSparkles,
    IconFilter,
    IconAdjustments,
    IconCheck,
    IconRefresh,
    IconTag,
    IconEye,
    IconDeviceFloppy,
    IconLoader2,
} from "@tabler/icons-vue"
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const $t = useTranslation()
const page = usePage()

const props = defineProps({
    filterableProperties: {
        type: Object,
        required: true,
    },
})

const showFilter = ref(false)

const filterTypes = [
    { name: "Enthält", type: "like", allowedTypes: ["string", "room", "manufacturer"] },
    { name: "Beginnt mit", type: "starts_with", allowedTypes: ["string", "room", "manufacturer"] },
    { name: "Endet mit", type: "ends_with", allowedTypes: ["string", "room", "manufacturer"] },
    { name: "Genau", type: "exact", allowedTypes: ["string", "date"] },
    { name: "Kleiner als", type: "less_than", allowedTypes: ["string", "date"] },
    { name: "Größer als", type: "greater_than", allowedTypes: ["string", "date"] },
    { name: "Bis", type: "until", allowedTypes: ["date"] },
    { name: "Von", type: "from", allowedTypes: ["date"] },
    { name: "Gleich", type: "equals", allowedTypes: ["string", "date", "room", "manufacturer"] },
    { name: "Ungleich", type: "not_equals", allowedTypes: ["boolean", "date"] },
    { name: "Ist leer", type: "is_null", allowedTypes: ["string", "date", "boolean"] },
    { name: "Enthält nicht", type: "not_like", allowedTypes: ["string"] },
    { name: "Datum vor", type: "date_before", allowedTypes: ["date", "datetime", "time"] },
    { name: "Datum nach", type: "date_after", allowedTypes: ["date", "datetime", "time"] },
]

const newFilterObject = ref([])

// Tags from Inertia
const tagGroups = inject("tagGroups", [])
const tags = inject("tags", [])

// Presets from backend props
const filterPresets = computed(() => page.props.filterPresets ?? [])
const filterSource = computed(() => page.props.filterSource ?? null)

const selectedPresetId = ref(page.props.activeFilterPresetId ?? null)
watch(
    () => page.props.activeFilterPresetId,
    (v) => {
        selectedPresetId.value = v ?? null
    }
)

const selectedTagIds = ref([])

const appliedFilters = computed(() => page.props.appliedFilters ?? [])
const appliedTagIds = computed(() => page.props.appliedTagIds ?? [])

const isBusy = ref(false)

// Tag Search + group collapsing
const tagSearch = ref("")
const collapsedGroupIds = ref([])

function initFromApplied() {
    const active = Array.isArray(appliedFilters.value) ? appliedFilters.value : []

    newFilterObject.value = []

    props.filterableProperties.forEach((property) => {
        if (property.type === "file") return

        const existingFilter = active.find((f) => f.property_id === property.id)

        newFilterObject.value.push({
            id: property.id,
            name: property.name,
            operator: existingFilter?.operator ?? "like",
            value: existingFilter?.value ?? "",
            type: property.type,
            select_values: property.select_values,
        })
    })

    selectedTagIds.value = Array.isArray(appliedTagIds.value)
        ? appliedTagIds.value.map((id) => Number(id)).filter((n) => !Number.isNaN(n))
        : []
}

onMounted(() => {
    initFromApplied()
})

watch([appliedFilters, appliedTagIds], () => {
    initFromApplied()
})

const getAllowedFilters = (type) => {
    if (!type) return []
    return filterTypes.filter((f) => f.allowedTypes.includes(type))
}

const hasValue = (filter) => {
    if (filter.type === "checkbox") return !!filter.value
    if (filter.type === "selection") return filter.value !== ""
    return filter.value !== "" && filter.value !== null && filter.value !== false
}

const clearSingleFilter = (filter) => {
    filter.value = filter.type === "checkbox" ? false : ""
    filter.operator = filter.operator ?? "like"
    submitFilter()
}

const buildCleanFilters = () => {
    return newFilterObject.value
        .filter((f) => f.value !== "" && f.value !== null && f.value !== false)
        .map((f) => ({
            property_id: f.id,
            operator: f.operator,
            value: f.value,
        }))
}

const submitFilter = () => {
    // manual filtering -> detach preset selection
    selectedPresetId.value = null

    const cleanFilters = buildCleanFilters()

    router.reload({
        data: {
            filters: JSON.stringify(cleanFilters),
            tag_ids: selectedTagIds.value,
            filter_preset_id: null,
        },
    })
}

const resetFilter = () => {
    newFilterObject.value.forEach((filter) => {
        filter.value = filter.type === "checkbox" ? false : ""
        filter.operator = "like"
    })

    selectedTagIds.value = []
    selectedPresetId.value = null

    router.reload({
        data: {
            filters: JSON.stringify([]),
            tag_ids: [],
            filter_preset_id: null,
        },
    })
}

const removeFilter = (filter) => {
    filter.value = filter.type === "checkbox" ? false : ""
    submitFilter()
}

const checkIfAnyFilterIsSet = computed(() => {
    const hasProps = newFilterObject.value.some((filter) => hasValue(filter))
    const hasTags = selectedTagIds.value.length > 0
    return hasProps || hasTags
})

/**
 * Tag helpers
 */
const toggleTag = (tagId) => {
    const idx = selectedTagIds.value.indexOf(tagId)
    if (idx === -1) selectedTagIds.value.push(tagId)
    else selectedTagIds.value.splice(idx, 1)
}

const clearTagsOnly = () => {
    selectedTagIds.value = []
    submitFilter()
}

const removeTag = (tagId) => {
    selectedTagIds.value = selectedTagIds.value.filter((id) => id !== tagId)
    submitFilter()
}

const activeTagObjects = computed(() => {
    if (!Array.isArray(tags) || !selectedTagIds.value.length) return []
    return tags.filter((t) => selectedTagIds.value.includes(t.id))
})

const tagChipStyle = (tag) => {
    const base = tag?.color || "#2563eb"
    return {
        backgroundColor: base + "10",
        borderColor: base + "40",
        color: base,
    }
}

const tagGroupsSorted = computed(() => {
    const arr = Array.isArray(tagGroups) ? tagGroups : []
    return [...arr].sort((a, b) => (a.position ?? 0) - (b.position ?? 0))
})

const filteredTagsFlat = computed(() => {
    const list = Array.isArray(tags) ? tags : []
    const q = (tagSearch.value || "").trim().toLowerCase()
    if (!q) return list
    return list.filter((t) => (t.name || "").toLowerCase().includes(q))
})

const filteredTagsByGroup = (group) => {
    const gTags = Array.isArray(group?.tags) ? group.tags : []
    const q = (tagSearch.value || "").trim().toLowerCase()
    const list = q ? gTags.filter((t) => (t.name || "").toLowerCase().includes(q)) : gTags
    return list
}

const toggleGroupCollapsed = (groupId) => {
    const idx = collapsedGroupIds.value.indexOf(groupId)
    if (idx === -1) collapsedGroupIds.value.push(groupId)
    else collapsedGroupIds.value.splice(idx, 1)
}

/**
 * Presets
 */
const activePresetName = computed(() => {
    if (!selectedPresetId.value) return null
    const p = filterPresets.value.find((x) => x.id === selectedPresetId.value)
    return p?.name ?? null
})

const applyPreset = () => {
    if (!selectedPresetId.value) return
    router.reload({
        data: {
            filter_preset_id: selectedPresetId.value,
        },
    })
}

const deletePreset = async () => {
    if (!selectedPresetId.value) return
    if (!confirm($t("Are you sure you want to delete this preset?") ?? "Are you sure you want to delete this preset?")) return

    try {
        isBusy.value = true
        await axios.delete(route("inventory.filter-presets.destroy", selectedPresetId.value))
        selectedPresetId.value = null
        router.reload()
    } finally {
        isBusy.value = false
    }
}

const setPresetDefault = async () => {
    if (!selectedPresetId.value) return

    try {
        isBusy.value = true
        await axios.put(route("inventory.filter-presets.update", selectedPresetId.value), {
            is_default: true,
        })
        router.reload()
    } finally {
        isBusy.value = false
    }
}

/**
 * Active chips (properties + tags)
 */
const activePropertyChips = computed(() => {
    return newFilterObject.value
        .filter((f) => hasValue(f))
        .map((f) => ({
            key: `p-${f.id}`,
            label: `${f.name}${isBool(f.value) ? "" : ": " + f.value}`,
            class: "bg-blue-50 text-blue-700 border-blue-100",
            style: null,
            onRemove: () => removeFilter(f),
        }))
})

const activeTagChips = computed(() => {
    return activeTagObjects.value.map((t) => ({
        key: `t-${t.id}`,
        label: t.name,
        class: "",
        style: tagChipStyle(t),
        onRemove: () => removeTag(t.id),
    }))
})

const activeChips = computed(() => [...activePropertyChips.value, ...activeTagChips.value])

const activeSummaryText = computed(() => {
    const p = activePropertyChips.value.length
    const t = activeTagChips.value.length
    if (!p && !t) return ""
    const parts = []
    if (p) parts.push(`${p} ${$t("Property filters")}`)
    if (t) parts.push(`${t} ${$t("Tag filters")}`)
    return parts.join(" · ")
})

/**
 * Preset Modal (Builder)
 */
const presetModalOpen = ref(false)
const presetModalMode = ref("create") // create | edit

const presetError = ref("")
const presetForm = ref({
    name: "",
    isDefault: false,
    overwriteSelected: false,
})

const previewPropertyChips = computed(() => activePropertyChips.value.map((c) => ({ key: c.key, label: c.label })))
const previewSummary = computed(() => activeSummaryText.value || $t("No active filters"))

const canSavePreset = computed(() => {
    const nameOk = (presetForm.value.name || "").trim().length >= 2
    const hasSomething = checkIfAnyFilterIsSet.value
    const overwriteOk = !presetForm.value.overwriteSelected || !!selectedPresetId.value
    return nameOk && hasSomething && overwriteOk
})

const openPresetModalForCreate = () => {
    presetModalMode.value = "create"
    presetError.value = ""
    presetForm.value = {
        name: activePresetName.value ? `${activePresetName.value} (Copy)` : "",
        isDefault: false,
        overwriteSelected: false,
    }
    presetModalOpen.value = true
}

const openPresetModalForEdit = () => {
    if (!selectedPresetId.value) return
    const p = filterPresets.value.find((x) => x.id === selectedPresetId.value)

    presetModalMode.value = "edit"
    presetError.value = ""
    presetForm.value = {
        name: p?.name ?? "",
        isDefault: !!p?.is_default,
        overwriteSelected: true,
    }
    presetModalOpen.value = true
}

const closePresetModal = () => {
    presetModalOpen.value = false
    presetError.value = ""
}

const savePresetFromModal = async () => {
    presetError.value = ""

    const name = (presetForm.value.name || "").trim()
    if (!name) {
        presetError.value = $t("Please enter a name.")
        return
    }

    const cleanFilters = buildCleanFilters()
    const currentCategoryId = page.props.currentCategory?.id ?? null
    const currentSubCategoryId = page.props.currentSubCategory?.id ?? null

    try {
        isBusy.value = true

        if (presetForm.value.overwriteSelected && selectedPresetId.value) {
            await axios.put(route("inventory.filter-presets.update", selectedPresetId.value), {
                name,
                filters: cleanFilters,
                tag_ids: selectedTagIds.value,
                is_default: presetForm.value.isDefault,
            })
        } else {
            await axios.post(route("inventory.filter-presets.store"), {
                name,
                inventory_category_id: currentCategoryId,
                inventory_sub_category_id: currentSubCategoryId,
                filters: cleanFilters,
                tag_ids: selectedTagIds.value,
                is_default: presetForm.value.isDefault,
            })
        }

        presetModalOpen.value = false
        router.reload()
    } catch (e) {
        presetError.value = $t("Saving failed. Please check your inputs.")
        console.error(e)
    } finally {
        isBusy.value = false
    }
}
</script>

<style scoped></style>
