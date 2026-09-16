<template>
    <ArtworkBaseModal
        modal-size="sm:max-w-7xl"
        :title="t('Shift history')"
        :description="t('Select a period, craft and optionally a person to load the shift history. Filters narrow the loaded entries without reloading.')"
        @close="handleClose"
    >
        <!-- Feste Höhe (nur Desktop): alles sichtbar, einzig die Ergebnisliste scrollt -->
        <div class="grid grid-cols-1 gap-4 lg:h-[74vh] lg:min-h-[30rem] lg:grid-cols-[280px_minmax(0,1fr)]">
            <!-- Linke Spalte: Datenauswahl (lädt vom Server) + Filter (nur clientseitig) -->
            <aside class="space-y-3 lg:min-h-0 lg:overflow-y-auto">
                <!-- Block 1: Datenauswahl — bestimmt, WAS vom Server geladen wird. Diskrete Eingaben
                     (Gewerk, Datum, Person, Reichweite) laden automatisch neu. -->
                <div class="rounded-xl border border-border-subtle bg-white p-3.5 space-y-3">
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] font-semibold uppercase tracking-wide text-text-subtle">{{ t('Data selection') }}</span>
                        <ToolTipComponent
                            direction="right"
                            :tooltip-text="t('Determines which entries are loaded from the server. Changing period, craft or person reloads the history automatically.')"
                            icon="IconInfoCircle"
                            icon-size="h-4 w-4"
                        />
                    </div>

                    <ArtworkBaseListbox
                        v-model="selectedCraft"
                        :items="craftsWithAll"
                        :disabled="loading"
                        :useTranslations="false"
                        :placeholder="t('Select craft')"
                        :emptyText="t('No options available')"
                        is-small
                        label="Craft"
                        optionLabel="name"
                        optionKey="id"
                    />

                    <div class="space-y-2">
                        <!-- Die Regel "Zeitraum = Schichtstart, nicht Änderungsdatum" steht im Label, nicht nur im Tooltip -->
                        <div class="flex items-center gap-1">
                            <span class="text-xs font-medium text-text-muted">{{ t('Shifts starting between') }}</span>
                            <ToolTipComponent
                                direction="right"
                                :tooltip-text="t('The period refers to the start date of the shifts, not to when a change was made. Every entry of a shift starting in this period is shown, regardless of when it was changed.')"
                                icon="IconInfoCircle"
                                icon-size="h-4 w-4"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <BaseInput
                                v-model="startDate"
                                id="shift-history-start"
                                type="date"
                                label="From"
                                is-small
                                :disabled="loading"
                            />
                            <BaseInput
                                v-model="endDate"
                                id="shift-history-end"
                                type="date"
                                label="To"
                                is-small
                                :disabled="loading"
                            />
                        </div>
                    </div>

                    <!-- Personenfilter: lädt serverseitig (Schichten der Person werden hinzugezogen),
                         Auswahl und Reichweite laden automatisch neu -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-1">
                            <span class="text-xs font-medium text-text-muted">{{ t('Person') }}</span>
                            <ToolTipComponent
                                direction="right"
                                :tooltip-text="t('Shows the history in relation to one person. Choose below how far this relation reaches. Each entry then shows why it is listed.')"
                                icon="IconInfoCircle"
                                icon-size="h-4 w-4"
                            />
                        </div>

                        <div
                            v-if="selectedPerson"
                            class="flex items-center justify-between gap-2 rounded-lg border border-accent-200 bg-accent-50 px-2.5 py-1.5"
                        >
                            <span class="inline-flex min-w-0 items-center gap-1.5 text-xs font-medium text-accent-700">
                                <IconUser class="h-3.5 w-3.5 shrink-0" stroke-width="2" />
                                <span class="truncate">{{ selectedPerson.name }}</span>
                            </span>
                            <button
                                type="button"
                                class="shrink-0 rounded-full p-0.5 text-accent-700 transition-colors hover:bg-accent-200/60 disabled:cursor-not-allowed"
                                :disabled="loading"
                                :title="t('Remove person filter')"
                                @click="clearPerson"
                            >
                                <IconX class="h-3.5 w-3.5" stroke-width="2" />
                            </button>
                        </div>
                        <UserSearch
                            v-else
                            search-workers
                            :label="t('Search person')"
                            :disabled="loading"
                            @user-selected="onPersonSelected"
                        />

                        <div
                            v-if="selectedPerson"
                            class="grid grid-cols-1 gap-[2px] rounded-[8px] bg-border-subtle p-[3px]"
                            role="radiogroup"
                            :aria-label="t('Scope of the person filter')"
                        >
                            <button
                                v-for="opt in personScopeOptions"
                                :key="opt.id"
                                type="button"
                                role="radio"
                                class="flex h-[26px] items-center rounded-[6px] px-2.5 text-left text-[12px] font-semibold transition disabled:cursor-not-allowed"
                                :class="personScope === opt.id ? 'bg-surface shadow-raised text-text' : 'text-text hover:bg-white/60'"
                                :aria-checked="personScope === opt.id"
                                :disabled="loading"
                                @click="personScope = opt.id"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                        <p v-if="selectedPerson" class="text-[11px] leading-4 text-text-subtle">{{ personScopeHint }}</p>
                    </div>

                    <div class="space-y-2">
                        <BaseUIButton
                            :disabled="loading"
                            is-add-button
                            :icon="loadBtnIcon"
                            @click="fetchHistory(true)"
                            class="w-full justify-center"
                            :class="paramsDirty ? 'ring-2 ring-warning-border' : ''"
                        >
                            {{ loading ? t('Loading...') : t('Load history') }}
                        </BaseUIButton>

                        <p v-if="paramsDirty" class="text-[11px] text-warning">
                            {{ t('Selection changed – reload to update the results.') }}
                        </p>

                        <!-- Excel-Export mit der aktuellen Auswahl (Gewerk, Zeitraum, Schicht, Suche, Sortierung) -->
                        <a :href="exportUrl" target="_blank" rel="noopener" class="block" :title="t('Exports the current filter selection.')">
                            <BaseUIButton
                                :disabled="loading"
                                icon="IconFileSpreadsheet"
                                class="w-full justify-center"
                            >
                                {{ t('Export as Excel') }}
                            </BaseUIButton>
                        </a>
                    </div>

                    <div v-if="error" class="rounded-lg border border-danger-border bg-danger-surface px-3 py-2 text-xs text-danger">
                        {{ error }}
                    </div>
                </div>

                <div class="rounded-xl border border-border-subtle bg-white p-3.5 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-text-subtle">{{ t('Narrow down') }}</span>
                            <ToolTipComponent
                                direction="right"
                                :tooltip-text="t('Narrows the entries already loaded, immediately and without reloading. To search beyond the loaded entries, use \'Search in all entries\'.')"
                                icon="IconInfoCircle"
                                icon-size="h-4 w-4"
                            />
                        </div>
                        <button
                            type="button"
                            class="text-[11px] text-text-subtle underline underline-offset-2 transition-colors hover:text-text disabled:cursor-not-allowed disabled:text-text-subtle"
                            :disabled="loading"
                            @click="resetFilters"
                        >
                            {{ t('Reset') }}
                        </button>
                    </div>

                    <div class="space-y-1">
                        <BaseInput
                            v-model="search"
                            id="shift-history-search"
                            type="text"
                            label="Search in results"
                            is-small
                            :placeholder="t('Search in history...')"
                            :disabled="loading"
                            @keyup.enter="runServerSearch"
                        />
                        <!-- Serverweite Suche nur auf ausdrücklichen Wunsch (Enter oder Link) -->
                        <button
                            v-if="canRunServerSearch"
                            type="button"
                            class="text-[11px] text-accent-700 underline underline-offset-2 hover:text-accent-600 disabled:cursor-not-allowed"
                            :disabled="loading"
                            @click="runServerSearch"
                        >
                            {{ t('Search "{term}" in all entries', { term: search.trim() }) }}
                        </button>
                    </div>

                    <ArtworkBaseListbox
                        v-model="selectedAction"
                        :items="actionItems"
                        :disabled="loading"
                        :useTranslations="true"
                        :enable-search="false"
                        :sort-fn="() => 0"
                        is-small
                        label="Kind of change"
                        placeholder="All"
                        emptyText="No options available"
                        optionLabel="name"
                        optionKey="id"
                    >
                        <template #label>
                            <span class="flex items-center gap-1">
                                {{ t('Kind of change') }}
                                <ToolTipComponent
                                    direction="right"
                                    :tooltip-text="actionFilterTooltip"
                                    allow-html
                                    tooltip-css-class="aw-tooltip-wide"
                                    icon="IconInfoCircle"
                                    icon-size="h-3.5 w-3.5"
                                />
                            </span>
                        </template>
                    </ArtworkBaseListbox>

                    <ArtworkBaseListbox
                        v-model="selectedShift"
                        :items="shifts"
                        :disabled="loading"
                        :useTranslations="false"
                        :placeholder="t('All shifts')"
                        :emptyText="t('No shifts in selected range')"
                        is-small
                        label="Shift"
                        :optionLabel="(s) => shiftLabel(s)"
                        optionKey="id"
                    />

                    <!-- Nur Änderungen zeigen, die nach dem Festschreiben der Schicht passiert sind -->
                    <div class="flex items-center justify-between gap-2 pt-0.5">
                        <ArtworkBaseToggle
                            v-model="onlyPostCommit"
                            :label="t('Only subsequent changes')"
                            :disabled="loading"
                            is-small
                        />
                        <ToolTipComponent
                            direction="left"
                            :tooltip-text="t('Shows only changes made after the affected shift was committed.')"
                            icon="IconInfoCircle"
                            icon-size="h-4 w-4"
                        />
                    </div>

                </div>
            </aside>

            <!-- Rechte Spalte: Ergebnisse – füllt die Modalhöhe, nur die Liste scrollt -->
            <section class="min-w-0 flex flex-col gap-4 lg:min-h-0">
                <!-- Zusammenfassung "Du siehst …": Zähler in Worten + aktive Auswahl als entfernbare Chips -->
                <div class="shrink-0 space-y-2 rounded-xl border border-border-subtle bg-white px-4 py-3">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                        <p class="text-sm text-text">
                            <span class="font-semibold">{{ t('{count} entries', { count: filteredLogs.length }) }}</span>
                            <span class="text-text-muted">{{ ' ' + t('for {count} shifts', { count: visibleShiftCount }) }}</span>
                            <span v-if="hasLoaded" class="text-text-subtle">{{ ' · ' + t('{loaded} of {total} loaded', { loaded: rawLogs.length, total: meta.total }) }}</span>
                        </p>
                        <button
                            v-if="anyFilterActive"
                            type="button"
                            class="text-[11px] text-text-subtle underline underline-offset-2 transition-colors hover:text-text disabled:cursor-not-allowed"
                            :disabled="loading"
                            @click="resetAll"
                        >
                            {{ t('Reset all') }}
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <BaseChip variant="neutral">{{ t('Shifts starting {from} – {to}', { from: formatDate(startDate), to: formatDate(endDate) }) }}</BaseChip>
                        <BaseChip variant="neutral">{{ selectedCraft?.name ?? t('All crafts') }}</BaseChip>
                        <BaseChip v-if="selectedPerson" variant="accent">
                            <IconUser class="h-3 w-3" stroke-width="2" />
                            {{ selectedPerson.name }} · {{ personScopeShortLabel }}
                            <button type="button" class="ml-0.5 rounded-full hover:bg-accent-200/60" :title="t('Remove person filter')" :disabled="loading" @click="clearPerson"><IconX class="h-3 w-3" /></button>
                        </BaseChip>
                        <BaseChip v-if="serverSearch" variant="warning">
                            {{ t('Search in all entries') }}: „{{ serverSearch }}“
                            <button type="button" class="ml-0.5 rounded-full hover:bg-warning-border/60" :disabled="loading" @click="clearServerSearch"><IconX class="h-3 w-3" /></button>
                        </BaseChip>
                        <BaseChip v-if="clientSearchActive" variant="warning">
                            {{ t('Search in results') }}: „{{ search.trim() }}“
                            <button type="button" class="ml-0.5 rounded-full hover:bg-warning-border/60" @click="search = ''"><IconX class="h-3 w-3" /></button>
                        </BaseChip>
                        <BaseChip v-if="selectedAction && selectedAction.id !== 'all'" variant="neutral">
                            {{ t(selectedAction.name) }}
                            <button type="button" class="ml-0.5 rounded-full hover:bg-border-subtle" @click="selectedAction = { id: 'all', name: 'All' }"><IconX class="h-3 w-3" /></button>
                        </BaseChip>
                        <BaseChip v-if="onlyPostCommit" variant="warning">
                            {{ t('Only subsequent changes') }}
                            <button type="button" class="ml-0.5 rounded-full hover:bg-warning-border/60" @click="onlyPostCommit = false"><IconX class="h-3 w-3" /></button>
                        </BaseChip>
                    </div>

                    <!-- Gruppierung ist Darstellung, kein Filter → hier statt in der Filterspalte -->
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-text-subtle">{{ t('Group by') }}</span>
                            <div class="inline-flex gap-[2px] rounded-[8px] bg-border-subtle p-[3px]" role="radiogroup" :aria-label="t('Group by')">
                                <button
                                    v-for="opt in groupOptions"
                                    :key="opt.id"
                                    type="button"
                                    role="radio"
                                    class="inline-flex h-[24px] items-center justify-center rounded-[6px] px-2.5 text-[11.5px] font-semibold transition disabled:cursor-not-allowed"
                                    :class="groupByShiftDay === opt.byShiftDay ? 'bg-surface shadow-raised text-text' : 'text-text hover:bg-white/60'"
                                    :aria-checked="groupByShiftDay === opt.byShiftDay"
                                    :disabled="loading"
                                    :title="opt.hint"
                                    @click="groupByShiftDay = opt.byShiftDay"
                                >
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>
                        <p class="text-[11px] text-text-subtle">
                            {{ groupByShiftDay ? t('Grouped by shift day, newest first.') : t('Grouped by change date, newest first.') }}
                            <span v-if="selectedPerson"> {{ personScopeHint }}</span>
                        </p>
                    </div>
                </div>

                <!-- Einstiegskontext "eine Schicht": feste Kopfzeile mit der gewählten Schicht -->
                <div
                    v-if="selectedShift"
                    class="shrink-0 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-accent-200 bg-accent-50 px-4 py-2.5"
                >
                    <p class="min-w-0 text-sm text-accent-700">
                        <span class="font-semibold">{{ t('History of shift #{id}', { id: selectedShift.id }) }}</span>
                        <span class="text-accent-700/80">{{ ' · ' + shiftLabel(selectedShift) }}</span>
                        <span v-if="selectedShift.deleted_at" class="ml-1 inline-flex items-center gap-1 rounded-full border border-danger-border bg-danger-surface px-2 py-0.5 text-[10px] font-medium text-danger">
                            <IconTrash class="h-3 w-3" />{{ t('deleted') }}
                        </span>
                    </p>
                    <button
                        type="button"
                        class="shrink-0 text-[11px] font-medium text-accent-700 underline underline-offset-2 hover:text-accent-600 disabled:cursor-not-allowed"
                        :disabled="loading"
                        @click="selectedShift = null"
                    >
                        {{ t('Show all shifts') }}
                    </button>
                </div>

                <!-- Noch nichts geladen: erst der Klick auf "Verlauf laden" holt Daten -->
                <div v-if="!hasLoaded && !loading" class="flex-1 flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-surface-sunken/60 px-6 py-10 text-center">
                    <IconHistory class="h-8 w-8 text-text-subtle" stroke-width="1.5" />
                    <p class="mt-3 text-sm font-medium text-text">{{ t('Nothing loaded yet') }}</p>
                    <p class="mt-1 text-xs text-text-subtle">{{ t('Choose a craft and period on the left, then load the shift history.') }}</p>
                    <div class="mt-4 flex justify-center">
                        <BaseUIButton is-add-button icon="IconRefresh" @click="fetchHistory(true)">
                            {{ t('Load history') }}
                        </BaseUIButton>
                    </div>
                </div>

                <!-- Ladezustand: Skeleton-Karten statt Textzeile -->
                <div v-else-if="loading && !rawLogs.length" class="flex-1 space-y-3 rounded-xl border border-border-subtle bg-white p-5" aria-busy="true">
                    <div v-for="n in 4" :key="n" class="animate-pulse rounded-xl border border-border-subtle overflow-hidden">
                        <div class="h-7 bg-surface-sunken/80"></div>
                        <div class="space-y-2 px-3 py-3">
                            <div class="h-3 w-1/3 rounded bg-surface-sunken"></div>
                            <div class="h-3 w-2/3 rounded bg-surface-sunken"></div>
                        </div>
                    </div>
                </div>

                <!-- Leerzustand: sagt konkret, WAS leer ist, und bietet den nächsten Schritt an -->
                <div v-else-if="!filteredLogs.length" class="flex-1 rounded-xl border border-border-subtle bg-white p-5 text-sm">
                    <template v-if="rawLogs.length">
                        <div class="font-medium text-text">{{ t('None of the {count} loaded entries match the current narrowing.', { count: rawLogs.length }) }}</div>
                        <div class="mt-1 text-text-muted">{{ t('Remove a filter chip above or reset the narrowing.') }}</div>
                        <div class="mt-3">
                            <BaseUIButton is-small hide-icon @click="resetFilters">{{ t('Reset narrowing') }}</BaseUIButton>
                        </div>
                    </template>
                    <template v-else>
                        <div class="font-medium text-text">
                            {{ selectedPerson
                                ? t('No entries for {name} for shifts starting {from} – {to}.', { name: selectedPerson.name, from: formatDate(startDate), to: formatDate(endDate) })
                                : t('No entries for shifts starting {from} – {to}.', { from: formatDate(startDate), to: formatDate(endDate) }) }}
                        </div>
                        <div class="mt-1 text-text-muted">
                            {{ selectedPerson && personScope !== 'subject'
                                ? t('{name} is not scheduled in any shift of this period and no action concerns them here.', { name: selectedPerson.name })
                                : t('Try expanding the period or choosing another craft.') }}
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <BaseUIButton v-if="!rangeCoversFullMonths" is-small hide-icon @click="expandRangeToMonths">
                                {{ t('Expand period to whole months') }}
                            </BaseUIButton>
                            <BaseUIButton v-if="selectedPerson && personScope === 'subject'" is-small hide-icon @click="personScope = 'assigned'">
                                {{ t('Include their shifts') }}
                            </BaseUIButton>
                        </div>
                    </template>
                </div>

                <div v-else class="flex-1 lg:min-h-0 rounded-xl border border-border-subtle bg-white">
                    <div class="h-full px-5 py-5 overflow-y-auto pr-4 space-y-5">
                        <div v-for="group in groupedLogs" :key="group.dayKey" class="space-y-3">
                            <DividerChip :label="group.unknown ? t('Unknown date') : groupHeaderLabel(group.dayKey)" variant="brand" />

                            <!-- Ein Block = eine Schicht (bei Schichttag-Gruppierung alle ihre Vorgänge, sonst ein Vorgang) -->
                            <ol class="space-y-3">
                                <li
                                    v-for="block in group.blocks"
                                    :key="block.key"
                                    class="rounded-xl border border-border-strong bg-white overflow-hidden"
                                >
                                    <!-- Schicht-Kopf in EINER Zeile; klickbar → "nur diese Schicht" -->
                                    <component
                                        :is="block.shift ? 'button' : 'div'"
                                        :type="block.shift ? 'button' : undefined"
                                        class="flex w-full flex-wrap items-center gap-x-2 gap-y-1 border-b border-border-subtle bg-surface-sunken/70 px-3 py-1.5 text-left"
                                        :class="block.shift ? 'transition-colors hover:bg-surface-sunken cursor-pointer' : ''"
                                        :title="block.shift ? t('Show only this shift') : undefined"
                                        :disabled="block.shift && loading ? true : undefined"
                                        @click="block.shift && focusShift(block.shift)"
                                    >
                                        <span class="text-[10px] font-semibold uppercase tracking-wide text-text-subtle">
                                            {{ block.commitSummary ? t('Commitment') : t('Shift') }}<span v-if="block.details.id"> #{{ block.details.id }}</span>
                                        </span>
                                        <span class="min-w-0 truncate text-[11.5px] font-medium text-text" :title="block.headline">{{ block.headline }}</span>
                                        <span
                                            v-if="block.details.deleted"
                                            class="inline-flex items-center gap-1 rounded-full border border-danger-border bg-danger-surface px-2 py-0.5 text-[10px] font-medium text-danger"
                                        >
                                            <IconTrash class="h-3 w-3" />
                                            {{ t('Subsequently deleted') }}
                                        </span>
                                        <span v-if="block.items.length > 1" class="ml-auto text-[10px] text-text-subtle">
                                            {{ t('{count} entries', { count: block.items.length }) }}
                                        </span>
                                    </component>

                                    <!-- Vorgänge als Zeitleiste: Zeitpunkt · Art/Bezug · Meldung · Verursacher -->
                                    <ol class="divide-y divide-border-subtle">
                                        <li v-for="entry in block.items" :key="entry.id" class="px-3 py-2">
                                            <div class="flex flex-col gap-1.5 sm:flex-row sm:items-start sm:gap-3">
                                                <span class="shrink-0 pt-0.5 text-[11px] tabular-nums text-text-subtle sm:w-[7.25rem]">
                                                    {{ entryTimeLabel(entry) }}
                                                </span>

                                                <div class="min-w-0 flex-1">
                                                    <div class="mb-0.5 flex flex-wrap items-center gap-1.5">
                                                        <span
                                                            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium"
                                                            :class="categoryChipClass(entry.category)"
                                                        >
                                                            {{ categoryLabel(entry.category) }}
                                                        </span>
                                                        <span
                                                            v-if="entry.context === 'post_commit'"
                                                            class="inline-flex items-center rounded-full border border-warning-border bg-warning-surface px-2 py-0.5 text-[10px] font-medium text-warning"
                                                        >
                                                            {{ t('Post-commit') }}
                                                        </span>
                                                        <span
                                                            v-for="chip in entry.reasonChips"
                                                            :key="chip.key"
                                                            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-medium"
                                                            :class="chip.class"
                                                        >
                                                            <component :is="chip.icon" class="h-3 w-3" stroke-width="2" />
                                                            {{ chip.label }}
                                                        </span>
                                                        <span
                                                            v-if="entry.searchHits.length"
                                                            class="inline-flex items-center gap-1 rounded-full border border-warning-border bg-warning-surface px-2 py-0.5 text-[10px] font-medium text-warning"
                                                        >
                                                            <IconSearch class="h-3 w-3" stroke-width="2" />
                                                            {{ t('Match in: {fields}', { fields: entry.searchHits.join(', ') }) }}
                                                        </span>
                                                    </div>

                                                    <p class="text-sm leading-5 text-text whitespace-pre-wrap">
                                                        <template v-for="(seg, i) in highlight(entry.message)" :key="i">
                                                            <mark v-if="seg.hit" class="rounded bg-warning-surface px-0.5 text-inherit">{{ seg.text }}</mark>
                                                            <template v-else>{{ seg.text }}</template>
                                                        </template>
                                                    </p>

                                                    <!-- Besetzung: Person + Funktion als Chip -->
                                                    <div v-if="entry.staffing" class="mt-1 flex flex-wrap items-center gap-1.5">
                                                        <span class="inline-flex items-center gap-1 rounded-full border border-border-subtle bg-surface-sunken px-2 py-0.5 text-[11px] text-text">
                                                            <IconUser class="h-3 w-3 text-text-subtle" stroke-width="2" />
                                                            <span class="font-medium">{{ entry.staffing.name }}</span>
                                                            <span v-if="entry.staffing.qualification" class="text-text-muted">· {{ entry.staffing.qualification }}</span>
                                                        </span>
                                                    </div>

                                                    <!-- Anlage: Felder, die der Schicht-Kopf nicht zeigt -->
                                                    <p v-if="entry.createdDetails.length" class="mt-1 text-[11px] text-text-muted">
                                                        <span class="text-text-subtle">{{ t('Created with') }}: </span>
                                                        <template v-for="(d, i) in entry.createdDetails" :key="d.label">
                                                            <span v-if="i > 0"> · </span>
                                                            <span>{{ d.label }} {{ d.value }}</span>
                                                        </template>
                                                    </p>

                                                    <!-- Vorher/Nachher aufklappbar (nicht bei Besetzung, dort reicht der Chip) -->
                                                    <template v-if="entry.changes.length && entry.category !== 'staffing'">
                                                        <button
                                                            type="button"
                                                            class="mt-1 inline-flex items-center gap-1 text-[11px] text-accent-700 underline underline-offset-2 hover:text-accent-600"
                                                            :aria-expanded="isExpanded(entry.id)"
                                                            @click="toggleExpanded(entry.id)"
                                                        >
                                                            <IconChevronDown class="h-3 w-3 transition-transform" :class="isExpanded(entry.id) ? 'rotate-180' : ''" stroke-width="2" />
                                                            {{ isExpanded(entry.id) ? t('Hide changes') : t('Show changes ({count})', { count: entry.changes.length }) }}
                                                        </button>
                                                        <div v-if="isExpanded(entry.id)" class="mt-1.5 rounded-lg border border-border-subtle bg-surface-sunken/70 p-3">
                                                            <table class="w-full border-collapse text-[11px]">
                                                                <thead>
                                                                <tr class="text-text-subtle text-[10px]">
                                                                    <th class="text-left font-medium pb-2 pr-3">{{ t('Field') }}</th>
                                                                    <th class="text-left font-medium pb-2 pr-3">{{ t('Before') }}</th>
                                                                    <th class="text-left font-medium pb-2">{{ t('After') }}</th>
                                                                </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-border-subtle">
                                                                <tr
                                                                    v-for="change in entry.changes"
                                                                    :key="change.fieldName + '-' + change.index"
                                                                    class="align-top"
                                                                >
                                                                    <td class="py-2 pr-3 text-text-muted">{{ fieldLabel(change.fieldName) }}</td>
                                                                    <td class="py-2 pr-3 text-text-subtle">{{ formatFieldValue(change.fieldName, change.oldValue) }}</td>
                                                                    <td class="py-2 text-text">{{ formatFieldValue(change.fieldName, change.newValue) }}</td>
                                                                </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </template>
                                                </div>

                                                <span class="shrink-0 self-start inline-flex items-center gap-1.5 rounded-full bg-surface-inverse px-2.5 py-0.5 text-[11px] font-medium text-text-inverse" :title="t('by {causer} · {datetime}', { causer: entry.causerName, datetime: entry.createdAtFormatted })">
                                                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-white/20 text-[8px]">
                                                        {{ entry.causerInitials }}
                                                    </span>
                                                    <span>
                                                        <template v-for="(seg, i) in highlight(entry.causerName ?? '')" :key="i">
                                                            <mark v-if="seg.hit" class="rounded bg-warning-surface px-0.5 text-warning">{{ seg.text }}</mark>
                                                            <template v-else>{{ seg.text }}</template>
                                                        </template>
                                                    </span>
                                                </span>
                                            </div>
                                        </li>
                                    </ol>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>

                <BaseUIButton
                    v-if="canLoadMore"
                    :disabled="loading"
                    :icon="loading ? 'IconLoader2' : 'IconChevronDown'"
                    is-small
                    class="w-full shrink-0 justify-center"
                    @click="fetchHistory(false)"
                >
                    {{ loading ? t('Loading...') : t('Load more') }}
                </BaseUIButton>
            </section>
        </div>
    </ArtworkBaseModal>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'

