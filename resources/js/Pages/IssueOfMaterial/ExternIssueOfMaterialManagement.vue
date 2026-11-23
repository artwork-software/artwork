<template>
    <AppLayout :title="$t('External material issues')">
        <div class="artwork-container-fluid">


            <ToolbarHeader title="External material issues"
                           description="Track and filter external issues, returns and recipients."
                           :icon="IconMenu4"
                           band
            >
                <template #actions>
                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Excel-Export')"
                        icon="IconFileSpreadsheet"
                        icon-size="h-5 w-5"
                        white-icon
                        classes-button="select-none size-[30px] min-h-0 p-0 inline-flex items-center justify-center rounded-md bg-white/8 hover:bg-white/16 cursor-pointer transition-[background-color] duration-150 ease-out"
                        @click="exportIssues()"
                    />
                    <BaseUIButton variant="primary" on-band hide-icon @click="openIssueOfMaterialModal">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('New issue of material') }}
                    </BaseUIButton>
                </template>

            </ToolbarHeader>


            <div class="mt-2">
                <IssueTabs />
            </div>

            <!-- Artikel-Chips -->
            <div v-if="articleNamesForFilter.length" class="mb-3">
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="(article, index) in articleNamesForFilter"
                        :key="index"
                        class="inline-flex items-center rounded-full border border-info-border bg-info-surface/70 px-2.5 py-0.5 text-sm text-info ring-1 ring-inset ring-info-border"
                    >
                        <span class="truncate max-w-[220px]">{{ article.name }}</span>
                        <button type="button" class="ml-2 text-info hover:text-info" @click="articleNamesForFilter.splice(index, 1)">
                            <component :is="IconX" class="size-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filterleiste: eine kompakte Zeile, Werte stehen direkt in den Feldern -->
            <div class="ui-card flex flex-wrap items-center gap-2">
                <BaseInput
                    id="extern-issue-filter-search"
                    v-model="filters.q"
                    label="Search issue/external name …"
                    :show-label="false"
                    :placeholder="$t('Search issue/external name …')"
                    class="w-full sm:w-60"
                />
                <!-- Filter nach enthaltenen Artikeln: gewählte Artikel stehen als Chips über der Leiste -->
                <ArticleSearch
                    id="extern-issue-filter-article"
                    label="Filter by article"
                    compact
                    class="w-full sm:w-52"
                    @article-selected="addArticleNameToFilter"
                />
                <button
                    type="button"
                    class="ui-button sm:hidden!"
                    :aria-expanded="showFilterControls"
                    @click="showFilterControls = !showFilterControls"
                >
                    <component :is="IconFilter" class="size-4" stroke-width="1.5"/>
                    {{ $t('Filters') }}
                    <span v-if="activeFilterCount" class="rounded-full bg-accent-50 px-1.5 text-[11px] text-accent-700">{{ activeFilterCount }}</span>
                </button>
                <div :class="showFilterControls ? 'flex' : 'hidden'" class="w-full flex-wrap items-center gap-2 sm:contents">
                    <IssueDateRangeFilter
                        v-model:from="filters.date_from"
                        v-model:to="filters.date_to"
                        id-prefix="extern-issue-filter"
                    />
                    <div class="w-full sm:w-52" :title="$t('Issued by')">
                        <SearchableSelect
                            v-model="filters.issued_by_id"
                            :options="users"
                            value-key="id"
                            :label-key="userDisplay"
                            :empty-option="{ label: 'Issued by', value: '' }"
                            :placeholder="$t('Issued by')"
                        />
                    </div>
                    <div class="w-full sm:w-52" :title="$t('Received by')">
                        <SearchableSelect
                            v-model="filters.received_by_id"
                            :options="users"
                            value-key="id"
                            :label-key="userDisplay"
                            :empty-option="{ label: 'Received by', value: '' }"
                            :placeholder="$t('Received by')"
                        />
                    </div>
                    <!-- Rückgabestatus: eine Auswahl statt nur "überfällig" -->
                    <div class="inline-flex items-center gap-1 rounded-lg border border-border-subtle p-0.5" role="group" :aria-label="$t('Return status')">
                        <button v-for="option in returnStateOptions" :key="option.value ?? 'all'"
                                type="button"
                                class="rounded-md px-2.5 py-1 text-xs transition"
                                :class="filters.return_state === option.value ? 'bg-accent-50 font-semibold text-accent-700' : 'text-text-muted hover:bg-surface-sunken'"
                                :aria-pressed="filters.return_state === option.value"
                                :title="option.value === 'overdue' ? $t('Shows issues whose return date has passed and for which no return has been entered.') : null"
                                @click="filters.return_state = option.value">
                            {{ $t(option.label) }}
                        </button>
                    </div>
                    <button
                        type="button"
                        class="ui-button"
                        :class="filters.special_open ? 'border-danger-border! bg-danger-surface! text-danger!' : ''"
                        :aria-pressed="filters.special_open"
                        @click="filters.special_open = !filters.special_open"
                    >
                        <component :is="IconAlertTriangle" class="size-4" stroke-width="1.5"/>
                        {{ $t('Special items open') }}
                    </button>
                </div>
                <button
                    v-if="hasAnyFilter"
                    type="button"
                    class="ui-button sm:ml-auto!"
                    @click="resetFilters"
                >
                    <component :is="IconFilterOff" class="size-4" stroke-width="1.5"/>
                    {{ $t('Reset filters') }}
                </button>
            </div>

            <!-- Tabelle -->
            <div class="mt-3">
                <div class="grid grid-cols-12 gap-4 px-2 py-2 text-[11px] font-semibold uppercase tracking-wide text-text-subtle">
                    <div class="col-span-3 flex items-center gap-2">
                        <input type="checkbox"
                               class="h-4 w-4 shrink-0 cursor-pointer rounded border-border text-accent-600 focus:ring-accent-600"
                               :checked="allOnPageSelected"
                               :indeterminate.prop="selectedCount > 0 && !allOnPageSelected"
                               :aria-label="$t('Select all')"
                               :title="$t('Select all')"
                               @change="selectPage($event.target.checked)"/>
                        <IssueSortHeader label="Name" sort-key="name" :sort="sorting.sort" :direction="sorting.direction" @sort="sortBy"/>
                    </div>
                    <div class="col-span-1 min-w-0">
                        <IssueSortHeader label="Material value" sort-key="material_value" :sort="sorting.sort" :direction="sorting.direction" @sort="sortBy"/>
                    </div>
                    <div class="col-span-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <IssueSortHeader label="Issue date" sort-key="issue_date" :sort="sorting.sort" :direction="sorting.direction" @sort="sortBy"/>
                        <IssueSortHeader label="Return date" sort-key="return_date" :sort="sorting.sort" :direction="sorting.direction" @sort="sortBy"/>
                    </div>
                    <div class="col-span-1">{{ $t('Issued by') }}</div>
                    <div class="col-span-2">
                        <IssueSortHeader label="External name" sort-key="external_name" :sort="sorting.sort" :direction="sorting.direction" @sort="sortBy"/>
                    </div>
                    <div class="col-span-1">{{ $t('Files') }}</div>
                    <div class="col-span-1">{{ $t('Received by') }}</div>
                    <div class="col-span-1 text-right">{{ $t('Status') }}</div>
                </div>
                <div class="border-y border-border-subtle"></div>
            </div>

            <!-- Rows -->
            <div>
                <template v-if="issues?.data?.length">
                    <SingleExternMaterialIssue
                        v-for="issueOfMaterial in issues.data"
                        :key="issueOfMaterial.id"
                        :extern-material-issue="issueOfMaterial"
                        :detailed-article="detailedArticle"
                        selectable
                        :selected="selectedIds[issueOfMaterial.id] === true"
                        :first-project-tab-id="first_project_tab_id"
                        @toggle-select="toggleSelected(issueOfMaterial.id, $event)"
                    />
                </template>
                <div v-else class="mt-6">
                    <BaseAlertComponent message="No issues of material found" type="info" use-translation />
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                <BasePaginator property-name="issues" :entities="issues" />
            </div>
        </div>

        <IssueBulkBar
            :count="selectedCount"
            :processing="bulkDeleting"
            @print="printSelected"
            @export="exportIssues(true)"
            @delete="showBulkDeleteModal = true"
            @clear="selectedIds = {}"
        />
        <ConfirmDeleteModal
            v-if="showBulkDeleteModal"
            :title="$t('Delete issue of material')"
            :description="selectedCount === 1 ? $t('Are you sure you want to delete this issue of material?') : $t('Do you really want to delete the {count} selected issues of material?', { count: selectedCount })"
            @closed="showBulkDeleteModal = false"
            @delete="bulkDelete"
        />

        <!-- Modal -->
        <issue-of-material-modal
            v-if="showIssueOfMaterialModal"
            :issue-of-material="null"
            :is-extern-or-intern="true"
            @close="showIssueOfMaterialModal = false"
        />
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import ArticleSearch from "@/Components/SearchBars/ArticleSearch.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import IssueTabs from "@/Pages/IssueOfMaterial/Components/IssueTabs.vue";
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import SingleExternMaterialIssue from "@/Pages/IssueOfMaterial/Components/SingleExternMaterialIssue.vue";
import { router, usePage } from "@inertiajs/vue3";
import { computed, provide, ref, watch } from "vue";
import { can, is } from "laravel-permission-to-vuejs";
import {IconAlertTriangle, IconCirclePlus, IconFilter, IconFilterOff, IconMenu4, IconX} from "@tabler/icons-vue";
import axios from "axios";
import {toastValidationFailure} from "@/Helper/appToast";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import IssueSortHeader from "@/Pages/IssueOfMaterial/Components/IssueSortHeader.vue";
import IssueBulkBar from "@/Pages/IssueOfMaterial/Components/IssueBulkBar.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {
    allIssuesSelected,
    compactIssueQuery,
    nextIssueSort,
    togglePageSelection
} from "@/Pages/IssueOfMaterial/Components/issueListTools.js";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import SearchableSelect from "@/Artwork/Listbox/SearchableSelect.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import IssueDateRangeFilter from "@/Pages/IssueOfMaterial/Components/IssueDateRangeFilter.vue";

