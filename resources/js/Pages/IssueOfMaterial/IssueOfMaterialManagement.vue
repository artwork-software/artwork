<template>
    <AppLayout :title="$t('Material issue book')">
        <div class="artwork-container">
            <!-- Header -->

            <ToolbarHeader title="Material issue book"
                           description="Track and filter all internal material issues across projects and rooms."
                           :icon="IconMenu4"
                           icon-bg-class="bg-amber-600/10 text-amber-700"
            >
                <template #actions>
                    <button class="ui-button-add" @click="openIssueOfMaterialModal">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5"/>
                        {{ $t('New issue of material') }}
                    </button>
                </template>

            </ToolbarHeader>


            <!--<div class="flex flex-wrap items-center justify-between gap-4 pt-6 pb-2 hidden">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                    <span class="inline-flex size-6 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-700">
                      <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.5" d="M4 7h16M4 12h10M4 17h16"/></svg>
                    </span>
                        <h1 class="text-2xl font-semibold tracking-tight">{{ $t('Material issue book') }}</h1>
                    </div>
                    <div class="mt-2 h-1 w-24 rounded-full bg-gradient-to-r from-indigo-500 via-sky-400 to-emerald-400"></div>
                    <p class="text-sm text-gray-500 mt-2">
                        {{ $t('Track and filter all internal material issues across projects and rooms.') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1 w-96">
                        <ArticleSearch
                            id="article-search"
                            class="w-72"
                            @article-selected="addArticleNameToFilter"
                        />
                        <button
                            type="button"
                            @click="filterIssueByArticleIds"
                            class="p-4 inline-flex items-center justify-center rounded-md border border-gray-200 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <component :is="IconSearch" class="size-5" stroke-width="1.5" />
                        </button>
                    </div>

                    <BaseButton
                        v-if="can('inventory.disposition') || is('artwork admin')"
                        :text="$t('New issue of material')"
                        @click="openIssueOfMaterialModal"
                        class="!bg-indigo-600 hover:!bg-indigo-700 !text-white !border-transparent"
                    >
                        <component :is="IconCopyPlus" class="size-5 mr-2" />
                    </BaseButton>
                </div>
            </div>-->

            <div>
                <IssueTabs/>
            </div>

            <!-- Artikel-Chips -->
            <div v-if="articleNamesForFilter.length" class="mb-3">
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="(article, index) in articleNamesForFilter"
                        :key="index"
                        class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50/70 px-2.5 py-0.5 text-sm text-sky-800 ring-1 ring-inset ring-sky-100"
                    >
                        <span class="truncate max-w-[220px]">{{ article.name }}</span>
                        <button
                            type="button"
                            class="ml-2 text-sky-500 hover:text-sky-700 focus:outline-none"
                            @click="articleNamesForFilter.splice(index, 1)"
                        >
                            <component :is="IconX" class="size-4"/>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sticky Filter Toolbar -->
            <div class="sticky top-0 z-20 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/70">
                <!-- ===== Mobile: Zusammenklappbarer Filter ===== -->
                <details class="sm:hidden border-b border-gray-100 open:shadow-[inset_0_-1px_0_0_rgba(0,0,0,0.06)]">
                    <summary class="flex items-center justify-between px-3 py-3 cursor-pointer select-none">
                        <span class="text-sm font-medium text-gray-900">{{ $t('Filters') }}</span>
                        <span class="ml-3 text-xs text-gray-500" aria-hidden="true">▾</span>
                    </summary>

                    <div class="px-3 pb-3 space-y-4">
                        <!-- Quick-Ranges (scrollbar-x) -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('Quick range') }}</label>
                            <div class="flex gap-1.5 overflow-x-auto no-scrollbar snap-x -mx-1.5 px-1.5">
                                <button type="button"
                                        class="snap-start shrink-0 rounded-md border border-indigo-200 bg-indigo-50/70 px-2.5 py-1 text-xs text-indigo-700 hover:bg-indigo-50 hover:border-indigo-300"
                                        @click="setRangeToday">{{ $t('Today') }}
                                </button>
                                <button type="button"
                                        class="snap-start shrink-0 rounded-md border border-sky-200 bg-sky-50/70 px-2.5 py-1 text-xs text-sky-700 hover:bg-sky-50 hover:border-sky-300"
                                        @click="setRangeThisWeek">{{ $t('This week') }}
                                </button>
                                <button type="button"
                                        class="snap-start shrink-0 rounded-md border border-emerald-200 bg-emerald-50/70 px-2.5 py-1 text-xs text-emerald-700 hover:bg-emerald-50 hover:border-emerald-300"
                                        @click="setRangeThisMonth">{{ $t('This month') }}
                                </button>
                                <button type="button"
                                        class="snap-start shrink-0 rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs text-gray-700 hover:bg-gray-50"
                                        @click="clearRange">{{ $t('All time') }}
                                </button>
                            </div>
                        </div>

                        <!-- Grid: Felder (1spaltig mobil) -->
                        <div class="grid grid-cols-1 gap-3">
                            <!-- Zeitraum -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{
                                        $t('Time range')
                                    }}</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="filters.date_from"
                                        type="date"
                                        :max="filters.date_to || undefined"
                                        class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                    <span class="text-gray-400 text-xs">–</span>
                                    <input
                                        v-model="filters.date_to"
                                        type="date"
                                        :min="filters.date_from || undefined"
                                        class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>

                            <!-- Raum -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('Room') }}</label>
                                <select
                                    v-model="filters.room_id"
                                    class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option :value="''">{{ $t('All rooms') }}</option>
                                    <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                                </select>
                            </div>

                            <!-- Projekt -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('Project') }}</label>
                                <select
                                    v-model="filters.project_id"
                                    class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option :value="''">{{ $t('All projects') }}</option>
                                    <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>

                            <!-- Name-Suche -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{
                                        $t('Name search')
                                    }}</label>
                                <div class="relative">
                                    <input
                                        v-model="filters.q"
                                        type="text"
                                        :placeholder="$t('Search by issue name …')"
                                        class="w-full rounded-md border border-gray-300 pl-8 pr-8 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                    <span class="pointer-events-none absolute left-2 top-1.5 text-gray-400">
                                      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <circle cx="11" cy="11" r="7" stroke-width="1.5"></circle>
                                        <path d="M20 20l-3.5-3.5" stroke-width="1.5"></path>
                                      </svg>
                                    </span>
                                    <button
                                        v-if="filters.q"
                                        type="button"
                                        class="absolute right-1.5 top-1.5 rounded p-1 text-gray-400 hover:text-gray-600"
                                        @click="filters.q = ''"
                                        :aria-label="$t('Clear search')"
                                    >
                                        &times;
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Aktionen (mobil) -->
                        <div class="flex items-center justify-end gap-2 pt-1">
                            <button
                                type="button"
                                @click="applyFilters"
                                class="inline-flex items-center justify-center rounded-md border border-indigo-200 bg-indigo-50/70 px-3 py-2 text-sm text-indigo-700 hover:bg-indigo-50 hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                :title="$t('Apply filters')"
                            >
                                <component :is="IconSearch" class="size-4 mr-1" stroke-width="1.5"/>
                                {{ $t('Apply') }}
                            </button>
                            <button
                                type="button"
                                @click="resetFilters"
                                class="inline-flex items-center justify-center rounded-md border border-gray-200 bg-white px-3 py-2 text-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sky-500"
                            >
                                {{ $t('Reset') }}
                            </button>
                        </div>
                    </div>
                </details>

                <!-- ===== Desktop/Tablet: klassische Toolbar ===== -->
                <div class="ui-card">
                    <div
                        class="flex items-center justify-between cursor-pointer select-none hidden sm:flex"
                        @click="toggleFilters"
                    >
                        <div :class="filtersCollapsed ? '' : 'pb-3'" class="text-xl sm:text-2xl font-semibold tracking-tight">{{
                                $t('Filters')
                            }}
                        </div>
                        <IconChevronDown
                            class="ml-3 text-lg text-gray-500 transition-transform duration-200 h-5 w-5"
                            :class="{ 'rotate-180': !filtersCollapsed }"
                            aria-hidden="true"
                        />
                    </div>
                    <div v-if="!filtersCollapsed" class="hidden sm:block">
                        <div class="glassy card px-4 pb-2 mb-2">
                            <!-- Zeile 1 -->
                            <div class="pt-2 text-sm font-medium text-gray-900">
                                {{ $t('Time') }}
                            </div>
                            <div class="grid grid-cols-12 lg:grid-cols-6 md:grid-cols-2 gap-3 items-end">
                                <!-- Time filters -->
                                <div class="col-span-12 md:col-span-6 lg:col-span-3">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{
                                            $t('Shortcuts')
                                        }}</label>
                                    <div class="flex flex-wrap gap-1.5">
                                        <button type="button"
                                                class="rounded-md border border-indigo-200 bg-indigo-50/70 px-2.5 py-1 text-xs text-indigo-700 hover:bg-indigo-50 hover:border-indigo-300"
                                                @click="setRangeToday">{{ $t('Today') }}
                                        </button>
                                        <button type="button"
                                                class="rounded-md border border-sky-200 bg-sky-50/70 px-2.5 py-1 text-xs text-sky-700 hover:bg-sky-50 hover:border-sky-300"
                                                @click="setRangeThisWeek">{{ $t('This week') }}
                                        </button>
                                        <button type="button"
                                                class="rounded-md border border-emerald-200 bg-emerald-50/70 px-2.5 py-1 text-xs text-emerald-700 hover:bg-emerald-50 hover:border-emerald-300"
                                                @click="setRangeThisMonth">{{ $t('This month') }}
                                        </button>
                                        <button type="button"
                                                class="rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs text-gray-700 hover:bg-gray-50"
                                                @click="clearRange">{{ $t('All time') }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Zeitraum -->
                                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{
                                            $t('Time range')
                                        }}</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="filters.date_from"
                                            type="date"
                                            :max="filters.date_to || undefined"
                                            class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        />
                                        <span class="text-gray-400 text-xs">–</span>
                                        <input
                                            v-model="filters.date_to"
                                            type="date"
                                            :min="filters.date_from || undefined"
                                            class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="!filtersCollapsed" class="glassy card px-4 pb-2 mb-2">
                        <div class="pt-2 pb-1 text-sm font-medium text-gray-900">
                            {{ $t('Room') }}
                        </div>
                        <div class="grid grid-cols-12 lg:grid-cols-6 md:grid-cols-2 gap-3 items-end">
                            <div class="col-span-12 md:col-span-6 lg:col-span-3">
                                <select
                                    v-model="filters.room_id"
                                    class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option :value="''">{{ $t('All rooms') }}</option>
                                    <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div v-if="!filtersCollapsed" class="glassy card px-4 pb-2 mb-2">
                        <div class="pt-2 pb-1 text-sm font-medium text-gray-900">
                            {{ $t('Project') }}
                        </div>
                        <div class="grid grid-cols-12 lg:grid-cols-6 md:grid-cols-2 gap-3 items-end">
                            <div class="col-span-12 md:col-span-6 lg:col-span-3">
                                <select
                                    v-model="filters.project_id"
                                    class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option :value="''">{{ $t('All projects') }}</option>
                                    <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div v-if="!filtersCollapsed" class="glassy card px-4 pb-2 mb-2">
                        <div class="pt-2 pb-1 text-sm font-medium text-gray-900">
                            {{ $t('Search by issue name') }}
                        </div>
                        <div class="grid grid-cols-12 lg:grid-cols-6 md:grid-cols-2 gap-3 items-end">
                            <div class="col-span-12 md:col-span-6 lg:col-span-3">
                                <div class="relative">
                                    <input
                                        v-model="filters.q"
                                        type="text"
                                        :placeholder="$t('Search by issue name')"
                                        class="w-full rounded-md border border-gray-300 pl-8 pr-8 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                    <span class="pointer-events-none absolute left-2 top-1.5 text-gray-400">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                          <circle cx="11" cy="11" r="7" stroke-width="1.5"></circle>
                                          <path d="M20 20l-3.5-3.5" stroke-width="1.5"></path>
                                        </svg>
                                      </span>
                                    <button
                                        v-if="filters.q"
                                        type="button"
                                        class="absolute right-1.5 top-1.5 rounded p-1 text-gray-400 hover:text-gray-600"
                                        @click="filters.q = ''"
                                        :aria-label="$t('Clear search')"
                                    >
                                        &times;
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="!filtersCollapsed" class="glassy card px-4 pb-2 mb-2">
                        <div class="pt-2 pb-1 text-sm font-medium text-gray-900">
                            {{ $t('Responsible') }}
                        </div>
                        <div class="grid grid-cols-12 lg:grid-cols-6 md:grid-cols-2 gap-3 items-end">
                            <div class="col-span-12 md:col-span-6 lg:col-span-3">
                                <div class="relative" @keydown.escape="respOpen = false">
                                    <!-- Feld -->
                                    <div
                                        class="min-h-10 w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm flex items-center gap-1 flex-nowrap overflow-x-auto no-scrollbar focus-within:ring-2 focus-within:ring-indigo-500"
                                        @click="openResp"
                                    >
                                        <template v-if="selectedResponsibleUsers.length">
                                        <span
                                            v-for="u in visibleResponsibleChipUsers"
                                            :key="u.id"
                                            class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50/70 px-2 py-0.5 text-emerald-800 ring-1 ring-inset ring-emerald-100 shrink-0"
                                        >
                                          {{ displayUserName(u) }}
                                          <button type="button" class="text-emerald-500 hover:text-emerald-700"
                                                  @click.stop="toggleResponsible(u.id)">&times;</button>
                                        </span>
                                            <span v-if="extraResponsibleChipCount > 0"
                                                  class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-gray-700 shrink-0"
                                                  :title="extraResponsibleTitles">+{{ extraResponsibleChipCount }}</span>
                                        </template>
                                        <span v-else class="text-gray-400">{{ $t('Select responsible users') }}</span>

                                        <div class="ml-auto flex items-center gap-1 shrink-0">
                                            <button type="button"
                                                    class="px-2 py-0.5 text-xs rounded border border-gray-200 bg-white hover:bg-gray-50"
                                                    @click.stop="selectAllResponsible">{{ $t('All') }}
                                            </button>
                                            <button type="button"
                                                    class="px-2 py-0.5 text-xs rounded border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-50"
                                                    :disabled="!filters.responsible_user_ids.length"
                                                    @click.stop="clearResponsible">{{ $t('Clear') }}
                                            </button>
                                            <button type="button" class="p-1 rounded hover:bg-gray-100 text-gray-600"
                                                    :aria-expanded="respOpen" @click.stop="toggleResp">▾
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Dropdown (mobil vollbreit) -->
                                    <div
                                        v-if="respOpen"
                                        class="absolute z-30 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg sm:max-w-none"
                                        @mousedown.prevent
                                    >
                                        <div class="p-2 border-b border-gray-200">
                                            <input
                                                ref="respSearchRef"
                                                v-model="respQuery"
                                                type="text"
                                                :placeholder="$t('Search users…')"
                                                class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                @keydown.enter.prevent="toggleHighlighted"
                                                @keydown.arrow-down.prevent="moveHighlight(1)"
                                                @keydown.arrow-up.prevent="moveHighlight(-1)"
                                            />
                                        </div>
                                        <ul class="max-h-60 overflow-auto py-1 text-sm">
                                            <li
                                                v-for="(u, idx) in filteredUsers"
                                                :key="u.id"
                                                class="flex items-center gap-2 px-3 py-2 cursor-pointer hover:bg-indigo-50/40"
                                                :class="{'bg-indigo-50/60': idx === respHighlightedIndex}"
                                                @mouseenter="respHighlightedIndex = idx"
                                                @click="toggleResponsible(u.id)"
                                            >
                                                <input type="checkbox"
                                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                       :checked="isResponsibleSelected(u.id)"/>
                                                <span class="truncate">{{ displayUserName(u) }}</span>
                                            </li>
                                            <li v-if="!filteredUsers.length" class="px-3 py-3 text-gray-400">
                                                {{ $t('No results') }}
                                            </li>
                                        </ul>
                                        <div
                                            class="flex items-center justify-between gap-2 px-2 py-2 border-t border-gray-200">
                                            <span class="text-xs text-gray-500">{{
                                                    $t('Selected')
                                                }}: {{ filters.responsible_user_ids.length }}</span>
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                        class="px-3 py-1.5 text-xs rounded border border-gray-200 bg-white hover:bg-gray-50"
                                                        @click="selectAllResponsible">{{ $t('Select all') }}
                                                </button>
                                                <button type="button"
                                                        class="px-3 py-1.5 text-xs rounded border border-gray-200 bg-white hover:bg-gray-50"
                                                        @click="respOpen = false">{{ $t('Done') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Apply/Reset buttons at bottom right -->
                    <div v-if="!filtersCollapsed" class="flex justify-end gap-2 mt-4">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="ui-button"
                        >
                            {{ $t('Reset') }}
                        </button>
                    </div>
                </div>
                <!-- Aktive Filter Zusammenfassung (immer sichtbar) -->
                <div v-if="hasAnyFilter" class="mt-2 flex items-center gap-2 overflow-x-auto no-scrollbar">
                    <span v-if="filtersCollapsed" class="text-sm font-medium text-gray-900 shrink-0">{{ $t('Filter') }}:</span>
                    <span v-if="filters.date_from || filters.date_to"
                          class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50/70 px-2.5 py-0.5 text-xs text-indigo-700 shrink-0">
                      {{ $t('Range') }}:
                      <span class="mx-1 font-medium">{{
                              formatDate(filters.date_from) || '…'
                          }} – {{ formatDate(filters.date_to) || '…' }}</span>
                      <button class="ml-1 text-indigo-500 hover:text-indigo-700"
                              @click="clearRange">&times;</button>
                    </span>
                    <span v-if="filters.room_id"
                          class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50/70 px-2.5 py-0.5 text-xs text-sky-700 shrink-0">
                      {{ $t('Room') }}: <span class="mx-1 font-medium">{{ roomName }}</span>
                      <button class="ml-1 text-sky-500 hover:text-sky-700"
                              @click="filters.room_id = ''">&times;</button>
                    </span>
                    <span v-if="filters.project_id"
                          class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50/70 px-2.5 py-0.5 text-xs text-emerald-700 shrink-0">
                      {{ $t('Project') }}: <span class="mx-1 font-medium">{{ projectName }}</span>
                      <button class="ml-1 text-emerald-500 hover:text-emerald-700"
                              @click="filters.project_id = ''">&times;</button>
                    </span>
                    <span v-if="filters.responsible_user_ids.length"
                          class="inline-flex items-center rounded-full border border-fuchsia-200 bg-fuchsia-50/70 px-2.5 py-0.5 text-xs text-fuchsia-700 shrink-0">
                      {{ $t('Responsible') }}: <span class="mx-1 font-medium">{{
                                selectedResponsibleUsers.length
                            }}</span>
                      <button class="ml-1 text-fuchsia-500 hover:text-fuchsia-700" @click="clearResponsible">&times;</button>
                    </span>
                    <span v-if="filters.q"
                          class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50/70 px-2.5 py-0.5 text-xs text-violet-700 shrink-0">
                      {{ $t('Search') }}: <span class="mx-1 font-medium">"{{ filters.q }}"</span>
                      <button class="ml-1 text-violet-500 hover:text-violet-700"
                              @click="filters.q = ''">&times;</button>
                    </span>
                    <button
                        v-if="hasAnyFilter"
                        type="button"
                        class="ml-auto inline-flex items-center rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs hover:bg-gray-50 shrink-0"
                        @click="resetFilters"
                    >{{ $t('Clear all') }}
                    </button>
                </div>
            </div>

            <div class="ui-card mt-5">
                <!-- Tabellenkopf -->
                <div class="mt-5">
                    <div
                        class="grid grid-cols-12 gap-4 px-2 py-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                        <div class="col-span-3">{{ $t('Name') }}</div>
                        <div class="col-span-2">{{ $t('Time range') }}</div>
                        <div class="col-span-1">{{ $t('Room') }}</div>
                        <div class="col-span-2">{{ $t('Project') }}</div>
                        <div class="col-span-1">{{ $t('Files') }}</div>
                        <div class="col-span-2">{{ $t('Responsible') }}</div>
                        <div class="col-span-1">{{ $t('Status') }}</div>
                    </div>
                    <div class="border-y border-gray-200"></div>
                </div>

                <!-- Rows -->
                <div>
                    <template v-if="issues?.data?.length">
                        <SingleInternMaterialIssue
                            v-for="issueOfMaterial in issues.data"
                            :key="issueOfMaterial.id"
                            :issue-of-material="issueOfMaterial"
                            :detailed-article="detailedArticle"
                        />
                    </template>
                    <div v-else class="mt-6">
                        <BaseAlertComponent message="No issues of material found" type="error" use-translation/>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                <BasePaginator property-name="issues" :entities="issues"/>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <issue-of-material-modal
            v-if="showIssueOfMaterialModal"
            :issue-of-material="issueToEdit"
            @close="closeIssueModal"
        />
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import ArticleSearch from "@/Components/SearchBars/ArticleSearch.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import IssueTabs from "@/Pages/IssueOfMaterial/Components/IssueTabs.vue";
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import SingleInternMaterialIssue from "@/Pages/IssueOfMaterial/Components/SingleInternMaterialIssue.vue";
import {router, usePage} from "@inertiajs/vue3";
import {computed, provide, ref, watch, nextTick, onMounted, onBeforeUnmount} from "vue";
import {can, is} from "laravel-permission-to-vuejs";
import {IconCirclePlus, IconCopyPlus, IconMenu, IconMenu4, IconSearch, IconX, IconChevronDown} from "@tabler/icons-vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";

const props = defineProps({
    issues: Object,
    articlesInFilter: {type: Array, default: () => []},
    materialSets: Object,
    detailedArticle: Object,
    categories: Object,
    filterable_properties: {type: Array, default: () => []},
    articles: {type: Object, default: () => []}
});

provide("materialSets", props.materialSets);

const page = usePage();
const rooms = computed(() => page.props.rooms ?? []);
const projects = computed(() => page.props.projects ?? []);
const users = computed(() => page.props.users ?? []);
const initial = page.props.urlParameters ?? {};

const showIssueOfMaterialModal = ref(false);
const issueToEdit = ref(null);
const openIssueOfMaterialModal = () => {
    issueToEdit.value = null;
    showIssueOfMaterialModal.value = true;
};
const closeIssueModal = () => {
    showIssueOfMaterialModal.value = false;
    issueToEdit.value = null;
};

// Artikel-Filter
const articleNamesForFilter = ref(props.articlesInFilter ?? []);
const addArticleNameToFilter = (article) => {
    if (!articleNamesForFilter.value.find(a => a.id === article.id)) {
        articleNamesForFilter.value.push(article);
    }
};
const currentArticleIdsCsv = computed(() =>
    articleNamesForFilter.value?.length
        ? articleNamesForFilter.value.map(a => a.id).join(',')
        : (page.props.urlParameters?.article_ids ?? '')
);
const filterIssueByArticleIds = () => {
    router.reload({
        preserveState: true, preserveScroll: true,
        data: {article_ids: currentArticleIdsCsv.value || undefined},
        only: ['issues', 'articlesInFilter'], replace: true
    });
};
watch(articleNamesForFilter, () => filterIssueByArticleIds(), {deep: true});

// Kernfilter
const filters = ref({
    date_from: initial.date_from ?? "",
    date_to: initial.date_to ?? "",
    project_id: initial.project_id ?? "",
    room_id: initial.room_id ?? "",
    responsible_user_ids: Array.isArray(initial.responsible_user_ids)
        ? initial.responsible_user_ids.map(Number)
        : (typeof initial.responsible_user_ids === 'string' && initial.responsible_user_ids.length
            ? initial.responsible_user_ids.split(',').map(x => Number(x))
            : []),
    q: initial.q ?? "", // <- NEU
});

const applyFilters = () => {
    router.reload({
        preserveState: true,
        preserveScroll: true,
        data: {
            article_ids: currentArticleIdsCsv.value || undefined,
            date_from: filters.value.date_from || undefined,
            date_to: filters.value.date_to || undefined,
            project_id: filters.value.project_id || undefined,
            room_id: filters.value.room_id || undefined,
            responsible_user_ids: filters.value.responsible_user_ids?.length
                ? filters.value.responsible_user_ids.join(',')
                : undefined,
            q: filters.value.q || undefined, // <- NEU
        },
        replace: true,
        only: ['issues', 'articlesInFilter']
    });
};

// Debounce auto-apply
let debounceTimer = null;
watch(filters, () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters(), 280);
}, {deep: true});

