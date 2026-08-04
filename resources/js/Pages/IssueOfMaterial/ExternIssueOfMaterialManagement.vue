<template>
    <AppLayout :title="$t('Inventory')">
        <div class="artwork-container">


            <ToolbarHeader title="External material issues"
                           description="Track and filter external issues, returns and recipients."
                           :icon="IconMenu4"
                           icon-bg-class="bg-accent-50 text-accent-700"
            >
                <template #actions>
                    <button class="ui-button-add"  @click="openIssueOfMaterialModal">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('New issue of material') }}
                    </button>
                </template>

            </ToolbarHeader>

            <!-- Header -->
            <!--<div class="flex flex-wrap items-center justify-between gap-4 pt-6 pb-2 hidden">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
            <span class="inline-flex size-6 items-center justify-center rounded-md bg-accent-50 text-accent-700">
              <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.5" d="M4 7h16M4 12h10M4 17h16"/></svg>
            </span>
                        <h1 class="text-2xl font-semibold tracking-tight">{{ $t('External material issues') }}</h1>
                    </div>
                    <div class="mt-2 h-1 w-24 rounded-full bg-gradient-to-r from-accent-600 via-info to-success"></div>
                    <p class="text-sm text-text-subtle mt-2">
                        {{ $t('Track and filter external issues, returns and recipients.') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1 w-96">
                        <ArticleSearch id="article-search" class="w-72" @article-selected="addArticleNameToFilter" />
                        <button
                            type="button"
                            @click="filterIssueByArticleIds"
                            class="p-4 inline-flex items-center justify-center rounded-md border border-border-subtle bg-white hover:bg-surface-sunken focus:outline-none focus:ring-2 focus:ring-accent-600"
                        >
                            <component :is="IconSearch" class="size-5" stroke-width="1.5" />
                        </button>
                    </div>

                    <BaseButton
                        v-if="can('inventory.disposition') || is('artwork admin')"
                        :text="$t('New issue of material')"
                        @click="openIssueOfMaterialModal"
                        class="!bg-accent-600 hover:!bg-accent-700 !text-white !border-transparent"
                    >
                        <component :is="IconCopyPlus" class="size-5 mr-2" />
                    </BaseButton>
                </div>
            </div>-->

            <div class="mt-2">
                <IssueTabs />
            </div>

            <!-- Artikel-Chips -->
            <div v-if="articleNamesForFilter.length" class="mb-3">
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="(article, index) in articleNamesForFilter"
                        :key="index"
                        class="inline-flex items-center rounded-full border border-info-border bg-info-surface px-2.5 py-0.5 text-sm text-info ring-1 ring-inset ring-info-border"
                    >
                        <span class="truncate max-w-[220px]">{{ article.name }}</span>
                        <button type="button" class="ml-2 text-info hover:text-info" @click="articleNamesForFilter.splice(index, 1)">
                            <component :is="IconX" class="size-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sticky Filter Toolbar -->
            <div class="sticky top-0 z-20 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/70">
                <!-- Desktop/Tablet: klassische Toolbar -->
                <div class="ui-card">
                    <div
                        class="flex items-center justify-between cursor-pointer select-none"
                        @click="toggleFilters"
                    >
                        <div :class="filtersCollapsed ? '' : 'pb-3'" class="text-xl sm:text-2xl font-semibold tracking-tight">{{
                                $t('Filters')
                            }}
                        </div>
                        <IconChevronDown
                            class="ml-3 text-lg text-text-subtle transition-transform duration-200 h-5 w-5"
                            :class="{ 'rotate-180': !filtersCollapsed }"
                            aria-hidden="true"
                        />
                    </div>
                    <div v-if="!filtersCollapsed">
                        <div class="glassy card px-4 pb-2 mb-2">
                            <!-- Zeile 1 -->
                            <div class="pt-2 text-sm font-medium text-text">
                                {{ $t('Time') }}
                            </div>
                            <div class="grid grid-cols-12 lg:grid-cols-6 md:grid-cols-2 gap-3 items-end">
                                <!-- Time filters -->
                                <div class="col-span-12 md:col-span-6 lg:col-span-3">
                                    <label class="block text-xs font-medium text-text-muted mb-1">{{
                                            $t('Shortcuts')
                                        }}</label>
                                    <div class="flex flex-wrap gap-1.5">
                                        <button type="button"
                                                class="rounded-md border border-accent-200 bg-accent-50 px-2.5 py-1 text-xs text-accent-700 hover:bg-accent-50 hover:border-accent-200"
                                                @click="setRangeToday">{{ $t('Today') }}
                                        </button>
                                        <button type="button"
                                                class="rounded-md border border-info-border bg-info-surface px-2.5 py-1 text-xs text-info hover:bg-info-surface hover:border-info-border"
                                                @click="setRangeThisWeek">{{ $t('This week') }}
                                        </button>
                                        <button type="button"
                                                class="rounded-md border border-success-border bg-success-surface px-2.5 py-1 text-xs text-success hover:bg-success-surface hover:border-success-border"
                                                @click="setRangeThisMonth">{{ $t('This month') }}
                                        </button>
                                        <button type="button"
                                                class="rounded-md border border-border-subtle bg-white px-2.5 py-1 text-xs text-text-muted hover:bg-surface-sunken"
                                                @click="clearRange">{{ $t('All time') }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Zeitraum -->
                                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                                    <label class="block text-xs font-medium text-text-muted mb-1">{{
                                            $t('Time range')
                                        }}</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="filters.date_from"
                                            type="date"
                                            :max="filters.date_to || undefined"
                                            class="w-full rounded-md border border-border px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-600"
                                        />
                                        <span class="text-text-subtle text-xs">–</span>
                                        <input
                                            v-model="filters.date_to"
                                            type="date"
                                            :min="filters.date_from || undefined"
                                            class="w-full rounded-md border border-border px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-600"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="!filtersCollapsed" class="glassy card px-4 pb-2 mb-2">
                        <div class="pt-2 pb-1 text-sm font-medium text-text">
                            {{ $t('Issued by') }}
                        </div>
                        <div class="grid grid-cols-12 lg:grid-cols-6 md:grid-cols-2 gap-3 items-end">
                            <div class="col-span-12 md:col-span-6 lg:col-span-3">
                                <SearchableSelect
                                    v-model="filters.issued_by_id"
                                    :options="users"
                                    value-key="id"
                                    :label-key="userDisplay"
                                    :empty-option="{ label: 'All', value: '' }"
                                    :placeholder="$t('All')"
                                />
                            </div>
                        </div>
                    </div>
                    <div v-if="!filtersCollapsed" class="glassy card px-4 py-3 mb-2">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                v-model="filters.overdue_only"
                                type="checkbox"
                                class="mt-0.5 size-4 rounded border-border text-accent-600 focus:ring-accent-600"
                            >
                            <span>
                                <span class="block text-sm font-medium text-text">{{ $t('Only overdue returns') }}</span>
                                <span class="block text-xs text-text-subtle">{{ $t('Shows issues whose return date has passed and for which no return has been entered.') }}</span>
                            </span>
                        </label>
                    </div>
                    <div v-if="!filtersCollapsed" class="glassy card px-4 pb-2 mb-2">
                        <div class="pt-2 pb-1 text-sm font-medium text-text">
                            {{ $t('Received by') }}
                        </div>
                        <div class="grid grid-cols-12 lg:grid-cols-6 md:grid-cols-2 gap-3 items-end">
                            <div class="col-span-12 md:col-span-6 lg:col-span-3">
                                <SearchableSelect
                                    v-model="filters.received_by_id"
                                    :options="users"
                                    value-key="id"
                                    :label-key="userDisplay"
                                    :empty-option="{ label: 'All', value: '' }"
                                    :placeholder="$t('All')"
                                />
                            </div>
                        </div>
                    </div>
                    <div v-if="!filtersCollapsed" class="glassy card px-4 pb-2 mb-2">
                        <div class="pt-2 pb-1 text-sm font-medium text-text">
                            {{ $t('Name search') }}
                        </div>
                        <div class="grid grid-cols-12 lg:grid-cols-6 md:grid-cols-2 gap-3 items-end">
                            <div class="col-span-12 md:col-span-6 lg:col-span-3">
                                <div class="relative">
                                    <input
                                        v-model="filters.q"
                                        type="text"
                                        :placeholder="$t('Search issue/external name …')"
                                        class="w-full rounded-md border border-border pl-8 pr-8 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-600"
                                    />
                                    <span class="pointer-events-none absolute left-2 top-1.5 text-text-subtle">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                          <circle cx="11" cy="11" r="7" stroke-width="1.5"></circle>
                                          <path d="M20 20l-3.5-3.5" stroke-width="1.5"></path>
                                        </svg>
                                      </span>
                                    <button
                                        v-if="filters.q"
                                        type="button"
                                        class="absolute right-1.5 top-1.5 rounded p-1 text-text-subtle hover:text-text-muted"
                                        @click="filters.q = ''"
                                        :aria-label="$t('Clear search')"
                                    >
                                        &times;
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Reset button at bottom right (no Apply button) -->
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
                    <span v-if="filtersCollapsed" class="text-sm font-medium text-text shrink-0">{{ $t('Filter') }}:</span>
                    <span v-if="filters.date_from || filters.date_to"
                          class="inline-flex items-center rounded-full border border-accent-200 bg-accent-50 px-2.5 py-0.5 text-xs text-accent-700 shrink-0">
                      {{ $t('Range') }}:
                      <span class="mx-1 font-medium">{{
                              formatDate(filters.date_from) || '…'
                          }} – {{ formatDate(filters.date_to) || '…' }}</span>
                      <button class="ml-1 text-accent-600 hover:text-accent-700"
                              @click="clearRange">&times;</button>
                    </span>
                    <span v-if="filters.issued_by_id"
                          class="inline-flex items-center rounded-full border border-info-border bg-info-surface px-2.5 py-0.5 text-xs text-info shrink-0">
                      {{ $t('Issued by') }}: <span class="mx-1 font-medium">{{ userNameById(filters.issued_by_id) }}</span>
                      <button class="ml-1 text-info hover:text-info"
                              @click="filters.issued_by_id = ''">&times;</button>
                    </span>
                    <span v-if="filters.received_by_id"
                          class="inline-flex items-center rounded-full border border-success-border bg-success-surface px-2.5 py-0.5 text-xs text-success shrink-0">
                      {{ $t('Received by') }}: <span class="mx-1 font-medium">{{ userNameById(filters.received_by_id) }}</span>
                      <button class="ml-1 text-success hover:text-success"
                              @click="filters.received_by_id = ''">&times;</button>
                    </span>
                    <span v-if="filters.q"
                          class="inline-flex items-center rounded-full border border-special-violet-border bg-special-violet-surface px-2.5 py-0.5 text-xs text-special-violet shrink-0">
                      {{ $t('Search') }}: <span class="mx-1 font-medium">"{{ filters.q }}"</span>
                      <button class="ml-1 text-special-violet hover:text-special-violet"
                              @click="filters.q = ''">&times;</button>
                    </span>
                    <span v-if="filters.overdue_only"
                          class="inline-flex items-center rounded-full border border-danger-border bg-danger-surface px-2.5 py-0.5 text-xs text-danger shrink-0">
                      {{ $t('Overdue returns') }}
                      <button class="ml-1 text-danger hover:text-danger" @click="filters.overdue_only = false">&times;</button>
                    </span>
                    <button
                        v-if="hasAnyFilter"
                        type="button"
                        class="ml-auto inline-flex items-center rounded-md border border-border-subtle bg-white px-2.5 py-1 text-xs hover:bg-surface-sunken shrink-0"
                        @click="resetFilters"
                    >{{ $t('Clear all') }}
                    </button>
                </div>
            </div>

            <!-- Tabelle -->
            <div class="mt-5">
                <div class="grid grid-cols-12 gap-4 px-2 py-2 text-[11px] font-semibold uppercase tracking-wide text-text-subtle">
                    <div class="col-span-3">{{ $t('Name') }}</div>
                    <div class="col-span-1">{{ $t('Material value') }}</div>
                    <div class="col-span-2">{{ $t('Time range') }}</div>
                    <div class="col-span-1">{{ $t('Issued by') }}</div>
                    <div class="col-span-2">{{ $t('External name') }}</div>
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
                    />
                </template>
                <div v-else class="mt-6">
                    <BaseAlertComponent message="No issues of material found" type="error" use-translation />
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                <BasePaginator property-name="issues" :entities="issues" />
            </div>
        </div>

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
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import IssueTabs from "@/Pages/IssueOfMaterial/Components/IssueTabs.vue";
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import SingleExternMaterialIssue from "@/Pages/IssueOfMaterial/Components/SingleExternMaterialIssue.vue";
import { router, usePage } from "@inertiajs/vue3";
import { computed, provide, ref, watch } from "vue";
import { can, is } from "laravel-permission-to-vuejs";
import {IconCirclePlus, IconCopyPlus, IconMenu4, IconSearch, IconX, IconChevronDown} from "@tabler/icons-vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import SearchableSelect from "@/Artwork/Listbox/SearchableSelect.vue";

const props = defineProps({
    issues: Object,
    articlesInFilter: { type: Array, default: () => [] },
    materialSets: { type: Object, required: true },
    detailedArticle: Object
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
        data: { article_ids: currentArticleIdsCsv.value || undefined },
        only: ['issues','articlesInFilter'], replace: true
    });
};
watch(articleNamesForFilter, () => filterIssueByArticleIds(), { deep: true });

// Filter-Status
const initial = page.props.urlParameters ?? {};
const filters = ref({
    date_from: initial.date_from ?? "",
    date_to: initial.date_to ?? "",
    issued_by_id: initial.issued_by_id ?? "",
    received_by_id: initial.received_by_id ?? "",
    overdue_only: initial.overdue_only === true || initial.overdue_only === '1' || initial.overdue_only === 1,
    q: initial.q ?? ""
});

const applyFilters = () => {
    router.reload({
        preserveState: true, preserveScroll: true,
        data: {
            article_ids: currentArticleIdsCsv.value || undefined,
            date_from: filters.value.date_from || undefined,
            date_to: filters.value.date_to || undefined,
            issued_by_id: filters.value.issued_by_id || undefined,
            received_by_id: filters.value.received_by_id || undefined,
            overdue_only: filters.value.overdue_only ? 1 : undefined,
            q: filters.value.q || undefined
        },
        replace: true,
        only: ['issues','articlesInFilter']
    });
};

// Debounce
let debounceTimer = null;
watch(filters, () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters(), 280);
}, { deep: true });