import { useShiftPlanRequest } from '../../ShiftPlanRequests/components/useShiftPlanRequest.js'

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import UserSearch from '@/Components/SearchBars/UserSearch.vue'
import DividerChip from '@/Artwork/Divider/DividerChip.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue'
import { usePage } from '@inertiajs/vue3'
import { IconChevronDown, IconHistory, IconSearch, IconTrash, IconUser, IconUserEdit, IconUsers, IconX } from '@tabler/icons-vue'

type ShiftActivityProperties = {
    translation_key?: string | null
    translation_key_placeholder_values?: any[] | null
    context?: 'normal' | 'in_workflow' | 'post_commit' | string | null
    shift_id?: number | null
    shift_ids?: number[]
    [key: string]: any
}

type RawShiftActivity = {
    id: number
    log_name: string
    description: string
    event: string
    subject_id: number | null
    created_at: string
    properties: ShiftActivityProperties
    /** Bezugsgründe bei aktivem Personenfilter (Backend: ShiftHistoryQueryService::matchReasons) */
    match_reasons?: MatchReason[]
    causer: {
        id: number
        first_name: string | null
        last_name: string | null
        full_name: string | null
    } | null
}

type CraftLite = { id: number; name: string; abbreviation?: string | null }

type PersonType = 'user' | 'freelancer' | 'service_provider'
type PersonRef = { id: number; type: PersonType; name: string }
type PersonScope = 'subject' | 'assigned' | 'causer'
type MatchReason = 'subject' | 'assigned' | 'causer'

