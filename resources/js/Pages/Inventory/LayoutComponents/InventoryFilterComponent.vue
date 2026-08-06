<!--
    Filter-Trigger + Modal der Inventarübersicht — gleiches Muster wie
    Kalender/Schichtplan: Icon-Button mit blauem Aktiv-Punkt, Modal mit
    "Gespeicherte Filter" (Chips, Klick = anwenden, Stern = Standard,
    × = löschen, "Speichern"-Link → Namensfeld), darunter Eigenschaften
    und Tags. Alle Feldtypen haben dieselbe Höhe (38px wie die Listboxen).
    Änderungen wirken sofort (Texteingaben debounced, Rest direkt).
-->
<template>
    <div class="relative">
        <ToolTipComponent
            direction="bottom"
            :tooltip-text="$t('Filter')"
            icon="IconFilter"
            icon-size="h-5 w-5"
            :white-icon="onBand"
            :classes-button="onBand ? BAND_ICON_BUTTON_CLASSES : 'ui-button'"
            @click="openModal"
        />
        <span class="absolute flex size-2.5 top-0 right-0 pointer-events-none" v-if="activeFilterCount > 0">
            <span class="relative inline-flex size-2.5 rounded-full bg-accent-600"></span>
        </span>
    </div>

    <teleport to="body">
        <ArtworkBaseModal
            v-if="showModal"
            modal-size="max-w-4xl"
            :title="$t('Inventory Filter')"
            :description="$t('Narrow down the article list. Changes take effect immediately.')"
            full-modal
            @close="showModal = false"
        >
            <!-- Gespeicherte Filter (Kalender-Workflow + Standard-Stern) -->
            <div>
                <div class="flex items-start justify-between">
                    <BasePageTitle
                        v-if="!saveFilterOption"
                        :title="$t('Saved filters')"
                        :description="$t('Your saved filters. Click on a filter to apply it.')"
                    />
                    <BasePageTitle
                        v-else
                        :title="$t('Save filter')"
                        :description="$t('Save your current filter settings.')"
                    />

                    <div class="select-none shrink-0">
                        <div
                            v-if="!saveFilterOption"
                            @click="startSaveFilter"
                            class="underline text-accent-600 text-sm underline-offset-2 cursor-pointer hover:text-accent-700 duration-200 ease-in-out"
                        >
                            {{ $t('Save') }}
                        </div>
                        <div
                            v-else
                            @click="saveFilterOption = false"
                            class="underline text-danger text-sm underline-offset-2 cursor-pointer hover:text-danger duration-200 ease-in-out"
                        >
                            {{ $t('Cancel') }}
                        </div>
                    </div>
                </div>

                <div class="mb-4 pb-4 border-b-2 border-dashed border-border">
                    <div v-if="!saveFilterOption" class="flex flex-wrap items-center gap-2 mt-3">
                        <div
                            v-for="preset in filterPresets"
                            :key="preset.id"
                            class="group flex items-center shrink-0 bg-accent-50 w-fit px-2 py-1.5 rounded-full border"
                            :class="activePresetId === preset.id ? 'border-accent-600' : 'border-accent-200'"
                        >
                            <button type="button" class="mx-1.5" @click="applyPreset(preset)">
                                <p class="text-accent-600 text-xs group-hover:text-accent-600">{{ preset.name }}</p>
                            </button>
                            <button
                                type="button"
                                class="flex items-center"
                                :title="$t('Set as default')"
                                @click="togglePresetDefault(preset)"
                            >
                                <component
                                    :is="IconStar"
                                    class="size-4"
                                    :class="preset.is_default ? 'text-warning fill-warning' : 'text-accent-500 hover:text-warning'"
                                />
                            </button>
                            <button type="button" class="ml-1 flex items-center" @click="deletePreset(preset)">
                                <component :is="IconX" class="size-4 text-accent-600 hover:text-danger" />
                            </button>
                        </div>

                        <span v-if="!filterPresets.length" class="text-xs text-text-subtle">
                            {{ $t('No saved filters yet.') }}
                        </span>
                    </div>

                    <div v-else class="flex items-center gap-x-4 mt-3">
                        <BaseInput
                            id="inventoryFilterPresetName"
                            v-model="saveFilterName"
                            label="Filter name"
                        />
                        <BaseUIButton
                            type="button"
                            label="Save"
                            use-translation
                            is-add-button
                            :disabled="!canSaveFilter || isBusy"
                            @click="saveFilter"
                        />
                    </div>

                    <div v-if="presetError" class="mt-2 rounded-lg border border-danger-border bg-danger-surface px-3 py-2 text-sm text-danger">
                        {{ presetError }}
                    </div>
                </div>
            </div>

            <!-- Eigenschaften -->
            <div v-if="newFilterObject.length" class="mb-4 pb-4 border-b-2 border-dashed border-border">
                <BasePageTitle
                    :title="$t('Properties')"
                    :description="$t('Filter changes take effect immediately')"
                />

                <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2 xl:grid-cols-3">
                    <div v-for="filterProperty in newFilterObject" :key="filterProperty.id" class="min-w-0">
                        <div class="flex items-center justify-between h-5 mb-1">
                            <label class="font-lexend text-xs text-text-muted truncate">
                                {{ filterProperty.name }}
                            </label>

                            <button
                                v-if="hasValue(filterProperty)"
                                type="button"
                                class="text-text-subtle hover:text-text-muted shrink-0"
                                :title="$t('Clear')"
                                @click="clearSingleFilter(filterProperty)"
                            >
                                <component :is="IconX" class="size-4" />
                            </button>
                        </div>

                        <!-- Text/Zahl/Datum: Operator + Wert, beide 38px hoch -->
                        <div
                            v-if="filterProperty.type !== 'selection' && filterProperty.type !== 'checkbox'"
                            class="flex items-center gap-1"
                        >
                            <SearchableSelect
                                v-if="getAllowedFilters(filterProperty.type).length > 0"
                                v-model="filterProperty.operator"
                                :options="getAllowedFilters(filterProperty.type)"
                                value-key="type"
                                label-key="name"
                                class="w-32 shrink-0"
                                :button-class="COMPACT_LISTBOX_BUTTON_CLASS"
                                @update:model-value="onOperatorChange(filterProperty)"
                            />

                            <input
                                v-model="filterProperty.value"
                                :type="inputTypeFor(filterProperty.type)"
                                class="h-[42px] w-full min-w-0 rounded-md border border-border-subtle bg-white shadow-sm px-3 text-sm text-text placeholder:text-text-subtle focus:outline-none focus:ring-1 focus:ring-accent-600 focus:border-accent-600"
                                :placeholder="filterProperty.name"
                                @input="debouncedSubmit()"
                                @keydown.enter.prevent="applyNow()"
                            />
                        </div>

                        <!-- Auswahl -->
                        <SearchableSelect
                            v-if="filterProperty.type === 'selection'"
                            :model-value="filterProperty.value"
                            @update:model-value="v => onSelectionChange(filterProperty, v)"
                            :options="filterProperty.select_values"
                            :empty-option="{ label: 'Please select', value: '' }"
                            :placeholder="$t('Please select')"
                            :button-class="COMPACT_LISTBOX_BUTTON_CLASS"
                        />

                        <!-- Checkbox: gleiche Höhe wie die übrigen Felder -->
                        <div
                            v-if="filterProperty.type === 'checkbox'"
                            class="h-[42px] flex items-center rounded-md border border-border-subtle bg-white shadow-sm px-3"
                        >
                            <BaseCheckbox
                                :model-value="!!filterProperty.value"
                                :label="filterProperty.name"
                                @update:model-value="v => onCheckboxChange(filterProperty, v)"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tags -->
            <div v-if="allTags.length" class="mb-2">
                <div class="flex items-start justify-between gap-3">
                    <BasePageTitle :title="$t('Tags')" />

                    <div class="flex items-center gap-2 shrink-0">
                        <div class="w-56">
                            <BaseInput
                                id="inventoryTagSearch"
                                v-model="tagSearch"
                                is-small
                                :label="$t('Search tags…')"
                            />
                        </div>

                        <button
                            v-if="selectedTagIds.length"
                            type="button"
                            class="h-9 inline-flex items-center gap-1.5 rounded-md border border-border-subtle bg-white px-2 text-[11px] text-text-muted hover:bg-surface-sunken"
                            @click="clearTagsOnly"
                        >
                            <component :is="IconX" class="size-4" />
                            {{ $t('Clear tags') }}
                        </button>
                    </div>
                </div>

                <div class="mt-3 space-y-3">
                    <div
                        v-for="group in displayTagGroups"
                        :key="`tg-${group.key}`"
                        :class="group.name ? 'rounded-lg border border-border-subtle bg-surface-sunken/40 p-3' : ''"
                    >
                        <div v-if="group.name" class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-lexend text-[11px] text-text-muted">{{ group.name }}</span>
                                <span class="text-[11px] text-text-subtle">({{ group.tags.length }})</span>
                            </div>

                            <button
                                type="button"
                                class="text-text-subtle hover:text-text-muted"
                                :title="$t('Collapse / expand')"
                                @click="toggleGroupCollapsed(group.key)"
                            >
                                <component
                                    :is="IconChevronDown"
                                    class="size-4 transition-transform duration-150"
                                    :class="collapsedGroupIds.includes(group.key) ? '' : 'rotate-180 transform'"
                                />
                            </button>
                        </div>

                        <div
                            v-if="!group.name || !collapsedGroupIds.includes(group.key)"
                            class="flex flex-wrap gap-1.5"
                            :class="group.name ? 'mt-2' : ''"
                        >
                            <button
                                v-for="tag in group.tags"
                                :key="tag.id"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition-colors"
                                :class="selectedTagIds.includes(tag.id) ? 'bg-accent-50 border-accent-200 text-accent-700'
                                    : 'bg-white border-border-subtle text-text-muted hover:bg-surface-sunken'"
                                @click="toggleTag(tag.id)"
                            >
                                <span
                                    class="inline-block h-2 w-2 rounded-full border border-white/60"
                                    :style="{ backgroundColor: tag.color || '#4f46e5' }"
                                />
                                <span class="truncate max-w-[140px]">{{ tag.name }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fußzeile -->
            <div class="mt-4 flex items-center justify-start" v-if="checkIfAnyFilterIsSet">
                <button
                    type="button"
                    class="text-xs text-text-subtle underline underline-offset-2 hover:text-danger transition-colors"
                    @click="resetPanelFilters"
                >
                    {{ $t('Reset all filters') }}
                </button>
            </div>
        </ArtworkBaseModal>
    </teleport>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import axios from "axios"
import debounce from "lodash.debounce"
import { router, usePage } from "@inertiajs/vue3"
import SearchableSelect from "@/Artwork/Listbox/SearchableSelect.vue"
import BaseInput from "@/Artwork/Inputs/BaseInput.vue"
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue"
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue"
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue"
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue"
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue"
import { useTranslation } from "@/Composeables/Translation.js"
import {
    COMPACT_LISTBOX_BUTTON_CLASS,
    FILTER_OPERATORS,
    useInventoryFilters,
} from "@/Pages/Inventory/Composables/useInventoryFilters.js"

import { IconChevronDown, IconStar, IconX } from "@tabler/icons-vue"

const $t = useTranslation()
const page = usePage()

const props = defineProps({
    filterableProperties: {
        type: Array,
        required: false,
        default: () => [],
    },
    /** Trigger sitzt im dunklen Toolbar-Band (CI »Bühnenlicht«):
     *  Icon-Kachel weiß-transluzent + weißes Icon. Das Modal bleibt hell. */
    onBand: {
        type: Boolean,
        default: false,
    },
})

// Icon-Kachel auf dem dunklen Toolbar-Band (Spec §3)
const BAND_ICON_BUTTON_CLASSES =
    'select-none size-[30px] inline-flex items-center justify-center rounded-md bg-white/8 hover:bg-white/16 ' +
    'text-text-inverse transition-[background-color] duration-150 ease-out cursor-pointer'

const {
    appliedFilters,
    appliedTagIds,
    activePresetId,
    activeFilterCount,
    filterPresets,
    allProperties,
    allTags,
    apply,
} = useInventoryFilters()

const showModal = ref(false)
const isBusy = ref(false)

// tagGroups sind Page-Props (Index reicht sie an Inertia durch)
const tagGroups = computed(() =>
    Array.isArray(page.props.tagGroups) ? page.props.tagGroups : []
)

/**
 * Editier-Zustand des Modals
 */
const newFilterObject = ref([])
const selectedTagIds = ref([])
const tagSearch = ref("")
const collapsedGroupIds = ref([])

function initFromApplied() {
    const active = appliedFilters.value

    newFilterObject.value = []
    const seenIds = new Set()

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
        seenIds.add(property.id)
    })

    // Filter gelten global: ein aktiver Filter kann sich auf eine Eigenschaft
    // beziehen, die in der aktuellen Kategorie nicht filterbar ist. Solche Filter
    // trotzdem sichtbar & editierbar halten, damit sie kategorieübergreifend
    // erhalten bleiben und beim Absenden nicht still verworfen werden.
    active.forEach((f) => {
        if (seenIds.has(f.property_id)) return

        const property = allProperties.value.find((p) => p.id === f.property_id)
        if (!property || property.type === "file") return

        newFilterObject.value.push({
            id: property.id,
            name: property.name,
            operator: f.operator ?? "like",
            value: f.value ?? "",
            type: property.type,
            select_values: property.select_values,
        })
        seenIds.add(property.id)
    })

    selectedTagIds.value = [...appliedTagIds.value]
}

