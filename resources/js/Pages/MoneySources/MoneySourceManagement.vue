<template>
    <app-layout :title="$t('Sources of funding')">
        <div class="artwork-container">
            <!-- Page header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="headline1">{{ $t('Sources of funding') }}</h1>

                <div class="flex items-center gap-2">
                    <button class="ui-button-add inline-flex items-center gap-2" @click="showMoneySourceModal = true">
                        <IconPlus class="size-5" stroke-width="1" />
                        {{ $t('New') }}
                    </button>
                </div>
            </div>

            <!-- Toolbar -->
            <div
                class="mb-4 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-xs md:flex-row md:items-center md:justify-between"
            >
                <!-- Filter: Typ -->
                <div class="flex items-center gap-3">
                    <Listbox as="div" v-model="moneySourceFilter">
                        <div class="relative">
                            <ListboxButton
                                class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <span class="text-gray-800">{{ moneySourceFilter.name }}</span>
                                <IconChevronDown class="h-4 w-4 text-gray-400" />
                            </ListboxButton>

                            <transition
                                enter-active-class="transition duration-100 ease-out"
                                enter-from-class="opacity-0 translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition duration-75 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 translate-y-1"
                            >
                                <ListboxOptions
                                    class="absolute z-20 mt-2 w-64 rounded-lg border border-gray-200 bg-white p-1 text-sm shadow-lg focus:outline-none"
                                >
                                    <ListboxOption
                                        v-for="filter in moneySourceFilters"
                                        :key="filter.type"
                                        :value="filter"
                                        as="template"
                                        v-slot="{ active, selected }"
                                    >
                                        <li
                                            :class="[
                        active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-800',
                        'flex cursor-pointer items-center justify-between rounded-md px-3 py-2'
                      ]"
                                        >
                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'truncate']">{{ filter.name }}</span>
                                            <IconCircleCheck v-if="selected" class="h-4 w-4 text-indigo-600" />
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>

                    <!-- Toggle: erweiterte Filter -->
                    <button
                        type="button"
                        class="inline-flex size-10 items-center justify-center rounded-lg border border-gray-200 bg-white shadow-sm transition hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        @click="showMoneySourceFilters = !showMoneySourceFilters"
                        :aria-expanded="showMoneySourceFilters"
                        :aria-controls="'filters-popover'"
                    >
                        <IconFilter class="h-5 w-5 text-gray-700" stroke-width="1.5" />
                    </button>
                </div>

                <!-- Suche -->
                <div class="relative w-full max-w-md md:ml-auto">
                    <div v-if="!showSearchbar" class="flex justify-end">
                        <button
                            type="button"
                            class="inline-flex size-10 items-center justify-center rounded-lg border border-gray-200 bg-white shadow-sm transition hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            @click="openSearchbar"
                        >
                            <IconSearch class="h-5 w-5 text-gray-700" stroke-width="1.5" />
                        </button>
                    </div>

                    <div v-else class="relative">
                        <input
                            ref="searchBarInput"
                            v-model="moneySource_query"
                            type="text"
                            :placeholder="$t('Search for sources')"
                            class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-10 pr-8 text-sm text-gray-900 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                        />
                        <IconSearch class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <button
                            type="button"
                            class="absolute right-2 top-1/2 grid size-7 -translate-y-1/2 place-items-center rounded hover:bg-gray-100"
                            @click="closeSearchbar"
                        >
                            <IconX class="h-4 w-4 text-gray-500" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Erweiterte Filter Popover -->
            <div
                v-if="showMoneySourceFilters"
                id="filters-popover"
                class="relative mb-4"
            >
                <div
                    class="z-10 w-full rounded-2xl border border-gray-200 bg-white p-4 shadow-lg"
                >
                    <!-- Kategorien -->
                    <Disclosure as="div" class="mb-3">
                        <DisclosureButton
                            class="flex w-full items-center justify-between rounded-lg px-2 py-1.5 text-left text-sm font-medium text-gray-800 hover:bg-gray-50 focus:outline-none"
                        >
                            <span>{{ $t('All categories') }}</span>
                            <ChevronDownIcon class="h-4 w-4 text-gray-500 ui-open:rotate-180 ui-open:transform" />
                        </DisclosureButton>
                        <DisclosurePanel class="mt-2 px-2">
                            <div v-if="moneySourceCategories?.length" class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
                                <label
                                    v-for="cat in moneySourceCategories"
                                    :key="cat.id"
                                    class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 hover:bg-gray-50"
                                >
                                    <input
                                        v-model="categoryFilters"
                                        class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        type="checkbox"
                                        :value="cat.id"
                                    />
                                    <span
                                        class="text-xs"
                                        :class="categoryFilters.includes(cat.id) ? 'text-gray-900' : 'text-gray-500'"
                                    >{{ cat.name }}</span>
                                </label>
                            </div>
                            <div v-else class="px-1 text-xs text-gray-500">
                                {{ $t('No categories for funding sources have been created yet.') }}
                            </div>
                        </DisclosurePanel>
                    </Disclosure>

                    <hr class="my-3 border-gray-200" />

                    <!-- Offene Aufgaben -->
                    <label class="flex cursor-pointer items-center gap-2 px-2 py-1.5 hover:bg-gray-50 rounded-md">
                        <input
                            v-model="openTasksFilter"
                            class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            type="checkbox"
                        />
                        <span :class="openTasksFilter ? 'text-gray-900' : 'text-gray-500'" class="text-xs">
              {{ $t('Sources with open tasks') }}
            </span>
                    </label>

                    <hr class="my-3 border-gray-200" />

                    <!-- Zeitraum -->
                    <button
                        type="button"
                        class="rounded-md px-2 py-1.5 text-left text-xs"
                        :class="timeSpanFilterActive ? 'text-gray-900 font-medium' : 'text-gray-500 hover:text-gray-700'"
                        @click="openTimeSpanFilterModal"
                    >
                        {{ $t('Period') }}
                    </button>
                </div>
            </div>

            <!-- Aktive Filter als Tags -->
            <div class="mb-4 flex flex-wrap gap-2">
                <TagComponent
                    v-if="timeSpanFilterActive"
                    :displayed-text="formatDateString(timeSpanFilterStart) + ' - ' + formatDateString(timeSpanFilterEnd)"
                    :method="deactivateTimeSpanFilter"
                    property=""
                />
                <TagComponent
                    v-for="catId in categoryFilters"
                    :key="'cat_'+catId"
                    :displayed-text="findCategoryName(catId)"
                    :method="() => removeCategoryFilter(catId)"
                    property=""
                />
                <TagComponent
                    v-if="openTasksFilter"
                    :displayed-text="$t('Sources with open tasks')"
                    :method="() => (openTasksFilter = false)"
                    property=""
                />
            </div>

            <!-- Sort & Create on the right -->
            <div class="mb-3 flex items-center justify-end gap-2">
                <Menu as="div" class="relative">
                    <MenuButton
                        class="inline-flex size-10 items-center justify-center rounded-lg border border-gray-200 bg-white shadow-sm transition hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        :aria-label="$t('Sort')"
                    >
                        <IconArrowsSort class="h-5 w-5 text-gray-700" stroke-width="1.5" />
                    </MenuButton>
                    <transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <MenuItems
                            class="absolute right-0 z-20 mt-2 w-60 rounded-lg border border-gray-200 bg-white p-1 text-sm shadow-lg focus:outline-none"
                        >
                            <div class="py-1">
                                <MenuItem v-slot="{ active }">
                                    <button
                                        type="button"
                                        @click="changeSortAlgorithm('name')"
                                        :class="[active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-800', 'flex w-full items-center justify-between rounded-md px-3 py-2']"
                                    >
                                        <span>{{ $t('Alphabetical') }}</span>
                                        <span v-if="sortType === 'name'">
                      <IconSortDescending v-if="sortOrder === 'descending'" class="h-4 w-4" />
                      <IconSortAscending v-else class="h-4 w-4" />
                    </span>
                                    </button>
                                </MenuItem>

                                <MenuItem v-slot="{ active }">
                                    <button
                                        type="button"
                                        @click="changeSortAlgorithm('funding_start_date')"
                                        :class="[active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-800', 'flex w-full items-center justify-between rounded-md px-3 py-2']"
                                    >
                                        <span>{{ $t('Start date') }}</span>
                                        <span v-if="sortType === 'funding_start_date'">
                      <IconSortDescending v-if="sortOrder === 'descending'" class="h-4 w-4" />
                      <IconSortAscending v-else class="h-4 w-4" />
                    </span>
                                    </button>
                                </MenuItem>

                                <MenuItem v-slot="{ active }">
                                    <button
                                        type="button"
                                        @click="changeSortAlgorithm('funding_end_date')"
                                        :class="[active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-800', 'flex w-full items-center justify-between rounded-md px-3 py-2']"
                                    >
                                        <span>{{ $t('End date') }}</span>
                                        <span v-if="sortType === 'funding_end_date'">
                      <IconSortDescending v-if="sortOrder === 'descending'" class="h-4 w-4" />
                      <IconSortAscending v-else class="h-4 w-4" />
                    </span>
                                    </button>
                                </MenuItem>

                                <MenuItem v-slot="{ active }">
                                    <button
                                        type="button"
                                        @click="changeSortAlgorithm('created_at')"
                                        :class="[active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-800', 'flex w-full items-center justify-between rounded-md px-3 py-2']"
                                    >
                                        <span>{{ $t('Created on') }}</span>
                                        <span v-if="sortType === 'created_at'">
                      <IconSortDescending v-if="sortOrder === 'descending'" class="h-4 w-4" />
                      <IconSortAscending v-else class="h-4 w-4" />
                    </span>
                                    </button>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>

                <!-- Optional zweite "Neu"-Schaltfläche (oben schon vorhanden) -->
                <GlassyIconButton v-if="is('view edit add money_sources') || is('can edit and delete money sources') || is('artwork admin')"
                                  text="New" :icon="IconPlus" @click="showMoneySourceModal = true"/>
            </div>

            <!-- Liste -->
            <ul role="list" class="w-full">
                <li
                    v-for="moneySource in filteredMoneySources"
                    :key="moneySource.id"
                    v-show="(moneySources.some(s => s.money_source_id === moneySource.id) || can('view edit add money_sources | can edit and delete money sources')) || is('artwork admin')"
                    class="mb-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-xs"
                >
                    <!-- Kopfzeile -->
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <img
                                    v-if="moneySource.is_group"
                                    src="/Svgs/IconSvgs/icon_group_red.svg"
                                    class="h-5 w-5"
                                    alt="groupIcon"
                                />
                                <Link :href="getEditHref(moneySource)" class="sDark truncate font-medium">
                                    {{ moneySource.name }}
                                </Link>
                                <IconPinned
                                    v-if="isPinned(moneySource)"
                                    class="h-4 w-4 text-indigo-600"
                                    stroke-width="1.5"
                                />
                            </div>

                            <div v-if="moneySource.group_id !== null" class="mt-1 text-xs text-gray-500">
                                {{ $t('Belongs to') }}:
                                {{ findGroupName(moneySource.group_id) }}
                            </div>
                        </div>

                        <!-- Aktionen -->
                        <div class="flex items-center gap-1">
                            <BaseMenu>
                                <MenuItem v-slot="{ active }">
                                    <a
                                        :href="getEditHref(moneySource)"
                                        :class="[active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700', 'flex items-center rounded-md px-3 py-2 text-sm']"
                                    >
                                        <IconEdit class="mr-2 h-4 w-4" stroke-width="1.5" />
                                        {{ $t('edit') }}
                                    </a>
                                </MenuItem>

                                <MenuItem
                                    v-if="canWriteOrCompetent(moneySource) || $can('view edit add money_sources') || can('can edit and delete money sources') || is('artwork admin')"
                                    v-slot="{ active }"
                                >
                                    <button
                                        type="button"
                                        @click="duplicateMoneySource(moneySource)"
                                        :class="[active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700', 'flex w-full items-center rounded-md px-3 py-2 text-sm']"
                                    >
                                        <IconCopy class="mr-2 h-4 w-4" stroke-width="1.5" />
                                        {{ $t('Duplicate') }}
                                    </button>
                                </MenuItem>

                                <MenuItem
                                    v-if="canWriteOrCompetent(moneySource) || can('view edit add money_sources') || can('can edit and delete money sources') || is('artwork admin')"
                                    v-slot="{ active }"
                                >
                                    <button
                                        type="button"
                                        @click="pinMoneySource(moneySource)"
                                        :class="[active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700', 'flex w-full items-center rounded-md px-3 py-2 text-sm']"
                                    >
                                        <IconPin class="mr-2 h-4 w-4" />
                                        {{ isPinned(moneySource) ? $t('Undo pinning') : $t('Pin') }}
                                    </button>
                                </MenuItem>

                                <MenuItem
                                    v-if="canWriteOrCompetent(moneySource) || can('can edit and delete money sources') || is('artwork admin')"
                                    v-slot="{ active }"
                                >
                                    <button
                                        type="button"
                                        @click="openDeleteSourceModal(moneySource)"
                                        :class="[active ? 'bg-red-50 text-red-700' : 'text-red-600', 'flex w-full items-center rounded-md px-3 py-2 text-sm']"
                                    >
                                        <IconTrash class="mr-2 h-4 w-4" stroke-width="1.5" />
                                        {{ $t('Delete') }}
                                    </button>
                                </MenuItem>
                            </BaseMenu>
                        </div>
                    </div>

                    <!-- Meta -->
                    <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-gray-600">
                        <div class="pl-1">
                            {{ toCurrencyString(moneySource.amount + moneySource.sumOfPositions) }}€ /
                            {{ toCurrencyString(moneySource.amount) }}€
                        </div>

                        <div v-if="moneySource.funding_start_date && moneySource.funding_end_date" class="pl-1">
                            | {{ $t('Period') }}:
                            {{ formatDateString(moneySource.funding_start_date) }} -
                            {{ formatDateString(moneySource.funding_end_date) }}
                        </div>
                    </div>

                    <!-- Beschreibung -->
                    <div v-if="moneySource.description" class="mt-1 pl-1 text-xs text-gray-600">
                        {{ moneySource.description }}
                    </div>

                    <!-- Letzte Änderung -->
                    <div v-if="moneySource.history?.[0]" class="mt-1 flex items-center pl-1 text-xs text-gray-500">
                        {{ $t('Last change') }}: {{ moneySource.history[0]?.created_at }} {{ $t('by') }}
                        <NewUserToolTip
                            class="ml-2"
                            :height="6"
                            :width="6"
                            v-if="moneySource.history[0]?.changes?.[0]?.changed_by"
                            :user="moneySource.history[0]?.changes?.[0]?.changed_by"
                            :id="(moneySource.history[0]?.changes?.[0]?.changed_by?.id || 0) + (moneySource.id || 0)"
                        />
                        <span class="ml-1">
              {{ moneySource.history[0]?.changes?.[0]?.changed_by?.first_name }}
              {{ moneySource.history[0]?.changes?.[0]?.changed_by?.last_name }}
            </span>
                    </div>

                    <!-- Kategorien -->
                    <div class="ml-1 mt-2 flex flex-wrap gap-1">
                        <TagComponent
                            v-for="cat in moneySource.categories"
                            :key="cat.id"
                            :displayed-text="cat.name"
                            :hide-x="true"
                            property=""
                        />
                    </div>
                </li>
            </ul>
        </div>

        <!-- Create Modal -->
        <create-money-source-component
            v-if="showMoneySourceModal"
            @closed="showMoneySourceModal = false"
            :moneySourceGroups="moneySourceGroups"
        />

        <!-- Delete Modal -->
        <confirm-delete-modal
            v-if="showDeleteSourceModal"
            :title="$t('Delete funding source/group')"
            :description="$t('Are you sure you want to delete the funding source/group {0}?', [sourceToDelete?.name])"
            @closed="afterConfirm(false)"
            @delete="afterConfirm(true)"
        />

        <!-- Zeitraum-Filter Modal -->
        <BaseModal v-if="timeSpanFilterModalVisible" @closed="closeTimeSpanFilterModal" modal-image="/Svgs/Overlays/illu_project_edit.svg">
            <div class="mx-4">
                <h1 class="headline1 my-1">{{ $t('Select period') }}</h1>
                <h2 class="xsLight mb-3 mt-4">
                    {{ $t('Please select the time period in which the financial sources should be displayed.') }}
                </h2>
                <div class="flex gap-2">
                    <input class="w-1/2 rounded-lg border border-gray-200 p-2" type="date" v-model="timeSpanFilterStart" />
                    <input class="w-1/2 rounded-lg border border-gray-200 p-2" type="date" v-model="timeSpanFilterEnd" />
                </div>
                <div class="my-3 flex w-full justify-center">
                    <FormButton
                        :disabled="!timeSpanFilterStart || !timeSpanFilterEnd"
                        :text="$t('Filtering')"
                        @click="activateTimeSpanFilter"
                    />
                </div>
            </div>
        </BaseModal>

        <!-- History Modal -->
        <MoneySourceHistoryComponent
            v-if="showMoneySourceHistory"
            :history="moneySourceToShowHistoryOf?.history"
            @closed="closeMoneySourceHistoryModal"
        />
    </app-layout>