type ShiftLite = {
    id: number
    craft_id: number
    start_date: string | null
    end_date: string | null
    start: string | null
    end: string | null
    description: string | null
    is_committed: boolean
    in_workflow: boolean
    room?: { id: number; name: string | null } | null
    project?: { id: number; name: string | null } | null
    craft?: CraftLite | null
    deleted_at?: string | null
}

const props = defineProps<{
    crafts: CraftLite[]
    initialCraftId?: number | null
    initialStartDate?: string | null
    initialEndDate?: string | null
    /** Nur noch Fallback (Freitext), wenn keine Person übergeben wird */
    prefillSearch?: string | null
    /** Personenfilter vorbelegen (Einsatzplan, Tagesmodal) */
    initialPerson?: PersonRef | null
    initialShiftId?: number | null
    autoLoad?: boolean
}>()

const emit = defineEmits<{ (e: 'close'): void }>()
const handleClose = () => emit('close')

const {
    t,
    fieldLabel,
    formatFieldValue,
    formatDateTime,
    activityContext,
    extractActivityChanges,
    activityTranslation,
    formatDate
} = useShiftPlanRequest()

const toYmd = (d: Date) => new Intl.DateTimeFormat('sv-SE').format(d)
const defaultStartOfMonth = () => {
    const n = new Date()
    return toYmd(new Date(n.getFullYear(), n.getMonth(), 1))
}
const defaultEndOfMonth = () => {
    const n = new Date()
    return toYmd(new Date(n.getFullYear(), n.getMonth() + 1, 0))
}

