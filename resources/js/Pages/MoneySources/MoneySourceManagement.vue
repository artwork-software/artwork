<template>
    <app-layout :title="$t('Sources of funding')">
        <div class="artwork-container-fluid">


            <ToolbarHeader
                band
                :icon="IconCurrencyEuro"
                :title="$t('Sources of funding')"
                :description="filteredMoneySources.length + ' ' + $t('Sources of funding')"
                v-model="moneySource_query"
                :search-enabled="true"
                :search-label="$t('Search for sources')"
                :search-tooltip="$t('Search')"
            >
                <template #actions>

                    <div class="flex items-center gap-3">
                        <Listbox as="div" v-model="moneySourceFilter">
                            <div class="relative">
                                <ListboxButton
                                    class="inline-flex items-center gap-1.5 h-[30px] px-3 rounded-md bg-white/8 hover:bg-white/16 text-text-inverse text-[13px] font-medium cursor-pointer transition-[background-color] duration-150 ease-out focus:outline-none focus:ring-2 focus:ring-accent-500"
                                >
                                    <span>{{ moneySourceFilter.name }}</span>
                                    <IconChevronDown class="h-4 w-4 text-text-inverse-muted" />
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
                                        class="absolute right-0 z-100 mt-2 min-w-full w-max max-w-xs rounded-lg border border-border-subtle bg-white p-1 text-sm shadow-lg focus:outline-none"
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
                                                active ? 'bg-accent-50 text-accent-700' : 'text-text',
                                                'flex cursor-pointer items-center justify-between rounded-md w-full px-3 py-2'
                                              ]"
                                            >
                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'truncate']">{{ filter.name }}</span>
                                                <IconCircleCheck v-if="selected" class="h-4 w-4 text-accent-600" />
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>

                        <div class="relative">
                            <BaseUIButton
                                label=""
                                on-band
                                :icon="IconFilter"
                                :title="$t('Filter')"
                                :aria-label="$t('Filter')"
                                :aria-expanded="showMoneySourceFilters"
                                @click="showMoneySourceFilters = !showMoneySourceFilters"
                            />
                            <span v-if="activeFilterCount > 0"
                                  class="pointer-events-none absolute -right-1.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-semibold text-white">
                                {{ activeFilterCount }}
                            </span>
                        </div>
                    </div>

                    <Menu as="div" class="relative">
                        <MenuButton
                            class="select-none size-[30px] min-h-0 p-0 inline-flex items-center justify-center rounded-md bg-white/8 hover:bg-white/16 cursor-pointer transition-[background-color] duration-150 ease-out"
                            :aria-label="$t('Sort')"
                            :title="$t('Sort')"
                        >
                            <IconArrowsSort class="h-5 w-5 text-text-inverse" stroke-width="1.5" />
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
                                class="absolute right-0 z-20 mt-2 w-60 rounded-lg border border-border-subtle bg-white p-1 text-sm shadow-lg focus:outline-none"
                            >
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <button
                                            type="button"
                                            @click="changeSortAlgorithm('name')"
                                            :class="[active ? 'bg-accent-50 text-accent-700' : 'text-text', 'flex w-full items-center justify-between rounded-md px-3 py-2']"
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
                                            :class="[active ? 'bg-accent-50 text-accent-700' : 'text-text', 'flex w-full items-center justify-between rounded-md px-3 py-2']"
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
                                            :class="[active ? 'bg-accent-50 text-accent-700' : 'text-text', 'flex w-full items-center justify-between rounded-md px-3 py-2']"
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
                                            :class="[active ? 'bg-accent-50 text-accent-700' : 'text-text', 'flex w-full items-center justify-between rounded-md px-3 py-2']"
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

                    <BaseUIButton
                        label=""
                        on-band
                        :icon="IconFileSpreadsheet"
                        :title="$t('Excel-Export')"
                        :aria-label="$t('Excel-Export')"
                        :disabled="filteredMoneySources.length === 0"
                        @click="exportMoneySources"
                    />

                    <BaseUIButton
                        label="New"
                        use-translation
                        is-add-button
                        on-band
                        @click="showMoneySourceModal = true"
                    />

                </template>

            </ToolbarHeader>

            <!-- Erweiterte Filter Popover -->
            <div
                v-if="showMoneySourceFilters"
                id="filters-popover"
                class="relative mb-4"
            >
                <div
                    class="z-10 w-full rounded-2xl border border-border-subtle bg-white p-4 shadow-lg"
                >
                    <!-- Kategorien -->
                    <Disclosure as="div" class="mb-3" default-open>
                        <DisclosureButton
                            class="flex w-full items-center justify-between rounded-lg px-2 py-1.5 text-left text-sm font-medium text-text hover:bg-surface-sunken focus:outline-none"
                        >
                            <span>{{ $t('Categories') }}</span>
                            <IconChevronDown class="h-4 w-4 text-text-subtle ui-open:rotate-180 ui-open:transform" />
                        </DisclosureButton>
                        <DisclosurePanel class="mt-2 px-2">
                            <div v-if="moneySourceCategories?.length" class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
                                <label
                                    v-for="cat in moneySourceCategories"
                                    :key="cat.id"
                                    class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 hover:bg-surface-sunken"
                                >
                                    <input
                                        v-model="categoryFilters"
                                        class="h-4 w-4 cursor-pointer rounded border-border text-accent-600 focus:ring-accent-600"
                                        type="checkbox"
                                        :value="cat.id"
                                    />
                                    <span
                                        class="text-xs"
                                        :class="categoryFilters.includes(cat.id) ? 'text-text' : 'text-text-subtle'"
                                    >{{ cat.name }}</span>
                                </label>
                            </div>
                            <div v-else class="px-1 text-xs text-text-subtle">
                                {{ $t('No categories for funding sources have been created yet.') }}
                            </div>
                        </DisclosurePanel>
                    </Disclosure>

                    <hr class="my-3 border-border-subtle" />

                    <!-- Offene Aufgaben -->
                    <label class="flex cursor-pointer items-center gap-2 px-2 py-1.5 hover:bg-surface-sunken rounded-md">
                        <input
                            v-model="openTasksFilter"
                            class="h-4 w-4 cursor-pointer rounded border-border text-accent-600 focus:ring-accent-600"
                            type="checkbox"
                        />
                        <span :class="openTasksFilter ? 'text-text' : 'text-text-subtle'" class="text-xs">
              {{ $t('Sources with open tasks') }}
            </span>
                    </label>

                    <hr class="my-3 border-border-subtle" />

                    <!-- Zeitraum -->
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md border border-border-subtle px-2.5 py-1.5 text-left text-xs hover:bg-surface-sunken"
                        :class="timeSpanFilterActive ? 'text-text font-medium' : 'text-text-muted'"
                        @click="openTimeSpanFilterModal"
                    >
                        <IconCalendar class="h-3.5 w-3.5" stroke-width="1.5"/>
                        {{ timeSpanFilterActive ? formatDateString(timeSpanFilterStart) + ' - ' + formatDateString(timeSpanFilterEnd) : $t('Select period') }}
                    </button>
                </div>
            </div>

            <!-- Aktive Filter als Tags -->
            <div class="mb-4 flex flex-wrap gap-2">
                <TagComponent
                    v-if="moneySource_query.trim() !== ''"
                    :displayed-text="$t('Search') + ': ' + moneySource_query.trim()"
                    :method="() => (moneySource_query = '')"
                    property=""
                />
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

            <!-- Kennzahlen über die gefilterten Quellen (ohne Gruppen, sonst zählte der Verbrauch doppelt) -->
            <dl v-if="listSummary.count > 0" class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-2xl border border-border-subtle bg-white px-4 py-3">
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-text-subtle">{{ $t('Sources of funding') }}</dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums text-text">{{ listSummary.count }}</dd>
                </div>
                <div class="rounded-2xl border border-border-subtle bg-white px-4 py-3">
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-text-subtle">{{ $t('Volume') }}</dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums text-text">{{ toCurrencyString(listSummary.volume) }} {{ $currencySymbol() }}</dd>
                </div>
                <div class="rounded-2xl border border-border-subtle bg-white px-4 py-3">
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-text-subtle">{{ $t('Verbraucht') }}</dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums text-text">
                        {{ toCurrencyString(listSummary.used) }} {{ $currencySymbol() }}
                        <span class="ml-1 text-xs font-normal text-text-subtle">{{ listSummary.usedPercent }} %</span>
                    </dd>
                </div>
                <div class="rounded-2xl border border-border-subtle bg-white px-4 py-3">
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-text-subtle">{{ $t('Noch verfügbar') }}</dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums" :class="listSummary.remaining < 0 ? 'text-danger' : 'text-text'">
                        {{ toCurrencyString(listSummary.remaining) }} {{ $currencySymbol() }}
                    </dd>
                </div>
            </dl>

            <!-- Liste: Gruppen mit ihren Quellen eingerückt darunter -->
            <ul role="list" class="w-full">
                <li
                    v-for="{ source: moneySource, depth, childCount, orphan } in displayedEntries"
                    :key="moneySource.id"
                    v-show="(moneySources.some(s => s.money_source_id === moneySource.id) || can('view edit add money_sources | can edit and delete money sources')) || is('artwork admin')"
                    class="mb-3 rounded-2xl border border-border-subtle bg-white p-4 shadow-xs"
                    :class="depth === 1 ? 'ml-8 border-l-4 border-l-border' : ''"
                >
                    <!-- Kopfzeile -->

                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="moneySource.is_group && childCount > 0"
                                    type="button"
                                    class="-ml-1 rounded p-0.5 text-text-muted hover:bg-surface-sunken"
                                    :title="collapsedGroups[moneySource.id] ? $t('Expand') : $t('Collapse')"
                                    :aria-expanded="!collapsedGroups[moneySource.id]"
                                    @click="toggleGroup(moneySource.id)"
                                >
                                    <IconChevronDown class="h-4 w-4 transition-transform" :class="collapsedGroups[moneySource.id] ? '-rotate-90' : ''" />
                                </button>
                                <PropertyIcon
                                    v-if="moneySource.is_group && moneySource.icon"
                                    :name="moneySource.icon"
                                    class="h-5 w-5 shrink-0 text-text"
                                    stroke-width="1.5"
                                />
                                <img
                                    v-else-if="moneySource.is_group"
                                    src="/Svgs/IconSvgs/icon_group_red.svg"
                                    class="h-5 w-5"
                                    alt="groupIcon"
                                />
                                <Link :href="getEditHref(moneySource)" class="text-base/5 font-semibold text-text truncate font-medium">
                                    {{ moneySource.name }}
                                </Link>
                                <IconPinned
                                    v-if="isPinned(moneySource)"
                                    class="h-4 w-4 text-accent-600"
                                    stroke-width="1.5"
                                />
                                <span v-if="moneySource.is_group"
                                      class="shrink-0 rounded-md border border-border-subtle px-2 py-0.5 text-[11px] text-text-muted">
                                    {{ $t('Gruppe') }}<template v-if="childCount > 0"> · {{ childCount }} {{ childCount === 1 ? $t('Source of funding') : $t('Sources of funding') }}</template>
                                </span>
                            </div>

                            <div v-if="orphan" class="mt-1 text-xs text-text-subtle">
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
                                        :class="[active ? 'bg-accent-50 text-accent-700' : 'text-text-muted', 'flex items-center rounded-md px-3 py-2 text-sm']"
                                    >
                                        <IconEdit class="mr-2 h-4 w-4" stroke-width="1.5" />
                                        {{ $t('edit') }}
                                    </a>
                                </MenuItem>

                                <!-- Duplizieren legt eine neue Quelle an → MoneySourcePolicy::create -->
                                <MenuItem
                                    v-if="can('view edit add money_sources') || is('artwork admin')"
                                    v-slot="{ active }"
                                >
                                    <button
                                        type="button"
                                        @click="duplicateMoneySource(moneySource)"
                                        :class="[active ? 'bg-accent-50 text-accent-700' : 'text-text-muted', 'flex w-full items-center rounded-md px-3 py-2 text-sm']"
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
                                        :class="[active ? 'bg-accent-50 text-accent-700' : 'text-text-muted', 'flex w-full items-center rounded-md px-3 py-2 text-sm']"
                                    >
                                        <IconPin class="mr-2 h-4 w-4" />
                                        {{ isPinned(moneySource) ? $t('Undo pinning') : $t('Pin') }}
                                    </button>
                                </MenuItem>

                                <!-- Spiegel von MoneySourcePolicy::delete (Mitglieder dürfen nicht löschen) -->
                                <MenuItem
                                    v-if="moneySource.creator_id === $page.props.auth.user.id || can('can edit and delete money sources') || can('can manage global project budgets') || is('artwork admin')"
                                    v-slot="{ active }"
                                >
                                    <button
                                        type="button"
                                        @click="openDeleteSourceModal(moneySource)"
                                        :class="[active ? 'bg-danger-surface text-danger' : 'text-danger', 'flex w-full items-center rounded-md px-3 py-2 text-sm']"
                                    >
                                        <IconTrash class="mr-2 h-4 w-4" stroke-width="1.5" />
                                        {{ $t('Delete') }}
                                    </button>
                                </MenuItem>
                            </BaseMenu>
                        </div>
                    </div>

                    <!-- Meta -->
                    <div class="mt-3 flex flex-wrap items-end gap-x-8 gap-y-3 pl-1">
                        <div class="w-full max-w-sm">
                            <div class="flex items-baseline justify-between gap-3 text-xs">
                                <span class="text-text-muted">
                                    {{ $t('Noch verfügbar') }}:
                                    <span class="font-semibold tabular-nums" :class="remainingAmount(moneySource) < 0 ? 'text-danger' : 'text-text'">
                                        {{ toCurrencyString(remainingAmount(moneySource)) }} {{ $currencySymbol() }}
                                    </span>
                                </span>
                                <span class="text-text-subtle tabular-nums">
                                    {{ $t('of') }} {{ toCurrencyString(moneySource.amount) }} {{ $currencySymbol() }}
                                </span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-surface-sunken"
                                 role="progressbar"
                                 :aria-valuenow="usedPercent(moneySource)" aria-valuemin="0" aria-valuemax="100"
                                 :aria-label="$t('Verbraucht')">
                                <div class="h-full rounded-full"
                                     :class="usedBarClass(moneySource)"
                                     :style="{ width: usedPercent(moneySource) + '%' }"/>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-text-muted">
                            <span v-if="moneySource.funding_start_date && moneySource.funding_end_date">
                                {{ $t('Funding period') }}:
                                {{ formatDateString(moneySource.funding_start_date) }} - {{ formatDateString(moneySource.funding_end_date) }}
                            </span>
                            <span v-if="moneySource.start_date && moneySource.end_date">
                                {{ $t('Term') }}:
                                {{ formatDateString(moneySource.start_date) }} - {{ formatDateString(moneySource.end_date) }}
                            </span>
                            <span v-if="fundingEndState(moneySource)"
                                  class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px]"
                                  :class="fundingEndState(moneySource).expired ? 'border-danger-border bg-danger-surface/70 text-danger' : 'border-warning text-warning'">
                                {{ fundingEndState(moneySource).label }}
                            </span>
                            <span v-if="openTaskCount(moneySource) > 0"
                                  class="inline-flex items-center gap-1 rounded-md border border-border-subtle px-2 py-0.5 text-[11px] text-text-muted">
                                <IconChecklist class="h-3.5 w-3.5" stroke-width="1.5"/>
                                {{ openTaskCount(moneySource) }} {{ openTaskCount(moneySource) === 1 ? $t('open task') : $t('open tasks') }}
                            </span>
                        </div>
                    </div>

                    <!-- Beschreibung -->
                    <div v-if="moneySource.description" class="mt-1 pl-1 text-xs text-text-muted">
                        {{ moneySource.description }}
                    </div>

                    <!-- Letzte Änderung -->
                    <div v-if="moneySource.history?.[0]" class="mt-1 flex items-center pl-1 text-xs text-text-subtle">
                        {{ $t('Last change') }}: {{ moneySource.history[0]?.created_at }} {{ $t('by') }}
                        <NewUserToolTip
                            class="ml-2"
                            :height="6"
                            :width="6"
                            v-if="lastChanger(moneySource)"
                            :user="lastChanger(moneySource)"
                            :id="(lastChanger(moneySource)?.id || 0) + (moneySource.id || 0)"
                        />
                        <span class="ml-1">
              {{ lastChanger(moneySource)?.first_name }}
              {{ lastChanger(moneySource)?.last_name }}
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

            <EmptyState
                v-if="filteredMoneySources.length === 0"
                icon="IconCurrencyEuro"
                :title="hasAnyFilter ? $t('No sources of funding match your search or filters.') : $t('No sources of funding have been created yet.')"
                :action-label="hasAnyFilter ? $t('Reset filters') : ''"
                @action="resetAllFilters"
            />
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
        <ArtworkBaseModal
            v-if="timeSpanFilterModalVisible"
            title="Select period"
            description="Please select the time period in which the financial sources should be displayed."
            modal-size="sm:max-w-lg"
            @close="closeTimeSpanFilterModal"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseInput
                    id="timeSpanFilterStart"
                    v-model="timeSpanFilterStart"
                    type="date"
                    label="Start date"
                />
                <BaseInput
                    id="timeSpanFilterEnd"
                    v-model="timeSpanFilterEnd"
                    type="date"
                    label="End date"
                />
            </div>
            <template #footer>
                <BaseUIButton
                    label="Cancel"
                    is-cancel-button
                    hide-icon
                    @click="closeTimeSpanFilterModal"
                />
                <BaseUIButton
                    label="Filtering"
                    variant="primary"
                    icon="IconFilter"
                    :disabled="!timeSpanFilterStart || !timeSpanFilterEnd"
                    @click="activateTimeSpanFilter"
                />
            </template>
        </ArtworkBaseModal>

        <!-- History Modal -->
        <MoneySourceHistoryComponent
            v-if="showMoneySourceHistory"
            :history="moneySourceToShowHistoryOf?.history"
            @closed="closeMoneySourceHistoryModal"
        />
    </app-layout>