</template>

<script setup>
import { ref, computed, getCurrentInstance, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

import {
    Disclosure, DisclosureButton, DisclosurePanel,
    Listbox, ListboxButton, ListboxOption, ListboxOptions,
    Menu, MenuButton, MenuItem, MenuItems
} from '@headlessui/vue'
import {
    IconPlus, IconChevronDown, IconCircleCheck, IconSearch, IconX, IconFilter, IconArrowsSort,
    IconSortAscending, IconSortDescending, IconEdit, IconCopy, IconPin, IconTrash, IconPinned
} from '@tabler/icons-vue'

import CreateMoneySourceComponent from '@/Layouts/Components/CreateMoneySourceComponent.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import TagComponent from '@/Layouts/Components/TagComponent.vue'
import NewUserToolTip from '@/Layouts/Components/NewUserToolTip.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import GlassyIconButton from '@/Artwork/Buttons/GlassyIconButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import {can, is} from "laravel-permission-to-vuejs";

defineOptions({ name: 'MoneySourceIndex' })

/* Props */
const props = defineProps({
    moneySourceCategories: { type: Array, default: () => [] },
    moneySources: { type: Array, default: () => [] },
    moneySourceGroups: { type: Array, default: () => [] },
})

/* i18n helper */
const { proxy } = getCurrentInstance()
const t = (s) => (proxy?.$t ? proxy.$t(s) : s)
const page = proxy?.$page

/* Filters: Typ */
const moneySourceFilters = computed(() => ([
    { name: t('All funding sources & groups'), type: 'all' },
    { name: t('All sources of funding'), type: 'single' },
    { name: t('All funding groups'), type: 'group' },
]))
const moneySourceFilter = ref(moneySourceFilters.value[0])

/* UI State */
const showSearchbar = ref(false)
const moneySource_query = ref('')
const searchBarInput = ref(null)

const showMoneySourceModal = ref(false)
const showDeleteSourceModal = ref(false)
const sourceToDelete = ref(null)

const showMoneySourceFilters = ref(false)
const categoryFilters = ref([])           // number[]
const openTasksFilter = ref(false)

const timeSpanFilterModalVisible = ref(false)
const timeSpanFilterStart = ref(null)
const timeSpanFilterEnd = ref(null)
const timeSpanFilterActive = ref(false)

const sortType = ref(null)                // 'name' | 'funding_start_date' | 'funding_end_date' | 'created_at'
const sortOrder = ref(null)               // 'ascending' | 'descending'

const showMoneySourceHistory = ref(false)
const moneySourceToShowHistoryOf = ref(null)

/* Toolbar actions */
function openSearchbar () {
    showSearchbar.value = true
    nextTick(() => searchBarInput.value?.focus?.())
}
function closeSearchbar () {
    showSearchbar.value = false
    moneySource_query.value = ''
}

/* Zeitspanne */
function openTimeSpanFilterModal () { timeSpanFilterModalVisible.value = true }
function closeTimeSpanFilterModal () { timeSpanFilterModalVisible.value = false }
function activateTimeSpanFilter () {
    timeSpanFilterActive.value = !!(timeSpanFilterStart.value && timeSpanFilterEnd.value)
    closeTimeSpanFilterModal()
}
function deactivateTimeSpanFilter () {
    timeSpanFilterActive.value = false
    timeSpanFilterStart.value = null
    timeSpanFilterEnd.value = null
}

/* Kategorie-Helfer */
function findCategoryName (id) {
    return props.moneySourceCategories.find(c => c.id === id)?.name ?? ''
}
function removeCategoryFilter (id) {
    categoryFilters.value = categoryFilters.value.filter(x => x !== id)
}

/* Datenquellen-Auswahl */
function sourcesToShow () {
    const type = moneySourceFilter.value?.type
    if (type === 'single') return props.moneySources.filter(s => s.is_group === false)
    if (type === 'group') return props.moneySourceGroups
    return [...props.moneySources] // all
}

/* Pinned */
function isPinned (src) {
    const uid = page?.props?.auth?.user?.id
    return Array.isArray(src?.pinned_by_users) && uid ? src.pinned_by_users.includes(uid) : false
}

/* Rechte-Check */
function getMemberInMoneySource (src) {
    const ret = { competent: [], write_access: [] }
    for (const u of src?.users ?? []) {
        if (u?.pivot?.competent) ret.competent.push(u.id)
        if (u?.pivot?.write_access) ret.write_access.push(u.id)
    }
    return ret
}
function canWriteOrCompetent (src) {
    const uid = page?.props?.auth?.user?.id
    const m = getMemberInMoneySource(src)
    return uid && (m.write_access.includes(uid) || m.competent.includes(uid))
}
function hasAdminRole () {
    // greift auf globale $role/$can im Template zu; hier nur Fallback
    return false
}

/* Formatierungen */
function formatDateString (dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    return `${day}.${month}.${year}`
}

/* Externe Helper (kommen vermutlich aus Mixin im Projekt) */
function toCurrencyString (val) {
    // Simplified Fallback; im Projekt habt ihr ggf. euren Formatter
    try {
        return new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val ?? 0)
    } catch { return String(val ?? 0) }
}