const props = defineProps({
    issues: Object,
    articlesInFilter: { type: Array, default: () => [] },
    materialSets: { type: Object, required: true },
    detailedArticle: Object,
    first_project_tab_id: { type: [Number, String], default: null }
});

provide("materialSets", props.materialSets);

const page = usePage();
const users = computed(() => page.props.users ?? []); // angenommen via Inertia geteilt

const showIssueOfMaterialModal = ref(false);
const openIssueOfMaterialModal = () => { showIssueOfMaterialModal.value = true; };

// Artikel-Filter (Chips)
const articleNamesForFilter = ref(props.articlesInFilter ?? []);
const addArticleNameToFilter = (article) => {
    if (!articleNamesForFilter.value.find(a => a.id === article.id)) {
        articleNamesForFilter.value.push(article);
    }
};
const currentArticleIdsCsv = computed(() =>
    articleNamesForFilter.value?.length ? articleNamesForFilter.value.map(a => a.id).join(',') : (page.props.urlParameters?.article_ids ?? '')
);
const filterIssueByArticleIds = () => {
    router.reload({
        preserveState: true, preserveScroll: true,
        data: { article_ids: currentArticleIdsCsv.value || undefined, page: 1 },
        only: ['issues','articlesInFilter'], replace: true
    });
};
watch(articleNamesForFilter, () => filterIssueByArticleIds(), { deep: true });