</template>

<script setup>
import { ref, computed, getCurrentInstance, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

import {
    Disclosure, DisclosureButton, DisclosurePanel,
    Listbox, ListboxButton, ListboxOption, ListboxOptions,
    Menu, MenuButton, MenuItem, MenuItems
} from '@headlessui/vue'
import {
    IconChevronDown, IconCircleCheck, IconFilter, IconArrowsSort,
    IconSortAscending, IconSortDescending, IconEdit, IconCopy, IconPin, IconTrash, IconPinned, IconCurrencyEuro,
    IconChecklist, IconCalendar, IconFileSpreadsheet
} from '@tabler/icons-vue'

import CreateMoneySourceComponent from '@/Layouts/Components/CreateMoneySourceComponent.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import TagComponent from '@/Layouts/Components/TagComponent.vue'
import NewUserToolTip from '@/Layouts/Components/NewUserToolTip.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import MoneySourceHistoryComponent from "@/Layouts/Components/MoneySourceHistoryComponent.vue";
import {can, is} from "laravel-permission-to-vuejs";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import { useInstanceFormat } from "@/Composeables/InstanceFormat.js";
import EmptyState from "@/Artwork/Feedback/EmptyState.vue";
import { safeGetItem, safeSetItem } from "@/Helper/safeStorage.js";
import {
    buildMoneySourceTree,
    daysUntilDate,
    matchesMoneySourceSearch,
    summarizeMoneySources,
    moneySourceRemainingAmount,
    moneySourceUsedPercent
} from "@/Pages/MoneySources/moneySourceListTools.js";

const instanceFormat = useInstanceFormat();

defineOptions({ name: 'MoneySourceIndex' })

/* Props */
const props = defineProps({
    moneySourceCategories: { type: Array, default: () => [] },
    moneySources: { type: Array, default: () => [] },
    moneySourceGroups: { type: Array, default: () => [] },
})

/* i18n helper */
const { proxy } = getCurrentInstance()
const t = (s, params) => (proxy?.$t ? proxy.$t(s, params) : s)
const page = proxy?.$page

/* Filters: Typ */
const moneySourceFilters = computed(() => ([
    { name: t('All funding sources & groups'), type: 'all' },
    { name: t('All sources of funding'), type: 'single' },
    { name: t('All funding groups'), type: 'group' },
]))
const moneySourceFilter = ref(moneySourceFilters.value[0])

/* UI State */
const moneySource_query = ref('')

/** Wer zuletzt geändert hat: historyForFrontend liefert die Person je Zeile (change_by) */
const lastChanger = (moneySource) =>
    moneySource.history?.[0]?.change_by ?? moneySource.history?.[0]?.changes?.[0]?.changed_by ?? null

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
    // aus moneySources statt moneySourceGroups: nur dort hängt die Positionssumme dran
    if (type === 'group') return props.moneySources.filter(s => s.is_group === true)
    return [...props.moneySources] // all
}