const openModal = () => {
    initFromApplied()
    showModal.value = true
}

// Während das Modal offen ist, Reload-Ergebnisse zurückspiegeln
// (z.B. nach Preset-Anwendung oder Chip-Entfernung außerhalb).
watch([appliedFilters, appliedTagIds], () => {
    if (showModal.value) initFromApplied()
})

const getAllowedFilters = (type) => {
    if (!type) return []
    return FILTER_OPERATORS.filter((f) => f.allowedTypes.includes(type))
}

const inputTypeFor = (type) => {
    if (type === 'number') return 'number'
    if (type === 'date') return 'date'
    if (type === 'time') return 'time'
    if (type === 'datetime') return 'datetime-local'
    return 'text'
}

const hasValue = (filter) => {
    if (filter.type === "checkbox") return !!filter.value
    if (filter.type === "selection") return filter.value !== "" && filter.value !== null
    return filter.value !== "" && filter.value !== null && filter.value !== false
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
    // manuelle Änderung -> Preset-Bindung lösen
    apply({
        filters: buildCleanFilters(),
        tagIds: selectedTagIds.value,
        presetId: null,
    })
}

// Für Texteingaben: gleiche Wartezeit wie die Artikelsuche, Enter überspringt sie.
const debouncedSubmit = debounce(submitFilter, 500)