// Filter-Status
const initial = page.props.urlParameters ?? {};
const initialOverdue = initial.overdue_only === true || initial.overdue_only === '1' || initial.overdue_only === 1;
const filters = ref({
    date_from: initial.date_from ?? "",
    date_to: initial.date_to ?? "",
    issued_by_id: initial.issued_by_id ?? "",
    received_by_id: initial.received_by_id ?? "",
    // der frühere Schalter "Nur überfällige" (auch im Link der Erinnerung) entspricht dem Status "overdue"
    return_state: initialOverdue ? 'overdue' : (initial.return_state ?? null),
    special_open: initial.special_open === true || initial.special_open === '1' || initial.special_open === 1,
    q: initial.q ?? ""
});

const returnStateOptions = [
    { value: null, label: 'All' },
    { value: 'open', label: 'Issued' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'returned', label: 'Returned' },
];

// Sortierung (Spaltenköpfe): aufsteigend, absteigend, zurück zur Standardreihenfolge
// ohne Parameter ist urlParameters ein leeres Array: initial.sort wäre dann dessen eingebaute Methode sort()
const sorting = ref({ sort: typeof initial.sort === 'string' ? initial.sort : null, direction: initial.direction ?? null });
const sortBy = (key) => {
    sorting.value = nextIssueSort(sorting.value, key);
    applyFilters();
};