const allCraftsOption: CraftLite = { id: 0, name: t('All crafts') }
const craftsWithAll = computed(() => [allCraftsOption, ...props.crafts])

const selectedCraft = ref<CraftLite | null>(
    props.initialCraftId
        ? (props.crafts.find(c => c.id === props.initialCraftId) ?? allCraftsOption)
        : allCraftsOption
)
const craftId = computed(() => selectedCraft.value?.id ?? 0)

const startDate = ref<string>(props.initialStartDate ?? defaultStartOfMonth())
const endDate = ref<string>(props.initialEndDate ?? defaultEndOfMonth())

const loading = ref(false)
const error = ref<string | null>(null)
const hasLoaded = ref(false)

const shifts = ref<ShiftLite[]>([])
const rawLogs = ref<RawShiftActivity[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 50, total: 0 })

const loadBtnIcon = computed(() => loading.value ? 'IconLoader2' : 'IconRefresh')
// Freitext-Vorbelegung nur, wenn der Aufrufer keine Person kennt (Altweg)
const initialSearch = props.initialPerson ? '' : (props.prefillSearch ?? '').trim()

// Suche im Ergebnis: rein clientseitig. Serverweite Suche nur ausdrücklich (Enter/Link) → serverSearch.
const search = ref(initialSearch)
const serverSearch = ref('')
const selectedShift = ref<ShiftLite | null>(null)

// Personenfilter + Reichweite (Default: Person + ihre Schichten)
const selectedPerson = ref<PersonRef | null>(props.initialPerson ?? null)
const personScope = ref<PersonScope>('assigned')

const PERSON_SCOPE_META: Record<PersonScope, { label: string; short: string; hint: string }> = {
    subject: {
        label: 'Only actions on the person',
        short: 'only actions on the person',
        hint: 'Shows only assignments, removals and confirmations of this person.',
    },
    assigned: {
        label: 'Person + their shifts',
        short: 'incl. their shifts',
        hint: 'Shows actions on this person plus every entry of the shifts the person is or was scheduled in (e.g. when a shift was created, changed or committed).',
    },
    causer: {
        label: 'Also carried out by the person',
        short: 'incl. carried out',
        hint: 'Additionally shows every entry this person carried out as a planner.',
    },
}
const personScopeOptions = computed(() =>
    (Object.keys(PERSON_SCOPE_META) as PersonScope[]).map((id) => ({ id, label: t(PERSON_SCOPE_META[id].label) }))
)
const personScopeHint = computed(() => t(PERSON_SCOPE_META[personScope.value].hint))
const personScopeShortLabel = computed(() => t(PERSON_SCOPE_META[personScope.value].short))

// Treffer der Personensuche (worker.scoutSearch) → PersonRef. manager_type trägt die Model-Klasse.
const onPersonSelected = (worker: any) => {
    const cls = String(worker?.manager_type ?? '')
    const type: PersonType = cls.includes('ServiceProvider') || worker?.provider_name
        ? 'service_provider'
        : (cls.includes('Freelancer') ? 'freelancer' : 'user')
    const name = worker?.provider_name
        || worker?.name
        || [worker?.first_name, worker?.last_name].filter(Boolean).join(' ')
        || `#${worker?.id}`
    selectedPerson.value = { id: Number(worker.id), type, name }
}
const clearPerson = () => {
    if (loading.value) return
    selectedPerson.value = null
}

const canRunServerSearch = computed(
    () => hasLoaded.value && search.value.trim() !== '' && search.value.trim() !== serverSearch.value
)
const runServerSearch = () => {
    const term = search.value.trim()
    if (!term || loading.value) return
    serverSearch.value = term
    fetchHistory(true)
}
const clearServerSearch = () => {
    if (loading.value) return
    serverSearch.value = ''
    search.value = ''
    if (hasLoaded.value) fetchHistory(true)
}
const clientSearchActive = computed(() => search.value.trim() !== '' && search.value.trim() !== serverSearch.value)

const REASON_META: Record<MatchReason, { label: string; icon: any; class: string }> = {
    subject: { label: 'Concerns {name}', icon: IconUser, class: 'border-accent-200 bg-accent-50 text-accent-700' },
    assigned: { label: '{name} is scheduled in this shift', icon: IconUsers, class: 'border-border-subtle bg-surface-sunken text-text-muted' },
    causer: { label: 'Carried out by {name}', icon: IconUserEdit, class: 'border-border-subtle bg-white text-text-subtle' },
}

const groupByShiftDay = ref(false)
const onlyPostCommit = ref(false)

