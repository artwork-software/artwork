<template>
    <AppLayout :title="$t('Week status')">
        <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-5">
            <!-- Kopf -->
            <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                <div class="flex items-start gap-2">
                    <div>
                        <h1 class="text-xl font-semibold text-text">{{ $t('Week status') }}</h1>
                        <p class="mt-1 max-w-2xl text-sm text-text-subtle">
                            {{ $t('Commitment, requests, changes, violations and staffing per craft and calendar week.') }}
                        </p>
                    </div>
                    <ToolTipComponent
                        direction="bottom"
                        icon="IconInfoCircle"
                        icon-size="h-5 w-5"
                        classes-button="mt-0.5 p-1 rounded-lg hover:bg-surface-sunken transition"
                        tooltip-css-class="aw-tooltip-wide"
                        :tooltip-text="helpText"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Zeitraum-Navigation -->
                    <div class="flex items-center gap-1 rounded-lg border border-border bg-surface p-1">
                        <ToolTipComponent
                            direction="bottom"
                            icon="IconChevronLeft"
                            icon-size="h-4 w-4"
                            classes-button="p-1 rounded-md hover:bg-surface-sunken transition"
                            :tooltip-text="$t('4 weeks back')"
                            :disabled="loading"
                            @click="shiftRange(-4)"
                        />
                        <BaseUIButton
                            type="button"
                            variant="ghost"
                            size="sm"
                            hide-icon
                            :label="$t('Today')"
                            :disabled="loading"
                            @click="goToToday"
                        />
                        <ToolTipComponent
                            direction="bottom"
                            icon="IconChevronRight"
                            icon-size="h-4 w-4"
                            classes-button="p-1 rounded-md hover:bg-surface-sunken transition"
                            :tooltip-text="$t('4 weeks forward')"
                            :disabled="loading"
                            @click="shiftRange(4)"
                        />
                    </div>

                    <!-- Wochenanzahl -->
                    <div class="flex items-center rounded-lg border border-border bg-surface p-1" role="group" :aria-label="$t('Number of weeks')">
                        <button
                            v-for="option in weekOptions"
                            :key="option"
                            type="button"
                            :class="[
                                'rounded-md px-2.5 py-1 text-xs font-medium transition',
                                option === filters.weeks
                                    ? 'bg-accent-50 text-accent-700'
                                    : 'text-text-muted hover:bg-surface-sunken hover:text-text',
                            ]"
                            :aria-pressed="option === filters.weeks"
                            :disabled="loading"
                            @click="setWeeks(option)"
                        >
                            {{ option }} {{ $t('weeks') }}
                        </button>
                    </div>

                    <!-- Gewerksfilter -->
                    <div class="w-64">
                        <ArtworkBaseListbox
                            :model-value="selectedCrafts"
                            :items="crafts"
                            multiple
                            by="id"
                            option-label="name"
                            is-small
                            show-color-indicator
                            :placeholder="$t('All crafts')"
                            :selected-formatter="craftFormatter"
                            :disabled="loading"
                            @update:model-value="onCraftsChanged"
                        />
                    </div>
                </div>
            </div>

            <!-- Zeitraum + Legende -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-text-muted">
                    {{ rangeLabel }}
                </p>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-text-muted">
                    <span
                        v-for="status in legendStatuses"
                        :key="status"
                        class="inline-flex items-center gap-1"
                        :title="$t(statusMeta(status).description)"
                    >
                        <span :class="['h-2 w-2 rounded-full', statusMeta(status).dot]"></span>
                        {{ $t(statusMeta(status).label) }}
                    </span>
                    <span class="hidden h-3 w-px bg-border sm:inline-block"></span>
                    <span class="inline-flex items-center gap-1" :title="$t('Deadline due soon')">
                        <span class="h-2 w-2 rounded-full bg-warning"></span>{{ $t('Deadline due soon') }}
                    </span>
                    <span class="inline-flex items-center gap-1" :title="$t('Deadline overdue')">
                        <span class="h-2 w-2 rounded-full bg-danger"></span>{{ $t('Deadline overdue') }}
                    </span>
                </div>
            </div>

            <!-- Fehlerzustand -->
            <BaseAlertComponent v-if="errorMessage" type="error" :message="errorMessage" />

            <!-- Leerzustand -->
            <EmptyState
                v-if="!crafts.length"
                icon="IconTableOptions"
                :title="$t('No crafts visible')"
                :description="$t('You have no planning responsibility for any craft. Ask an administrator to assign you to a craft.')"
            />
            <EmptyState
                v-else-if="!filters.craft_ids.length"
                icon="IconFilter"
                :title="$t('No craft selected')"
                :description="$t('Select at least one craft to display the week status.')"
                :action-label="$t('Show all crafts')"
                @action="onCraftsChanged([])"
            />

            <!-- Tabelle -->
            <div v-else class="relative overflow-x-auto rounded-2xl border border-border-subtle bg-white shadow-sm">
                <div
                    v-if="loading"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-white/60"
                    aria-live="polite"
                >
                    <div class="flex items-center gap-2 text-sm text-text-muted">
                        <BaseSkeleton variant="circle" width="w-4" height="h-4" />
                        {{ $t('Loading week status...') }}
                    </div>
                </div>

                <table class="min-w-full border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr>
                            <th
                                scope="col"
                                class="sticky left-0 z-[5] min-w-[220px] border-b border-r border-border-subtle bg-surface-sunken px-3 py-2 text-left text-xs font-medium text-text-muted"
                            >
                                {{ $t('Craft') }}
                            </th>
                            <th
                                v-for="week in weeks"
                                :key="week.key"
                                scope="col"
                                :class="[
                                    'min-w-[150px] border-b border-border-subtle px-2 py-2 text-left align-top',
                                    isCurrentWeek(week) ? 'bg-accent-50/60' : 'bg-surface-sunken',
                                ]"
                            >
                                <div class="text-xs font-semibold text-text">
                                    KW {{ week.week_number }}
                                    <span v-if="isCurrentWeek(week)" class="ml-1 rounded-full bg-accent-100 px-1.5 py-px text-[10px] font-medium text-accent-700">
                                        {{ $t('Current') }}
                                    </span>
                                </div>
                                <div class="text-[11px] font-normal text-text-subtle">
                                    {{ week.monday_formatted }} – {{ week.sunday_formatted }}
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="craft in visibleRows" :key="craft.id" class="group/row">
                            <th
                                scope="row"
                                class="sticky left-0 z-[4] border-b border-r border-border-subtle bg-white px-3 py-2 text-left align-top font-normal group-hover/row:bg-surface-sunken/60"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold text-white"
                                        :style="{ backgroundColor: craft.color || '#4f46e5' }"
                                    >
                                        {{ craft.abbreviation }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-text">{{ craft.name }}</div>
                                        <div class="text-[11px] text-text-subtle">
                                            <template v-if="craft.commit_request_deadline_days !== null && craft.commit_request_deadline_days !== undefined">
                                                {{ $t('Request deadline') }}: {{ craft.commit_request_deadline_days }} {{ $t('days before week start') }}
                                            </template>
                                            <template v-else>
                                                {{ $t('No deadline') }}
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </th>
                            <td
                                v-for="week in weeks"
                                :key="week.key"
                                :class="[
                                    'border-b border-border-subtle p-1 align-top',
                                    isCurrentWeek(week) ? 'bg-accent-50/30' : '',
                                ]"
                            >
                                <WeekStatusCell
                                    :cell="cellFor(craft.id, week.key)"
                                    :craft-name="craft.name"
                                    @select="openDetails(craft, week)"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th
                                scope="row"
                                class="sticky left-0 z-[4] border-r border-t border-border-subtle bg-surface-sunken px-3 py-2 text-left text-xs font-medium text-text-muted"
                            >
                                {{ $t('Total per week') }}
                            </th>
                            <td
                                v-for="week in weeks"
                                :key="week.key"
                                class="border-t border-border-subtle bg-surface-sunken px-2 py-2 align-top text-[11px] leading-4 text-text-muted"
                            >
                                <div :title="$t('Crafts committed')">
                                    <span class="font-medium text-text">{{ summaryFor(week.key).crafts_committed }}/{{ summaryFor(week.key).crafts_with_shifts }}</span>
                                    {{ $t('crafts committed') }}
                                </div>
                                <div class="mt-0.5 flex flex-wrap gap-x-2">
                                    <span
                                        :class="['inline-flex items-center gap-0.5', summaryFor(week.key).open_violations > 0 ? 'text-danger font-medium' : '']"
                                        :title="$t('Open rule violations')"
                                    >
                                        <IconAlertTriangle class="h-3 w-3" stroke-width="1.5" />
                                        {{ summaryFor(week.key).open_violations }}
                                    </span>
                                    <span
                                        :class="['inline-flex items-center gap-0.5', summaryFor(week.key).open_slots > 0 ? 'text-text font-medium' : '']"
                                        :title="$t('Open slots / demand')"
                                    >
                                        <IconUsers class="h-3 w-3" stroke-width="1.5" />
                                        {{ summaryFor(week.key).open_slots }}/{{ summaryFor(week.key).required_slots }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <WeekStatusDetailModal
            v-if="selected"
            :cell="selected.cell"
            :week="selected.week"
            :craft="selected.craft"
            :workflow-enabled="workflowEnabled"
            :can-commit="canCommit"
            :can-approve-requests="canApproveRequests"
            :can-see-violations="canSeeViolations"
            :can-see-change-list="canSeeChangeList"
            @close="selected = null"
            @changed="onChanged"
        />
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { IconAlertTriangle, IconUsers } from '@tabler/icons-vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import EmptyState from '@/Artwork/Feedback/EmptyState.vue'
import BaseSkeleton from '@/Artwork/Feedback/BaseSkeleton.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import WeekStatusCell from '@/Pages/Shifts/WeekStatus/WeekStatusCell.vue'
import WeekStatusDetailModal from '@/Pages/Shifts/WeekStatus/WeekStatusDetailModal.vue'
import { STATUS_ORDER, statusMeta } from '@/Pages/Shifts/WeekStatus/weekStatus.js'
import { toYmd } from '@/Helper/IsoWeek.js'

const props = defineProps({
    weeks: { type: Array, default: () => [] },
    rows: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
    crafts: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ from: null, to: null, weeks: 8, craft_ids: [] }) },
    maxWeeks: { type: Number, default: 26 },
    workflowEnabled: { type: Boolean, default: false },
    canCommit: { type: Boolean, default: false },
    canPlan: { type: Boolean, default: false },
    canApproveRequests: { type: Boolean, default: false },
    canSeeViolations: { type: Boolean, default: false },
})

