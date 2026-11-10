<template>
    <AppLayout :title="$t('Inventory')">
        <div class="artwork-container-fluid">


            <ToolbarHeader title="External material issues"
                           description="Track and filter external issues, returns and recipients."
                           :icon="IconMenu4"
                           band
            >
                <template #actions>
                    <BaseUIButton variant="primary" on-band hide-icon @click="openIssueOfMaterialModal">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('New issue of material') }}
                    </BaseUIButton>
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
                    <button
                        type="button"
                        class="ui-button"
                        :class="filters.overdue_only ? 'border-danger-border! bg-danger-surface! text-danger!' : ''"
                        :aria-pressed="filters.overdue_only"
                        :title="$t('Shows issues whose return date has passed and for which no return has been entered.')"
                        @click="filters.overdue_only = !filters.overdue_only"
                    >
                        <component :is="IconClockExclamation" class="size-4" stroke-width="1.5"/>
                        {{ $t('Only overdue returns') }}
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
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import IssueTabs from "@/Pages/IssueOfMaterial/Components/IssueTabs.vue";
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import SingleExternMaterialIssue from "@/Pages/IssueOfMaterial/Components/SingleExternMaterialIssue.vue";
import { router, usePage } from "@inertiajs/vue3";
import { computed, provide, ref, watch } from "vue";
import { can, is } from "laravel-permission-to-vuejs";
import {IconCirclePlus, IconClockExclamation, IconCopyPlus, IconFilter, IconFilterOff, IconMenu4, IconSearch, IconX} from "@tabler/icons-vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import SearchableSelect from "@/Artwork/Listbox/SearchableSelect.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import IssueDateRangeFilter from "@/Pages/IssueOfMaterial/Components/IssueDateRangeFilter.vue";

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

/** Anzahl gesetzter Filter für das Badge am mobilen Filter-Button */
const activeFilterCount = computed(() =>
    [
        filters.value.date_from || filters.value.date_to,
        filters.value.issued_by_id,
        filters.value.received_by_id,
        filters.value.overdue_only,
    ].filter(Boolean).length
);

/** Mobil sind die Filterfelder hinter einem Button eingeklappt, ab sm immer sichtbar */
const showFilterControls = ref(false);

// Helpers
const userDisplay = (u) => [u.first_name, u.last_name].filter(Boolean).join(' ') || u.name || u.email || `#${u.id}`;
</script>