/* Edit/CRUD Aktionen */
function getEditHref (src) {
    return route('money_sources.show', { moneySource: src.id })
}
function duplicateMoneySource (src) {
    router.post(`/money_sources/${src.id}/duplicate`)
}
function pinMoneySource (src) {
    router.post(`/money_sources/${src.id}/pin`)
}
function deleteMoneySource (src) {
    router.delete(`/money_sources/${src.id}`)
    showDeleteSourceModal.value = false
}
function openDeleteSourceModal (src) {
    sourceToDelete.value = src
    showDeleteSourceModal.value = true
}
function afterConfirm (ok) {
    if (!ok) { showDeleteSourceModal.value = false; return }
    if (sourceToDelete.value) deleteMoneySource(sourceToDelete.value)
}
function openMoneySourceHistoryModal (src) {
    moneySourceToShowHistoryOf.value = src
    showMoneySourceHistory.value = true
}
function closeMoneySourceHistoryModal () {
    showMoneySourceHistory.value = false
    moneySourceToShowHistoryOf.value = null
}

/* Sortierung */
function changeSortAlgorithm (type) {
    if (sortType.value === type) {
        sortOrder.value = sortOrder.value === 'ascending' ? 'descending' : 'ascending'
    } else {
        sortType.value = type
        sortOrder.value = 'ascending'
    }
}
function sortMoneySources (arr) {
    const uid = page?.props?.auth?.user?.id
    const pinCompare = (a, b) => {
        const ap = Array.isArray(a?.pinned_by_users) && uid ? a.pinned_by_users.includes(uid) : false
        const bp = Array.isArray(b?.pinned_by_users) && uid ? b.pinned_by_users.includes(uid) : false
        if (ap && !bp) return -1
        if (!ap && bp) return 1
        return 0
    }

    let cmp
    switch (sortType.value) {
        case 'funding_start_date':
        case 'funding_end_date':
        case 'created_at':
            cmp = (a, b) => {
                const p = pinCompare(a, b); if (p !== 0) return p
                const da = new Date(a?.[sortType.value] || 0)
                const db = new Date(b?.[sortType.value] || 0)
                return da - db
            }
            break
        case 'name':
        default:
            cmp = (a, b) => {
                const p = pinCompare(a, b); if (p !== 0) return p
                const na = (a?.name || '').toLowerCase()
                const nb = (b?.name || '').toLowerCase()
                return na < nb ? -1 : 1
            }
    }
    if (sortOrder.value === 'descending') {
        const base = cmp
        cmp = (a, b) => -1 * base(a, b)
    }
    return arr.slice().sort(cmp)
}