const groupOptions = computed(() => [
    { id: 'change', byShiftDay: false, label: t('Change date'), hint: t('Grouped by change date, newest first.') },
    { id: 'shift', byShiftDay: true, label: t('Shift day'), hint: t('Grouped by shift day, newest first.') },
])

// Gruppen-Header sagt, WAS das Datum ist: "Änderungen am 13.09.2026" bzw. "Schichttag Mi, 17.06.2026"
const uiLocale = (() => {
    const lang = (usePage().props as any)?.auth?.user?.language || document.documentElement.lang || 'de'
    return String(lang).startsWith('en') ? 'en-GB' : 'de-DE'
})()
const weekdayShort = (isoDay: string) => {
    if (!isoDay) return ''
    const d = new Date(`${isoDay}T00:00:00`)
    return Number.isNaN(d.getTime()) ? '' : d.toLocaleDateString(uiLocale, { weekday: 'short' }).replace(/\.$/, '')
}
const groupHeaderLabel = (isoDay: string) => {
    const date = formatDate(isoDay)
    if (!groupByShiftDay.value) return t('Changes on {date}', { date })
    const weekday = weekdayShort(isoDay)
    return t('Shift day {date}', { date: weekday ? `${weekday}, ${date}` : date })
}

// Zeitpunkt eines Vorgangs in der Zeitleiste: bei Änderungsdatum-Gruppierung reicht die Uhrzeit,
// bei Schichttag-Gruppierung liegen die Vorgänge einer Schicht an verschiedenen Tagen → Datum + Uhrzeit
const entryTimeLabel = (entry: NormalizedLogEntry) => {
    if (groupByShiftDay.value) return entry.createdAtFormatted
    const m = entry.createdAtFormatted.match(/(\d{1,2}:\d{2})\s*$/)
    return m ? m[1] : entry.createdAtFormatted
}

// Aufklappbare Vorher/Nachher-Tabellen (Standard: zu)
const expandedIds = ref<Set<number>>(new Set())
const isExpanded = (id: number) => expandedIds.value.has(id)
const toggleExpanded = (id: number) => {
    const next = new Set(expandedIds.value)
    if (next.has(id)) next.delete(id)
    else next.add(id)
    expandedIds.value = next
}

// Klick auf den Schicht-Kopf → "nur diese Schicht" (Eingrenzung)
const focusShift = (shift: ShiftLite) => {
    if (loading.value) return
    selectedShift.value = shift
}

// Leerzustand: Zeitraum auf ganze Monate erweitern (Datums-Watcher lädt neu)
const monthBounds = (ymd: string) => {
    const [y, m] = ymd.split('-').map(Number)
    if (!y || !m) return null
    return { first: toYmd(new Date(y, m - 1, 1)), last: toYmd(new Date(y, m, 0)) }
}
const rangeCoversFullMonths = computed(() => {
    const a = monthBounds(startDate.value)
    const b = monthBounds(endDate.value)
    return !!a && !!b && a.first === startDate.value && b.last === endDate.value
})
const expandRangeToMonths = () => {
    const a = monthBounds(startDate.value)
    const b = monthBounds(endDate.value)
    if (!a || !b || loading.value) return
    startDate.value = a.first
    endDate.value = b.last
}

type ActionCategory = 'staffing' | 'shift_data' | 'lifecycle' | 'commitment' | 'confirmation' | 'other'

const ACTION_META: Record<ActionCategory, { label: string; description: string; chip: string }> = {
    staffing: { label: 'Staffing', description: 'Person assigned to or removed from a shift.', chip: 'border-accent-200 bg-accent-50 text-accent-700' },
    shift_data: { label: 'Shift details', description: 'Times, room, qualifications or similar changed.', chip: 'border-warning-border bg-warning-surface text-warning' },
    lifecycle: { label: 'Shift created/deleted', description: 'Shift created, deleted or restored.', chip: 'border-border-strong bg-surface-sunken text-text' },
    commitment: { label: 'Commitment & request', description: 'Shifts committed, commitment revoked or requested for approval.', chip: 'border-success-border bg-success-surface text-success' },
    confirmation: { label: 'Confirmations', description: 'Shift assignments accepted or declined by the scheduled person.', chip: 'border-special-teal-border bg-special-teal-surface text-special-teal' },
    other: { label: 'Other', description: 'Rare entries that do not fit any other category, e.g. older log entries.', chip: 'border-border-subtle bg-surface-sunken text-text-muted' },
}

const categoryLabel = (category: ActionCategory) => t(ACTION_META[category].label)
const categoryChipClass = (category: ActionCategory) => ACTION_META[category].chip

const selectedAction = ref<{ id: string; name: string } | null>({ id: 'all', name: 'All' })

const actionItems = computed(() => {
    const items = [
        { id: 'all', name: 'All' },
        { id: 'staffing', name: ACTION_META.staffing.label },
        { id: 'shift_data', name: ACTION_META.shift_data.label },
        { id: 'lifecycle', name: ACTION_META.lifecycle.label },
        { id: 'commitment', name: ACTION_META.commitment.label },
    ]
    // Rubrik nur anbieten, wenn Zu-/Absagen im Verlauf sichtbar sind
    // (Backend filtert sie je nach Setting bereits heraus).
    if (normalizedLogs.value.some((e) => e.category === 'confirmation')) {
        items.push({ id: 'confirmation', name: ACTION_META.confirmation.label })
    }
    if (normalizedLogs.value.some((e) => e.category === 'other')) {
        items.push({ id: 'other', name: ACTION_META.other.label })
    }
    return items
})

const actionFilterTooltip = computed(() => {
    const items = (Object.keys(ACTION_META) as ActionCategory[])
        .map((c) => `<li><span class="font-semibold">${categoryLabel(c)}:</span> ${t(ACTION_META[c].description)}</li>`)
        .join('')
    return `${t('Which kind of change does an entry show?')}<ul>${items}</ul>`
})

// Lesbares Schicht-Label: "Di 16.06.2026 · 18:00–23:00 · CS · Waldbühne · Sommerfestival (· gelöscht)"
const shiftLabel = (shift: ShiftLite) => {
    const d1 = normalizeDate(shift.start_date)
    const d2 = normalizeDate(shift.end_date)
    const weekday = weekdayShort(toIsoDay(d1))
    const datePart = (weekday ? weekday + ' ' : '') + (d1 === d2 || !d2 ? d1 : `${d1}–${d2}`)
    const time = [shift.start, shift.end].filter(Boolean).join('–')
    const craft = shift.craft?.abbreviation || shift.craft?.name || ''
    const parts = [datePart, time, craft, shift.room?.name ?? '', shift.project?.name ?? ''].filter(Boolean)
    if (shift.deleted_at) parts.push(t('deleted'))
    return parts.join(' · ')
}

const shiftsById = computed<Record<string, ShiftLite>>(() => {
    const map: Record<string, ShiftLite> = {}
    for (const s of shifts.value) map[String(s.id)] = s
    return map
})

const shiftLabelById = (id: number) => {
    const s = shiftsById.value[String(id)]
    return s ? `#${id} · ${shiftLabel(s)}` : `#${id}`
}

// Monats-Abkürzungen (DE/EN) → für Alt-Einträge, deren properties.old das Datum als
// "21. Sep 2026" / "30. Jun 2026" speichert (Backend ->format('d. M Y')).
const MONTH_ABBR: Record<string, string> = {
    jan: '01', feb: '02', mar: '03', 'mär': '03', maerz: '03', apr: '04',
    may: '05', mai: '05', jun: '06', jul: '07', aug: '08', sep: '09',
    oct: '10', okt: '10', nov: '11', dec: '12', dez: '12',
}

// Wandelt verschiedene Datumsdarstellungen nach DD.MM.YYYY. ISO/DD.MM.YYYY laufen über
// formatDate; das gespeicherte "21. Sep 2026" wird per Monats-Map konvertiert.
const normalizeDate = (value?: string | null): string => {
    if (!value) return ''
    const m = String(value).match(/^(\d{1,2})\.\s*([^\s.]+)\.?\s*(\d{4})$/)
    if (m) {
        const mon = MONTH_ABBR[m[2].toLowerCase()]
        if (mon) return `${m[1].padStart(2, '0')}.${mon}.${m[3]}`
    }
    return formatDate(value)
}

// Liefert einen sortierbaren ISO-Tagesschlüssel (YYYY-MM-DD) aus ISO, DD.MM.YYYY oder
// "21. Sep 2026". Wird für die Gruppierung nach Schichttag benötigt.
const toIsoDay = (value?: string | null): string => {
    if (!value) return ''
    const s = String(value)
    let m = s.match(/^(\d{4})-(\d{2})-(\d{2})/)
    if (m) return `${m[1]}-${m[2]}-${m[3]}`
    m = s.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})/)
    if (m) return `${m[3]}-${m[2].padStart(2, '0')}-${m[1].padStart(2, '0')}`
    m = s.match(/^(\d{1,2})\.\s*([^\s.]+)\.?\s*(\d{4})$/)
    if (m) {
        const mon = MONTH_ABBR[m[2].toLowerCase()]
        if (mon) return `${m[3]}-${mon}-${m[1].padStart(2, '0')}`
    }
    return ''
}