const resetFilters = () => {
    filters.value = {
        date_from: "",
        date_to: "",
        project_id: "",
        room_id: "",
        responsible_user_ids: [],
        q: "", // <- NEU
    };
    applyFilters();
};

const hasAnyFilter = computed(() =>
    !!(filters.value.date_from || filters.value.date_to || filters.value.project_id || filters.value.room_id || filters.value.responsible_user_ids?.length || filters.value.q)
);

const roomName = computed(() => rooms.value.find(r => r.id == filters.value.room_id)?.name ?? '');
const projectName = computed(() => projects.value.find(p => p.id == filters.value.project_id)?.name ?? '');

// Quick ranges
const pad = (n) => String(n).padStart(2, '0');
const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const today = () => new Date();
const startOfWeek = () => {
    const d = today();
    const day = (d.getDay() + 6) % 7;
    const s = new Date(d);
    s.setDate(d.getDate() - day);
    return s;
};
const endOfWeek = () => {
    const s = startOfWeek();
    const e = new Date(s);
    e.setDate(s.getDate() + 6);
    return e;
};
const startOfMonth = () => {
    const d = today();
    return new Date(d.getFullYear(), d.getMonth(), 1);
};
const endOfMonth = () => {
    const d = today();
    return new Date(d.getFullYear(), d.getMonth() + 1, 0);
};
const setRangeToday = () => {
    const t = today();
    filters.value.date_from = fmt(t);
    filters.value.date_to = fmt(t);
};
const setRangeThisWeek = () => {
    filters.value.date_from = fmt(startOfWeek());
    filters.value.date_to = fmt(endOfWeek());
};
const setRangeThisMonth = () => {
    filters.value.date_from = fmt(startOfMonth());
    filters.value.date_to = fmt(endOfMonth());
};
const clearRange = () => {
    filters.value.date_from = "";
    filters.value.date_to = "";
};