// Für diskrete Aktionen (Auswahl, Checkbox, Tag, Feld leeren): sofort anwenden.
const applyNow = () => {
    debouncedSubmit.cancel()
    submitFilter()
}

const onOperatorChange = (filterProperty) => {
    if (hasValue(filterProperty)) applyNow()
}

const onSelectionChange = (filterProperty, value) => {
    filterProperty.value = value
    applyNow()
}

const onCheckboxChange = (filterProperty, value) => {
    filterProperty.value = value
    applyNow()
}

const clearSingleFilter = (filter) => {
    filter.value = filter.type === "checkbox" ? false : ""
    filter.operator = filter.operator ?? "like"
    applyNow()
}

const resetPanelFilters = () => {
    newFilterObject.value.forEach((filter) => {
        filter.value = filter.type === "checkbox" ? false : ""
        filter.operator = "like"
    })
    selectedTagIds.value = []
    applyNow()
}

const checkIfAnyFilterIsSet = computed(() => {
    const hasProps = newFilterObject.value.some((filter) => hasValue(filter))
    const hasTags = selectedTagIds.value.length > 0
    return hasProps || hasTags
})

/**
 * Tags
 */
const toggleTag = (tagId) => {
    const idx = selectedTagIds.value.indexOf(tagId)
    if (idx === -1) selectedTagIds.value.push(tagId)
    else selectedTagIds.value.splice(idx, 1)
    applyNow()
}