// Strukturierte Schichtdaten für die "Schicht-Card" pro Verlaufseintrag.
// Priorität: Snapshot (Stand zum Zeitpunkt des Eintrags) → aktuelle Live-Schicht →
// properties.old des Log-Eintrags (enthält bei Alt-Einträgen die Schichtdaten zum
// Zeitpunkt, z.B. bei Lösch-/Update-Einträgen). So zeigen auch alte Einträge zu
// (force-)gelöschten Schichten echte Daten statt nur "–".
const buildShiftDetails = (log: RawShiftActivity): EntryShiftDetails => {
    // Sammel-Einträge (Festschreibung KW/Zeitraum) haben keine einzelne Schicht —
    // Zeitraum/Gewerke kommen aus properties.commit_summary, die Karte wird im
    // Template separat gerendert.
    const summary = (log.properties as any)?.commit_summary ?? null
    if (summary) {
        const sd = summary.start_date || ''
        const ed = summary.end_date || ''
        return {
            id: null,
            dayKey: toIsoDay(sd),
            dateLabel: sd ? (ed && ed !== sd ? `${normalizeDate(sd)} – ${normalizeDate(ed)}` : normalizeDate(sd)) : '–',
            timeLabel: '–',
            craft: (summary.crafts || []).join(', ') || '–',
            room: '–',
            project: '–',
            deleted: false,
        }
    }

    const snap = (log.properties?.shift_snapshot as ShiftSnapshot | undefined) ?? null
    const id = (
        (log.properties?.shift_id as number | null | undefined) ??
        (log.subject_id as number | null | undefined) ??
        snap?.id ??
        null
    )
    const live = id != null ? shiftsById.value[String(id)] as any : null
    const old = (log.properties as any)?.old ?? null

    const sd = snap?.start_date || live?.start_date || old?.start_date || ''
    const ed = snap?.end_date || live?.end_date || old?.end_date || ''
    const dateLabel = sd
        ? (ed && ed !== sd ? `${normalizeDate(sd)} – ${normalizeDate(ed)}` : normalizeDate(sd))
        : '–'

    const start = snap?.start || live?.start || old?.start || ''
    const end = snap?.end || live?.end || old?.end || ''
    const timeLabel = (start || end) ? [start, end].filter(Boolean).join(' – ') : '–'

    const craft = snap?.craft || live?.craft?.name || live?.craft?.abbreviation || old?.['craft.name'] || '–'
    const room = snap?.room || live?.room?.name || old?.['room.name'] || '–'
    const project = snap?.project || live?.project?.name || old?.['project.name'] || '–'

    // "Nicht mehr existent": Live-Schicht ist (soft-)gelöscht ODER es gibt gar keine
    // Live-Row mehr (force-deleted, nur über Snapshot/old rekonstruiert). Wiederhergestellte
    // Schichten haben eine Live-Row ohne deleted_at → werden NICHT markiert.
    const deleted = id != null && (!live || !!live?.deleted_at)

    const dayKey = toIsoDay(snap?.start_date || live?.start_date || old?.start_date)

    return {
        id: id ?? null,
        dayKey,
        dateLabel,
        timeLabel,
        craft: craft || '–',
        room: room || '–',
        project: project || '–',
        deleted,
    }
}

// "Zurücksetzen" im Filterblock: nur die Eingrenzung (kein Reload, außer eine serverweite Suche war aktiv)
const resetFilters = () => {
    if (loading.value) return

    const hadServerSearch = serverSearch.value !== ''
    search.value = ''
    serverSearch.value = ''
    selectedAction.value = { id: 'all', name: 'All' }
    const hadServerShift = !!loadedQuery.value?.shiftId
    selectedShift.value = null
    onlyPostCommit.value = false

    if (hasLoaded.value && (hadServerSearch || hadServerShift)) fetchHistory(true)
}

// "Alles zurücksetzen" in der Zusammenfassung: Eingrenzung + Personenfilter
const resetAll = () => {
    if (loading.value) return
    const reload = hasLoaded.value
        && (serverSearch.value !== '' || selectedPerson.value !== null || !!loadedQuery.value?.shiftId)
    search.value = ''
    serverSearch.value = ''
    selectedAction.value = { id: 'all', name: 'All' }
    selectedShift.value = null
    onlyPostCommit.value = false
    selectedPerson.value = null
    personScope.value = 'assigned'
    if (reload) fetchHistory(true)
}

const anyFilterActive = computed(() =>
    selectedPerson.value !== null
    || serverSearch.value !== ''
    || search.value.trim() !== ''
    || (selectedAction.value?.id ?? 'all') !== 'all'
    || selectedShift.value !== null
    || onlyPostCommit.value
)

let pendingShiftId: number | null = props.initialShiftId ?? null

// Schichtfilter ist Eingrenzung (clientseitig). Nur wenn noch nicht alle Seiten geladen sind, wird
// beim Auswählen gezielt mit shiftId nachgeladen, damit der Verlauf der Schicht vollständig ist.
const shiftFilterOnServer = ref(false)

type HistoryQuery = {
    craftId: number
    shiftId?: number
    start_date: string
    end_date: string
    per_page: number
    search?: string
    sort?: 'shift_day'
    person_type?: PersonType
    person_id?: number
    person_scope?: PersonScope
}

const loadedQuery = ref<HistoryQuery | null>(null)
const currentQuery = computed<HistoryQuery>(() => {
    const loaded = loadedQuery.value
    const selectionScopeChanged = loaded && (
        loaded.craftId !== craftId.value
        || loaded.start_date !== startDate.value
        || loaded.end_date !== endDate.value
    )

    return {
        craftId: craftId.value,
        shiftId: selectionScopeChanged
            ? undefined
            : ((shiftFilterOnServer.value ? selectedShift.value?.id : undefined) ?? pendingShiftId ?? undefined),
        start_date: startDate.value,
        end_date: endDate.value,
        per_page: meta.value.per_page,
        search: serverSearch.value || undefined,
        sort: groupByShiftDay.value ? 'shift_day' : undefined,
        person_type: selectedPerson.value?.type,
        person_id: selectedPerson.value?.id,
        person_scope: selectedPerson.value ? personScope.value : undefined,
    }
})
const querySignature = (query: HistoryQuery | null) => JSON.stringify(query)

// Export-Link mit der aktuellen Auswahl (gleiche Parameter wie der Verlaufs-Request, ohne Paginierung)
const exportUrl = computed(() => {
    const query = currentQuery.value
    const params: Record<string, string | number> = {
        craftId: query.craftId,
        start_date: query.start_date,
        end_date: query.end_date,
    }
    if (query.shiftId) params.shiftId = query.shiftId
    if (query.search) params.search = query.search
    if (query.sort) params.sort = query.sort
    if (query.person_type && query.person_id) {
        params.person_type = query.person_type
        params.person_id = query.person_id
        params.person_scope = query.person_scope ?? 'assigned'
    }
    return route('shift-history.export', params)
})
const paramsDirty = computed(
    () => hasLoaded.value && querySignature(currentQuery.value) !== querySignature(loadedQuery.value)
)
const canLoadMore = computed(
    () => hasLoaded.value && !paramsDirty.value && meta.value.current_page < meta.value.last_page
)

const fetchHistory = async (reset: boolean) => {
    if (loading.value) return

    const query = reset ? currentQuery.value : loadedQuery.value
    if (!query) return

    loading.value = true
    error.value = null

    try {
        const nextPage = reset ? 1 : meta.value.current_page + 1

        const res = await axios.get(route('shift.history.index'), {
            params: {
                ...query,
                page: nextPage,
            },
        })

        const payload = res.data
        if (reset) {
            loadedQuery.value = { ...query }
            shifts.value = payload.shifts ?? []
            const selectedShiftId = query.shiftId ?? selectedShift.value?.id
            selectedShift.value = shifts.value.find((shift) => shift.id === selectedShiftId) ?? null
            shiftFilterOnServer.value = !!query.shiftId && selectedShift.value !== null
            pendingShiftId = null
        }

        const newLogs: RawShiftActivity[] = payload.logs?.data ?? []
        meta.value = payload.logs?.meta ?? { current_page: 1, last_page: 1, per_page: 50, total: 0 }

        rawLogs.value = reset ? newLogs : [...rawLogs.value, ...newLogs]
        hasLoaded.value = true
    } catch (e: any) {
        error.value = e?.response?.data?.message || e?.message || t('Failed to load history.')
    } finally {
        loading.value = false
    }
}