// Filter card collapse/expand
const filtersCollapsed = ref(true);
const toggleFilters = () => {
    filtersCollapsed.value = !filtersCollapsed.value;
};

// Responsible Multi-Select
const respOpen = ref(false);
const respQuery = ref('');
const respSearchRef = ref(null);
const respHighlightedIndex = ref(-1);

const displayUserName = (u) => {
    const first = (u.first_name || '').trim();
    const last = (u.last_name || '').trim();
    const full = [first, last].filter(Boolean).join(' ').trim();
    return full || u.name || u.email || `#${u.id}`;
};
const selectedResponsibleUsers = computed(() =>
    (users.value || []).filter(u => (filters.value.responsible_user_ids || []).includes(u.id))
);
const visibleResponsibleChipUsers = computed(() => selectedResponsibleUsers.value.slice(0, 3));
const extraResponsibleChipCount = computed(() => Math.max(0, selectedResponsibleUsers.value.length - 3));
const extraResponsibleTitles = computed(() => selectedResponsibleUsers.value.slice(3).map(displayUserName).join(', '));

const filteredUsers = computed(() => {
    const q = respQuery.value.trim().toLowerCase();
    const base = users.value || [];
    if (!q) return base;
    return base.filter(u => displayUserName(u).toLowerCase().includes(q));
});
const isResponsibleSelected = (id) => (filters.value.responsible_user_ids || []).includes(id);
const toggleResponsible = (id) => {
    const set = new Set(filters.value.responsible_user_ids || []);
    set.has(id) ? set.delete(id) : set.add(id);
    filters.value.responsible_user_ids = Array.from(set);
};
const selectAllResponsible = () => {
    filters.value.responsible_user_ids = (users.value || []).map(u => u.id);
};
const clearResponsible = () => {
    filters.value.responsible_user_ids = [];
};