/* Pinned */
function isPinned (src) {
    const uid = page?.props?.auth?.user?.id
    // Altbestand kann als Objekt statt Liste gespeichert sein ({"1": 7}), daher Object.values
    return uid ? Object.values(src?.pinned_by_users ?? {}).includes(uid) : false
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
        return new Intl.NumberFormat(instanceFormat.numberLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val ?? 0)
    } catch { return String(val ?? 0) }
}

/* Edit/CRUD Aktionen */
function getEditHref (src) {
    return route('money_sources.show', { moneySource: src.id })
}
function duplicateMoneySource (src) {
    router.post(`/money_sources/${src.id}/duplicate`, {}, { preserveScroll: true })
}
function pinMoneySource (src) {
    router.post(`/money_sources/${src.id}/pin`, {}, { preserveScroll: true })
}
function deleteMoneySource (src) {
    router.delete(`/money_sources/${src.id}`, { preserveScroll: true })
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
    const pinCompare = (a, b) => {
        const ap = isPinned(a)
        const bp = isPinned(b)
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
    if (moneySource_query.value.trim()) {
        list = list.filter(ms => matchesMoneySourceSearch(ms, moneySource_query.value, findGroupName(ms?.group_id)))
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
            return tasks.some(t => !t?.done)
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

/* Karten-Kennzahlen */
const remainingAmount = (src) => moneySourceRemainingAmount(src)
const usedPercent = (src) => moneySourceUsedPercent(src)
function usedBarClass (src) {
    if (remainingAmount(src) < 0 || usedPercent(src) >= 100) return 'bg-danger'
    return usedPercent(src) >= 80 ? 'bg-warning' : 'bg-success'
}
const openTaskCount = (src) => (src?.money_source_tasks ?? []).filter(task => !task?.done).length
/** Hinweis, wenn der Förderzeitraum abgelaufen ist oder in den nächsten 30 Tagen endet */
function fundingEndState (src) {
    const days = daysUntilDate(src?.funding_end_date)
    if (days === null || days > 30) return null
    if (days < 0) return { expired: true, label: t('Expired') }
    if (days === 0) return { expired: false, label: t('Ends today') }
    return { expired: false, label: t('Ends in {count} days', { count: days }) }
}

/* Baum, Kennzahlen, Export */
const collapsedGroups = ref({})            // { [groupId]: true }
function toggleGroup (groupId) {
    if (collapsedGroups.value[groupId]) {
        delete collapsedGroups.value[groupId]
    } else {
        collapsedGroups.value[groupId] = true
    }
}
const displayedEntries = computed(() => buildMoneySourceTree(filteredMoneySources.value, collapsedGroups.value))
const listSummary = computed(() => summarizeMoneySources(filteredMoneySources.value))
function exportMoneySources () {
    window.open(
        route('money_sources.export', { ids: filteredMoneySources.value.map(source => source.id) }),
        '_blank',
        'noopener'
    )
}

/* Filter-Übersicht */
const activeFilterCount = computed(() =>
    categoryFilters.value.length + (openTasksFilter.value ? 1 : 0) + (timeSpanFilterActive.value ? 1 : 0)
)
const hasAnyFilter = computed(() =>
    activeFilterCount.value > 0 || moneySource_query.value.trim() !== '' || moneySourceFilter.value?.type !== 'all'
)
function resetAllFilters () {
    moneySource_query.value = ''
    categoryFilters.value = []
    openTasksFilter.value = false
    deactivateTimeSpanFilter()
    moneySourceFilter.value = moneySourceFilters.value[0]
}

/* Filter und Sortierung überleben den Abstecher in eine Quelle (pro Browser-Sitzung) */
const LIST_STATE_KEY = 'moneySourceListState'
try {
    const stored = JSON.parse(safeGetItem(LIST_STATE_KEY, 'session'))
    if (stored && typeof stored === 'object') {
        moneySource_query.value = typeof stored.query === 'string' ? stored.query : ''
        moneySourceFilter.value = moneySourceFilters.value.find(f => f.type === stored.type) ?? moneySourceFilters.value[0]
        const knownCategoryIds = new Set(props.moneySourceCategories.map(c => c.id))
        categoryFilters.value = Array.isArray(stored.categories) ? stored.categories.filter(id => knownCategoryIds.has(id)) : []
        openTasksFilter.value = stored.openTasks === true
        if (stored.timeSpanStart && stored.timeSpanEnd) {
            timeSpanFilterStart.value = stored.timeSpanStart
            timeSpanFilterEnd.value = stored.timeSpanEnd
            timeSpanFilterActive.value = true
        }
        sortType.value = stored.sortType ?? null
        sortOrder.value = stored.sortOrder ?? null
        if (stored.collapsedGroups && typeof stored.collapsedGroups === 'object') {
            collapsedGroups.value = stored.collapsedGroups
        }
    }
} catch {
    // unlesbarer Stand: mit Standardwerten starten
}
watch(
    [moneySource_query, moneySourceFilter, categoryFilters, openTasksFilter, timeSpanFilterActive, timeSpanFilterStart,
        timeSpanFilterEnd, sortType, sortOrder, collapsedGroups],
    () => {
        safeSetItem(LIST_STATE_KEY, JSON.stringify({
            query: moneySource_query.value,
            type: moneySourceFilter.value?.type,
            categories: categoryFilters.value,
            openTasks: openTasksFilter.value,
            timeSpanStart: timeSpanFilterActive.value ? timeSpanFilterStart.value : null,
            timeSpanEnd: timeSpanFilterActive.value ? timeSpanFilterEnd.value : null,
            sortType: sortType.value,
            sortOrder: sortOrder.value,
            collapsedGroups: collapsedGroups.value,
        }), 'session')
    },
    { deep: true }
)

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