watch(selectedShift, (shift) => {
    if (!hasLoaded.value || loading.value) return
    const loadedShiftId = loadedQuery.value?.shiftId
    if (shift) {
        // fetchHistory setzt selectedShift nach jedem Reload neu (neues Objekt) → nur echte Wechsel behandeln
        if (shift.id === loadedShiftId) return
        // Alles geladen → reine Eingrenzung; sonst gezielt nachladen (vollständiger Verlauf der Schicht)
        const allLoaded = meta.value.current_page >= meta.value.last_page
        if (!allLoaded || loadedShiftId) {
            shiftFilterOnServer.value = true
            fetchHistory(true)
        }
    } else if (loadedShiftId) {
        shiftFilterOnServer.value = false
        fetchHistory(true)
    }
})

watch(groupByShiftDay, () => {
    if (hasLoaded.value) fetchHistory(true)
})

// Datenauswahl: Gewerk sofort, Datum leicht verzögert (Tippen im Datumsfeld) – nur mit gültigem Zeitraum
watch(craftId, () => {
    if (hasLoaded.value) fetchHistory(true)
})
let dateReloadTimer: ReturnType<typeof setTimeout> | null = null
watch([startDate, endDate], ([from, to]) => {
    if (dateReloadTimer) clearTimeout(dateReloadTimer)
    const valid = /^\d{4}-\d{2}-\d{2}$/.test(from) && /^\d{4}-\d{2}-\d{2}$/.test(to) && from <= to
    if (!hasLoaded.value || !valid) return
    dateReloadTimer = setTimeout(() => {
        if (paramsDirty.value && !loading.value) fetchHistory(true)
    }, 600)
})

// Person auswählen/entfernen ist eine diskrete Eingabe → lädt direkt (auch vor dem ersten "Verlauf laden");
// Reichweite wechseln lädt nur, wenn schon etwas geladen ist.
watch(selectedPerson, () => {
    fetchHistory(true)
})
watch(personScope, () => {
    if (hasLoaded.value && selectedPerson.value) fetchHistory(true)
})

onMounted(() => {
    if (props.autoLoad) fetchHistory(true)
})

type NormalizedChange = { index: number; fieldName: string; oldValue: any; newValue: any }
type EntryShiftDetails = {
    id: number | null
    dayKey: string
    dateLabel: string
    timeLabel: string
    craft: string
    room: string
    project: string
    deleted: boolean
}
type ReasonChip = { key: string; label: string; icon: any; class: string }
type StaffingInfo = { name: string; qualification: string | null }
type CreatedDetail = { label: string; value: string }
type NormalizedLogEntry = {
    id: number
    message: string
    staffing: StaffingInfo | null
    createdDetails: CreatedDetail[]
    reasons: MatchReason[]
    reasonChips: ReasonChip[]
    searchHits: string[]
    createdAt: string
    createdAtFormatted: string
    context: string | null
    contextLabel: string | null
    causerName: string | null
    causerInitials: string | null
    category: ActionCategory
    changes: NormalizedChange[]
    shiftId: number | null
    shiftIds: number[]
    snapshot: ShiftSnapshot | null
    shiftDetails: EntryShiftDetails
    commitSummary: CommitSummary | null
    haystack: string
}

// Sammel-Eintrag einer Festschreibungs-Aktion (aus properties.commit_summary).
type CommitSummary = {
    committed?: boolean
    start_date?: string | null
    end_date?: string | null
    week?: number | null
    year?: number | null
    crafts?: string[]
    count?: number | null
}

// Zustand der Schicht zum Zeitpunkt des Log-Eintrags (aus properties.shift_snapshot).
type ShiftSnapshot = {
    id?: number | null
    start_date?: string | null
    end_date?: string | null
    start?: string | null
    end?: string | null
    craft_id?: number | null
    craft?: string | null
    room?: string | null
    project?: string | null
}

const getCauserName = (log: RawShiftActivity) => {
    const causer = log.causer
    if (!causer) return { name: t('System'), initials: 'S' }

    const name =
        causer.full_name ||
        [causer.first_name, causer.last_name].filter(Boolean).join(' ') ||
        null

    if (!name) return { name: t('Unknown user'), initials: '?' }

    const initials = name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(p => p.charAt(0))
        .join('')
        .toUpperCase()

    return { name, initials: initials || name.charAt(0).toUpperCase() }
}

// Ordnet einen Log-Eintrag einer Aktions-Kategorie zu. Signale in absteigender
// Spezifität: Festschreibungs-/Anfrage-Aktionen → Besetzung → Schicht angelegt/
// gelöscht → Schichtdaten. Alles Unerkannte landet in "Sonstiges".
const detectCategory = (log: RawShiftActivity): ActionCategory => {
    const desc = (log.description || '').toLowerCase()
    const ev = log.event || ''
    const key = String(log.properties?.translation_key || '').toLowerCase()
    const ctx = log.properties?.context || ''

    if (ev === 'confirmation_accepted' || ev === 'confirmation_declined') return 'confirmation'

    if ((log.properties as any)?.commit_summary) return 'commitment'
    if (ctx === 'commit') return 'commitment'
    if (['committed', 'uncommitted', 'committed_bulk', 'uncommitted_bulk', 'shift_committed'].includes(ev)) return 'commitment'
    if (ev === 'shift_added_to_request' || ev === 'workflow_withdrawn' || key.includes('request')) return 'commitment'

    if (ev === 'assigned' || ev === 'removed' || desc.includes('assigned') || desc.includes('removed')
        || key.includes('assigned to shift') || key.includes('removed from shift')) return 'staffing'

    if (['created', 'deleted', 'deleted_with_reason', 'restored'].includes(ev)
        || desc.includes('deleted') || desc.includes('restored') || key.includes('shift was deleted')) return 'lifecycle'

    if (ev.includes('updated') || desc.includes('updated') || desc.includes('reverted')
        || key.includes('updated') || key.includes('reverted') || key.includes('changed')) return 'shift_data'

    return 'other'
}

const messageForLog = (log: RawShiftActivity) => {
    const msgFromKey = activityTranslation(log)
    if (msgFromKey) return msgFromKey
    // Lösch-Einträge klar benennen ("Schicht gelöscht") statt nur "gelöscht" – gilt auch
    // für Alt-Einträge ohne translation_key. Die betroffene Schicht steht im Kontext-Chip.
    if (log.event === 'deleted') return t('Shift was deleted')
    // "erstellt" allein ist zu dünn – klar benennen ("Schicht angelegt")
    if (log.event === 'created' || log.description === 'created') return t('Shift created')
    if (log.event === 'updated' || log.description === 'updated') return t('Shift data changed')
    // "restored" allein ist unklar – klar benennen ("Schicht wiederhergestellt").
    if (log.event === 'restored' || log.description === 'restored') return t('Shift was restored')
    if (log.description) return t(log.description)
    if (log.event) return t(log.event)
    return t('Change in shift')
}

// Besetzungs-Einträge: Platzhalter = [Name, Funktion, Gewerk, Kürzel] → Chip "Name · Funktion"
const staffingFor = (log: RawShiftActivity, category: ActionCategory): StaffingInfo | null => {
    if (category !== 'staffing') return null
    const values = Array.isArray(log.properties?.translation_key_placeholder_values)
        ? log.properties!.translation_key_placeholder_values!.map((v) => String(v ?? '').trim())
        : []
    if (!values[0]) return null
    return { name: values[0], qualification: values[1] || null }
}

// Anlage-Einträge tragen nur attributes (kein old) → Felder zeigen, die der Schicht-Kopf nicht abdeckt
const CREATED_DETAIL_FIELDS = ['break_minutes', 'description', 'shiftGroup.name']
const createdDetailsFor = (log: RawShiftActivity): CreatedDetail[] => {
    if (log.event !== 'created' && log.description !== 'created') return []
    const attrs = (log.properties as any)?.attributes
    if (!attrs || typeof attrs !== 'object' || (log.properties as any)?.old) return []
    return CREATED_DETAIL_FIELDS
        .filter((f) => attrs[f] !== null && attrs[f] !== undefined && attrs[f] !== '' && attrs[f] !== 0)
        .map((f) => ({ label: fieldLabel(f), value: formatFieldValue(f, attrs[f]) }))
}

const normalizeChanges = (log: RawShiftActivity): NormalizedChange[] => {
    const changes = extractActivityChanges(log) || []
    return changes.map((fc: any, index: number) => ({
        index,
        fieldName: fc.fieldName,
        oldValue: fc.old_label ?? fc.old ?? null,
        newValue: fc.new_label ?? fc.new ?? null,
    }))
}