/* Gefilterte Liste */
const filteredMoneySources = computed(() => {
    let list = sourcesToShow()

    // Suche
    const q = moneySource_query.value.toLowerCase().trim()
    if (q) {
        list = list.filter(ms => (ms?.name || '').toLowerCase().includes(q))
    }

    // Kategorien (AND-Logik)
    if (categoryFilters.value.length > 0) {
        const wanted = new Set(categoryFilters.value)
        list = list.filter(ms => {
            const cats = ms?.categories ?? []
            if (cats.length === 0) return false
            const ids = new Set(cats.map(c => c.id))
            for (const id of wanted) if (!ids.has(id)) return false
            return true
        })
    }

    // Offene Aufgaben
    if (openTasksFilter.value) {
        list = list.filter(ms => {
            const tasks = ms?.money_source_tasks ?? []
            return tasks.some(t => t?.done === 0)
        })
    }

    // Zeitraum-Filter (Überlappung)
    if (timeSpanFilterActive.value && timeSpanFilterStart.value && timeSpanFilterEnd.value) {
        const start = new Date(timeSpanFilterStart.value)
        const end = new Date(timeSpanFilterEnd.value)
        list = list.filter(ms => {
            // Wenn keine Daten -> nicht filtern (wie im Original)
            if (ms?.start_date == null || ms?.end_date == null) return true
            const s = new Date(ms.start_date)
            const e = new Date(ms.end_date)
            return s <= end && e >= start
        })
    }

    return sortMoneySources(list)
})

/* kleine Helfer */
function findGroupName (groupId) {
    return props.moneySourceGroups.find(g => g.id === groupId)?.name ?? ''
}
</script>

<style scoped>
.shadow-xs {
    --tw-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
    --tw-shadow-colored: 0 1px 2px var(--tw-shadow-color);
    box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000),
    var(--tw-ring-shadow, 0 0 #0000),
    var(--tw-shadow);
}
</style>