/** Aktuelle Filter und Sortierung als Abfrage-Parameter – für Liste und Excel-Export identisch */
const currentQuery = () => compactIssueQuery({
    article_ids: currentArticleIdsCsv.value,
    date_from: filters.value.date_from,
    date_to: filters.value.date_to,
    issued_by_id: filters.value.issued_by_id,
    received_by_id: filters.value.received_by_id,
    return_state: filters.value.return_state,
    special_open: filters.value.special_open,
    q: filters.value.q,
    sort: sorting.value.sort,
    direction: sorting.value.sort ? sorting.value.direction : null,
});

const applyFilters = () => {
    const query = currentQuery();
    // Nicht mehr gesetzte Filter ausdrücklich leeren: reload führt die Daten mit der Adresse zusammen
    const cleared = Object.fromEntries(
        ['article_ids', 'date_from', 'date_to', 'issued_by_id', 'received_by_id', 'return_state', 'overdue_only',
            'special_open', 'q', 'sort', 'direction']
            .filter(key => !(key in query))
            .map(key => [key, undefined])
    );
    router.reload({
        preserveState: true, preserveScroll: true,
        // geänderte Filter oder Sortierung beginnen wieder auf Seite 1
        data: { ...cleared, ...query, page: 1 },
        replace: true,
        only: ['issues','articlesInFilter']
    });
};

// Mehrfachauswahl (bleibt über Seitenwechsel erhalten)
const selectedIds = ref({});
const selectedCount = computed(() => Object.keys(selectedIds.value).length);
const allOnPageSelected = computed(() => allIssuesSelected(props.issues?.data, selectedIds.value));
const toggleSelected = (id, checked) => {
    if (checked) {
        selectedIds.value[id] = true;
    } else {
        delete selectedIds.value[id];
    }
};
const selectPage = (checked) => {
    selectedIds.value = togglePageSelection(props.issues?.data, selectedIds.value, checked);
};
const exportIssues = (onlySelection = false) => {
    const query = onlySelection ? { ids: Object.keys(selectedIds.value).join(',') } : currentQuery();
    window.open(route('extern-issue-of-material.export', query), '_blank', 'noopener');
};
const printSelected = () => {
    window.open(route('extern-issue-of-material.print-many', { ids: Object.keys(selectedIds.value).join(',') }), '_blank', 'noopener');
};
const showBulkDeleteModal = ref(false);
const bulkDeleting = ref(false);
const bulkDelete = () => {
    if (bulkDeleting.value) return;
    bulkDeleting.value = true;
    showBulkDeleteModal.value = false;
    axios.post(route('extern-issue-of-material.bulk-delete'), { ids: Object.keys(selectedIds.value).map(Number) })
        .then(() => {
            selectedIds.value = {};
            router.reload({ only: ['issues'] });
        })
        .catch(toastValidationFailure)
        .finally(() => {
            bulkDeleting.value = false;
        });
};

// Debounce
let debounceTimer = null;
watch(filters, () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters(), 280);
}, { deep: true });

const resetFilters = () => {
    filters.value = { date_from: "", date_to: "", issued_by_id: "", received_by_id: "", return_state: null, special_open: false, q: "" };
    if (articleNamesForFilter.value.length) {
        articleNamesForFilter.value = [];
    }
    applyFilters();
};

const hasAnyFilter = computed(() =>
    !!(filters.value.date_from || filters.value.date_to || filters.value.issued_by_id || filters.value.received_by_id || filters.value.return_state || filters.value.special_open || filters.value.q || articleNamesForFilter.value.length)
);

/** Anzahl gesetzter Filter für das Badge am mobilen Filter-Button */
const activeFilterCount = computed(() =>
    [
        filters.value.date_from || filters.value.date_to,
        filters.value.issued_by_id,
        filters.value.received_by_id,
        filters.value.return_state,
        filters.value.special_open,
    ].filter(Boolean).length
);

/** Mobil sind die Filterfelder hinter einem Button eingeklappt, ab sm immer sichtbar */
const showFilterControls = ref(false);

// Helpers
const userDisplay = (u) => [u.first_name, u.last_name].filter(Boolean).join(' ') || u.name || u.email || `#${u.id}`;
</script>
