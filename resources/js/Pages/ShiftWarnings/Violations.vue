<template>
    <ShiftSettingsHeader
        :title="$t('Open violations')"
        :description="$t('Overview of all open shift rule violations.')"
    >
        <SettingsGuideBanner
            storage-key="settings-guide.shift.violations"
            title="How to handle open violations"
            class="mb-6"
            :paragraphs="[
                'This list collects all violations detected by the shift rule check. You have three ways to handle them:',
                '\'Resolve\' marks a violation as done without further consequences.',
                '\'Ignore\' discards it with a documented reason that remains visible on the violation.',
                '\'Edit\' lets you set substitute days off that are credited directly to the person\'s hour account.'
            ]"
        />

        <!-- Filterleiste: Zeitraum, Gewerke, Person, Regel, Schwere, Status, Sortierung — in der URL gehalten -->
        <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5 space-y-4">
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-40">
                    <BaseInput
                        id="violations-date-from"
                        v-model="filterState.date_from"
                        type="date"
                        :label="$t('From')"
                        no-margin-top
                        @change="applyFilters"
                    />
                </div>
                <div class="w-40">
                    <BaseInput
                        id="violations-date-to"
                        v-model="filterState.date_to"
                        type="date"
                        :label="$t('To')"
                        no-margin-top
                        @change="applyFilters"
                    />
                </div>
                <!-- Server hat "bis" auf von + 1 Jahr begrenzt (Liste antwortet nie mit 422) -->
                <p v-if="filters?.period_clamped" class="self-end pb-2 text-xs text-warning" role="status">
                    {{ $t('The period was limited to one year.') }}
                </p>
                <div class="min-w-[13rem]">
                    <ArtworkBaseListbox
                        v-model="selectedCrafts"
                        :items="crafts"
                        multiple
                        :use-translations="false"
                        :label="$t('Crafts')"
                        :placeholder="$t('All crafts')"
                        :empty-text="$t('No options available')"
                        option-label="name"
                        option-key="id"
                        @change="applyFilters"
                    />
                </div>
                <div class="min-w-[12rem]">
                    <SearchableSelect
                        v-model="filterState.user_id"
                        :options="users"
                        value-key="id"
                        :label-key="user => `${user.last_name}, ${user.first_name}`"
                        :empty-option="{ label: 'All persons', value: null }"
                        :placeholder="$t('All persons')"
                        :label="$t('Person')"
                        @change="applyFilters"
                    />
                </div>
                <div class="min-w-[12rem]">
                    <SearchableSelect
                        v-model="filterState.shift_rule_id"
                        :options="rules"
                        value-key="id"
                        label-key="name"
                        :empty-option="{ label: 'All rules', value: null }"
                        :placeholder="$t('All rules')"
                        :label="$t('Rule')"
                        @change="applyFilters"
                    />
                </div>
                <div class="min-w-[9rem]">
                    <SearchableSelect
                        v-model="filterState.severity"
                        :options="severityOptions"
                        value-key="value"
                        label-key="label"
                        translate-option-labels
                        :empty-option="{ label: 'All', value: null }"
                        :placeholder="$t('All')"
                        :label="$t('Severity')"
                        @change="applyFilters"
                    />
                </div>
                <div class="min-w-[9rem]">
                    <SearchableSelect
                        v-model="filterState.status"
                        :options="statusOptions"
                        value-key="value"
                        label-key="label"
                        translate-option-labels
                        :label="$t('Status')"
                        @change="applyFilters"
                    />
                </div>
                <div class="min-w-[10rem]">
                    <SearchableSelect
                        v-model="filterState.sort"
                        :options="sortOptions"
                        value-key="value"
                        label-key="label"
                        translate-option-labels
                        :label="$t('Sort')"
                        @change="applyFilters"
                    />
                </div>
                <BaseUIButton
                    v-if="hasActiveFilters"
                    :label="$t('Reset filters')"
                    is-cancel-button
                    @click="resetFilters"
                />
                <div class="ml-auto flex items-center gap-2">
                    <!-- Direkter Export mit den aktuell gesetzten Filtern (ohne Zeitraum: aktueller Monat) -->
                    <a :href="exportUrl" :title="exportTooltip">
                        <BaseUIButton :label="$t('Export as Excel')" :icon="IconFileSpreadsheet" />
                    </a>
                    <ToolTipComponent
                        direction="left"
                        :tooltip-text="$t('Open export dialog')"
                        icon="IconFileExport"
                        icon-size="h-5 w-5"
                        classes-button="p-2 rounded-lg hover:bg-surface-sunken transition-colors"
                        @click="showExportModal = true"
                    />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Zähler-Chips (aktive Verstöße über alle Seiten, unabhängig vom Statusfilter) -->
                <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-sunken px-3 py-1 text-xs font-medium text-text-muted">
                    {{ $t('Active') }}: <span class="font-semibold text-text tabular-nums">{{ counters.total }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-danger-surface px-3 py-1 text-xs font-medium text-danger">
                    {{ $t('Errors') }}: <span class="font-semibold tabular-nums">{{ counters.error }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-warning-surface px-3 py-1 text-xs font-medium text-warning">
                    {{ $t('Warnings') }}: <span class="font-semibold tabular-nums">{{ counters.warning }}</span>
                </span>
                <p v-if="!filterState.date_from && !filterState.date_to" class="text-xs text-text-subtle">
                    {{ $t('Without a period, all violations of the selected status are listed.') }}
                </p>
            </div>
        </div>

        <!-- Sammelaktion -->
        <div
            v-if="selectedIds.length"
            class="mt-4 flex flex-wrap items-center gap-3 rounded-lg border border-accent-200 bg-accent-50 px-4 py-2.5"
        >
            <span class="text-sm font-medium text-accent-700">
                {{ $t('{count} selected', { count: selectedIds.length }) }}
            </span>
            <span v-if="selectedIds.length > bulkIgnoreLimit" class="text-xs text-danger">
                {{ $t('At most {limit} violations can be ignored at once.', { limit: bulkIgnoreLimit }) }}
            </span>
            <div class="ml-auto flex items-center gap-2">
                <BaseUIButton
                    :label="$t('Ignore')"
                    icon="IconEyeOff"
                    is-delete-button
                    :disabled="selectedIds.length > bulkIgnoreLimit"
                    @click="showBulkIgnoreModal = true"
                />
                <BaseUIButton :label="$t('Clear selection')" is-cancel-button @click="clearSelection" />
            </div>
        </div>

        <div class="mt-4 rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
            <div v-if="rows.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                <IconShieldCheck class="h-10 w-10 text-success mb-3" stroke-width="1.5" />
                <p class="text-sm font-medium text-text">
                    {{ hasActiveFilters ? $t('No violations match the current filters.') : $t('No open violations — all clear.') }}
                </p>
                <p class="mt-1 text-xs text-text-subtle max-w-md">
                    {{ hasActiveFilters
                        ? $t('Adjust or reset the filters to see more entries.')
                        : $t('New violations appear here automatically as soon as the rule check detects one in the shift plan.') }}
                </p>
                <BaseUIButton
                    v-if="hasActiveFilters"
                    class="mt-4"
                    :label="$t('Reset filters')"
                    is-cancel-button
                    @click="resetFilters"
                />
            </div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border-subtle">
                    <thead class="bg-surface-sunken">
                        <tr>
                            <th class="px-4 py-3 w-8">
                                <BaseCheckbox
                                    id="violations-select-page"
                                    :model-value="pageSelectionState"
                                    @update:model-value="togglePageSelection"
                                />
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Employee') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Rule') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Date') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Severity') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Status') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Measured value') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Details') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-border-subtle">
                        <tr v-for="violation in rows" :key="violation.id" :class="isSelected(violation.id) ? 'bg-accent-50/40' : ''">
                            <td class="px-4 py-3">
                                <BaseCheckbox
                                    :id="`violation-select-${violation.id}`"
                                    :model-value="isSelected(violation.id)"
                                    :disabled="violation.status !== 'active'"
                                    @update:model-value="(checked) => toggleSelection(violation, checked)"
                                />
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-text">
                                {{ violation.user_name }}
                                <div v-if="violation.user_crafts?.length" class="text-[11px] font-normal text-text-subtle">
                                    {{ violation.user_crafts.join(', ') }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-text-subtle">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-block h-3 w-3 rounded-full shrink-0"
                                        :style="{ backgroundColor: violation.warning_color || '#ff0000' }"
                                    ></span>
                                    {{ violation.display_name || violation.rule_name }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-text-subtle">
                                {{ formatDate(violation.violation_date) }}
                                <div v-if="violation.shift" class="text-[11px] text-text-subtle">
                                    {{ violation.shift.start }} – {{ violation.shift.end }}<span v-if="violation.shift.room"> · {{ violation.shift.room }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <span
                                    :class="violation.severity === 'error' ? 'bg-danger-surface text-danger' : 'bg-warning-surface text-warning'"
                                    class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded-full"
                                >
                                    {{ violation.severity === 'error' ? $t('Error') : $t('Warning') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <span
                                    class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded-full"
                                    :class="statusClass(violation.status)"
                                >
                                    {{ statusLabel(violation.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-text-subtle">
                                {{ formatViolationMeasure(violation, $t) || '–' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-text-subtle max-w-xs truncate" :title="violation.message">
                                {{ violation.message }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                <BaseMenu white-menu-background has-no-offset>
                                    <BaseMenuItem
                                        v-if="violation.status !== 'ignored'"
                                        white-menu-background
                                        :title="$t('Edit')"
                                        @click="openEditModal(violation)"
                                    />
                                    <BaseMenuItem
                                        v-if="violation.status === 'active'"
                                        white-menu-background
                                        :title="$t('Resolve')"
                                        @click="resolveViolation(violation)"
                                    />
                                    <BaseMenuItem
                                        v-if="violation.status === 'active'"
                                        white-menu-background
                                        :title="$t('Ignore')"
                                        @click="ignoreViolation(violation)"
                                    />
                                </BaseMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="violations.total > 0" class="mt-4">
                <BasePaginator
                    :entities="violations"
                    property-name="violations"
                    emit-update-entities-per-page
                    @update-page="goToPage"
                    @update-entities-per-page="changePerPage"
                />
            </div>
        </div>

        <ViolationEditModal
            v-if="selectedViolation"
            :violation="selectedViolation"
            @close="selectedViolation = null"
            @updated="onViolationUpdated"
        />

        <IgnoreViolationModal
            v-if="violationToIgnore"
            :violation="violationToIgnore"
            @close="violationToIgnore = null"
            @ignored="onViolationIgnored"
        />

        <!-- Sammelaktion: Ignorieren mit Grund -->
        <ArtworkBaseModal
            v-if="showBulkIgnoreModal"
            :title="$t('Ignore rule violation')"
            :description="$t('{count} selected', { count: selectedIds.length })"
            @close="showBulkIgnoreModal = false"
        >
            <div class="space-y-4 text-sm">
                <p class="text-xs text-text-subtle">
                    {{ $t('Please provide a reason for ignoring this violation.') }}
                </p>
                <div>
                    <BaseTextarea
                        id="bulk_ignore_reason"
                        v-model="bulkIgnoreReason"
                        :label="$t('Reason for ignoring')"
                    />
                    <p v-if="bulkIgnoreError && !bulkIgnoreReason.trim()" class="mt-1 text-xs text-danger">
                        {{ $t('Reason for ignoring') }}
                    </p>
                </div>
                <div class="flex justify-between pt-2 border-t border-border-subtle">
                    <BaseUIButton :label="$t('Cancel')" is-cancel-button @click="showBulkIgnoreModal = false" />
                    <BaseUIButton
                        :label="$t('Ignore')"
                        is-delete-button
                        :disabled="bulkIgnoring"
                        :processing="bulkIgnoring"
                        @click="submitBulkIgnore"
                    />
                </div>
            </div>
        </ArtworkBaseModal>

        <ExportModal
            v-if="showExportModal"
            :enums="exportTabs"
            :configuration="exportConfiguration"
            @close="showExportModal = false"
        />
    </ShiftSettingsHeader>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { IconShieldCheck, IconFileSpreadsheet } from '@tabler/icons-vue'
import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import ViolationEditModal from "@/Pages/Shifts/Components/ViolationEditModal.vue";
import IgnoreViolationModal from "@/Pages/Shifts/Components/IgnoreViolationModal.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import SearchableSelect from "@/Artwork/Listbox/SearchableSelect.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import ExportModal from "@/Layouts/Components/Export/Modals/ExportModal.vue";
import { useExportTabEnums } from "@/Layouts/Components/Export/Enums/ExportTabEnum.js";
import { usePermission } from "@/Composeables/Permission.js";
import { useTranslation } from "@/Composeables/Translation.js";
import { formatViolationMeasure } from "@/Pages/ShiftWarnings/ruleTypes.js";

const props = defineProps({
    // Laravel-Paginator (data, total, per_page, current_page, links, …), Einträge bereits gemappt
    violations: { type: Object, default: () => ({ data: [], total: 0, per_page: 50, current_page: 1, links: [] }) },
    counters: { type: Object, default: () => ({ total: 0, error: 0, warning: 0 }) },
    filters: { type: Object, default: () => ({}) },
    perPage: { type: Number, default: 50 },
    crafts: { type: Array, default: () => [] },
    rules: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    bulkIgnoreLimit: { type: Number, default: 200 },
})

const $t = useTranslation()
const { can, hasAdminRole } = usePermission(usePage().props)
const exportTabEnums = useExportTabEnums()

const rows = computed(() => props.violations?.data ?? [])

// Filterzustand aus der URL (Backend gibt die validierten Filter zurück)
const filterState = reactive({
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
    user_id: props.filters?.user_id ?? null,
    shift_rule_id: props.filters?.shift_rule_id ?? null,
    severity: props.filters?.severity ?? null,
    status: props.filters?.status ?? 'active',
    sort: props.filters?.sort ?? 'desc',
})
const selectedCrafts = ref(
    props.crafts.filter((craft) => (props.filters?.craft_ids ?? []).map(Number).includes(Number(craft.id)))
)

// Nach jedem Teil-Reload (preserveState) die Eingaben mit den vom Server bereinigten Filtern abgleichen —
// z. B. zeigt "bis" nach der Begrenzung auf ein Jahr das tatsächlich wirksame Datum.
watch(() => props.filters, (filters) => {
    filterState.date_from = filters?.date_from ?? ''
    filterState.date_to = filters?.date_to ?? ''
    filterState.user_id = filters?.user_id ?? null
    filterState.shift_rule_id = filters?.shift_rule_id ?? null
    filterState.severity = filters?.severity ?? null
    filterState.status = filters?.status ?? 'active'
    filterState.sort = filters?.sort ?? 'desc'
})

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'resolved', label: 'Processed' },
    { value: 'ignored', label: 'Ignored' },
    { value: 'all', label: 'All statuses' },
]
const severityOptions = [
    { value: 'error', label: 'Error' },
    { value: 'warning', label: 'Warning' },
]
const sortOptions = [
    { value: 'desc', label: 'Newest first' },
    { value: 'asc', label: 'Oldest first' },
]

const hasActiveFilters = computed(() =>
    !!(filterState.date_from || filterState.date_to || filterState.user_id || filterState.shift_rule_id
        || filterState.severity || selectedCrafts.value.length || filterState.status !== 'active' || filterState.sort !== 'desc')
)

function filterParams() {
    const params = {}
    if (filterState.date_from) params.date_from = filterState.date_from
    if (filterState.date_to) params.date_to = filterState.date_to
    if (filterState.user_id) params.user_id = filterState.user_id
    if (filterState.shift_rule_id) params.shift_rule_id = filterState.shift_rule_id
    if (filterState.severity) params.severity = filterState.severity
    if (filterState.status && filterState.status !== 'active') params.status = filterState.status
    if (filterState.sort && filterState.sort !== 'desc') params.sort = filterState.sort
    if (selectedCrafts.value.length) params.craft_id = selectedCrafts.value.map((craft) => craft.id)
    if (props.perPage !== 50) params.per_page = props.perPage
    return params
}

// Teil-Reload: Filter-Stammdaten (crafts/rules/users) sind Closure-Props des Controllers und werden
// nur beim Erstaufruf geladen; Paginierung/Filterwechsel holen nur Liste, Zähler und Filterzustand.
const LIST_PROPS = ['violations', 'counters', 'filters', 'perPage']

function visit(extra = {}) {
    clearSelection()
    router.get(route('shift-rules.pending'), { ...filterParams(), ...extra }, {
        only: LIST_PROPS,
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function applyFilters() {
    visit({ page: 1 })
}

function resetFilters() {
    filterState.date_from = ''
    filterState.date_to = ''
    filterState.user_id = null
    filterState.shift_rule_id = null
    filterState.severity = null
    filterState.status = 'active'
    filterState.sort = 'desc'
    selectedCrafts.value = []
    applyFilters()
}

function goToPage(page) {
    visit({ page })
}

// Erlaubte Seitengrößen: 25/50/100 (kleinere Werte des Paginator-Menüs werden auf 25 gehoben)
function changePerPage(perPage) {
    const allowed = [25, 50, 100].includes(Number(perPage)) ? Number(perPage) : 25
    clearSelection()
    router.get(route('shift-rules.pending'), { ...filterParams(), per_page: allowed, page: 1 }, {
        only: LIST_PROPS,
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

// Export mit den aktuell gesetzten Filtern; ohne Zeitraum nutzt der Server den aktuellen Monat
const exportUrl = computed(() => route('shift-rules.violations.export', filterParams()))
const exportTooltip = computed(() => (filterState.date_from || filterState.date_to)
    ? $t('Exports the current filter selection.')
    : $t('Exports the current filter selection; without a period the current month is exported.'))

const showExportModal = ref(false)
const exportTabs = computed(() => {
    const tabs = [exportTabEnums.EXCEL_SHIFT_RULE_VIOLATIONS_EXPORT]
    if (hasAdminRole() || can('can view shift plan')) {
        tabs.push(exportTabEnums.EXCEL_SHIFT_HISTORY_EXPORT)
    }
    return tabs
})
const exportConfiguration = computed(() => ({
    [exportTabEnums.EXCEL_SHIFT_RULE_VIOLATIONS_EXPORT]: {
        crafts: props.crafts,
        filters: {
            craft_ids: selectedCrafts.value.map((craft) => craft.id),
            status: filterState.status,
            severity: filterState.severity,
            date_from: filterState.date_from,
            date_to: filterState.date_to,
            user_id: filterState.user_id,
            shift_rule_id: filterState.shift_rule_id,
        },
    },
    [exportTabEnums.EXCEL_SHIFT_HISTORY_EXPORT]: {
        crafts: props.crafts,
        filters: { start_date: filterState.date_from, end_date: filterState.date_to },
    },
}))

// Auswahl (nur aktive Verstöße) für die Sammelaktion
const selectedIds = ref([])
const isSelected = (id) => selectedIds.value.includes(id)
function toggleSelection(violation, checked) {
    if (checked) {
        if (!isSelected(violation.id)) selectedIds.value = [...selectedIds.value, violation.id]
    } else {
        selectedIds.value = selectedIds.value.filter((id) => id !== violation.id)
    }
}
const selectablePageIds = computed(() => rows.value.filter((v) => v.status === 'active').map((v) => v.id))
const pageSelectionState = computed(() => {
    if (!selectablePageIds.value.length) return false
    const selectedOnPage = selectablePageIds.value.filter((id) => isSelected(id)).length
    if (selectedOnPage === 0) return false
    return selectedOnPage === selectablePageIds.value.length ? true : 'indeterminate'
})
function togglePageSelection(checked) {
    if (checked === true) {
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...selectablePageIds.value]))
    } else {
        selectedIds.value = selectedIds.value.filter((id) => !selectablePageIds.value.includes(id))
    }
}
function clearSelection() {
    selectedIds.value = []
}

const showBulkIgnoreModal = ref(false)
const bulkIgnoreReason = ref('')
const bulkIgnoreError = ref(false)
const bulkIgnoring = ref(false)
function submitBulkIgnore() {
    bulkIgnoreError.value = true
    if (!bulkIgnoreReason.value.trim() || bulkIgnoring.value) return
    bulkIgnoring.value = true
    router.post(route('shift-rule-violations.bulk-ignore'), {
        ids: selectedIds.value.slice(0, props.bulkIgnoreLimit),
        ignore_reason: bulkIgnoreReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showBulkIgnoreModal.value = false
            bulkIgnoreReason.value = ''
            bulkIgnoreError.value = false
            clearSelection()
        },
        onFinish: () => { bulkIgnoring.value = false },
    })
}

const selectedViolation = ref(null)
const violationToIgnore = ref(null)

function formatDate(date) {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('de-DE')
}

function statusLabel(status) {
    if (status === 'resolved') return $t('Processed')
    if (status === 'ignored') return $t('Ignored')
    return $t('Active')
}

function statusClass(status) {
    if (status === 'resolved') return 'bg-success-surface text-success'
    if (status === 'ignored') return 'bg-surface-sunken text-text-muted'
    return 'bg-accent-50 text-accent-700'
}

function openEditModal(violation) {
    selectedViolation.value = violation
}

function resolveViolation(violation) {
    router.post(route('shift-rule-violations.resolve', { violation: violation.id }), {}, {
        preserveScroll: true,
    })
}

function ignoreViolation(violation) {
    violationToIgnore.value = violation
}

function onViolationUpdated() {
    selectedViolation.value = null
    router.reload({ only: LIST_PROPS })
}

function onViolationIgnored() {
    violationToIgnore.value = null
    router.reload({ only: LIST_PROPS })
}
</script>