const clearTagsOnly = () => {
    selectedTagIds.value = []
    applyNow()
}

const filterTagsBySearch = (list) => {
    const q = (tagSearch.value || "").trim().toLowerCase()
    if (!q) return list
    return list.filter((t) => (t.name || "").toLowerCase().includes(q))
}

// Eine Gruppenliste für ein einziges Markup: benannte Gruppen, "Ungruppiert",
// oder (ohne Gruppen) alle Tags als flache Liste ohne Gruppenkopf.
const displayTagGroups = computed(() => {
    const groups = [...tagGroups.value]
        .sort((a, b) => (a.position ?? 0) - (b.position ?? 0))
        .map((g) => ({
            key: g.id,
            name: g.name,
            tags: filterTagsBySearch(Array.isArray(g.tags) ? g.tags : []),
        }))
        .filter((g) => g.tags.length)

    if (groups.length) {
        const ungrouped = filterTagsBySearch(allTags.value.filter((t) => !t.inventory_tag_group_id))
        if (ungrouped.length) {
            groups.push({ key: 'ungrouped', name: $t('Ungrouped'), tags: ungrouped })
        }
        return groups
    }

    const flat = filterTagsBySearch(allTags.value)
    return flat.length ? [{ key: 'all', name: null, tags: flat }] : []
})

