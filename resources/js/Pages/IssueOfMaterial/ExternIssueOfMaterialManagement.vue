<template>
    <AppLayout :title="$t('Inventory')">
        <div class="px-10 w-full mx-auto font-lexend">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 pt-6 pb-2">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
            <span class="inline-flex size-6 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-700">
              <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.5" d="M4 7h16M4 12h10M4 17h16"/></svg>
            </span>
                        <h1 class="text-2xl font-semibold tracking-tight">{{ $t('External material issues') }}</h1>
                    </div>
                    <div class="mt-2 h-1 w-24 rounded-full bg-gradient-to-r from-indigo-500 via-sky-400 to-emerald-400"></div>
                    <p class="text-sm text-gray-500 mt-2">
                        {{ $t('Track and filter external issues, returns and recipients.') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1 w-96">
                        <ArticleSearch id="article-search" class="w-72" @article-selected="addArticleNameToFilter" />
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
            </div>

            <div class="mt-2">
                <IssueTabs />
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
                        <button type="button" class="ml-2 text-sky-500 hover:text-sky-700" @click="articleNamesForFilter.splice(index, 1)">
                            <component :is="IconX" class="size-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sticky Filter Toolbar -->
            <div class="sticky top-0 z-20 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/70">
                <!-- Zeile 1: Quick-Ranges + Kernfilter -->
                <div class="py-4 grid grid-cols-12 gap-3 items-end">
                    <!-- Quick-Ranges -->
                    <div class="col-span-12 lg:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('Quick range') }}</label>
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" class="rounded-md border border-indigo-200 bg-indigo-50/70 px-2.5 py-1 text-xs text-indigo-700 hover:bg-indigo-50 hover:border-indigo-300" @click="setRangeToday">{{ $t('Today') }}</button>
                            <button type="button" class="rounded-md border border-sky-200 bg-sky-50/70 px-2.5 py-1 text-xs text-sky-700 hover:bg-sky-50 hover:border-sky-300" @click="setRangeThisWeek">{{ $t('This week') }}</button>
                            <button type="button" class="rounded-md border border-emerald-200 bg-emerald-50/70 px-2.5 py-1 text-xs text-emerald-700 hover:bg-emerald-50 hover:border-emerald-300" @click="setRangeThisMonth">{{ $t('This month') }}</button>
                            <button type="button" class="rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs text-gray-700 hover:bg-gray-50" @click="clearRange">{{ $t('All time') }}</button>
                        </div>
                    </div>

                    <!-- Zeitraum (Issue/Return) -->
                    <div class="col-span-12 sm:col-span-4 lg:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('Time range') }}</label>
                        <div class="flex items-center gap-2">
                            <input v-model="filters.date_from" type="date" :max="filters.date_to || undefined" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                            <span class="text-gray-400 text-xs">–</span>
                            <input v-model="filters.date_to" type="date" :min="filters.date_from || undefined" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        </div>
                    </div>

                    <!-- Ausgegeben von -->
                    <div class="col-span-12 sm:col-span-4 lg:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('Issued by') }}</label>
                        <select v-model="filters.issued_by_id" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option :value="''">{{ $t('All') }}</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ userDisplay(u) }}</option>
                        </select>
                    </div>

                    <!-- Empfangen von -->
                    <div class="col-span-12 sm:col-span-4 lg:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('Received by') }}</label>
                        <select v-model="filters.received_by_id" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option :value="''">{{ $t('All') }}</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ userDisplay(u) }}</option>
                        </select>
                    </div>

                    <!-- Name-Suche (Vorgang + Externer Name) -->
                    <div class="col-span-12 sm:col-span-4 lg:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('Name search') }}</label>
                        <div class="relative">
                            <input
                                v-model="filters.q"
                                type="text"
                                :placeholder="$t('Search issue/external name …')"
                                class="w-full rounded-md border border-gray-300 pl-8 pr-8 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                            <span class="pointer-events-none absolute left-2 top-1.5 text-gray-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <circle cx="11" cy="11" r="7" stroke-width="1.5"></circle>
                  <path d="M20 20l-3.5-3.5" stroke-width="1.5"></path>
                </svg>
              </span>
                            <button v-if="filters.q" type="button" class="absolute right-1.5 top-1.5 rounded p-1 text-gray-400 hover:text-gray-600" @click="filters.q = ''">&times;</button>
                        </div>
                    </div>

                    <!-- Aktionen -->
                    <div class="col-span-12 sm:col-span-4 lg:col-span-1 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="applyFilters"
                            class="inline-flex items-center justify-center rounded-md border border-indigo-200 bg-indigo-50/70 px-3 py-2 text-sm text-indigo-700 hover:bg-indigo-50 hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <component :is="IconSearch" class="size-4 mr-1" stroke-width="1.5" /> {{ $t('Apply') }}
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

                <!-- Filter-Summary -->
                <div class="pb-3">
                    <div class="mt-1 flex flex-wrap items-center gap-2">
            <span v-if="filters.date_from || filters.date_to" class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50/70 px-2.5 py-0.5 text-xs text-indigo-700">
              {{ $t('Range') }}:
              <span class="mx-1 font-medium">{{ formatDate(filters.date_from) || '…' }} – {{ formatDate(filters.date_to) || '…' }}</span>
              <button class="ml-1 text-indigo-500 hover:text-indigo-700" @click="clearRange">&times;</button>
            </span>
                        <span v-if="filters.issued_by_id" class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50/70 px-2.5 py-0.5 text-xs text-sky-700">
              {{ $t('Issued by') }}: <span class="mx-1 font-medium">{{ userNameById(filters.issued_by_id) }}</span>
              <button class="ml-1 text-sky-500 hover:text-sky-700" @click="filters.issued_by_id = ''">&times;</button>
            </span>
                        <span v-if="filters.received_by_id" class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50/70 px-2.5 py-0.5 text-xs text-emerald-700">
              {{ $t('Received by') }}: <span class="mx-1 font-medium">{{ userNameById(filters.received_by_id) }}</span>
              <button class="ml-1 text-emerald-500 hover:text-emerald-700" @click="filters.received_by_id = ''">&times;</button>
            </span>
                        <span v-if="filters.q" class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50/70 px-2.5 py-0.5 text-xs text-violet-700">
              {{ $t('Search') }}: <span class="mx-1 font-medium">“{{ filters.q }}”</span>
              <button class="ml-1 text-violet-500 hover:text-violet-700" @click="filters.q = ''">&times;</button>
            </span>
                        <button
                            v-if="hasAnyFilter"
                            type="button"
                            class="ml-auto inline-flex items-center rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs hover:bg-gray-50"
                            @click="resetFilters"
                        >{{ $t('Clear all') }}</button>
                    </div>
                </div>
            </div>

            <!-- Tabelle -->
            <div class="mt-5">
                <div class="grid grid-cols-12 gap-4 px-2 py-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                    <div class="col-span-3">{{ $t('Name') }}</div>
                    <div class="col-span-1">{{ $t('Material value') }}</div>
                    <div class="col-span-2">{{ $t('Time range') }}</div>
                    <div class="col-span-1">{{ $t('Issued by') }}</div>
                    <div class="col-span-2">{{ $t('External name') }}</div>
                    <div class="col-span-1">{{ $t('Files') }}</div>
                    <div class="col-span-1">{{ $t('Received by') }}</div>
                    <div class="col-span-1 text-right">{{ $t('Status') }}</div>
                </div>
                <div class="border-y border-gray-200"></div>
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
import { IconCopyPlus, IconSearch, IconX } from "@tabler/icons-vue";

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
    filters.value = { date_from: "", date_to: "", issued_by_id: "", received_by_id: "", q: "" };
    applyFilters();
};

const hasAnyFilter = computed(() =>
    !!(filters.value.date_from || filters.value.date_to || filters.value.issued_by_id || filters.value.received_by_id || filters.value.q)
);

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
/* utilities only */
</style>