const moveHighlight = (delta) => {
    const len = filteredUsers.value.length;
    if (!len) return;
    let idx = respHighlightedIndex.value + delta;
    if (idx < 0) idx = len - 1;
    if (idx >= len) idx = 0;
    respHighlightedIndex.value = idx;
};
const toggleHighlighted = () => {
    const u = filteredUsers.value[respHighlightedIndex.value];
    if (u) toggleResponsible(u.id);
};

const openResp = async () => {
    respOpen.value = true;
    await nextTick();
    respSearchRef.value?.focus();
};
const toggleResp = async () => {
    respOpen.value = !respOpen.value;
    if (respOpen.value) {
        await nextTick();
        respSearchRef.value?.focus();
    }
};


// Outside click
const onDocClick = (e) => {
    const el = e.target?.closest?.('.relative');
    if (!el) respOpen.value = false;
};
onMounted(() => document.addEventListener('click', onDocClick, {capture: true}));
onBeforeUnmount(() => document.removeEventListener('click', onDocClick, {capture: true}));

// function to rerender the date to translated to page locale
const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString(page.props.locale, {year: 'numeric', month: '2-digit', day: '2-digit'});
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Smooth transitions for collapsible filters */
.v-enter-active, .v-leave-active {
    transition: all 0.3s ease;
}

.v-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.v-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

</style>