const { t } = useI18n()
const page = usePage()

const weekOptions = [4, 8, 13, 26]
const legendStatuses = STATUS_ORDER.filter((status) => status !== 'none')

const canSeeChangeList = computed(() => !!page.props?.canSeeShiftPlanChangeList)

const helpText = computed(() => [
    `${t('Open')}: ${t('Shifts exist, nothing is committed and no request has been submitted.')}`,
    `${t('Requested')}: ${t('An approval request is pending.')}`,
    `${t('Partially committed')}: ${t('Some shifts of this week are committed, others are not.')}`,
    `${t('Committed')}: ${t('All shifts of this week are committed.')}`,
    `${t('Rejected')}: ${t('The last request was rejected and nothing has been resubmitted.')}`,
    t('The deadline marker refers to the request deadline of the craft (days before the week starts).'),
].join(' · '))

const loading = ref(false)
const errorMessage = ref('')
const selected = ref(null)

const selectedCrafts = computed(() => {
    const ids = new Set((props.filters?.craft_ids ?? []).map(Number))
    return props.crafts.filter((craft) => ids.has(Number(craft.id)))
})

const visibleRows = computed(() => {
    const ids = new Set((props.filters?.craft_ids ?? []).map(Number))
    return props.crafts.filter((craft) => ids.has(Number(craft.id)))
})