const toggleGroupCollapsed = (groupId) => {
    const idx = collapsedGroupIds.value.indexOf(groupId)
    if (idx === -1) collapsedGroupIds.value.push(groupId)
    else collapsedGroupIds.value.splice(idx, 1)
}

/**
 * Gespeicherte Filter (Presets)
 */
const saveFilterOption = ref(false)
const saveFilterName = ref("")
const presetError = ref("")

const canSaveFilter = computed(
    () => (saveFilterName.value || "").trim().length >= 2 && checkIfAnyFilterIsSet.value
)

const startSaveFilter = () => {
    presetError.value = ""
    saveFilterName.value = ""
    saveFilterOption.value = true
}

const applyPreset = (preset) => {
    apply({ filters: [], tagIds: [], presetId: preset.id })
}

const saveFilter = async () => {
    presetError.value = ""
    if (!canSaveFilter.value) return

    try {
        isBusy.value = true
        await axios.post(route("inventory.filter-presets.store"), {
            name: saveFilterName.value.trim(),
            inventory_category_id: page.props.currentCategory?.id ?? null,
            inventory_sub_category_id: page.props.currentSubCategory?.id ?? null,
            filters: buildCleanFilters(),
            tag_ids: selectedTagIds.value,
            is_default: false,
        })
        saveFilterOption.value = false
        saveFilterName.value = ""
        router.reload()
    } catch (e) {
        presetError.value = $t("Saving failed. Please check your inputs.")
        console.error(e)
    } finally {
        isBusy.value = false
    }
}

const togglePresetDefault = async (preset) => {
    try {
        isBusy.value = true
        await axios.put(route("inventory.filter-presets.update", preset.id), {
            is_default: !preset.is_default,
        })
        router.reload()
    } finally {
        isBusy.value = false
    }
}

const deletePreset = async (preset) => {
    if (!confirm($t("Are you sure you want to delete this preset?") ?? "Are you sure you want to delete this preset?")) return

    try {
        isBusy.value = true
        await axios.delete(route("inventory.filter-presets.destroy", preset.id))
        router.reload()
    } finally {
        isBusy.value = false
    }
}
</script>

<style scoped></style>
