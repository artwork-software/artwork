<template>
    <AppLayout :title="$t('BI Dashboard')">
        <div class="artwork-container-fluid space-y-6">
            <ToolbarHeader
                :icon="IconChartHistogram"
                :title="$t('BI Dashboard')"
                band
                :description="$t('Business intelligence overview')"
                :search-enabled="false"
            >
                <template #actions>
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="flex items-center gap-1.5 pb-2">
                            <button
                                v-for="preset in rangePresets"
                                :key="preset.key"
                                type="button"
                                class="rounded-full border px-3 py-1 text-xs font-medium transition cursor-pointer"
                                :class="activePreset === preset.key ? 'border-accent-500 bg-accent-500 text-white'
                                    : 'border-transparent bg-white/8 text-text-inverse hover:bg-white/16'"
                                @click="applyPreset(preset)"
                            >
                                {{ $t(preset.label) }}
                            </button>
                        </div>
                        <BaseInput type="date" id="bi_dash_from" v-model="dateFrom" :label="$t('From')" class="w-40 [&_label]:text-text-inverse-muted!" input-classes="bg-white/10! border-white/16! text-text-inverse! [color-scheme:dark]" />
                        <BaseInput type="date" id="bi_dash_to" v-model="dateTo" :label="$t('To')" class="w-40 [&_label]:text-text-inverse-muted!" input-classes="bg-white/10! border-white/16! text-text-inverse! [color-scheme:dark]" />
                        <BaseUIButton :label="$t('Apply')" @click="reload()" :disabled="loading" hide-icon on-band />
                        <BaseUIButton
                            v-if="canExportBiData"
                            :label="$t('Excel-Export')"
                            @click="openHeaderExport"
                            hide-icon
                            on-band
                        />
                        <BaseUIButton
                            v-if="canExportBiData"
                            :label="$t('Budget export')"
                            @click="showBudgetExportModal = true"
                            hide-icon
                            on-band
                        />
                        <!-- Vergleichszeitraum -->
                        <div class="w-full flex flex-wrap items-end gap-2 pt-1">
                            <span class="pb-2 text-xs text-text-inverse-muted">{{ $t('Comparison') }}:</span>
                            <div class="flex items-center gap-1.5 pb-1.5">
                                <button
                                    v-for="preset in comparePresets"
                                    :key="preset.key"
                                    type="button"
                                    class="rounded-full border px-3 py-1 text-xs font-medium transition cursor-pointer"
                                    :class="comparePreset === preset.key ? 'border-white/40 bg-white/25 text-text-inverse'
                                        : 'border-transparent bg-white/8 text-text-inverse hover:bg-white/16'"
                                    @click="applyComparePreset(preset.key)"
                                >
                                    {{ $t(preset.label) }}
                                </button>
                            </div>
                            <template v-if="comparePreset === 'free'">
                                <BaseInput type="date" id="bi_dash_cmp_from" v-model="compareFrom" :label="$t('From')" class="w-40 [&_label]:text-text-inverse-muted!" input-classes="bg-white/10! border-white/16! text-text-inverse! [color-scheme:dark]" />
                                <BaseInput type="date" id="bi_dash_cmp_to" v-model="compareTo" :label="$t('To')" class="w-40 [&_label]:text-text-inverse-muted!" input-classes="bg-white/10! border-white/16! text-text-inverse! [color-scheme:dark]" />
                                <BaseUIButton :label="$t('Apply')" @click="reload(true)" :disabled="loading" hide-icon on-band />
                            </template>
                            <span v-if="comparisonLabel" class="pb-2 text-xs text-text-inverse-muted">{{ comparisonLabel }}</span>
                        </div>
                    </div>
                </template>
            </ToolbarHeader>

            <BiBudgetExportModal
                v-if="showBudgetExportModal"
                :default-date-from="dateFrom"
                :default-date-to="dateTo"
                @close="showBudgetExportModal = false"
            />

            <BiExportDialog
                v-if="showExportModal && canExportBiData"
                mode="dashboard"
                :initial-columns="steeringExportPreset?.columns ?? null"
                :initial-project-ids="steeringExportPreset?.projectIds ?? null"
                :default-date-from="dateFrom"
                :default-date-to="dateTo"
                date-source="dashboard"
                @close="showExportModal = false"
            />

            <!-- Download-Link nach Ablauf der Datei (Redirect aus bi.export.download) -->
            <div v-if="exportExpired" class="rounded-2xl border border-warning-border bg-warning-surface px-4 py-3 text-sm text-warning flex items-center justify-between gap-4">
                <span>{{ $t('This export file has expired — files are kept for 24 hours. Please create the export again.') }}</span>
                <button type="button" class="shrink-0 font-medium hover:underline" @click="exportExpired = false">{{ $t('Close') }}</button>
            </div>

            <!-- Aktiver Zeitraum als Satz: welche Spanne gilt gerade, und womit wird verglichen -->
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-text-muted px-1">
                <span class="font-medium text-text">{{ $t('Period') }}:</span>
                <span>{{ periodSentence }}</span>
                <span class="text-text-subtle" aria-hidden="true">·</span>
                <span class="font-medium text-text">{{ $t('Comparison') }}:</span>
                <span>{{ comparisonSentence }}</span>
            </div>

            <!-- Kein Spielzeitfenster hinterlegt → "Spielzeit" heißt in Wahrheit "alles" -->
            <div v-if="seasonMissing" class="rounded-2xl border border-warning-border bg-warning-surface px-4 py-3 text-sm text-warning flex items-center justify-between gap-4">
                <span>{{ $t('No season window is configured, so all periods are evaluated. Set the season under “Communication & Legal” in the tool settings.') }}</span>
                <Link :href="route('tool.communication-and-legal')" class="shrink-0 font-medium text-warning hover:underline">
                    {{ $t('Set season window') }}
                </Link>
            </div>

            <!-- Reload fehlgeschlagen: Zahlen sind die alten -->
            <div v-if="reloadError" class="rounded-2xl border border-danger-border bg-danger-surface px-4 py-3 text-sm text-danger flex items-center justify-between gap-4">
                <span>{{ $t('The dashboard could not be reloaded — the figures shown are the previous ones.') }}</span>
                <button type="button" class="shrink-0 font-medium hover:underline" @click="reload(true)">{{ $t('Try again') }}</button>
            </div>

            <!-- Erfolg der Schnellerfassung -->
            <div v-if="savedNotice" class="rounded-2xl border border-success-border bg-success-surface px-4 py-2.5 text-sm text-success flex items-center gap-2">
                <IconCircleCheck class="size-4 shrink-0" />
                <span>{{ savedNotice }}</span>
            </div>

            <!-- Zwei Sichten: Überblick (Zahlen, Trend, Lücken) und Steuerung (Aufwand vs. Ertrag, Tabelle) -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex rounded-lg border border-border-subtle bg-white p-0.5 shadow-sm" role="tablist">
                    <button
                        v-for="tab in viewTabs"
                        :key="tab.key"
                        type="button"
                        role="tab"
                        :aria-selected="view === tab.key"
                        class="rounded-md px-3.5 py-1.5 text-sm font-medium transition"
                        :class="view === tab.key ? 'bg-accent-600 text-white' : 'text-text-muted hover:bg-surface-sunken'"
                        @click="setView(tab.key)"
                    >
                        {{ $t(tab.label) }}
                    </button>
                </div>

                <!-- Sparten-Filter: wirkt serverseitig auf Kacheln, Charts und Tabelle -->
                <div v-if="categories.length > 1" class="flex flex-wrap items-center gap-1.5">
                    <span class="text-xs text-text-subtle mr-1">{{ $t('Category (Sector)') }}:</span>
                    <button
                        type="button"
                        class="rounded-full border px-2.5 py-0.5 text-xs font-medium transition"
                        :class="!categoryFilter ? 'border-accent-500 bg-accent-500 text-white' : 'border-border-subtle bg-white text-text-muted hover:bg-surface-sunken'"
                        @click="applyCategory(null)"
                    >
                        {{ $t('All categories') }}
                    </button>
                    <button
                        v-for="entry in categories"
                        :key="entry.category"
                        type="button"
                        class="rounded-full border px-2.5 py-0.5 text-xs font-medium transition"
                        :class="categoryFilter === entry.category ? 'border-accent-500 bg-accent-500 text-white' : 'border-border-subtle bg-white text-text-muted hover:bg-surface-sunken'"
                        @click="applyCategory(entry.category)"
                    >
                        {{ categoryLabel(entry.category) }} <span class="opacity-70">({{ entry.project_count }})</span>
                    </button>
                </div>
            </div>

            <!-- Während des Reloads bleiben die alten Zahlen sichtbar, aber erkennbar "in Arbeit" -->
            <div class="space-y-6 transition-opacity" :class="{ 'opacity-50 pointer-events-none': loading }" :aria-busy="loading">

            <!-- Leerzustand: keine Produktion im Zeitraum (ggf. in der Sparte) -->
            <div v-if="isEmpty" class="rounded-2xl border border-border-subtle bg-white px-6 py-10 text-center shadow-sm">
                <p class="text-sm font-medium text-text">{{ $t('No productions in this period.') }}</p>
                <p class="mt-1 text-xs text-text-subtle">{{ $t('Choose another period or category — only productions with at least one event in the period are counted.') }}</p>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <BaseUIButton v-if="categoryFilter" :label="$t('All categories')" hide-icon white @click="applyCategory(null)" />
                    <BaseUIButton v-if="activePreset !== 'playing_time'" :label="$t('Season (default)')" hide-icon white @click="applyPreset(rangePresets[0])" />
                </div>
            </div>

            <!-- Onboarding hint: BI component not placed in any project tab -->
            <div v-if="!biComponentInTab" class="rounded-2xl border border-warning-border bg-warning-surface px-4 py-3 text-sm text-warning flex items-center justify-between gap-4">
                <span>{{ $t('The BI component is not yet included in any project tab. Add it in the project settings under "Tab Settings" so BI figures can be entered on projects.') }}</span>
                <Link :href="route('tab.index')" class="shrink-0 font-medium text-warning hover:underline">
                    {{ $t('Open tab settings') }}
                </Link>
            </div>

            <!-- Onboarding hint: KPI tag(s) not linked to event types → the affected figures stay empty -->
            <div v-if="!tagsLinked" class="rounded-2xl border border-warning-border bg-warning-surface px-4 py-3 text-sm text-warning flex items-center justify-between gap-4">
                <span class="space-y-0.5">
                    <span v-if="!kpiTags.performance" class="block">{{ $t('The BI tag “Performance” is not linked to any event type yet — performances and occupancy stay empty until you assign it.') }}</span>
                    <span v-if="!kpiTags.event_day" class="block">{{ $t('The BI tag “Event day” is not linked to any event type yet — event days stay empty until you assign it.') }}</span>
                </span>
                <Link :href="route('event_types.bi_tags')" class="shrink-0 font-medium text-warning hover:underline">
                    {{ $t('Configure BI tags') }}
                </Link>
            </div>

            <!-- Datenlücken: Projekte ohne erfasste BI-Zahlen -->
            <div v-if="showOverview && dataGaps.length > 0" class="rounded-2xl border border-warning-border bg-warning-surface px-4 py-3">
                <p class="text-sm font-medium text-warning mb-1.5">
                    {{ dataGaps.length }} {{ $t('projects with events but no BI figures — they pull every total towards zero.') }}
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="gap in visibleGaps"
                        :key="gap.project_id"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full bg-white/80 border border-warning-border px-2.5 py-0.5 text-xs text-warning hover:bg-white"
                        @click="quickEntryGap = gap"
                    >
                        <IconPencil class="size-3" />
                        {{ gap.project_name }}
                    </button>
                    <button
                        v-if="dataGaps.length > gapLimit"
                        type="button"
                        class="rounded-full px-2.5 py-0.5 text-xs text-warning hover:underline"
                        @click="showAllGaps = !showAllGaps"
                    >
                        {{ showAllGaps ? $t('Show less') : `+${dataGaps.length - gapLimit} ${$t('more')}` }}
                    </button>
                </div>
            </div>

            <BiQuickEntryModal
                v-if="quickEntryGap"
                :project-id="quickEntryGap.project_id"
                :project-name="quickEntryGap.project_name"
                :project-link="route('projects.tab', { project: quickEntryGap.project_id, projectTab: firstProjectTabId })"
                @close="quickEntryGap = null"
                @saved="onQuickEntrySaved"
            />

            <!-- KPI tiles -->
            <div v-if="showOverview">
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
                    <div v-for="kpi in kpiTiles" :key="kpi.key" class="rounded-2xl border border-border-subtle bg-white p-4 shadow-sm">
                        <p class="text-xs text-text-subtle inline-flex items-center gap-1">
                            {{ $t(kpi.label) }}
                            <ToolTipComponent
                                v-if="kpi.tooltip"
                                direction="bottom"
                                :tooltip-text="kpi.tooltip"
                                icon="IconInfoCircle"
                                icon-size="h-3.5 w-3.5"
                            />
                        </p>
                        <p class="text-xl font-semibold text-text mt-1">{{ kpi.value }}</p>
                        <p v-if="kpi.delta !== null" :class="['text-xs mt-1', kpi.delta >= 0 ? 'text-success' : 'text-danger']">
                            {{ kpi.delta >= 0 ? '▲' : '▼' }} {{ kpi.deltaText }} {{ compareShortLabel }}
                        </p>
                        <p v-if="kpi.note" class="text-[10px] text-accent-600 mt-0.5 leading-tight">{{ kpi.note }}</p>
                        <p v-if="kpi.planLine" class="text-[10px] text-text-subtle mt-0.5 leading-tight">{{ kpi.planLine }}</p>
                    </div>
                </div>
                <p v-if="yoyExcludedCount > 0" class="text-xs text-text-subtle mt-1.5">
                    {{ yoyExcludedCount }} {{ $t('project(s) with time-neutral total figures are not part of the period comparison.') }}
                </p>

                <!-- Quoten aus Besucher*innen-Kategorien (nur mit erfassten Kategoriewerten) -->
                <div v-if="quotaTiles.length > 0" class="mt-3">
                    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
                        <div
                            v-for="kpi in quotaTiles"
                            :key="kpi.key"
                            class="rounded-2xl border border-border-subtle bg-surface-sunken/70 p-4"
                        >
                            <p class="text-xs text-text-subtle inline-flex items-center gap-1">
                                {{ $t(kpi.label) }}
                                <ToolTipComponent
                                    v-if="kpi.tooltip"
                                    direction="bottom"
                                    :tooltip-text="kpi.tooltip"
                                    icon="IconInfoCircle"
                                    icon-size="h-3.5 w-3.5"
                                />
                            </p>
                            <p class="text-lg font-semibold mt-1" :class="kpi.value === null ? 'text-text-subtle' : 'text-text'">
                                {{ kpi.value ?? '–' }}
                            </p>
                        </div>
                    </div>
                    <p class="text-xs text-text-subtle mt-1.5">
                        {{ audienceQuotas.projects_with_categories }}
                        {{ $t('project(s) with category breakdown in this period.') }}
                    </p>
                </div>
            </div>

            <!-- Monatliche Entwicklung -->
            <div v-if="showOverview && monthlyChart" class="rounded-2xl border border-border-subtle bg-white p-5 shadow-sm">
                <h4 class="text-sm font-medium text-text-muted mb-3">{{ $t('Monthly trend (per-event figures)') }}</h4>
                <BiChart type="bar" :data="monthlyChart" :options="monthlyOptions" height="300px" />
            </div>

            <!-- Charts by category (Klick auf eine Sparte setzt den Seitenfilter) -->
            <div v-if="showOverview" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-border-subtle bg-white p-5 shadow-sm">
                    <h4 class="text-sm font-medium text-text-muted mb-1">{{ $t('Revenue by category') }}</h4>
                    <p class="text-xs text-text-subtle mb-3">{{ $t('Click a category to filter the whole dashboard.') }}</p>
                    <BiChart v-if="hasCategoryData" type="doughnut" :data="revenueChart" :options="categoryChartOptions" />
                    <p v-else class="text-sm text-text-subtle py-8 text-center">{{ $t('No data available.') }}</p>
                </div>
                <div class="rounded-2xl border border-border-subtle bg-white p-5 shadow-sm">
                    <h4 class="text-sm font-medium text-text-muted mb-1">{{ $t('Visitors by category') }}</h4>
                    <p class="text-xs text-text-subtle mb-3">{{ $t('Click a category to filter the whole dashboard.') }}</p>
                    <BiChart v-if="hasCategoryData" type="bar" :data="visitorsChart" :options="categoryChartOptions" />
                    <p v-else class="text-sm text-text-subtle py-8 text-center">{{ $t('No data available.') }}</p>
                </div>
            </div>

            <!-- Aufwand vs. Ertrag -->
            <div v-if="showSteering && scatterChart" class="rounded-2xl border border-border-subtle bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h4 class="text-sm font-medium text-text-muted">{{ $t('Effort vs. output') }}</h4>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-text-subtle">
                        <span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-full bg-danger inline-block"></span>{{ $t('High effort, low output') }}</span>
                        <span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-full bg-success inline-block"></span>{{ $t('Low effort, high output') }}</span>
                        <span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-full bg-accent-500 inline-block"></span>{{ $t('High effort, high output') }}</span>
                        <span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-full bg-border-strong inline-block"></span>{{ $t('Low effort, low output') }}</span>
                    </div>
                </div>
                <p class="text-xs text-text-subtle mb-3">{{ $t('Compared against the median of all productions in the period. Click a point to open the project.') }}</p>
                <BiChart type="scatter" :data="scatterChart" :options="scatterOptions" height="320px" />
            </div>

            <!-- Steuerung ohne Scatter (unter 3 Produktionen): Hinweis statt stiller Lücke -->
            <p v-if="showSteering && !scatterChart" class="text-xs text-text-subtle px-1">
                {{ $t('The effort-vs-output chart appears once at least three productions in the period have figures.') }}
            </p>

            <!-- Drilldown table -->
            <div v-if="showSteering" class="rounded-2xl border border-border-subtle bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h4 class="text-sm font-medium text-text-muted">{{ $t('Internal steering (effort vs. output)') }}</h4>
                    <div class="flex items-center gap-2">
                        <!-- Zustandsübernahme: exportiert genau die sichtbare Tabelle -->
                        <button
                            v-if="canExportBiData"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md border border-border-subtle px-2.5 py-1 text-xs font-medium text-text-muted hover:bg-surface-sunken transition"
                            @click="openSteeringExport"
                        >
                            <IconFileExport class="size-3.5" />
                            {{ $t('Export table') }}
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto max-h-[32rem] overflow-y-auto">
                    <table class="min-w-full text-sm">
                        <thead class="sticky top-0 bg-white z-10 shadow-[0_1px_0_0_#e5e7eb]">
                            <tr class="text-left text-xs text-text-subtle">
                                <th v-for="col in columns" :key="col.key" class="px-3 py-2 cursor-pointer whitespace-nowrap bg-white" @click="sortBy(col.key)">
                                    <span class="inline-flex items-center gap-1">
                                        {{ $t(col.label) }}
                                        <span v-if="sortKey === col.key">{{ sortAsc ? '▲' : '▼' }}</span>
                                        <span v-if="col.key === 'effort_score'" @click.stop>
                                            <ToolTipComponent
                                                direction="left"
                                                :tooltip-text="effortScoreTooltip"
                                                icon="IconInfoCircle"
                                                icon-size="h-3.5 w-3.5"
                                            />
                                        </span>
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in sortedProjects" :key="row.project_id" class="border-b border-border-subtle hover:bg-surface-sunken">
                                <td class="px-3 py-2">
                                    <Link :href="route('projects.tab', { project: row.project_id, projectTab: firstProjectTabId })" class="text-accent-600 hover:underline">
                                        {{ row.project_name }}
                                    </Link>
                                </td>
                                <td class="px-3 py-2 text-text-muted">{{ row.category || '—' }}</td>
                                <!-- Leerwerte: "—" = nichts erfasst (formatInt/formatCurrency liefern das selbst) -->
                                <td class="px-3 py-2">
                                    <span
                                        v-if="row.visitors_estimated"
                                        class="text-accent-700"
                                        v-tooltip.top="{ value: $t('Estimated from sold tickets'), appendTo: 'body', class: 'aw-tooltip' }"
                                    >≈ {{ formatInt(row.visitors) }}</span>
                                    <template v-else>{{ formatInt(row.visitors) }}</template>
                                </td>
                                <td class="px-3 py-2">{{ formatCurrency(row.revenue) }}</td>
                                <td class="px-3 py-2">
                                    <div v-if="row.occupancy !== null" class="flex items-center gap-2">
                                        <div class="h-1.5 w-14 rounded-full bg-surface-sunken overflow-hidden shrink-0">
                                            <div
                                                class="h-full rounded-full"
                                                :class="occupancyBarClass(row.occupancy)"
                                                :style="{ width: Math.min(row.occupancy, 100) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-xs whitespace-nowrap">{{ formatPercent(row.occupancy) }}</span>
                                    </div>
                                    <span v-else class="text-text-subtle">—</span>
                                </td>
                                <td class="px-3 py-2">{{ formatInt(row.performances) }}</td>
                                <td class="px-3 py-2">{{ row.contracts_per_performance ?? '—' }}</td>
                                <td v-if="sageApiEnabled" class="px-3 py-2">{{ row.bookings_per_performance ?? '—' }}</td>
                                <td class="px-3 py-2">{{ row.tasks_docs_per_production }}</td>
                                <td class="px-3 py-2 font-medium">{{ row.effort_score }}</td>
                                <template v-if="planSummary">
                                    <td class="px-3 py-2 text-text-muted">{{ row.plan_visitors !== null ? formatInt(row.plan_visitors) : '—' }}</td>
                                    <td class="px-3 py-2 text-text-muted">{{ row.plan_revenue !== null ? formatCurrency(row.plan_revenue) : '—' }}</td>
                                    <td class="px-3 py-2 text-text-muted">{{ row.plan_costs !== null ? formatCurrency(row.plan_costs) : '—' }}</td>
                                    <td class="px-3 py-2">
                                        <span
                                            v-if="row.costs_attainment !== null"
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="row.costs_attainment <= 100 ? 'bg-success-surface text-success' : (row.costs_attainment <= 120 ? 'bg-warning-surface text-warning' : 'bg-danger-surface text-danger')"
                                        >{{ formatPercent(row.costs_attainment) }}</span>
                                        <span v-else class="text-text-subtle">—</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            v-if="row.attainment !== null"
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="row.attainment >= 100 ? 'bg-success-surface text-success' : (row.attainment >= 80 ? 'bg-warning-surface text-warning' : 'bg-danger-surface text-danger')"
                                        >{{ formatPercent(row.attainment) }}</span>
                                        <span v-else class="text-text-subtle">—</span>
                                    </td>
                                </template>
                            </tr>
                            <tr v-if="sortedProjects.length === 0">
                                <td :colspan="columns.length" class="px-3 py-8 text-center text-text-subtle">{{ $t('No data available.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Ampel-Legende: drei Farblogiken in einer Tabelle brauchen eine Erklärung -->
                <p class="text-xs text-text-subtle mt-3">
                    {{ $t('Occupancy: green from 90 %, blue from 50 %, yellow below · Attainment: green from 100 %, yellow from 80 %, red below · Costs: green up to plan, yellow up to 120 % of plan, red above.') }}
                    · {{ $t('Not recorded') }}: —
                </p>
            </div>
            </div><!-- /loading-wrapper -->
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import { IconChartHistogram, IconPencil, IconFileExport, IconCircleCheck } from '@tabler/icons-vue';
import BiQuickEntryModal from '@/Pages/Projects/Components/BiComponents/BiQuickEntryModal.vue';
import BiExportDialog from '@/Pages/Projects/Components/BiComponents/BiExportDialog.vue';
import BiBudgetExportModal from '@/Pages/Projects/Components/BiComponents/BiBudgetExportModal.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import BiChart from '@/Artwork/Charts/BiChart.vue';
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue';
import { useTranslation } from '@/Composeables/Translation.js';

const t = useTranslation();

const props = defineProps({
    dashboard: { type: Object, required: true },
    firstProjectTabId: { type: [Number, String], default: null },
    biComponentInTab: { type: Boolean, default: true },
    // Export-Buttons nur mit Recht; der Dialog lädt seine Optionen selbst
    canExportBiData: { type: Boolean, default: false },
});

const canExportBiData = computed(() => props.canExportBiData);
const showExportModal = ref(false);
const exportExpired = ref(new URLSearchParams(window.location.search).get('export') === 'expired');
const showBudgetExportModal = ref(false);

const firstProjectTabId = computed(() => props.firstProjectTabId);

const kpis = computed(() => props.dashboard.kpis ?? {});
// Jahresvergleich läuft über comparable_kpis (gleiches Zeitfenster, gleiche
// Ausschlussregeln wie previous_kpis) — nicht über die Anzeige-KPIs
const comparableKpis = computed(() => props.dashboard.comparable_kpis ?? props.dashboard.kpis ?? {});
const previousKpis = computed(() => props.dashboard.previous_kpis ?? {});
const yoyExcludedCount = computed(() => previousKpis.value.excluded_total_mode_projects ?? 0);
const scoreWeights = computed(() => props.dashboard.score_weights ?? null);
const byCategory = computed(() => props.dashboard.by_category ?? []);
const projects = computed(() => props.dashboard.projects ?? []);
const monthly = computed(() => props.dashboard.monthly ?? []);
const dataGaps = computed(() => props.dashboard.data_gaps ?? []);
const categories = computed(() => props.dashboard.categories ?? []);
const tagsLinked = computed(() => props.dashboard.tags_linked !== false);

// --- Sichten & Seitenzustand ---

const viewTabs = [
    { key: 'overview', label: 'Overview' },
    { key: 'steering', label: 'Steering' },
];
const initialQueryForView = new URLSearchParams(window.location.search);
const view = ref(initialQueryForView.get('view') === 'steering' ? 'steering' : 'overview');
const setView = (key) => {
    view.value = key;
    // Sicht in der URL halten, damit Reload/Teilen dieselbe Sicht öffnet
    const url = new URL(window.location.href);
    if (key === 'overview') {
        url.searchParams.delete('view');
    } else {
        url.searchParams.set('view', key);
    }
    window.history.replaceState(window.history.state, '', url);
};

const isEmpty = computed(() => (props.dashboard.kpis?.project_count ?? 0) === 0);
const showOverview = computed(() => view.value === 'overview' && !isEmpty.value);
const showSteering = computed(() => view.value === 'steering' && !isEmpty.value);

const reloadError = ref(false);
const savedNotice = ref(null);
let savedNoticeTimer = null;
// Je KPI-Tag: ohne Terminart-Zuordnung bleibt die Kennzahl leer (kein Fallback)
const kpiTags = computed(() => props.dashboard.kpi_tags ?? { performance: true, event_day: true });

const dateFrom = ref(props.dashboard.range?.from ?? '');
const dateTo = ref(props.dashboard.range?.to ?? '');
const loading = ref(false);
// Ohne explizite URL-Daten gilt serverseitig die Spielzeit → Preset als aktiv markieren
const initialQuery = new URLSearchParams(window.location.search);
const activePreset = ref(initialQuery.get('date_from') || initialQuery.get('date_to') ? null : 'playing_time');

// --- Vergleichszeitraum (Default: Vorjahr, serverseitig) ---

const comparePresets = [
    { key: 'previous_year', label: 'Previous year' },
    { key: 'previous_period', label: 'Previous period' },
    { key: 'free', label: 'Free choice' },
    { key: 'none', label: 'No comparison' },
];

const comparePreset = ref(
    initialQuery.get('compare') === 'none'
        ? 'none'
        : (initialQuery.get('compare_from') || initialQuery.get('compare_to') ? 'free' : 'previous_year')
);
const compareFrom = ref(initialQuery.get('compare_from') ?? '');
const compareTo = ref(initialQuery.get('compare_to') ?? '');

const applyComparePreset = (key) => {
    comparePreset.value = key;
    if (key === 'previous_period') {
        // Gleiche Länge direkt vor dem aktuellen Zeitraum
        const from = new Date((dateFrom.value || props.dashboard.range?.from) + 'T00:00:00');
        const to = new Date((dateTo.value || props.dashboard.range?.to) + 'T00:00:00');
        if (!isNaN(from) && !isNaN(to)) {
            const lengthMs = to - from;
            const cmpTo = new Date(from);
            cmpTo.setDate(cmpTo.getDate() - 1);
            const cmpFrom = new Date(cmpTo.getTime() - lengthMs);
            compareFrom.value = isoDate(cmpFrom);
            compareTo.value = isoDate(cmpTo);
        }
    }
    if (key !== 'free') {
        reload(true);
    }
};

const compareParams = () => {
    if (comparePreset.value === 'none') return { compare: 'none' };
    if (comparePreset.value === 'previous_year') return {};
    return {
        compare_from: compareFrom.value || null,
        compare_to: compareTo.value || null,
    };
};

const formatRangeDate = (iso) => {
    if (!iso) return '';
    const [y, m, d] = iso.split('-');
    return `${d}.${m}.${y}`;
};

const comparisonRange = computed(() => props.dashboard.comparison_range ?? null);

const comparisonLabel = computed(() => {
    if (!comparisonRange.value) return null;
    return `${t('vs.')} ${formatRangeDate(comparisonRange.value.from)} – ${formatRangeDate(comparisonRange.value.to)}`;
});

// Kurzform an den Delta-Chips: konkreter Zeitraum statt pauschal "Vorjahr"
const compareShortLabel = computed(() => comparisonLabel.value ?? '');

// --- Zeitraum als Satz (Kopfzeile) ---

const rangeText = (from, to) => {
    if (!from && !to) return t('All periods');
    return `${formatRangeDate(from) || '…'} – ${formatRangeDate(to) || '…'}`;
};

const periodSentence = computed(() => {
    const range = props.dashboard.range ?? {};
    const presetLabel = {
        playing_time: t('Season'),
        calendar_year: t('Calendar year'),
        last_12_months: t('Last 12 months'),
    }[activePreset.value] ?? t('Custom period');
    return `${presetLabel} (${rangeText(range.from, range.to)})`;
});

const comparisonSentence = computed(() => {
    if (!comparisonRange.value) return t('no comparison');
    const presetLabel = {
        previous_year: t('Previous year'),
        previous_period: t('Previous period'),
        free: t('Custom period'),
    }[comparePreset.value] ?? t('Comparison period');
    return `${presetLabel} (${rangeText(comparisonRange.value.from, comparisonRange.value.to)})`;
});

// "Spielzeit" ohne hinterlegtes Fenster = alle Zeiträume → das muss der Nutzer sehen
const seasonMissing = computed(() =>
    activePreset.value === 'playing_time'
    && !props.dashboard.range?.from
    && !props.dashboard.range?.to
);

// Nach einem Reload die effektiv angewandte Spanne in die Inputs spiegeln
watch(() => props.dashboard.range, (range) => {
    dateFrom.value = range?.from ?? '';
    dateTo.value = range?.to ?? '';
});

const gapLimit = 8;
const showAllGaps = ref(false);
const visibleGaps = computed(() => showAllGaps.value ? dataGaps.value : dataGaps.value.slice(0, gapLimit));

const quickEntryGap = ref(null);

// --- Export der Steuerungstabelle (sichtbarer Zustand → Modal-Vorbelegung) ---

const steeringExportPreset = ref(null);

const openHeaderExport = () => {
    // Header-Button: Standard-Vorbelegung (kein Tabellen-Preset)
    steeringExportPreset.value = null;
    showExportModal.value = true;
};

const openSteeringExport = () => {
    // Sichtbare Tabellenspalten auf Export-Spalten abgebildet
    const exportColumns = [
        'project_name',
        'main_category',
        'visitors',
        'revenue',
        'occupancy_rate',
        'contracts_per_performance',
        'bookings_per_performance',
        'tasks_docs_per_production',
        'effort_score',
        ...(planSummary.value ? ['plan_visitors', 'plan_revenue', 'attainment'] : []),
    ];
    steeringExportPreset.value = {
        columns: exportColumns,
        projectIds: sortedProjects.value.map(row => row.project_id),
    };
    showExportModal.value = true;
};

const onQuickEntrySaved = (projectName) => {
    quickEntryGap.value = null;
    savedNotice.value = `${t('Figures saved')}${projectName ? ` — ${projectName}` : ''}.`;
    clearTimeout(savedNoticeTimer);
    savedNoticeTimer = setTimeout(() => { savedNotice.value = null; }, 4000);
    // Schreibzugriff hat die Dashboard-Cache-Version gebumpt → Reload liefert frische Zahlen
    reload(true);
};

const numberFmt = new Intl.NumberFormat('de-DE');
const currencyFmt = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
const percentFmt = new Intl.NumberFormat('de-DE', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
// null/undefined = nicht erfasst → "—" statt einer scheinbar echten 0
const formatInt = (v) => (v === null || v === undefined) ? '—' : numberFmt.format(v);
const formatCurrency = (v) => (v === null || v === undefined) ? '—' : currencyFmt.format(v);
const formatPercent = (v) => (v === null || v === undefined) ? '—' : `${percentFmt.format(v)} %`;

// --- Zeitraum-Schnellwahl ---

const isoDate = (d) => {
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${month}-${day}`;
};

const rangePresets = [
    { key: 'playing_time', label: 'Season (default)' },
    { key: 'calendar_year', label: 'Calendar year' },
    { key: 'last_12_months', label: 'Last 12 months' },
];

const applyPreset = (preset) => {
    const now = new Date();
    if (preset.key === 'playing_time') {
        dateFrom.value = '';
        dateTo.value = '';
    } else if (preset.key === 'calendar_year') {
        dateFrom.value = `${now.getFullYear()}-01-01`;
        dateTo.value = `${now.getFullYear()}-12-31`;
    } else {
        const from = new Date(now);
        from.setFullYear(from.getFullYear() - 1);
        from.setDate(from.getDate() + 1);
        dateFrom.value = isoDate(from);
        dateTo.value = isoDate(now);
    }
    activePreset.value = preset.key;
    reload(true);
};

// Relative Änderung in % (Zähl-/Summen-KPIs), verglichen über comparable_kpis,
// damit aktuelle und Vorjahres-Basis denselben Ausschlussregeln folgen
const delta = (key) => {
    const prev = previousKpis.value[key];
    const cur = comparableKpis.value[key];
    if (!prev || prev === 0 || cur === null || cur === undefined) return null;
    return (cur - prev) / prev * 100;
};

// Differenz in Prozentpunkten — für Raten-KPIs wie die Auslastung, wo eine
// relative Änderung ("+12,5 %") garantiert falsch gelesen würde
const deltaPoints = (key) => {
    const prev = previousKpis.value[key];
    const cur = comparableKpis.value[key];
    if (prev === null || prev === undefined || cur === null || cur === undefined) return null;
    return cur - prev;
};

const relDeltaText = (d) => (d === null ? null : `${percentFmt.format(Math.abs(d))} %`);

const effortScoreTooltip = computed(() => {
    const w = scoreWeights.value;
    const fmt = (v) => numberFmt.format(v);
    // Einordnung: eine Formel allein sagt nicht, ob 14,5 viel ist
    const scores = projects.value.map(row => row.effort_score).filter(v => v !== null && v !== undefined);
    const context = scores.length
        ? ` ${t('Median of all productions in the period')}: ${fmt(median(scores))} — ${t('higher = more internal effort')}.`
        : ` ${t('higher = more internal effort')}.`;
    if (!w) {
        return t('Weighted proxy for internal effort: 2 × contracts + 1 × bookings + 1.5 × open tasks + 0.5 × documents + 0.1 × effort hours.') + context;
    }
    // Sage-Buchungen nur nennen, wenn die Schnittstelle aktiv ist
    const bookingsPart = sageApiEnabled ? `${fmt(w.bookings)} × ${t('bookings')} + ` : '';
    return `${t('Weighted proxy for internal effort')}: `
        + `${fmt(w.contracts)} × ${t('contracts')} + ${bookingsPart}`
        + `${fmt(w.open_tasks)} × ${t('open tasks')} + ${fmt(w.documents)} × ${t('documents')} + `
        + `${fmt(w.effort_hours)} × ${t('effort hours')}.` + context;
});

const kpiTiles = computed(() => {
    const visitorsDelta = delta('visitors');
    const revenueDelta = delta('revenue');
    const occupancyDelta = deltaPoints('occupancy');
    const eventDaysDelta = delta('event_days');
    const performancesDelta = delta('performances');

    const unlinkedTooltip = t('BI tag not linked to any event type — assign it in the event type settings.');
    const unlinkedNote = t('Tag not assigned');

    return [
        {
            key: 'visitors',
            label: 'Visitors',
            value: (kpis.value.visitors_estimated ? '≈ ' : '') + formatInt(kpis.value.visitors),
            delta: visitorsDelta,
            deltaText: relDeltaText(visitorsDelta),
            note: kpis.value.visitors_estimated ? t('partly estimated from sold tickets') : null,
            tooltip: (kpis.value.visitors_estimated ? t('Estimated from sold tickets') + ' · ' : '')
                + t('Sum of recorded visitors of all productions in the period. Per-event figures count only events in the period, total figures count fully.'),
            planLine: planSummary.value?.visitors_attainment !== null && planSummary.value?.visitors_attainment !== undefined
                ? `${t('Plan')}: ${formatInt(planSummary.value.plan_visitors)} · ${formatPercent(planSummary.value.visitors_attainment)}`
                : null,
        },
        {
            key: 'revenue',
            label: 'Revenue',
            value: formatCurrency(kpis.value.revenue),
            delta: revenueDelta,
            deltaText: relDeltaText(revenueDelta),
            tooltip: t('Sum of recorded revenue of all productions in the period.'),
            planLine: planSummary.value?.revenue_attainment !== null && planSummary.value?.revenue_attainment !== undefined
                ? `${t('Plan')}: ${formatCurrency(planSummary.value.plan_revenue)} · ${formatPercent(planSummary.value.revenue_attainment)}`
                : null,
        },
        // Kosten-Kachel nur, wenn mindestens ein Projekt Kosten erfasst hat
        ...(kpis.value.costs !== null && kpis.value.costs !== undefined ? [{
            key: 'costs',
            label: 'Costs',
            value: formatCurrency(kpis.value.costs),
            delta: null,
            tooltip: t('Sum of recorded total costs — only productions that entered costs.'),
            planLine: planSummary.value?.costs_attainment !== null && planSummary.value?.costs_attainment !== undefined
                ? `${t('Plan')}: ${formatCurrency(planSummary.value.plan_costs)} · ${formatPercent(planSummary.value.costs_attainment)}`
                : null,
        }] : []),
        {
            key: 'occupancy',
            label: 'Occupancy rate',
            value: formatPercent(kpis.value.occupancy),
            delta: occupancyDelta,
            deltaText: occupancyDelta !== null ? `${percentFmt.format(Math.abs(occupancyDelta))} ${t('percentage points')}` : null,
            note: kpiTags.value.performance ? null : unlinkedNote,
            tooltip: t('Sold tickets ÷ seat capacity of the rooms played in the selected period.')
                + (kpiTags.value.performance ? '' : ' ' + unlinkedTooltip),
        },
        {
            key: 'event_days',
            label: 'Event days',
            value: formatInt(kpis.value.event_days),
            delta: eventDaysDelta,
            deltaText: relDeltaText(eventDaysDelta),
            note: kpiTags.value.event_day ? null : unlinkedNote,
            tooltip: kpiTags.value.event_day
                ? t('Distinct days with events whose event type carries the BI tag “Event day”.')
                : unlinkedTooltip,
        },
        {
            key: 'performances',
            label: 'Performances',
            value: formatInt(kpis.value.performances),
            delta: performancesDelta,
            deltaText: relDeltaText(performancesDelta),
            note: kpiTags.value.performance ? null : unlinkedNote,
            tooltip: kpiTags.value.performance
                ? t('Events whose event type carries the BI tag “Performance”.')
                : unlinkedTooltip,
        },
        {
            key: 'project_count',
            label: 'Productions',
            value: formatInt(kpis.value.project_count),
            delta: null,
            tooltip: t('Productions with at least one event in the period.'),
        },
        // Erfassungsgrad: die wichtigste Kennzahl für die Belastbarkeit aller anderen
        {
            key: 'recorded',
            label: 'Recorded',
            value: `${formatInt(Math.max((kpis.value.project_count ?? 0) - dataGaps.value.length, 0))} / ${formatInt(kpis.value.project_count)}`,
            delta: null,
            note: dataGaps.value.length > 0 ? `${dataGaps.value.length} ${t('without figures')}` : null,
            tooltip: t('Productions in the period that have at least one BI figure (or are marked as not relevant). The others pull every total towards zero.'),
        },
    ];
});

const audienceQuotas = computed(() => props.dashboard.audience_quotas ?? null);

const quotaTiles = computed(() => {
    const q = audienceQuotas.value;
    if (!q) return [];

    const pct = (v) => (v === null || v === undefined) ? null : formatPercent(v);

    // Absolutwert und zugehörige Quote stehen nebeneinander; jede Kachel nennt ihren Nenner
    return [
        { key: 'tickets_issued', label: 'Tickets issued', value: formatInt(q.tickets_issued), tooltip: t('All tickets from the audience categories (full, reduced and free).') },
        { key: 'free', label: 'Free tickets', value: q.free !== null ? formatInt(q.free) : null, tooltip: t('Tickets in categories with pricing type “free”.') },
        { key: 'free_tickets_rate', label: 'Free ticket rate', value: pct(q.free_tickets_rate), tooltip: t('Free tickets ÷ all issued tickets.') },
        { key: 'reduced', label: 'Reduced tickets', value: q.reduced !== null ? formatInt(q.reduced) : null, tooltip: t('Tickets in categories with pricing type “reduced”.') },
        { key: 'reduced_tickets_rate', label: 'Reduced ticket rate', value: pct(q.reduced_tickets_rate), tooltip: t('Reduced tickets ÷ paid tickets (full + reduced).') },
        { key: 'paying_rate', label: 'Paying rate', value: pct(q.paying_rate), tooltip: t('Paid tickets (full + reduced) ÷ all issued tickets.') },
    ];
});

const palette = ['#6366f1', '#22c55e', '#f59e0b', '#ec4899', '#06b6d4', '#a855f7', '#ef4444', '#14b8a6', '#eab308', '#3b82f6'];

const hasCategoryData = computed(() => byCategory.value.length > 0);

// --- Monats-Zeitreihe ---

const monthLabel = (ym) => {
    const [year, month] = String(ym).split('-');
    return `${month}.${year}`;
};

const monthlyChart = computed(() => {
    const rows = monthly.value;
    if (!rows.length || !rows.some(r => r.visitors > 0 || r.revenue > 0 || r.prev_visitors > 0)) return null;

    return {
        labels: rows.map(r => monthLabel(r.month)),
        datasets: [
            {
                label: t('Visitors'),
                data: rows.map(r => r.visitors),
                backgroundColor: '#6366f1',
                yAxisID: 'y',
                order: 3,
            },
            {
                type: 'line',
                label: `${t('Visitors')} (${t('comparison period')})`,
                data: rows.map(r => r.prev_visitors),
                borderColor: '#9ca3af',
                backgroundColor: '#9ca3af',
                borderDash: [5, 4],
                pointRadius: 2,
                tension: 0.3,
                yAxisID: 'y',
                order: 2,
                // Monate ohne Vorjahresdaten kommen als null → Linie aussetzen
                // statt eine irreführende 0-Linie zu zeichnen
                spanGaps: false,
            },
            {
                type: 'line',
                label: t('Revenue'),
                data: rows.map(r => r.revenue),
                borderColor: '#f59e0b',
                backgroundColor: '#f59e0b',
                pointRadius: 2,
                tension: 0.3,
                yAxisID: 'y1',
                order: 1,
            },
        ],
    };
});

const monthlyOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { font: { family: 'inherit' }, boxWidth: 12 } },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    if (ctx.parsed.y === null) return null;
                    const formatted = ctx.dataset.yAxisID === 'y1'
                        ? currencyFmt.format(ctx.parsed.y)
                        : numberFmt.format(ctx.parsed.y);
                    return `${ctx.dataset.label}: ${formatted}`;
                },
            },
        },
    },
    scales: {
        y: { beginAtZero: true, position: 'left', ticks: { callback: (v) => numberFmt.format(v) } },
        y1: {
            beginAtZero: true,
            position: 'right',
            grid: { drawOnChartArea: false },
            ticks: { callback: (v) => currencyFmt.format(v) },
        },
    },
};

// --- Kategorie-Charts (klickbar → filtert Tabelle) ---

// Serverseitiger Seitenfilter (Kacheln, Charts, Tabelle) — Wahrheit ist der Payload
const categoryFilter = computed(() => props.dashboard.category_filter ?? null);

const applyCategory = (key) => {
    pendingCategory = key;
    reload(true);
};
let pendingCategory = categoryFilter.value;

// Backend liefert '—' als Schlüssel für Projekte ohne Sparte → lesbares Label
const categoryLabel = (key) => (key === '—' || !key) ? t('Without category') : key;

const categoryChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { font: { family: 'inherit' } } },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    // Umsatz-Doughnut in €, Besucher-Bar mit Tausendertrennung
                    const value = ctx.parsed?.y ?? ctx.parsed;
                    const formatted = ctx.chart.config.type === 'doughnut'
                        ? currencyFmt.format(value)
                        : numberFmt.format(value);
                    return `${ctx.dataset.label ? ctx.dataset.label + ': ' : ''}${formatted}`;
                },
            },
        },
    },
    onHover: (event, elements) => {
        // Klickbarkeit sichtbar machen
        const target = event?.native?.target;
        if (target) target.style.cursor = elements?.length ? 'pointer' : 'default';
    },
    onClick: (event, elements) => {
        if (!elements?.length) return;
        // Roh-Schlüssel statt Anzeige-Label; Klick auf die aktive Sparte hebt den Filter auf
        const key = byCategory.value[elements[0].index]?.category ?? null;
        applyCategory(categoryFilter.value === key ? null : key);
    },
}));

const revenueChart = computed(() => ({
    labels: byCategory.value.map(c => categoryLabel(c.category)),
    datasets: [{
        data: byCategory.value.map(c => c.revenue),
        backgroundColor: byCategory.value.map((_, i) => palette[i % palette.length]),
    }],
}));

const visitorsChart = computed(() => ({
    labels: byCategory.value.map(c => categoryLabel(c.category)),
    datasets: [{
        label: t('Visitors'),
        data: byCategory.value.map(c => c.visitors),
        backgroundColor: '#6366f1',
    }],
}));

// --- Aufwand vs. Ertrag (Quadranten relativ zum Median) ---

const median = (values) => {
    if (!values.length) return 0;
    const sorted = [...values].sort((a, b) => a - b);
    const mid = Math.floor(sorted.length / 2);
    return sorted.length % 2 ? sorted[mid] : (sorted[mid - 1] + sorted[mid]) / 2;
};

const scatterRows = computed(() => projects.value.filter(
    row => row.effort_score > 0 || row.revenue > 0 || row.visitors > 0
));

const outputUsesRevenue = computed(() => scatterRows.value.some(row => row.revenue > 0));

const scatterChart = computed(() => {
    const rows = scatterRows.value;
    if (rows.length < 3) return null;

    // null (nicht erfasst) als 0 auf der Achse — sonst verschwindet der Punkt
    const outputOf = (row) => (outputUsesRevenue.value ? row.revenue : row.visitors) ?? 0;
    const effortMedian = median(rows.map(r => r.effort_score));
    const outputMedian = median(rows.map(outputOf));

    const quadrantColor = (row) => {
        const highEffort = row.effort_score > effortMedian;
        const highOutput = outputOf(row) > outputMedian;
        if (highEffort && !highOutput) return '#f43f5e';
        if (!highEffort && highOutput) return '#10b981';
        if (highEffort && highOutput) return '#818cf8';
        return '#9ca3af';
    };

    return {
        datasets: [{
            label: t('Productions'),
            data: rows.map(row => ({
                x: row.effort_score,
                y: outputOf(row),
                name: row.project_name,
                projectId: row.project_id,
            })),
            backgroundColor: rows.map(quadrantColor),
            pointRadius: 6,
            pointHoverRadius: 8,
        }],
    };
});

const scatterOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    const output = outputUsesRevenue.value ? formatCurrency(ctx.raw.y) : formatInt(ctx.raw.y);
                    return `${ctx.raw.name} — ${t('Effort score')}: ${ctx.raw.x}, ${outputUsesRevenue.value ? t('Revenue') : t('Visitors')}: ${output}`;
                },
            },
        },
    },
    onClick: (event, elements, chart) => {
        if (!elements?.length) return;
        const point = chart.data.datasets[elements[0].datasetIndex].data[elements[0].index];
        if (point?.projectId) {
            router.visit(route('projects.tab', { project: point.projectId, projectTab: firstProjectTabId.value }));
        }
    },
    scales: {
        x: { title: { display: true, text: t('Effort score') }, beginAtZero: true },
        y: {
            title: { display: true, text: outputUsesRevenue.value ? t('Revenue') : t('Visitors') },
            beginAtZero: true,
            ticks: outputUsesRevenue.value
                ? { callback: (v) => currencyFmt.format(v) }
                : { callback: (v) => numberFmt.format(v) },
        },
    },
}));

// --- Drilldown-Tabelle ---

const occupancyBarClass = (value) => {
    if (value >= 90) return 'bg-success';
    if (value >= 50) return 'bg-accent-600';
    return 'bg-warning';
};

const planSummary = computed(() => props.dashboard.plan_summary ?? null);

// Sage-Bezug (Buchungs-Spalten) nur mit aktiver Schnittstelle zeigen
const sageApiEnabled = !!usePage().props.sageApiEnabled;

const columns = computed(() => [
    { key: 'project_name', label: 'Production' },
    { key: 'category', label: 'Category (Sector)' },
    { key: 'visitors', label: 'Visitors' },
    { key: 'revenue', label: 'Revenue' },
    { key: 'occupancy', label: 'Occupancy rate' },
    { key: 'performances', label: 'Performances' },
    { key: 'contracts_per_performance', label: 'Contracts / performance' },
    ...(sageApiEnabled ? [{ key: 'bookings_per_performance', label: 'Bookings / performance' }] : []),
    { key: 'tasks_docs_per_production', label: 'Tasks + documents' },
    { key: 'effort_score', label: 'Effort score' },
    ...(planSummary.value ? [
        { key: 'plan_visitors', label: 'Plan visitors' },
        { key: 'plan_revenue', label: 'Plan revenue' },
        { key: 'plan_costs', label: 'Plan costs' },
        { key: 'costs_attainment', label: 'Costs attainment' },
        { key: 'attainment', label: 'Attainment' },
    ] : []),
]);

const sortKey = ref('effort_score');
const sortAsc = ref(false);

const sortBy = (key) => {
    if (sortKey.value === key) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortKey.value = key;
        sortAsc.value = true;
    }
};

const sortedProjects = computed(() => {
    // Sparten-Filter greift bereits serverseitig — hier nur noch sortieren
    const rows = [...projects.value];
    rows.sort((a, b) => {
        const av = a[sortKey.value];
        const bv = b[sortKey.value];
        if (av === null || av === undefined) return 1;
        if (bv === null || bv === undefined) return -1;
        if (typeof av === 'string') return sortAsc.value ? av.localeCompare(bv) : bv.localeCompare(av);
        return sortAsc.value ? av - bv : bv - av;
    });
    return rows;
});

const reload = (fromPreset = false) => {
    if (!fromPreset) {
        activePreset.value = null;
    }
    loading.value = true;
    reloadError.value = false;
    router.get(route('bi.dashboard'), {
        date_from: dateFrom.value || null,
        date_to: dateTo.value || null,
        category: pendingCategory || null,
        ...(view.value === 'steering' ? { view: 'steering' } : {}),
        ...compareParams(),
    }, {
        preserveState: true,
        preserveScroll: true,
        onError: () => { reloadError.value = true; },
        onFinish: () => { loading.value = false; },
    });
};
</script>