const resetFilters = () => {
    filters.value = { date_from: "", date_to: "", issued_by_id: "", received_by_id: "", overdue_only: false, q: "" };
    applyFilters();
};

const hasAnyFilter = computed(() =>
    !!(filters.value.date_from || filters.value.date_to || filters.value.issued_by_id || filters.value.received_by_id || filters.value.overdue_only || filters.value.q)
);

// Filter card collapse/expand
const filtersCollapsed = ref(true);
const toggleFilters = () => {
    filtersCollapsed.value = !filtersCollapsed.value;
};

// Quick ranges
const pad = (n) => String(n).padStart(2, '0');
const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
const today = () => new Date();
const startOfWeek = () => { const d=today(); const day=(d.getDay()+6)%7; const s=new Date(d); s.setDate(d.getDate()-day); return s; };
const endOfWeek   = () => { const s=startOfWeek(); const e=new Date(s); e.setDate(s.getDate()+6); return e; };
const startOfMonth= () => { const d=today(); return new Date(d.getFullYear(), d.getMonth(), 1); };
const endOfMonth  = () => { const d=today(); return new Date(d.getFullYear(), d.getMonth()+1, 0); };
const setRangeToday     = () => { const t=today(); filters.value.date_from = fmt(t); filters.value.date_to = fmt(t); };
const setRangeThisWeek  = () => { filters.value.date_from = fmt(startOfWeek()); filters.value.date_to = fmt(endOfWeek()); };
const setRangeThisMonth = () => { filters.value.date_from = fmt(startOfMonth()); filters.value.date_to = fmt(endOfMonth()); };
const clearRange        = () => { filters.value.date_from = ""; filters.value.date_to = ""; };

// Helpers
const userDisplay = (u) => [u.first_name, u.last_name].filter(Boolean).join(' ') || u.name || u.email || `#${u.id}`;
const userNameById = (id) => users.value.find(u => String(u.id) === String(id)) ? userDisplay(users.value.find(u => String(u.id) === String(id))) : id;
const formatDate = (s) => !s ? "" : new Date(s).toLocaleDateString(page.props.locale, { year: 'numeric', month: '2-digit', day: '2-digit' });
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