const normalizedLogs = computed<NormalizedLogEntry[]>(() => {
    return [...rawLogs.value]
        .sort((a, b) => b.created_at.localeCompare(a.created_at) || b.id - a.id)
        .map((log) => {
            const category = detectCategory(log)
            const { name: causerName, initials: causerInitials } = getCauserName(log)

            const context = log.properties?.context || null
            const contextLabel = activityContext(log) || null

            const createdAt = log.created_at
            const createdAtFormatted = formatDateTime(createdAt)

            const shiftId =
                (log.properties?.shift_id as number | null | undefined) ??
                (log.subject_id as number | null | undefined) ??
                null
            const shiftIds = (log.properties?.shift_ids ?? [])
                .map(Number)
                .filter(Number.isFinite)

            const message = messageForLog(log)

            // Such-Haystack enthält neben der gerenderten Nachricht auch die rohen
            // Platzhalterwerte (z.B. zugewiesene Mitarbeiternamen) und die Beschreibung,
            // damit die clientseitige Suche keinen serverseitigen Treffer ausblendet.
            const placeholderValues = Array.isArray(log.properties?.translation_key_placeholder_values)
                ? log.properties!.translation_key_placeholder_values!.map((v) => String(v ?? '')).join(' ')
                : ''
            const commitSummary = ((log.properties as any)?.commit_summary as CommitSummary | undefined) ?? null

            const haystack = [
                message,
                log.description ?? '',
                placeholderValues,
                causerName ?? '',
                contextLabel ?? '',
                shiftId ? shiftLabelById(shiftId) : '',
                shiftIds.map(shiftLabelById).join(' '),
                commitSummary?.crafts?.join(' ') ?? '',
            ].join(' ').toLowerCase()

            const reasons = (log.match_reasons ?? []).filter((r): r is MatchReason => r in REASON_META)
            const personName = selectedPerson.value?.name ?? ''
            const reasonChips: ReasonChip[] = selectedPerson.value
                ? reasons.map((r) => ({
                    key: r,
                    label: t(REASON_META[r].label, { name: personName }),
                    icon: REASON_META[r].icon,
                    class: REASON_META[r].class,
                }))
                : []

            return {
                id: log.id,
                message,
                staffing: staffingFor(log, category),
                createdDetails: createdDetailsFor(log),
                reasons,
                reasonChips,
                searchHits: [],
                createdAt,
                createdAtFormatted,
                context,
                contextLabel,
                causerName,
                causerInitials,
                category,
                changes: normalizeChanges(log),
                shiftId,
                shiftIds,
                snapshot: (log.properties?.shift_snapshot as ShiftSnapshot | undefined) ?? null,
                shiftDetails: buildShiftDetails(log),
                commitSummary,
                haystack,
            }
        })
})

// Wo trifft die (clientseitige) Suche? → Chip "Treffer in: …" am Eintrag
const searchHitsFor = (e: NormalizedLogEntry, q: string): string[] => {
    if (!q) return []
    const has = (v: unknown) => String(v ?? '').toLowerCase().includes(q)
    const hits: string[] = []
    if (has(e.message)) hits.push(t('Message'))
    if (has(e.causerName)) hits.push(t('Changed by'))
    if (has(e.shiftDetails.room)) hits.push(t('Room'))
    if (has(e.shiftDetails.project)) hits.push(t('Project'))
    if (has(e.shiftDetails.craft)) hits.push(t('Craft'))
    if (e.changes.some((c) => has(formatFieldValue(c.fieldName, c.oldValue)) || has(formatFieldValue(c.fieldName, c.newValue)))) {
        hits.push(t('Modification'))
    }
    if (!hits.length && has(e.haystack)) hits.push(t('Details'))
    return hits
}

// Suchtreffer im Text markieren (case-insensitiv, alle Vorkommen)
const highlight = (text: string): Array<{ text: string; hit: boolean }> => {
    const q = search.value.trim().toLowerCase()
    const value = String(text ?? '')
    if (!q || !value) return [{ text: value, hit: false }]
    const lower = value.toLowerCase()
    const segments: Array<{ text: string; hit: boolean }> = []
    let pos = 0
    let idx = lower.indexOf(q, pos)
    while (idx !== -1) {
        if (idx > pos) segments.push({ text: value.slice(pos, idx), hit: false })
        segments.push({ text: value.slice(idx, idx + q.length), hit: true })
        pos = idx + q.length
        idx = lower.indexOf(q, pos)
    }
    if (pos < value.length) segments.push({ text: value.slice(pos), hit: false })
    return segments
}

const filteredLogs = computed(() => {
    const q = search.value.trim().toLowerCase()
    const action = selectedAction.value?.id ?? 'all'
    const shiftId = selectedShift.value?.id ?? null

    return normalizedLogs.value
        .filter((e) => {
            if (action !== 'all' && e.category !== action) return false
            if (onlyPostCommit.value && e.context !== 'post_commit') return false
            if (shiftId && e.shiftId !== shiftId && !e.shiftIds.includes(shiftId)) return false

            if (q && !e.haystack.includes(q)) return false
            return true
        })
        .map((e) => (q ? { ...e, searchHits: searchHitsFor(e, q) } : e))
})

// Anzahl unterschiedlicher Schichten hinter den sichtbaren Einträgen (Sammel-Einträge zählen ihre shift_ids)
const visibleShiftCount = computed(() => {
    const ids = new Set<number>()
    for (const e of filteredLogs.value) {
        if (e.shiftId != null) ids.add(e.shiftId)
        for (const id of e.shiftIds) ids.add(id)
    }
    return ids.size
})

// Gruppierung: nach Änderungsdatum (created_at, Default) ODER nach Schichttag (Toggle).
// Innerhalb einer Gruppe bleibt die Reihenfolge "neueste Änderung zuerst" (filteredLogs
// erbt die created_at-DESC-Sortierung aus normalizedLogs). Gruppen absteigend nach Tag,
// "ohne Datum" ganz unten.
type EntryBlock = {
    key: string
    shift: ShiftLite | null
    details: EntryShiftDetails
    commitSummary: CommitSummary | null
    headline: string
    items: NormalizedLogEntry[]
}

// Kopfzeile eines Blocks: Schicht in EINER Zeile bzw. Zeitraum/KW/Gewerke/Anzahl beim Sammel-Eintrag
const blockHeadline = (entry: NormalizedLogEntry) => {
    const d = entry.shiftDetails
    if (entry.commitSummary) {
        const week = entry.commitSummary.week
            ? `${t('Calendar week')} ${entry.commitSummary.week}${entry.commitSummary.year ? '/' + entry.commitSummary.year : ''}`
            : ''
        const count = entry.commitSummary.count != null ? t('{count} shifts', { count: entry.commitSummary.count }) : ''
        return [d.dateLabel, week, d.craft, count].filter((p) => p && p !== '–').join(' · ')
    }
    const weekday = weekdayShort(d.dayKey)
    const date = weekday ? `${weekday} ${d.dateLabel}` : d.dateLabel
    return [date, d.timeLabel, d.craft, d.room, d.project].filter((p) => p && p !== '–').join(' · ')
}

// Gruppierung: Tage (nach Änderungsdatum oder Schichttag) → Blöcke. Bei Schichttag-Gruppierung
// bündelt ein Block ALLE Vorgänge derselben Schicht (Verlauf am Stück lesbar), sonst ist jeder
// Vorgang ein eigener Block. Reihenfolge: neueste Änderung zuerst, Tage absteigend, "ohne Datum" unten.
const groupedLogs = computed(() => {
    const byShiftDay = groupByShiftDay.value
    const groups: Record<string, NormalizedLogEntry[]> = {}

    for (const item of filteredLogs.value) {
        let key: string
        if (byShiftDay) {
            key = item.shiftDetails.dayKey || 'unknown'
        } else {
            const date = item.createdAt.slice(0, 10)
            key = date.length === 10 ? date : 'unknown'
        }
        groups[key] ??= []
        groups[key].push(item)
    }

    const toBlocks = (items: NormalizedLogEntry[]): EntryBlock[] => {
        const blocks: EntryBlock[] = []
        const index: Record<string, EntryBlock> = {}
        for (const entry of items) {
            const blockKey = byShiftDay && !entry.commitSummary && entry.shiftDetails.id != null
                ? `shift-${entry.shiftDetails.id}`
                : `entry-${entry.id}`
            let block = index[blockKey]
            if (!block) {
                const shift = entry.shiftDetails.id != null ? (shiftsById.value[String(entry.shiftDetails.id)] ?? null) : null
                block = {
                    key: blockKey,
                    shift: entry.commitSummary ? null : shift,
                    details: entry.shiftDetails,
                    commitSummary: entry.commitSummary,
                    headline: blockHeadline(entry),
                    items: [],
                }
                index[blockKey] = block
                blocks.push(block)
            }
            block.items.push(entry)
        }
        return blocks
    }

    const orderedKeys = Object.keys(groups).sort((a, b) => {
        if (a === 'unknown') return 1
        if (b === 'unknown') return -1
        return a > b ? -1 : 1
    })
    return orderedKeys.map((k) => ({
        dayKey: k,
        dayLabel: k === 'unknown' ? '' : k,
        unknown: k === 'unknown',
        blocks: toBlocks(groups[k]),
    }))
})
</script>