const craftFormatter = (items) => {
    if (!items?.length || items.length === props.crafts.length) return t('All crafts')
    if (items.length <= 2) return items.map((item) => item.name).join(', ')
    return `${items.length} ${t('crafts')}`
}

const firstWeek = computed(() => props.weeks[0] ?? null)
const lastWeek = computed(() => props.weeks[props.weeks.length - 1] ?? null)

const rangeLabel = computed(() => {
    if (!firstWeek.value || !lastWeek.value) return ''
    return `KW ${firstWeek.value.week_number} / ${firstWeek.value.year} – KW ${lastWeek.value.week_number} / ${lastWeek.value.year} (${firstWeek.value.monday_formatted} – ${lastWeek.value.sunday_formatted})`
})

const todayYmd = toYmd(new Date())
const isCurrentWeek = (week) => week.monday <= todayYmd && todayYmd <= week.sunday

const cellFor = (craftId, weekKey) => props.rows?.[craftId]?.[weekKey] ?? null
const summaryFor = (weekKey) => props.summary?.[weekKey] ?? {
    crafts_with_shifts: 0,
    crafts_committed: 0,
    open_violations: 0,
    open_slots: 0,
    required_slots: 0,
}

// Navigation: Query-Parameter, Seite wird per Inertia neu geladen (State bleibt erhalten)
const navigate = (overrides = {}) => {
    const params = {
        from: props.filters.from,
        weeks: props.filters.weeks,
        craft_ids: props.filters.craft_ids,
        ...overrides,
    }
    // „Alle Gewerke" = kein craft_ids-Parameter (Backend nimmt alle sichtbaren)
    if (!params.craft_ids?.length || params.craft_ids.length === props.crafts.length) {
        delete params.craft_ids
    }
    loading.value = true
    errorMessage.value = ''
    router.get(route('shifts.week-status'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onError: (errors) => {
            const messages = Object.values(errors || {}).flat().filter(Boolean)
            errorMessage.value = messages[0] || t('The week status could not be loaded.')
        },
        onFinish: () => {
            loading.value = false
        },
    })
}

const addDays = (ymd, days) => {
    const [year, month, day] = String(ymd).split('-').map(Number)
    const date = new Date(year, month - 1, day)
    date.setDate(date.getDate() + days)
    return toYmd(date)
}

const shiftRange = (weeks) => navigate({ from: addDays(props.filters.from, weeks * 7) })

const goToToday = () => {
    const today = new Date()
    const dayOfWeek = today.getDay()
    const monday = new Date(today)
    monday.setDate(today.getDate() - (dayOfWeek === 0 ? 6 : dayOfWeek - 1))
    navigate({ from: toYmd(monday) })
}

const setWeeks = (weeks) => navigate({ weeks: Math.min(props.maxWeeks, weeks) })

const onCraftsChanged = (items) => {
    navigate({ craft_ids: (items ?? []).map((item) => Number(item.id)) })
}

const openDetails = (craft, week) => {
    const cell = cellFor(craft.id, week.key)
    if (!cell) return
    selected.value = { craft, week, cell }
}

// Nach Aktionen (Anfrage / Festschreiben): Seite neu laden, Modal schließen
const onChanged = () => {
    selected.value = null
    loading.value = true
    router.reload({
        preserveScroll: true,
        onFinish: () => {
            loading.value = false
        },
    })
}
</script>
